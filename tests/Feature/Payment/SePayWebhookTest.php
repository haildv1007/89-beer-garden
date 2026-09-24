<?php

namespace Tests\Feature\Payment;

use App\Models\FulfillmentOrder;
use App\Models\PaymentWebhookTransaction;
use App\Models\SystemSetting;
use App\Services\SystemSetting\SystemSettingCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class SePayWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        foreach ([
            SystemSettingCatalog::VIETQR_BANK_ID => 'MB',
            SystemSettingCatalog::VIETQR_ACCOUNT_NUMBER => '1234567890',
            SystemSettingCatalog::VIETQR_ACCOUNT_NAME => 'BEER GARDEN',
            SystemSettingCatalog::VIETQR_TRANSFER_PREFIX => 'BG',
        ] as $key => $value) {
            SystemSetting::query()->forceCreate(['key' => $key, 'value' => $value, 'type' => 'string']);
        }
        SystemSetting::query()->forceCreate([
            'key' => SystemSettingCatalog::SEPAY_WEBHOOK_API_KEY,
            'value' => Crypt::encryptString('sepay-test-secret-key'),
            'type' => 'string',
        ]);
    }

    public function test_matching_incoming_transaction_marks_order_paid_once(): void
    {
        $order = $this->order();
        $payload = $this->payload(1001, 'BGNQ2609220001', 169000);

        $this->withHeader('Authorization', 'Apikey sepay-test-secret-key')
            ->postJson(route('webhooks.sepay'), $payload)->assertOk()->assertJson(['success' => true]);
        $this->withHeader('Authorization', 'Apikey sepay-test-secret-key')
            ->postJson(route('webhooks.sepay'), $payload)->assertOk();

        $order->refresh();
        $this->assertSame(FulfillmentOrder::PAYMENT_PAID, $order->payment_status);
        $this->assertSame('bank_transfer', $order->payment_method);
        $this->assertSame(169000, $order->received_amount);
        $this->assertNotNull($order->paid_at);
        $this->assertDatabaseCount('payment_webhook_transactions', 1);
        $this->assertSame('matched', PaymentWebhookTransaction::query()->sole()->status);
    }

    public function test_invalid_key_is_rejected_and_transaction_is_not_stored(): void
    {
        $this->order();
        $this->withHeader('Authorization', 'Apikey wrong-key')
            ->postJson(route('webhooks.sepay'), $this->payload(1002, 'BGNQ2609220001', 169000))
            ->assertForbidden();
        $this->assertDatabaseCount('payment_webhook_transactions', 0);
    }

    public function test_bidv_virtual_account_matches_sepay_sub_account(): void
    {
        SystemSetting::query()
            ->where('key', SystemSettingCatalog::VIETQR_ACCOUNT_NUMBER)
            ->update(['value' => '96247QUAN89']);
        $order = $this->order();
        $payload = $this->payload(1005, $order->payment_reference, 169000);
        $payload['accountNumber'] = '2141600456';
        $payload['subAccount'] = '96247QUAN89';

        $this->withHeader('Authorization', 'Apikey sepay-test-secret-key')
            ->postJson(route('webhooks.sepay'), $payload)
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame(FulfillmentOrder::PAYMENT_PAID, $order->fresh()->payment_status);
        $this->assertSame(169000, $order->fresh()->total_amount);
        $this->assertDatabaseHas('payment_webhook_transactions', [
            'external_transaction_id' => '1005',
            'account_number' => '96247QUAN89',
            'status' => 'matched',
        ]);
    }

    public function test_bidv_webhook_matches_order_from_content_when_code_is_null(): void
    {
        SystemSetting::query()
            ->where('key', SystemSettingCatalog::VIETQR_ACCOUNT_NUMBER)
            ->update(['value' => '96247PWY7A']);
        $order = $this->order('NQ-260922-0003', 'QUANNQ2609220003', now()->addMinutes(30));
        $payload = $this->payload(83783286, '', 169000);
        $payload['gateway'] = 'BIDV';
        $payload['accountNumber'] = '2141600456';
        $payload['subAccount'] = '96247PWY7A';
        $payload['code'] = null;
        $payload['content'] = 'QUANNQ2609220003';
        $payload['description'] = 'BankAPINotify QUANNQ2609220003';

        $this->withHeader('Authorization', 'Apikey sepay-test-secret-key')
            ->postJson(route('webhooks.sepay'), $payload)
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame(FulfillmentOrder::PAYMENT_PAID, $order->fresh()->payment_status);
        $this->assertDatabaseHas('payment_webhook_transactions', [
            'external_transaction_id' => '83783286',
            'fulfillment_order_id' => $order->id,
            'status' => 'matched',
        ]);

        $this->get(URL::temporarySignedRoute(
            'customer.cart.external-payment.confirmed',
            now()->addMinutes(10),
            ['fulfillmentOrder' => $order],
        ))
            ->assertOk()
            ->assertSee('Đã thanh toán thành công')
            ->assertSee('Thanh toán đã được xác nhận')
            ->assertSee('Đã thanh toán')
            ->assertSee('Chuyển khoản ngân hàng')
            ->assertSee('169.000 ₫');
    }

    public function test_expired_or_wrong_amount_transactions_require_manual_review(): void
    {
        $expired = $this->order('NQ-260922-0002', 'BGNQ2609220002', now()->subMinute());
        $wrongAmount = $this->order('NQ-260922-0003', 'BGNQ2609220003');

        foreach ([
            $this->payload(1003, $expired->payment_reference, 169000),
            $this->payload(1004, $wrongAmount->payment_reference, 168000),
        ] as $payload) {
            $this->withHeader('Authorization', 'Apikey sepay-test-secret-key')
                ->postJson(route('webhooks.sepay'), $payload)->assertOk();
        }

        $this->assertSame(FulfillmentOrder::PAYMENT_UNPAID, $expired->fresh()->payment_status);
        $this->assertSame(FulfillmentOrder::PAYMENT_UNPAID, $wrongAmount->fresh()->payment_status);
        $this->assertEqualsCanonicalizing(
            ['expired', 'amount_mismatch'],
            PaymentWebhookTransaction::query()->pluck('status')->all(),
        );
    }

    private function order(
        string $code = 'NQ-260922-0001',
        string $reference = 'BGNQ2609220001',
        $expiresAt = null,
    ): FulfillmentOrder {
        return FulfillmentOrder::query()->forceCreate([
            'order_code' => $code,
            'fulfillment_type' => FulfillmentOrder::TYPE_DELIVERY,
            'payment_option' => FulfillmentOrder::PAYMENT_BANK_TRANSFER,
            'payment_reference' => $reference,
            'payment_expires_at' => $expiresAt ?? now()->addMinutes(15),
            'status' => FulfillmentOrder::STATUS_PENDING,
            'customer_name' => 'Khách',
            'phone' => '0901234567',
            'requested_for' => now()->addHour(),
            'subtotal' => 139000,
            'discount_amount' => 0,
            'shipping_fee' => 30000,
            'total_amount' => 169000,
            'placed_at' => now()->subMinute(),
        ]);
    }

    private function payload(int $id, string $code, int $amount): array
    {
        return [
            'id' => $id,
            'gateway' => 'MBBank',
            'transactionDate' => now()->format('Y-m-d H:i:s'),
            'accountNumber' => '1234567890',
            'code' => $code,
            'content' => $code.' thanh toan',
            'transferType' => 'in',
            'transferAmount' => $amount,
            'referenceCode' => 'FT'.$id,
        ];
    }
}

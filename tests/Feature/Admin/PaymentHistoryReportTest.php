<?php

namespace Tests\Feature\Admin;

use App\Enums\EmployeeStatus;
use App\Models\Employee;
use App\Models\FulfillmentOrder;
use App\Models\PaymentWebhookTransaction;
use App\Models\Role;
use App\Models\User;
use App\Queries\Reports\OperationalReportQuery;
use Carbon\CarbonImmutable;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentHistoryReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_presets_use_today_seven_and_fourteen_days(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::factory()->forRole(Role::query()->where('code', 'admin')->firstOrFail())->create();
        Employee::query()->forceCreate([
            'user_id' => $admin->id,
            'employee_code' => 'REPORT-PRESETS',
            'name' => 'Quản trị',
            'status' => EmployeeStatus::Active,
        ]);
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-22 12:00:00', config('app.timezone')));

        try {
            foreach (['today' => '2026-09-22', '7' => '2026-09-16', '14' => '2026-09-09'] as $preset => $start) {
                $this->actingAs($admin)->get(route('admin.reports.index', ['preset' => $preset]))
                    ->assertOk()
                    ->assertViewHas('from', fn ($from) => $from->toDateString() === $start)
                    ->assertViewHas('to', fn ($to) => $to->toDateString() === '2026-09-22');
                $this->get(route('admin.reports.payments', ['preset' => $preset]))
                    ->assertOk()
                    ->assertViewHas('from', fn ($from) => $from->toDateString() === $start);
            }
            $this->get(route('admin.reports.index', ['preset' => '30']))->assertSessionHasErrors('preset');
            $this->get(route('admin.reports.payments', ['preset' => 'custom', 'from' => '2026-09-01', 'to' => '2026-09-22']))
                ->assertOk()
                ->assertViewHas('from', fn ($from) => $from->toDateString() === '2026-09-01');
            $this->get(route('admin.reports.payments', ['preset' => 'custom', 'from' => '2026-09-23', 'to' => '2026-09-22']))
                ->assertSessionHasErrors('to');
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_comparison_uses_preceding_equal_length_period(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-22 12:00:00', config('app.timezone')));

        try {
            foreach (['2026-09-22' => 100000, '2026-09-21' => 200000, '2026-09-15' => 300000, '2026-09-01' => 400000] as $date => $amount) {
                FulfillmentOrder::query()->forceCreate([
                    'order_code' => 'NQ-'.str_replace('-', '', $date),
                    'fulfillment_type' => 'pickup',
                    'status' => FulfillmentOrder::STATUS_CONFIRMED,
                    'customer_name' => 'Khách kiểm thử',
                    'phone' => '0900000000',
                    'requested_for' => "$date 12:00:00",
                    'placed_at' => "$date 10:00:00",
                    'subtotal' => $amount,
                    'total_amount' => $amount,
                    'payment_status' => FulfillmentOrder::PAYMENT_PAID,
                    'payment_method' => 'cash',
                    'paid_at' => "$date 11:00:00",
                ]);
            }

            $query = app(OperationalReportQuery::class);
            foreach ([
                '7' => ['2026-09-16', '2026-09-22', 300000, 300000],
                '14' => ['2026-09-09', '2026-09-22', 600000, 400000],
            ] as $preset => [$start, $end, $current, $previous]) {
                $report = $query->run(CarbonImmutable::parse("$start 00:00:00"), CarbonImmutable::parse("$end 23:59:59"), $preset);
                $this->assertSame($current, $report['summary']['revenue']['value']);
                $this->assertSame($previous, $report['summary']['revenue']['previous']);
            }
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_administrator_can_view_payment_history_and_reconciliation_tabs(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::factory()->forRole(Role::query()->where('code', 'admin')->firstOrFail())->create();
        Employee::query()->forceCreate([
            'user_id' => $admin->id,
            'employee_code' => 'REPORT-ADMIN',
            'name' => 'Người quản trị',
            'status' => EmployeeStatus::Active,
        ]);
        PaymentWebhookTransaction::query()->create([
            'provider' => 'sepay',
            'external_transaction_id' => 'BANK-REVIEW-01',
            'status' => 'amount_mismatch',
            'amount' => 125000,
            'occurred_at' => now(),
            'reference_code' => 'FT260922001',
            'payload' => [],
        ]);

        $this->actingAs($admin)->get(route('admin.reports.payments', ['preset' => 'today']))
            ->assertOk()->assertSee('Lịch sử thanh toán')->assertSee('Cần đối soát')->assertSee('Đơn / phiên');
        $this->get(route('admin.reports.index', ['preset' => 'today']))
            ->assertOk()->assertSee('Xu hướng doanh thu')->assertSee('Hiệu quả phục vụ tại bàn')->assertSee('Đơn ngoài quán')
            ->assertDontSee('@else')->assertDontSee('So với kỳ trước');
        $this->get(route('admin.reports.payments', ['preset' => 'today', 'tab' => 'reconciliation']))
            ->assertOk()->assertSee('BANK-REVIEW-01')->assertSee('Lệch số tiền')->assertSee('125,000 ₫');
    }

    public function test_paid_order_is_counted_on_payment_date_in_both_reports(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::factory()->forRole(Role::query()->where('code', 'admin')->firstOrFail())->create();
        Employee::query()->forceCreate([
            'user_id' => $admin->id,
            'employee_code' => 'REPORT-DATE',
            'name' => 'Quản trị',
            'status' => EmployeeStatus::Active,
        ]);
        $paidAt = now()->startOfDay()->addHours(10);
        FulfillmentOrder::query()->forceCreate([
            'order_code' => 'NQ-DATE-001', 'fulfillment_type' => 'pickup',
            'status' => FulfillmentOrder::STATUS_CONFIRMED,
            'customer_name' => 'Khách kiểm thử', 'phone' => '0900000000',
            'requested_for' => $paidAt, 'placed_at' => $paidAt->copy()->subDays(2),
            'subtotal' => 135000, 'total_amount' => 135000,
            'payment_status' => FulfillmentOrder::PAYMENT_PAID,
            'payment_method' => 'bank_transfer', 'paid_at' => $paidAt,
        ]);

        $this->actingAs($admin)->get(route('admin.reports.index', ['preset' => 'today']))
            ->assertOk()->assertSee('135,000 ₫');
        $this->get(route('admin.reports.payments', ['preset' => 'today']))
            ->assertOk()->assertSee('NQ-DATE-001')->assertSee('135,000 ₫');
        $this->get(route('admin.reports.payments', ['preset' => '14']))
            ->assertOk()->assertSee('NQ-DATE-001');
    }
}

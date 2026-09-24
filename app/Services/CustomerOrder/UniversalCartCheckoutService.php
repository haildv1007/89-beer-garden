<?php

namespace App\Services\CustomerOrder;

use App\Models\Voucher;
use App\Services\Billing\BillCalculator;
use App\Services\SystemSetting\TypedSystemSettingResolver;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class UniversalCartCheckoutService
{
    public const FULFILLMENT_KEY = 'customer_cart_fulfillment';

    public const VOUCHER_KEY = 'customer_cart_voucher_code';

    public function __construct(
        private readonly BillCalculator $calculator,
        private readonly TypedSystemSettingResolver $settings,
    ) {}

    public function selectFulfillment(Request $request, string $type): void
    {
        if ($type === 'delivery' && $this->settings->deliveryFee() === null) {
            throw ValidationException::withMessages([
                'fulfillment_type' => __('customer_order.errors.delivery_unavailable'),
            ]);
        }

        $request->session()->put(self::FULFILLMENT_KEY, $type);
    }

    public function applyVoucher(Request $request, array $rows, string $code): void
    {
        $code = strtoupper(trim($code));
        $voucher = Voucher::query()->where('code', $code)->first();
        if ($voucher === null) {
            throw ValidationException::withMessages(['voucher_code' => __('billing.errors.voucher_invalid')]);
        }

        $subtotal = $this->subtotal($rows, true);
        $discount = $this->calculator->discount($voucher, $subtotal);
        $this->calculator->ensurePositiveTotal($subtotal, $discount);
        $request->session()->put(self::VOUCHER_KEY, $code);
    }

    public function removeVoucher(Request $request): void
    {
        $request->session()->forget(self::VOUCHER_KEY);
    }

    /** @return array{fulfillment_type:?string, subtotal:int, discount:int, shipping_fee:?int, total:int, voucher_code:?string} */
    public function summary(Request $request, array $rows): array
    {
        $subtotal = $this->subtotal($rows, false);
        $discount = 0;
        $code = $request->session()->get(self::VOUCHER_KEY);
        if (is_string($code) && $code !== '') {
            $voucher = Voucher::query()->where('code', $code)->first();
            try {
                $discount = $voucher === null ? 0 : $this->calculator->discount($voucher, $subtotal);
                $this->calculator->ensurePositiveTotal($subtotal, $discount);
            } catch (ValidationException) {
                $request->session()->forget(self::VOUCHER_KEY);
                $code = null;
            }
        } else {
            $code = null;
        }

        $fulfillment = $request->session()->get(self::FULFILLMENT_KEY);
        $fulfillment = in_array($fulfillment, ['at_table', 'dine_in', 'pickup', 'delivery'], true)
            ? $fulfillment
            : null;
        $shippingFee = $fulfillment === 'delivery' ? $this->settings->deliveryFee() : 0;
        if ($fulfillment === 'delivery' && $shippingFee === null) {
            $request->session()->forget(self::FULFILLMENT_KEY);
            $fulfillment = null;
        }

        return [
            'fulfillment_type' => $fulfillment,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'shipping_fee' => $shippingFee,
            'total' => $subtotal - $discount + ($shippingFee ?? 0),
            'voucher_code' => $code,
        ];
    }

    private function subtotal(array $rows, bool $requireAvailable): int
    {
        $subtotal = 0;
        foreach ($rows as $row) {
            if ($requireAvailable && ! $row['available']) {
                throw ValidationException::withMessages(['cart' => __('customer_order.errors.cart_invalid')]);
            }
            if (! is_int($row['line_total'])) {
                continue;
            }
            if ($row['line_total'] > PHP_INT_MAX - $subtotal) {
                throw ValidationException::withMessages(['cart' => __('customer_order.errors.cart_invalid')]);
            }
            $subtotal += $row['line_total'];
        }

        if ($requireAvailable && $subtotal <= 0) {
            throw ValidationException::withMessages(['cart' => __('customer_order.errors.cart_empty')]);
        }

        return $subtotal;
    }
}

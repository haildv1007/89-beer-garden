<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BillStatus;
use App\Enums\OrderItemStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\POS\ApplyVoucherRequest;
use App\Http\Requests\POS\ProcessPaymentRequest;
use App\Http\Requests\POS\RecordFailedPaymentRequest;
use App\Models\Bill;
use App\Models\DiningSession;
use App\Models\FulfillmentOrder;
use App\Services\Billing\ApplyVoucherService;
use App\Services\Billing\OpenBillService;
use App\Services\Payment\CompletePaymentService;
use App\Services\Payment\RecordFailedPaymentService;
use App\Services\SystemSetting\TypedSystemSettingResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function open(DiningSession $diningSession, OpenBillService $service): RedirectResponse
    {
        $bill = $service->open($diningSession);

        return redirect()->route('admin.bills.show', $bill)->with('success', __('billing.refreshed'));
    }

    public function show(Bill $bill): View
    {
        $this->loadBill($bill);
        $adminContext = request()->routeIs('admin.*');

        return view('admin.billing.show', compact('bill', 'adminContext'));
    }

    public function activateBankTransfer(Request $request, Bill $bill, TypedSystemSettingResolver $settings, OpenBillService $openBill): JsonResponse
    {
        abort_unless($request->user()?->can('billing.view') && $request->user()?->can('payment.complete'), 403);
        if ($bill->status === BillStatus::Paid) {
            throw ValidationException::withMessages(['payment' => 'Hóa đơn đã được thanh toán.']);
        }
        $bank = $settings->vietQr();
        if ($bank === null) {
            throw ValidationException::withMessages(['payment' => 'Chưa cấu hình tài khoản VietQR.']);
        }
        $bill = $openBill->open($bill->diningSession);

        if ($bill->payment_reference === null || $bill->payment_expires_at?->isPast()) {
            $reference = strtoupper($bank['prefix'].'HD'.$bill->id.Str::random(6));
            $bill->forceFill(['payment_reference' => $reference, 'payment_expires_at' => now()->addMinutes(30)]);
        }
        Bill::query()->where('displayed_by_user_id', $request->user()->id)->where('id', '!=', $bill->id)
            ->update(['displayed_by_user_id' => null]);
        FulfillmentOrder::query()->where('displayed_by_user_id', $request->user()->id)
            ->update(['displayed_by_user_id' => null]);
        $bill->forceFill(['displayed_by_user_id' => $request->user()->id])->save();

        return response()->json(['success' => true, 'expires_at' => $bill->payment_expires_at?->toIso8601String()]);
    }

    public function customerDisplay(Request $request, TypedSystemSettingResolver $settings): View
    {
        abort_unless($request->user()?->can('billing.view'), 403);
        $statusRoute = $request->routeIs('admin.*') ? 'admin.customer-display.status' : 'pos.customer-display.status';

        return view('admin.billing.customer-display', ['bankConfigured' => $settings->vietQr() !== null, 'statusRoute' => $statusRoute]);
    }

    public function customerDisplayStatus(Request $request, TypedSystemSettingResolver $settings): JsonResponse
    {
        abort_unless($request->user()?->can('billing.view'), 403);
        $bill = Bill::query()->where('displayed_by_user_id', $request->user()->id)->latest('updated_at')->first();
        $order = FulfillmentOrder::query()->where('displayed_by_user_id', $request->user()->id)
            ->latest('updated_at')->first();
        if ($order && (! $bill || $order->updated_at->greaterThan($bill->updated_at))) {
            if ($order->payment_status === FulfillmentOrder::PAYMENT_PAID) {
                return response()->json(['active' => true, 'paid' => true]);
            }
            if ($order->payment_expires_at === null || $order->payment_expires_at->isPast()) {
                return response()->json(['active' => true, 'expired' => true]);
            }
            $bank = $settings->vietQr();
            if (! $bank) return response()->json(['active' => false, 'unavailable' => true]);
            $query = http_build_query(['amount' => $order->total_amount, 'addInfo' => $order->payment_reference, 'accountName' => $bank['account_name']]);

            return response()->json([
                'active' => true, 'paid' => false, 'expired' => false,
                'bill_code' => $order->order_code, 'table' => 'Đơn nhận tại quán',
                'amount' => $order->total_amount, 'reference' => $order->payment_reference,
                'expires_at' => $order->payment_expires_at->getTimestampMs(),
                'qr_url' => 'https://img.vietqr.io/image/'.$bank['bank_id'].'-'.$bank['account_number'].'-compact2.png?'.$query,
            ]);
        }
        if (! $bill) return response()->json(['active' => false]);
        if ($bill->status === BillStatus::Paid) return response()->json(['active' => true, 'paid' => true]);
        if ($bill->payment_expires_at === null || $bill->payment_expires_at->isPast()) {
            return response()->json(['active' => true, 'expired' => true]);
        }
        $bank = $settings->vietQr();
        if (! $bank) return response()->json(['active' => false, 'unavailable' => true]);
        $query = http_build_query(['amount' => $bill->total_amount, 'addInfo' => $bill->payment_reference, 'accountName' => $bank['account_name']]);

        return response()->json([
            'active' => true, 'paid' => false, 'expired' => false,
            'bill_code' => $bill->bill_code, 'table' => $bill->diningSession?->table?->name,
            'amount' => $bill->total_amount, 'reference' => $bill->payment_reference,
            'expires_at' => $bill->payment_expires_at->getTimestampMs(),
            'qr_url' => 'https://img.vietqr.io/image/'.$bank['bank_id'].'-'.$bank['account_number'].'-compact2.png?'.$query,
        ]);
    }

    public function paymentStatus(Bill $bill): JsonResponse
    {
        return response()->json([
            'paid' => $bill->status === BillStatus::Paid,
            'expired' => $bill->payment_expires_at?->isPast() ?? false,
        ]);
    }

    public function applyVoucher(
        ApplyVoucherRequest $request,
        Bill $bill,
        ApplyVoucherService $service,
    ): RedirectResponse {
        $service->apply($bill, $request->validated('voucher_code'));

        return back()->with('success', __('billing.voucher_applied'));
    }

    public function removeVoucher(Bill $bill, ApplyVoucherService $service): RedirectResponse
    {
        abort_unless(request()->user()?->can('billing.view') && request()->user()?->can('voucher.apply'), 403);
        $service->remove($bill);

        return back()->with('success', __('billing.voucher_removed'));
    }

    public function complete(
        ProcessPaymentRequest $request,
        Bill $bill,
        CompletePaymentService $service,
    ): RedirectResponse {
        $data = $request->validated();
        $service->complete(
            $bill,
            $request->user(),
            $data['method'],
            $data['transaction_reference'] ?? null,
            isset($data['cash_received']) ? (int) $data['cash_received'] : null,
        );

        $route = $request->routeIs('admin.*') ? 'admin.bills.invoice' : 'pos.bills.invoice';

        return redirect()->route($route, $bill)->with('success', __('billing.payment_completed'));
    }

    public function fail(
        RecordFailedPaymentRequest $request,
        Bill $bill,
        RecordFailedPaymentService $service,
    ): RedirectResponse {
        $data = $request->validated();
        $service->record(
            $bill,
            $request->user(),
            $data['method'],
            $data['transaction_reference'] ?? null,
            $data['failure_reason'],
        );

        return back()->with('success', __('billing.payment_failed_recorded'));
    }

    public function invoice(Bill $bill): View
    {
        abort_unless($bill->status === BillStatus::Paid, 404);
        $this->loadBill($bill);
        $adminContext = request()->routeIs('admin.*');

        return view('admin.billing.invoice', compact('bill', 'adminContext'));
    }

    private function loadBill(Bill $bill): void
    {
        $bill->load([
            'voucher' => fn ($query) => $query->withTrashed(),
            'diningSession.table',
            'diningSession.customer',
            'diningSession.orders' => fn ($query) => $query->oldest('ordered_at')->oldest('id'),
            'diningSession.orders.items' => fn ($query) => $query
                ->where('status', '!=', OrderItemStatus::Cancelled->value)
                ->oldest('id'),
            'diningSession.orders.items.product.media',
            'payments' => fn ($query) => $query->with('processedBy:id,name')->latest('id'),
            'successfulPayment.processedBy:id,name',
        ]);
    }
}

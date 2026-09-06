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
use App\Services\Billing\ApplyVoucherService;
use App\Services\Billing\OpenBillService;
use App\Services\Payment\CompletePaymentService;
use App\Services\Payment\RecordFailedPaymentService;
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
            'payments' => fn ($query) => $query->with('processedBy:id,name')->latest('id'),
            'successfulPayment.processedBy:id,name',
        ]);
    }
}

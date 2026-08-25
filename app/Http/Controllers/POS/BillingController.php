<?php

namespace App\Http\Controllers\POS;

use App\Enums\BillStatus;
use App\Enums\OrderItemStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\POS\ApplyVoucherRequest;
use App\Models\Bill;
use App\Models\DiningSession;
use App\Services\Billing\ApplyVoucherService;
use App\Services\Billing\OpenBillService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function open(DiningSession $diningSession, OpenBillService $service): RedirectResponse
    {
        abort_unless(request()->user()?->can('billing.view'), 403);
        $bill = $service->open($diningSession);

        return redirect()->route('pos.bills.show', $bill)->with('success', __('billing.refreshed'));
    }

    public function show(Bill $bill): View
    {
        abort_unless(request()->user()?->can('billing.view'), 403);
        $this->loadBill($bill);

        return view('pos.billing.show', compact('bill'));
    }

    public function applyVoucher(ApplyVoucherRequest $request, Bill $bill, ApplyVoucherService $service): RedirectResponse
    {
        $service->apply($bill, $request->validated('voucher_code'));

        return back()->with('success', __('billing.voucher_applied'));
    }

    public function removeVoucher(Bill $bill, ApplyVoucherService $service): RedirectResponse
    {
        abort_unless(request()->user()?->can('billing.view') && request()->user()?->can('voucher.apply'), 403);
        $service->remove($bill);

        return back()->with('success', __('billing.voucher_removed'));
    }

    public function invoice(Bill $bill): View
    {
        abort_unless(request()->user()?->can('billing.view'), 403);
        abort_unless($bill->status === BillStatus::Paid, 404);
        $this->loadBill($bill);

        return view('pos.billing.invoice', compact('bill'));
    }

    private function loadBill(Bill $bill): void
    {
        $bill->load([
            'voucher' => fn ($query) => $query->withTrashed(),
            'diningSession.table', 'diningSession.customer',
            'diningSession.orders' => fn ($query) => $query->oldest('ordered_at')->oldest('id'),
            'diningSession.orders.items' => fn ($query) => $query->where('status', '!=', OrderItemStatus::Cancelled->value)->oldest('id'),
            'payments' => fn ($query) => $query->with('processedBy:id,name')->latest('id'),
            'successfulPayment.processedBy:id,name',
        ]);
    }
}

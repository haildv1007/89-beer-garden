<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Http\Requests\POS\ProcessPaymentRequest;
use App\Http\Requests\POS\RecordFailedPaymentRequest;
use App\Models\Bill;
use App\Services\Payment\CompletePaymentService;
use App\Services\Payment\RecordFailedPaymentService;
use Illuminate\Http\RedirectResponse;

class PaymentController extends Controller
{
    public function complete(ProcessPaymentRequest $request, Bill $bill, CompletePaymentService $service): RedirectResponse
    {
        $data = $request->validated();
        $service->complete($bill, $request->user(), $data['method'], $data['transaction_reference'] ?? null);

        return redirect()->route('pos.bills.invoice', $bill)->with('success', __('billing.payment_completed'));
    }

    public function fail(RecordFailedPaymentRequest $request, Bill $bill, RecordFailedPaymentService $service): RedirectResponse
    {
        $data = $request->validated();
        $service->record($bill, $request->user(), $data['method'], $data['transaction_reference'] ?? null, $data['failure_reason']);

        return back()->with('success', __('billing.payment_failed_recorded'));
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OperationalReportRequest;
use App\Http\Requests\Admin\PaymentHistoryRequest;
use App\Queries\Reports\OperationalReportQuery;
use App\Queries\Reports\PaymentHistoryQuery;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(OperationalReportRequest $request, OperationalReportQuery $query): View
    {
        [$from, $to, $preset] = $request->dateRange();

        return view('admin.reports.index', $query->run($from, $to, $preset) + compact('from', 'to', 'preset'));
    }

    public function payments(PaymentHistoryRequest $request, PaymentHistoryQuery $query): View
    {
        [$from, $to, $preset] = $request->dateRange();

        return view('admin.reports.payments', $query->run($from, $to, $request->validated()) + compact('from', 'to', 'preset'));
    }
}

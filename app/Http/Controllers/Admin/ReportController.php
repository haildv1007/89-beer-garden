<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OperationalReportRequest;
use App\Queries\Reports\OperationalReportQuery;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(OperationalReportRequest $request, OperationalReportQuery $query): View
    {
        [$from, $to, $preset] = $request->dateRange();

        return view('admin.reports.index', $query->run($from, $to) + compact('from', 'to', 'preset'));
    }
}

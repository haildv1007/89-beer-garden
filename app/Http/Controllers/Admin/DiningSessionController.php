<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderItemStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CancelDiningSessionRequest;
use App\Http\Requests\Admin\ManageDiningSessionRequest;
use App\Http\Requests\Admin\TransferDiningSessionTableRequest;
use App\Http\Requests\Admin\UpdateDiningSessionRequest;
use App\Models\DiningSession;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\RestaurantTable;
use App\Services\Billing\OpenBillService;
use App\Services\DiningSession\BuildDiningSessionTimelineService;
use App\Services\DiningSession\CancelDiningSessionService;
use App\Services\DiningSession\ManageDiningSessionService;
use App\Services\DiningSession\TransferDiningSessionTableService;
use App\Services\DiningSession\UpdateDiningSessionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DiningSessionController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:active,completed,cancelled,all'],
        ]);
        $search = trim((string) ($filters['q'] ?? ''));
        $status = $filters['status'] ?? 'active';
        $sessions = DiningSession::query()
            ->with([
                'table:id,code,name',
                'customer:id,name,phone',
                'reservation:id,reservation_code',
                'openedBy:id,name',
            ])
            ->withCount('orders')
            ->addSelect([
                'order_total' => OrderItem::query()
                    ->selectRaw('COALESCE(SUM(order_items.line_total), 0)')
                    ->join('orders', 'orders.id', '=', 'order_items.order_id')
                    ->whereColumn('orders.dining_session_id', 'dining_sessions.id')
                    ->where('order_items.status', '!=', OrderItemStatus::Cancelled->value),
            ])
            ->when($status !== 'all', fn (Builder $query) => $query->where('status', $status))
            ->when(
                $search !== '',
                fn (Builder $query) => $query->where(
                    fn (Builder $query) => $query
                        ->where('session_code', 'like', "%{$search}%")
                        ->orWhereHas(
                            'table',
                            fn (Builder $table) => $table
                                ->where('code', 'like', "%{$search}%")
                                ->orWhere('name', 'like', "%{$search}%"),
                        )
                        ->orWhereHas(
                            'customer',
                            fn (Builder $customer) => $customer
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%"),
                        ),
                ),
            )
            ->latest('started_at')
            ->paginate(25)
            ->withQueryString();

        $adminContext = $request->routeIs('admin.*');

        return view('admin.dining-sessions.index', compact('sessions', 'filters', 'search', 'status', 'adminContext'));
    }

    public function show(DiningSession $diningSession, BuildDiningSessionTimelineService $timelineService): View
    {
        $diningSession->load([
            'table:id,code,name,capacity',
            'customer:id,name,phone',
            'reservation:id,reservation_code',
            'openedBy:id,name',
            'completedBy:id,name',
            'bill:id,dining_session_id,bill_code,status,total_amount',
            'orders' => fn ($query) => $query->latest('ordered_at')->latest('id'),
            'orders.createdByEmployee:id,name',
            'orders.items.product.media',
            'tableTransfers' => fn ($query) => $query->latest('transferred_at'),
            'tableTransfers.fromTable:id,code,name',
            'tableTransfers.toTable:id,code,name',
            'tableTransfers.transferredBy:id,name',
        ]);
        $transferTables = RestaurantTable::query()
            ->assignableFor($diningSession->guest_count)
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'capacity', 'location']);
        $products = Product::query()
            ->publicMenu()
            ->where('is_available', true)
            ->with(['category:id,name', 'media', 'availableVariants'])
            ->orderBy('name')
            ->get(['id', 'category_id', 'name', 'price', 'image_url']);

        $adminContext = request()->routeIs('admin.*');
        $sessionTimeline = $timelineService->build($diningSession);

        return view('admin.dining-sessions.show', compact('diningSession', 'products', 'transferTables', 'sessionTimeline', 'adminContext'));
    }

    public function update(
        UpdateDiningSessionRequest $request,
        DiningSession $diningSession,
        UpdateDiningSessionService $service,
    ): RedirectResponse {
        $service->update($diningSession, $request->validated());

        return back()->with('success', 'Đã cập nhật thông tin phiên.');
    }

    public function openBilling(DiningSession $diningSession, OpenBillService $service): RedirectResponse
    {
        $bill = $service->open($diningSession);

        $route = request()->routeIs('admin.*') ? 'admin.bills.show' : 'pos.bills.show';

        return redirect()->route($route, $bill)->with('success', 'Đã mở thanh toán và cập nhật tổng tiền.');
    }

    public function transferTable(
        TransferDiningSessionTableRequest $request,
        DiningSession $diningSession,
        TransferDiningSessionTableService $service,
    ): RedirectResponse {
        $employee = $request->user()->employee;
        abort_unless($employee, 403);
        $service->transfer($diningSession, (int) $request->validated('table_id'), $request->validated('reason'), $employee);

        return back()->with('success', 'Đã đổi bàn. Bàn cũ được chuyển sang trạng thái chờ dọn.');
    }

    public function cancel(
        CancelDiningSessionRequest $request,
        DiningSession $diningSession,
        CancelDiningSessionService $service,
    ): RedirectResponse {
        $employee = $request->user()->employee;
        abort_unless($employee, 403);
        $service->cancel($diningSession, $employee, $request->validated('cancellation_reason'));

        return back()->with('success', 'Đã hủy phiên phục vụ. Bàn được chuyển sang trạng thái chờ dọn.');
    }

    public function manage(
        ManageDiningSessionRequest $request,
        DiningSession $diningSession,
        ManageDiningSessionService $service,
    ): RedirectResponse {
        $service->manage($diningSession, $request->user(), $request->validated());

        return back()->with('success', 'Đã lưu thay đổi và gửi các món mới xuống bếp.');
    }
}

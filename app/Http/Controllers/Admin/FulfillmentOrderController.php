<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateFulfillmentOrderRequest;
use App\Http\Requests\POS\CompleteFulfillmentPaymentRequest;
use App\Http\Requests\POS\RejectFulfillmentOrderRequest;
use App\Http\Requests\POS\StoreManualFulfillmentOrderRequest;
use App\Models\FulfillmentOrder;
use App\Models\Product;
use App\Services\CustomerOrder\CreateManualFulfillmentOrderService;
use App\Services\CustomerOrder\ManageFulfillmentOrderService;
use App\Services\CustomerOrder\UpdateFulfillmentOrderService;
use App\Services\Payment\CompleteFulfillmentPaymentService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FulfillmentOrderController extends Controller
{
    public function create(): View
    {
        $products = Product::query()
            ->publicMenu()
            ->where('is_available', true)
            ->orderBy('name')
            ->get(['id', 'name', 'price']);
        $adminContext = request()->routeIs('admin.*');

        return view('admin.fulfillment-orders.create', compact('products', 'adminContext'));
    }

    public function store(
        StoreManualFulfillmentOrderRequest $request,
        CreateManualFulfillmentOrderService $service,
    ): RedirectResponse {
        $order = $service->create($request->validated());
        $route = $request->routeIs('admin.*') ? 'admin.fulfillment-orders.show' : 'pos.fulfillment-orders.show';

        return redirect()->route($route, $order)->with('success', 'Đã tạo đơn ngoài quán thủ công.');
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', 'in:pickup,delivery'],
            'status' => ['nullable', 'in:pending,confirmed,rejected'],
        ]);
        $search = trim((string) ($filters['q'] ?? ''));
        $summary = [
            'pending' => FulfillmentOrder::query()
                ->whereIn('fulfillment_type', [FulfillmentOrder::TYPE_PICKUP, FulfillmentOrder::TYPE_DELIVERY])
                ->where('status', FulfillmentOrder::STATUS_PENDING)
                ->count(),
            'pickup' => FulfillmentOrder::query()->where('fulfillment_type', FulfillmentOrder::TYPE_PICKUP)->count(),
            'delivery' => FulfillmentOrder::query()
                ->where('fulfillment_type', FulfillmentOrder::TYPE_DELIVERY)
                ->count(),
        ];
        $orders = FulfillmentOrder::query()
            ->whereIn('fulfillment_type', [FulfillmentOrder::TYPE_PICKUP, FulfillmentOrder::TYPE_DELIVERY])
            ->withCount('items')
            ->when(
                $search !== '',
                fn (Builder $query) => $query->where(
                    fn (Builder $query) => $query
                        ->where('order_code', 'like', "%{$search}%")
                        ->orWhere('customer_name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%"),
                ),
            )
            ->when(
                $filters['type'] ?? null,
                fn (Builder $query, string $type) => $query->where('fulfillment_type', $type),
            )
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->latest('placed_at')
            ->paginate(25)
            ->withQueryString();

        $adminContext = $request->routeIs('admin.*');

        return view(
            'admin.fulfillment-orders.index',
            compact('orders', 'filters', 'search', 'summary', 'adminContext'),
        );
    }

    public function show(FulfillmentOrder $fulfillmentOrder): View
    {
        abort_if($fulfillmentOrder->fulfillment_type === FulfillmentOrder::TYPE_DINE_IN, 404);
        $fulfillmentOrder->load([
            'customer:id,name,phone',
            'items.product.media',
            'voucher:id,code',
            'confirmedByEmployee:id,name',
            'paidByEmployee:id,name',
        ]);
        $products = Product::query()
            ->publicMenu()
            ->where('is_available', true)
            ->orderBy('name')
            ->get(['id', 'name', 'price']);

        $adminContext = request()->routeIs('admin.*');

        return view('admin.fulfillment-orders.show', compact('fulfillmentOrder', 'products', 'adminContext'));
    }

    public function completePayment(
        CompleteFulfillmentPaymentRequest $request,
        FulfillmentOrder $fulfillmentOrder,
        CompleteFulfillmentPaymentService $service,
    ): RedirectResponse {
        $data = $request->validated();
        $service->complete(
            $fulfillmentOrder,
            $request->user(),
            $data['payment_method'],
            isset($data['cash_received']) ? (int) $data['cash_received'] : null,
        );
        $route = $request->routeIs('admin.*') ? 'admin.fulfillment-orders.invoice' : 'pos.fulfillment-orders.invoice';

        return redirect()->route($route, $fulfillmentOrder)->with('success', 'Đã xác nhận thanh toán đơn ngoài quán.');
    }

    public function invoice(FulfillmentOrder $fulfillmentOrder): View
    {
        abort_unless($fulfillmentOrder->payment_status === FulfillmentOrder::PAYMENT_PAID, 404);
        abort_if($fulfillmentOrder->fulfillment_type === FulfillmentOrder::TYPE_DINE_IN, 404);
        $fulfillmentOrder->load(['items', 'voucher:id,code', 'paidByEmployee:id,name']);
        $adminContext = request()->routeIs('admin.*');

        return view('admin.fulfillment-orders.invoice', compact('fulfillmentOrder', 'adminContext'));
    }

    public function update(
        UpdateFulfillmentOrderRequest $request,
        FulfillmentOrder $fulfillmentOrder,
        UpdateFulfillmentOrderService $service,
    ): RedirectResponse|JsonResponse {
        abort_if($fulfillmentOrder->fulfillment_type === FulfillmentOrder::TYPE_DINE_IN, 404);
        $service->update($fulfillmentOrder, $request->validated());

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Đã cập nhật đơn ngoài quán.']);
        }

        return back()->with('success', 'Đã cập nhật đơn ngoài quán.');
    }

    public function confirm(
        Request $request,
        FulfillmentOrder $fulfillmentOrder,
        ManageFulfillmentOrderService $service,
    ): RedirectResponse {
        abort_if($fulfillmentOrder->fulfillment_type === FulfillmentOrder::TYPE_DINE_IN, 404);
        $service->confirm($fulfillmentOrder, $request->user());

        return back()->with('success', __('fulfillment_order.confirmed'));
    }

    public function reject(
        RejectFulfillmentOrderRequest $request,
        FulfillmentOrder $fulfillmentOrder,
        ManageFulfillmentOrderService $service,
    ): RedirectResponse {
        abort_if($fulfillmentOrder->fulfillment_type === FulfillmentOrder::TYPE_DINE_IN, 404);
        $service->reject($fulfillmentOrder, $request->user(), $request->validated('reason'));

        return back()->with('success', __('fulfillment_order.rejected'));
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CustomerHistoryRequest;
use App\Http\Requests\Admin\CustomerIndexRequest;
use App\Http\Requests\Admin\MergeCustomerRequest;
use App\Http\Requests\Admin\UpdateCustomerRequest;
use App\Models\Customer;
use App\Services\Customer\CustomerActivityService;
use App\Services\Customer\MergeCustomerService;
use App\Services\Customer\UpdateCustomerService;
use App\Services\CustomerHistory\CustomerHistoryService;
use App\Support\PhoneNumber;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(CustomerIndexRequest $request): View
    {
        $search = trim((string) ($request->validated('q') ?? ''));
        $sort = $request->validated('sort');
        $direction = $request->validated('direction') ?? 'desc';
        $customers = Customer::query()
            ->with('user:id,email,status,last_login_at,created_at')
            ->withCount([
                'fulfillmentOrders as takeaway_orders_count' => fn (Builder $query) => $query->whereIn(
                    'fulfillment_type',
                    ['pickup', 'delivery'],
                ),
                'diningSessions as dining_order_sessions_count' => fn (Builder $query) => $query->whereHas('orders'),
                'diningSessions as completed_dining_sessions_count' => fn (Builder $query) => $query
                    ->where('status', 'completed')
                    ->whereHas('orders'),
                'fulfillmentOrders as completed_fulfillment_orders_count' => fn (Builder $query) => $query
                    ->whereIn('fulfillment_type', ['pickup', 'delivery'])
                    ->where('status', 'confirmed')
                    ->whereHas('items', fn (Builder $items) => $items->whereIn('status', ['ready', 'served']))
                    ->whereDoesntHave(
                        'items',
                        fn (Builder $items) => $items->whereNotIn('status', ['ready', 'served', 'cancelled']),
                    ),
            ])
            ->withMax('reservations as last_reservation_at', 'created_at')
            ->withMax('diningSessions as last_dining_at', 'started_at')
            ->withMax('fulfillmentOrders as last_fulfillment_at', 'placed_at')
            ->selectSub(
                fn ($query) => $query
                    ->from('bills')
                    ->join('dining_sessions', 'dining_sessions.id', '=', 'bills.dining_session_id')
                    ->whereColumn('dining_sessions.customer_id', 'customers.id')
                    ->where('bills.status', 'paid')
                    ->selectRaw('COALESCE(SUM(bills.total_amount), 0)'),
                'dining_spending',
            )
            ->selectSub(
                fn ($query) => $query
                    ->from('fulfillment_orders')
                    ->whereColumn('fulfillment_orders.customer_id', 'customers.id')
                    ->whereIn('fulfillment_type', ['pickup', 'delivery'])
                    ->where('status', 'confirmed')
                    ->selectRaw('COALESCE(SUM(total_amount), 0)'),
                'takeaway_spending',
            )
            ->when(
                $search !== '',
                fn (Builder $query) => $query->where(function (Builder $query) use ($search): void {
                    $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhereHas('user', fn (Builder $user) => $user->where('email', 'like', "%{$search}%"));
                }),
            )
            ->when(
                ($request->validated('account') ?? null) === 'member',
                fn (Builder $query) => $query->whereHas('user', fn (Builder $user) => $user->where('status', 'active')),
            )
            ->when(
                ($request->validated('account') ?? null) === 'guest',
                fn (Builder $query) => $query->whereDoesntHave(
                    'user',
                    fn (Builder $user) => $user->where('status', 'active'),
                ),
            )
            ->when(
                $sort === 'total_orders',
                fn (Builder $query) => $query->orderByRaw(
                    "(dining_order_sessions_count + takeaway_orders_count) {$direction}",
                ),
            )
            ->when(
                $sort === 'completed_orders',
                fn (Builder $query) => $query->orderByRaw(
                    "(completed_dining_sessions_count + completed_fulfillment_orders_count) {$direction}",
                ),
            )
            ->when($sort === null, fn (Builder $query) => $query->orderBy('name'))
            ->paginate(20)
            ->withQueryString();
        $pagePhoneVariants = $customers->getCollection()
            ->pluck('phone')
            ->flatMap(fn (?string $phone) => PhoneNumber::variants($phone))
            ->unique()
            ->values();
        $phoneCounts = $pagePhoneVariants->isEmpty()
            ? collect()
            : Customer::query()
                ->whereIn('phone', $pagePhoneVariants)
                ->selectRaw('phone, COUNT(*) as aggregate')
                ->groupBy('phone')
                ->get()
                ->reduce(function ($counts, Customer $row) {
                    $phone = PhoneNumber::normalize($row->phone);
                    if ($phone !== null) {
                        $counts[$phone] = ($counts[$phone] ?? 0) + (int) $row->aggregate;
                    }

                    return $counts;
                }, collect());
        $customers->getCollection()->each(function (Customer $customer) use ($phoneCounts): void {
            $customer->setAttribute(
                'suspected_duplicates_count',
                max(0, ($phoneCounts[PhoneNumber::normalize($customer->phone)] ?? 1) - 1),
            );
        });
        $customerCount = Customer::query()
            ->whereNotNull('phone')
            ->distinct()
            ->count('phone');
        $memberCount = Customer::query()
            ->whereNotNull('phone')
            ->whereHas('user', fn (Builder $user) => $user->where('status', 'active'))
            ->distinct()
            ->count('phone');
        $summary = [
            'customers' => $customerCount,
            'members' => $memberCount,
            'guests' => max(0, $customerCount - $memberCount),
        ];
        $account = $request->validated('account');

        return view('admin.customers.index', compact('customers', 'search', 'summary', 'account', 'sort', 'direction'));
    }

    public function show(
        CustomerHistoryRequest $request,
        Customer $customer,
        CustomerHistoryService $history,
        CustomerActivityService $activity,
    ): View {
        $customer
            ->load([
                'user:id,email,status,last_login_at',
                'fulfillmentOrders' => fn ($query) => $query->latest('placed_at')->limit(10),
            ])
            ->loadCount([
                'reservations',
                'diningSessions',
                'orders',
                'fulfillmentOrders',
                'fulfillmentOrders as pickup_orders_count' => fn (Builder $query) => $query->where(
                    'fulfillment_type',
                    'pickup',
                ),
                'fulfillmentOrders as delivery_orders_count' => fn (Builder $query) => $query->where(
                    'fulfillment_type',
                    'delivery',
                ),
                'diningSessions as dining_order_sessions_count' => fn (Builder $query) => $query->whereHas('orders'),
                'diningSessions as completed_dining_sessions_count' => fn (Builder $query) => $query
                    ->where('status', 'completed')
                    ->whereHas('orders'),
                'fulfillmentOrders as completed_fulfillment_orders_count' => fn (Builder $query) => $query
                    ->whereIn('fulfillment_type', ['pickup', 'delivery'])
                    ->where('status', 'confirmed')
                    ->whereHas('items', fn (Builder $items) => $items->whereIn('status', ['ready', 'served']))
                    ->whereDoesntHave(
                        'items',
                        fn (Builder $items) => $items->whereNotIn('status', ['ready', 'served', 'cancelled']),
                    ),
            ]);
        $filters = $request->validated();
        $overview = $history->overview($customer);
        $activities = $activity->timeline($customer, $filters);
        $metrics = $activity->metrics($customer, $overview['spending']);
        $metrics['total_orders'] =
            $customer->dining_order_sessions_count + $customer->pickup_orders_count + $customer->delivery_orders_count;
        $metrics['completed_orders'] =
            $customer->completed_dining_sessions_count + $customer->completed_fulfillment_orders_count;
        $phoneVariants = PhoneNumber::variants($customer->phone);
        $duplicates =
            $phoneVariants === []
                ? collect()
                : Customer::query()
                    ->with('user:id,email')
                    ->whereKeyNot($customer->id)
                    ->whereIn('phone', $phoneVariants)
                    ->withCount(['reservations', 'diningSessions', 'fulfillmentOrders'])
                    ->get();

        return view(
            'admin.customers.show',
            compact('customer', 'activities', 'overview', 'metrics', 'duplicates', 'filters'),
        );
    }

    public function merge(MergeCustomerRequest $request, Customer $customer, MergeCustomerService $merger)
    {
        $source = Customer::query()->findOrFail($request->integer('source_customer_id'));
        $merger->merge($customer, $source);

        return redirect()
            ->route('admin.customers.show', $customer)
            ->with('success', 'Đã gộp hồ sơ và chuyển toàn bộ lịch sử về khách hàng này.');
    }

    public function edit(Customer $customer): View
    {
        $customer->load('user:id,email,phone,status');

        return view('admin.customers.edit', compact('customer'));
    }

    public function update(
        UpdateCustomerRequest $request,
        Customer $customer,
        UpdateCustomerService $service,
    ): RedirectResponse {
        $service->update($customer, $request->validated());

        return redirect()->route('admin.customers.show', $customer)->with('success', 'Đã cập nhật hồ sơ khách hàng.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        DB::transaction(function () use ($customer): void {
            $customer = Customer::query()->lockForUpdate()->findOrFail($customer->id);
            if ($customer->user_id) {
                $customer->user()->update(['status' => 'disabled', 'phone' => null]);
            }
            $customer->delete();
        });

        return redirect()
            ->route('admin.customers.index')
            ->with('success', 'Đã lưu trữ hồ sơ và vô hiệu hóa tài khoản đăng nhập liên quan.');
    }
}

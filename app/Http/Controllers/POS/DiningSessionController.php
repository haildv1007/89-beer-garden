<?php

namespace App\Http\Controllers\POS;

use App\Enums\DiningSessionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\POS\DiningSessionIndexRequest;
use App\Http\Requests\POS\OpenDiningSessionRequest;
use App\Models\Customer;
use App\Models\DiningSession;
use App\Models\RestaurantTable;
use App\Services\DiningSession\OpenDiningSessionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DiningSessionController extends Controller
{
    public function index(DiningSessionIndexRequest $request): View
    {
        $search = trim((string) ($request->validated('q') ?? ''));
        $sessions = DiningSession::query()->where('status', DiningSessionStatus::Active->value)
            ->with(['table:id,code,name', 'customer:id,name', 'reservation:id,reservation_code', 'openedBy:id,name'])
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $query) use ($search): void {
                $query->where('session_code', 'like', "%{$search}%")
                    ->orWhereHas('table', fn (Builder $table) => $table->where('code', 'like', "%{$search}%"))
                    ->orWhereHas('customer', fn (Builder $customer) => $customer->where('name', 'like', "%{$search}%"));
            }))->latest('started_at')->paginate(30)->withQueryString();

        return view('pos.dining-sessions.index', compact('sessions', 'search'));
    }

    public function show(DiningSession $diningSession): View
    {
        $diningSession->load(['table:id,code,name,capacity', 'customer:id,name,phone',
            'reservation:id,reservation_code', 'openedBy:id,name',
            'bill:id,dining_session_id,bill_code,status',
            'orders' => fn ($query) => $query->oldest('ordered_at')->oldest('id'),
            'orders.createdByEmployee:id,name', 'orders.items']);

        return view('pos.dining-sessions.show', compact('diningSession'));
    }

    public function create(RestaurantTable $restaurantTable): View
    {
        abort_unless(auth()->user()->can('dining-session.open') && auth()->user()->can('table.operate'), 403);
        $customers = Customer::query()->orderBy('name')->get(['id', 'name', 'phone']);

        return view('pos.dining-sessions.create', compact('restaurantTable', 'customers'));
    }

    public function store(OpenDiningSessionRequest $request, RestaurantTable $restaurantTable, OpenDiningSessionService $service): RedirectResponse
    {
        $data = $request->validated();
        $session = $service->open($restaurantTable, $request->user(), (int) $data['guest_count'],
            isset($data['customer_id']) ? (int) $data['customer_id'] : null, $data['note'] ?? null);

        return redirect()->route('pos.dining-sessions.show', $session)->with('success', __('dining_session.opened'));
    }
}

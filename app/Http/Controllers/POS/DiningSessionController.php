<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Http\Requests\POS\OpenDiningSessionRequest;
use App\Models\Customer;
use App\Models\RestaurantTable;
use App\Services\DiningSession\OpenDiningSessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DiningSessionController extends Controller
{
    public function create(RestaurantTable $restaurantTable): View
    {
        abort_unless(auth()->user()->can('dining-session.open') && auth()->user()->can('table.operate'), 403);
        $adminContext = request()->routeIs('admin.*');

        return view('pos.dining-sessions.create', compact('restaurantTable', 'adminContext'));
    }

    public function searchCustomers(Request $request): JsonResponse
    {
        $request->validate(['q' => ['required', 'string', 'min:2', 'max:100']]);
        $query = trim((string) $request->query('q'));
        if (mb_strlen($query) < 2) {
            return response()->json(['customers' => []]);
        }

        $customers = Customer::query()
            ->where(fn ($builder) => $builder->where('name', 'like', "%{$query}%")
                ->orWhere('phone', 'like', "%{$query}%"))
            ->orderBy('name')
            ->limit(12)
            ->get(['id', 'name', 'phone']);

        return response()->json(['customers' => $customers]);
    }

    public function store(
        OpenDiningSessionRequest $request,
        RestaurantTable $restaurantTable,
        OpenDiningSessionService $service,
    ): RedirectResponse {
        $data = $request->validated();
        $session = $service->open(
            $restaurantTable,
            $request->user(),
            (int) $data['guest_count'],
            isset($data['customer_id']) ? (int) $data['customer_id'] : null,
            $data['note'] ?? null,
        );

        $route = $request->routeIs('admin.*') ? 'admin.dining-sessions.show' : 'pos.dining-sessions.show';

        return redirect()->route($route, $session)->with('success', __('dining_session.opened'));
    }
}

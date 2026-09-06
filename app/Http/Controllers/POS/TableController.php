<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Http\Requests\POS\MarkTableAvailableRequest;
use App\Http\Requests\POS\TableIndexRequest;
use App\Models\RestaurantTable;
use App\Services\RestaurantTable\MarkRestaurantTableAvailableService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TableController extends Controller
{
    public function index(TableIndexRequest $request): View
    {
        $filters = $request->validated();
        $search = trim((string) ($filters['q'] ?? ''));
        $partySize = isset($filters['party_size']) ? (int) $filters['party_size'] : null;

        $tables = RestaurantTable::query()
            ->with('activeDiningSession')
            ->when(
                $search !== '',
                fn ($query) => $query->where(function ($query) use ($search): void {
                    $query
                        ->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%");
                }),
            )
            ->when(isset($filters['status']), fn ($query) => $query->where('runtime_status', $filters['status']))
            ->when(($filters['location'] ?? '') !== '', fn ($query) => $query->where('location', $filters['location']))
            ->when(
                ($filters['active'] ?? 'all') !== 'all',
                fn ($query) => $query->where('is_active', $filters['active'] === '1'),
            )
            ->when($partySize !== null, fn ($query) => $query->assignableFor($partySize))
            ->orderBy('location')
            ->orderBy('code')
            ->paginate(48)
            ->withQueryString();

        $locations = RestaurantTable::query()
            ->whereNotNull('location')
            ->where('location', '<>', '')
            ->distinct()
            ->orderBy('location')
            ->pluck('location');

        $adminContext = $request->routeIs('admin.*');

        return view('pos.tables.index', compact('tables', 'locations', 'filters', 'partySize', 'adminContext'));
    }

    public function markAvailable(
        MarkTableAvailableRequest $request,
        RestaurantTable $restaurantTable,
        MarkRestaurantTableAvailableService $service,
    ): RedirectResponse {
        $service->handle($restaurantTable);

        return back()->with('success', __('table.pos.marked_available'));
    }
}

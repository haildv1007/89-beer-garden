<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RestaurantTableStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RestaurantTableIndexRequest;
use App\Http\Requests\Admin\StoreRestaurantTableRequest;
use App\Http\Requests\Admin\UpdateRestaurantTableRequest;
use App\Models\RestaurantTable;
use App\Services\RestaurantTable\ManageRestaurantTableService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RestaurantTableController extends Controller
{
    public function index(RestaurantTableIndexRequest $request): View
    {
        $filters = $request->validated();
        $search = trim((string) ($filters['q'] ?? ''));
        $status = $filters['status'] ?? '';
        $active = $filters['active'] ?? '';
        $minCapacity = isset($filters['min_capacity']) ? (int) $filters['min_capacity'] : null;

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
            ->when($status !== '', fn ($query) => $query->where('runtime_status', $status))
            ->when($active !== '', fn ($query) => $query->where('is_active', $active === '1'))
            ->when($minCapacity !== null, fn ($query) => $query->where('capacity', '>=', $minCapacity))
            ->orderBy('location')
            ->orderBy('code')
            ->paginate(30)
            ->withQueryString();

        return view('admin.restaurant-tables.index', compact('tables', 'search', 'status', 'active', 'minCapacity'));
    }

    public function create(): View
    {
        return view('admin.restaurant-tables.create', [
            'table' => new RestaurantTable()->forceFill(['is_active' => true]),
        ]);
    }

    public function store(StoreRestaurantTableRequest $request): RedirectResponse
    {
        $table = new RestaurantTable()->forceFill(
            $request->safe()->except(['is_active']) + [
                'is_active' => $request->boolean('is_active'),
                'runtime_status' => RestaurantTableStatus::Available,
            ],
        );
        $table->save();

        return redirect()->route('admin.restaurant-tables.index')->with('success', __('app.saved'));
    }

    public function show(RestaurantTable $restaurantTable): View
    {
        $restaurantTable->load('activeDiningSession')->loadCount(['reservations', 'diningSessions']);

        return view('admin.restaurant-tables.show', ['table' => $restaurantTable]);
    }

    public function edit(RestaurantTable $restaurantTable): View
    {
        return view('admin.restaurant-tables.edit', ['table' => $restaurantTable]);
    }

    public function update(
        UpdateRestaurantTableRequest $request,
        RestaurantTable $restaurantTable,
        ManageRestaurantTableService $service,
    ): RedirectResponse {
        $attributes = $request->safe()->except(['is_active']) + ['is_active' => $request->boolean('is_active')];
        $table = $service->update($restaurantTable, $attributes);

        return redirect()->route('admin.restaurant-tables.index')->with('success', __('app.saved'));
    }

    public function destroy(RestaurantTable $restaurantTable, ManageRestaurantTableService $service): RedirectResponse
    {
        $service->delete($restaurantTable);

        return redirect()->route('admin.restaurant-tables.index')->with('success', __('app.deleted'));
    }
}

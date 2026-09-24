<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StockMovementType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\InventoryItemIndexRequest;
use App\Http\Requests\Admin\StoreInventoryItemRequest;
use App\Http\Requests\Admin\StoreStockMovementRequest;
use App\Http\Requests\Admin\UpdateInventoryItemRequest;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Services\Inventory\AdjustStockService;
use App\Services\Inventory\ManageInventoryItemService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function index(InventoryItemIndexRequest $request): View
    {
        $search = trim((string) ($request->validated('q') ?? ''));
        $status = $request->validated('status') ?? '';
        $lowStock = $request->boolean('low_stock');
        $items = InventoryItem::query()
            ->with('product:id,name')
            ->when(
                $search !== '',
                fn (Builder $query) => $query->where(function (Builder $query) use ($search): void {
                    $query
                        ->where('sku', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhereHas('product', fn (Builder $product) => $product->where('name', 'like', "%{$search}%"));
                }),
            )
            ->when($status !== '', fn (Builder $query) => $query->where('status', $status))
            ->when($lowStock, fn (Builder $query) => $query->lowStock())
            ->orderBy('name')
            ->paginate(30)
            ->withQueryString();

        return view('admin.inventory.index', compact('items', 'search', 'status', 'lowStock'));
    }

    public function create(): View
    {
        return view('admin.inventory.create', [
            'item' => new InventoryItem()->forceFill(['minimum_stock' => 0, 'status' => InventoryItem::STATUS_ACTIVE]),
            'products' => $this->availableProducts(),
        ]);
    }

    public function store(StoreInventoryItemRequest $request, ManageInventoryItemService $service): RedirectResponse
    {
        $item = $service->create($request->validated());

        return redirect()->route('admin.inventory-items.show', $item)->with('success', __('app.saved'));
    }

    public function show(InventoryItem $inventoryItem): View
    {
        abort_unless(request()->user()?->can('inventory.view'), 403);
        $inventoryItem->load('product:id,name,slug');
        $movements = $inventoryItem->stockMovements()->with('createdBy:id,name')->latest('id')->paginate(30);

        return view('admin.inventory.show', ['item' => $inventoryItem, 'movements' => $movements]);
    }

    public function edit(InventoryItem $inventoryItem): View
    {
        abort_unless(request()->user()?->can('inventory.view'), 403);

        return view('admin.inventory.edit', [
            'item' => $inventoryItem,
            'products' => $this->availableProducts($inventoryItem),
        ]);
    }

    public function update(
        UpdateInventoryItemRequest $request,
        InventoryItem $inventoryItem,
        ManageInventoryItemService $service,
    ): RedirectResponse {
        $service->update($inventoryItem, $request->validated());

        return redirect()->route('admin.inventory-items.show', $inventoryItem)->with('success', __('app.saved'));
    }

    public function createMovement(InventoryItem $inventoryItem): View
    {
        abort_unless(
            request()->user()?->can('inventory.view') && request()->user()?->can('inventory.stock-movement.create'),
            403,
        );

        return view('admin.inventory.movements.create', [
            'item' => $inventoryItem,
            'types' => StockMovementType::cases(),
        ]);
    }

    public function storeMovement(
        StoreStockMovementRequest $request,
        InventoryItem $inventoryItem,
        AdjustStockService $service,
    ): RedirectResponse {
        $data = $request->validated();
        $service->record(
            $inventoryItem,
            $request->user(),
            StockMovementType::from($data['type']),
            (int) $data['quantity'],
            $data['note'] ?? null,
        );

        return redirect()
            ->route('admin.inventory-items.show', $inventoryItem)
            ->with('success', __('inventory.movement_created'));
    }

    /** @return Collection<int, Product> */
    private function availableProducts(?InventoryItem $item = null): Collection
    {
        $products = Product::query()
            ->where('status', Product::STATUS_ACTIVE)
            ->where(function (Builder $query) use ($item): void {
                $query->whereDoesntHave('inventoryItem');
                if ($item?->product_id !== null) {
                    $query->orWhereKey($item->product_id);
                }
            })
            ->orderBy('name')
            ->get(['id', 'name']);
        if ($item?->product_id !== null && $products->doesntContain('id', $item->product_id)) {
            $current = Product::withTrashed()->find($item->product_id, ['id', 'name']);
            if ($current !== null) {
                $products->push($current);
            }
        }

        return $products;
    }
}

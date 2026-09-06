<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductPriceRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $search = is_string($request->query('q')) ? mb_substr(trim($request->query('q')), 0, 100) : '';
        $category = is_string($request->query('category')) ? $request->query('category') : '';
        $status = in_array($request->query('status'), [Product::STATUS_ACTIVE, Product::STATUS_INACTIVE], true)
            ? $request->query('status')
            : '';

        $products = Product::query()
            ->with(['category', 'media'])
            ->when(
                $search !== '',
                fn (Builder $query) => $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")->orWhere('slug', 'like', "%{$search}%");
                }),
            )
            ->when($category !== '', fn (Builder $query) => $query->where('category_id', $category))
            ->when($status !== '', fn (Builder $query) => $query->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.products.index', [
            'products' => $products,
            'categories' => Category::query()->orderBy('name')->get(),
            'filters' => compact('search', 'category', 'status'),
        ]);
    }

    public function create(): View
    {
        return view('admin.products.create', [
            'product' => new Product,
            'categories' => Category::query()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $product = DB::transaction(function () use ($request): Product {
            $product = Product::query()->create(
                Arr::except($request->validated(), ['media_slots', 'media_order', 'remove_media']),
            );
            $this->storeMediaSlots($product, $request->file('media_slots', []));

            return $product;
        });

        return redirect()->route('admin.products.index')->with('success', __('app.saved'));
    }

    public function show(Product $product): View
    {
        $product->load(['category', 'media']);

        return view('admin.products.show', compact('product'));
    }

    public function edit(Product $product): View
    {
        return view('admin.products.edit', [
            'product' => $product->load(['category', 'media']),
            'categories' => Category::query()->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $removedPaths = DB::transaction(function () use ($request, $product): array {
            $product->update(Arr::except($request->validated(), ['media_slots', 'media_order', 'remove_media']));
            $mediaToRemove = $product->media()->whereIn('id', $request->input('remove_media', []))->get();
            $submittedOrder = $request->input('media_order');
            $orderedIds = collect(
                is_array($submittedOrder) ? $submittedOrder : $product->media()->pluck('id')->all(),
            )->map(fn ($id): ?int => $id ? (int) $id : null);
            $replacementIds = collect(array_keys($request->file('media_slots', [])))
                ->map(fn (int|string $position): ?int => $orderedIds->get((int) $position - 1))
                ->filter();
            $mediaToReplace = $product->media()->whereKey($replacementIds)->get();
            $mediaToDelete = $mediaToRemove->merge($mediaToReplace)->unique('id');
            $paths = $mediaToDelete->pluck('path')->all();
            $product->media()->whereKey($mediaToDelete->modelKeys())->delete();
            $this->reorderMedia(
                $product,
                $orderedIds->filter()->reject(fn (int $id): bool => $mediaToDelete->contains('id', $id))->all(),
            );
            $this->storeMediaSlots($product, $request->file('media_slots', []));

            return $paths;
        });

        Storage::disk('public')->delete($removedPaths);

        return redirect()->route('admin.products.index')->with('success', __('app.saved'));
    }

    public function updatePrice(UpdateProductPriceRequest $request, Product $product): RedirectResponse
    {
        $product->update(['price' => $request->integer('price')]);

        return redirect()->route('admin.products.edit', $product)->with('success', __('app.saved'));
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return redirect()->route('admin.products.index')->with('success', __('app.deleted'));
    }

    /** @param array<int|string, UploadedFile> $files */
    private function storeMediaSlots(Product $product, array $files): void
    {
        foreach ($files as $position => $file) {
            $path = $file->store("products/{$product->id}", 'public');
            abort_if($path === false, 500, 'Product media could not be stored.');
            $mime = (string) $file->getMimeType();
            $product->media()->create([
                'path' => $path,
                'media_type' => str_starts_with($mime, 'video/') ? 'video' : 'image',
                'mime_type' => $mime,
                'original_name' => $file->getClientOriginalName(),
                'sort_order' => (int) $position - 1,
            ]);
        }
    }

    /** @param array<int, int> $mediaIds */
    private function reorderMedia(Product $product, array $mediaIds): void
    {
        $product
            ->media()
            ->whereKey($mediaIds)
            ->get()
            ->each(fn ($media, int $index) => $media->update(['sort_order' => 100 + $index]));
        foreach ($mediaIds as $position => $mediaId) {
            $product
                ->media()
                ->whereKey($mediaId)
                ->update(['sort_order' => $position]);
        }
    }
}

<?php

namespace App\Services\AIChat;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;

class MenuContextBuilder
{
    /** @return list<array{id:int,name:string,category:string,price:int}> */
    public function catalog(): array
    {
        return $this->baseQuery()
            ->limit(60)
            ->get()
            ->map(fn (Product $product): array => [
                'id' => $product->id,
                'name' => $product->name,
                'category' => $product->category->name,
                'price' => (int) ($product->availableVariants->min('price') ?? $product->price),
                'variants' => $product->availableVariants->map->only(['id', 'name', 'price'])->values()->all(),
            ])
            ->all();
    }

    /** @return list<array<string,mixed>> */
    public function search(string $query): array
    {
        $tokens = $this->tokens($query);
        if ($tokens === []) {
            return [];
        }

        return $this->baseQuery()
            ->where(function (Builder $builder) use ($tokens): void {
                foreach ($tokens as $variants) {
                    $builder->where(function (Builder $term) use ($variants): void {
                        foreach ($variants as $token) {
                            $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $token).'%';
                            $term->orWhere('name', 'like', $like)
                                ->orWhere('short_description', 'like', $like)
                                ->orWhere('description', 'like', $like)
                                ->orWhereHas('category', fn (Builder $category) => $category
                                    ->where('name', 'like', $like)
                                    ->orWhere('slug', 'like', $like));
                        }
                    });
                }
            })
            ->limit((int) config('ai_chat.limits.products', 8))
            ->get()
            ->map(fn (Product $product): array => $this->card($product))
            ->all();
    }

    /** @param list<int> $ids @return list<array<string,mixed>> */
    public function byIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return $this->baseQuery()
            ->whereIn('id', array_slice(array_values(array_unique($ids)), 0, 8))
            ->get()
            ->map(fn (Product $product): array => $this->card($product))
            ->all();
    }

    private function baseQuery(): Builder
    {
        return Product::query()
            ->publicMenu()
            ->where('is_available', true)
            ->with(['category:id,name,slug,status,sort_order', 'media', 'availableVariants'])
            ->orderBy('id');
    }

    /** @return array<string,mixed> */
    private function card(Product $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'detail_url' => route('customer.products.show', $product, false),
            'category' => [
                'id' => $product->category->id,
                'name' => $product->category->name,
                'slug' => $product->category->slug,
            ],
            'short_description' => $product->short_description,
            'description' => $product->description,
            'price' => $product->availableVariants->min('price') ?? $product->price,
            'variants' => $product->availableVariants->map->only(['id', 'name', 'price'])->values()->all(),
            'image_url' => $product->primary_image_url,
            'media' => $product->primary_image_url
                ? [['type' => 'image', 'url' => $product->primary_image_url]]
                : [],
            'is_available' => true,
        ];
    }

    /** @return list<list<string>> */
    private function tokens(string $query): array
    {
        $cleanQuery = preg_replace('/[\w.+-]+@[\w.-]+\.[a-z]{2,}|\+?\d[\d\s.-]{7,}\d/iu', ' ', mb_strtolower($query));
        $words = preg_split('/[^\pL\pN]+/u', $cleanQuery ?? mb_strtolower($query), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $stopWords = [
            'tìm', 'kiếm', 'xem', 'món', 'cho', 'tôi', 'có', 'không', 'giá', 'bao', 'nhiêu',
            'nào', 'về', 'gì', 'được', 'phù', 'hợp', 'nay', 'hôm', 'k', 'ko', 'khum',
            'thế', 'nhỉ', 'đang', 'hỏi', 'mà', 'của', 'là', 'và', 'email', 'số', 'điện', 'thoại',
            'gợi', 'các', 'những', 'xin', 'mình', 'bạn', 'cái', 'mấy', 'thử', 'loại', 'xịn',
            'phải', 'thật', 'sự', 'chất', 'lượng', 'ngon', 'nhất', 'nên', 'ăn', 'với', 'ạ',
            'find', 'show', 'menu', 'product', 'price', 'please', 'the', 'a', 'is',
            '找', '菜', '菜单', '价格',
        ];

        $tokens = array_slice(array_values(array_unique(array_filter(
            $words,
            fn (string $word): bool => mb_strlen($word) > 1 && ! in_array($word, $stopWords, true),
        ))), 0, 4);
        $aliases = [
            'bia' => ['beer'],
            'beer' => ['bia'],
            'nướng' => ['grill', 'grilled'],
            'lẩu' => ['hotpot'],
            'hải' => ['seafood'],
        ];

        return array_map(fn (string $token): array => array_values(array_unique([$token, ...($aliases[$token] ?? [])])), $tokens);
    }
}

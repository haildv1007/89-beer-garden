<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    use SoftDeletes;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'short_description',
        'description',
        'price',
        'image_url',
        'status',
        'is_available',
    ];

    protected function casts(): array
    {
        return ['price' => 'integer', 'is_available' => 'boolean'];
    }

    public function scopePublicMenu(Builder $query): Builder
    {
        return $query
            ->where('status', self::STATUS_ACTIVE)
            ->whereHas('category', fn (Builder $category) => $category->active());
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(ProductMedia::class)->orderBy('sort_order')->orderBy('id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class)->orderBy('sort_order')->orderBy('name');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order')->orderBy('id');
    }

    public function availableVariants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->where('is_available', true)->orderBy('sort_order')->orderBy('id');
    }

    public function getDisplayPriceAttribute(): string
    {
        $variants = $this->relationLoaded('availableVariants')
            ? $this->availableVariants
            : ($this->relationLoaded('variants') ? $this->variants->where('is_available', true) : null);
        if ($variants?->isNotEmpty()) {
            $minimum = (int) $variants->min('price');
            $maximum = (int) $variants->max('price');

            return $minimum === $maximum
                ? number_format($minimum, 0, ',', '.').'đ'
                : number_format($minimum, 0, ',', '.').'đ - '.number_format($maximum, 0, ',', '.').'đ';
        }

        return number_format($this->price, 0, ',', '.').' ₫';
    }

    public function getPrimaryImageUrlAttribute(): ?string
    {
        return $this->media->firstWhere('media_type', 'image')?->url ?? $this->image_url;
    }

    public function inventoryItem(): HasOne
    {
        return $this->hasOne(InventoryItem::class);
    }

    public function translations(): MorphMany
    {
        return $this->morphMany(Translation::class, 'translatable');
    }
}

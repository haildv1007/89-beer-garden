<?php

namespace App\Services\Translation;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;

final class TranslationCatalog
{
    public const LOCALES = ['en', 'zh'];

    public const ENTITIES = ['category' => Category::class, 'product' => Product::class];

    public const FIELDS = ['name', 'short_description', 'description'];

    public function model(string $alias, int $id, bool $lock = false): ?Model
    {
        $class = self::ENTITIES[$alias] ?? null;

        return $class === null ? null : $class::query()->when($lock, fn ($q) => $q->lockForUpdate())->find($id);
    }

    public function alias(Model|string $model): ?string
    {
        $class = is_string($model) ? $model : $model::class;

        return array_search($class, self::ENTITIES, true) ?: null;
    }

    public function valid(Model $model, string $field, string $locale): bool
    {
        return $this->alias($model) !== null &&
            in_array($field, self::FIELDS, true) &&
            in_array($locale, self::LOCALES, true);
    }

    public function hash(string $source): string
    {
        return hash('sha256', $source);
    }
}

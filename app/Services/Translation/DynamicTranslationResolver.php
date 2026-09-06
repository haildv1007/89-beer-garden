<?php

namespace App\Services\Translation;

use App\Contracts\TranslationProvider;
use App\Models\Translation;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DynamicTranslationResolver
{
    public function __construct(private TranslationCatalog $catalog, private TranslationProvider $provider) {}

    public function resolve(Model $entity, string $field, ?string $locale = null): string
    {
        $locale ??= app()->getLocale();
        $source = (string) $entity->getAttribute($field);
        if (
            $locale === 'vi' ||
            ! $this->catalog->valid($entity, $field, $locale) ||
            ! $entity->exists ||
            $entity->trashed()
        ) {
            return $source;
        }
        $row = Translation::query()
            ->whereMorphedTo('translatable', $entity)
            ->where(compact('field', 'locale'))
            ->first();

        return $this->resolveLoaded($entity, $field, $locale, $row);
    }

    private function resolveLoaded(Model $entity, string $field, string $locale, ?Translation $row): string
    {
        $source = (string) $entity->getAttribute($field);
        $hash = $this->catalog->hash($source);
        if ($row && $row->source_hash === $hash && trim($row->translated_text) !== '') {
            return $row->translated_text;
        }
        if ($source === '') {
            return $source;
        }
        $result = $this->provider->translate($source, 'vi', $locale);
        $translated = trim((string) $result->text);
        if (! $result->successful || $translated === '' || mb_strlen($translated) > 10000) {
            return $source;
        }

        return $this->persistProvider($entity, $field, $locale, $source, $hash, $translated) ?? $source;
    }

    /** @return Collection<string, string> */
    public function batch(
        EloquentCollection|Collection $entities,
        ?string $locale = null,
        ?array $fields = null,
    ): Collection {
        $locale ??= app()->getLocale();
        $fields = array_values(array_intersect($fields ?? TranslationCatalog::FIELDS, TranslationCatalog::FIELDS));
        $map = collect();
        if ($locale === 'vi' || ! in_array($locale, TranslationCatalog::LOCALES, true) || $entities->isEmpty()) {
            return $map;
        }
        foreach ($entities->groupBy(fn ($e) => $e::class) as $class => $group) {
            if ($this->catalog->alias($class) === null) {
                continue;
            }
            $rows = Translation::query()
                ->where('translatable_type', $class)
                ->whereIn('translatable_id', $group->pluck('id'))
                ->where('locale', $locale)
                ->whereIn('field', $fields)
                ->get();
            $indexed = $rows->keyBy(fn (Translation $row) => $row->translatable_id.':'.$row->field);
            foreach ($group as $entity) {
                foreach ($fields as $field) {
                    if ($entity->getAttribute($field) === null || $entity->getAttribute($field) === '') {
                        continue;
                    }
                    $translated = $this->resolveLoaded(
                        $entity,
                        $field,
                        $locale,
                        $indexed->get($entity->id.':'.$field),
                    );
                    if ($translated !== (string) $entity->getAttribute($field)) {
                        $map->put($class.':'.$entity->id.':'.$field, $translated);
                    }
                }
            }
        }

        return $map;
    }

    private function persistProvider(
        Model $entity,
        string $field,
        string $locale,
        string $source,
        string $hash,
        string $translated,
    ): ?string {
        try {
            return $this->persistProviderTransaction($entity, $field, $locale, $source, $hash, $translated);
        } catch (QueryException $exception) {
            if (! in_array((string) ($exception->errorInfo[0] ?? ''), ['23000', '23505'], true)) {
                throw $exception;
            }

            return $this->persistProviderTransaction($entity, $field, $locale, $source, $hash, $translated);
        }
    }

    private function persistProviderTransaction(
        Model $entity,
        string $field,
        string $locale,
        string $source,
        string $hash,
        string $translated,
    ): ?string {
        return DB::transaction(function () use ($entity, $field, $locale, $source, $hash, $translated) {
            $locked = $entity::query()->lockForUpdate()->find($entity->id);
            if (! $locked || (string) $locked->getAttribute($field) !== $source) {
                return null;
            }
            $row = Translation::query()
                ->whereMorphedTo('translatable', $locked)
                ->where(compact('field', 'locale'))
                ->lockForUpdate()
                ->first();
            $row ??= new Translation;
            $row->forceFill([
                'translatable_type' => $locked::class,
                'translatable_id' => $locked->id,
                'field' => $field,
                'locale' => $locale,
                'source_text' => $source,
                'translated_text' => $translated,
                'source_hash' => $hash,
                'source' => 'provider',
                'updated_by_employee_id' => null,
            ])->save();

            return $translated;
        }, 3);
    }
}

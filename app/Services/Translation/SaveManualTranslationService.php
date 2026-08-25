<?php

namespace App\Services\Translation;

use App\Enums\EmployeeStatus;
use App\Models\Employee;
use App\Models\Translation;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveManualTranslationService
{
    public function __construct(private TranslationCatalog $catalog) {}

    public function save(string $entityType, int $entityId, string $field, string $locale, mixed $text, User $actor): Translation
    {
        $text = is_string($text) ? trim($text) : '';
        if ($text === '' || mb_strlen($text) > 10000 || ! in_array($field, TranslationCatalog::FIELDS, true) || ! in_array($locale, TranslationCatalog::LOCALES, true)) {
            throw ValidationException::withMessages(['translated_text' => __('translation.validation.invalid')]);
        }

        try {
            return $this->persist($entityType, $entityId, $field, $locale, $text, $actor);
        } catch (QueryException $exception) {
            if (! in_array((string) ($exception->errorInfo[0] ?? ''), ['23000', '23505'], true)) {
                throw $exception;
            }

            return $this->persist($entityType, $entityId, $field, $locale, $text, $actor);
        }
    }

    private function persist(string $entityType, int $entityId, string $field, string $locale, string $text, User $actor): Translation
    {
        return DB::transaction(function () use ($entityType, $entityId, $field, $locale, $text, $actor) {
            $entity = $this->catalog->model($entityType, $entityId, true);
            if (! $entity) {
                throw ValidationException::withMessages(['entity_id' => __('translation.validation.invalid_entity')]);
            }
            $row = Translation::query()->whereMorphedTo('translatable', $entity)->where(compact('field', 'locale'))->lockForUpdate()->first();
            $employee = Employee::query()->where('user_id', $actor->id)->lockForUpdate()->first();
            $user = User::query()->lockForUpdate()->find($actor->id);
            if (! $user?->isActive() || $employee?->status !== EmployeeStatus::Active) {
                throw ValidationException::withMessages(['translated_text' => __('translation.validation.actor')]);
            }
            $source = (string) $entity->getAttribute($field);
            $row ??= new Translation;
            $row->forceFill(['translatable_type' => $entity::class, 'translatable_id' => $entity->id, 'field' => $field,
                'locale' => $locale, 'source_text' => $source, 'translated_text' => $text,
                'source_hash' => $this->catalog->hash($source), 'source' => 'manual', 'updated_by_employee_id' => $employee->id])->save();

            return $row;
        }, 3);
    }
}

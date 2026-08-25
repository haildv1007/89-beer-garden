<?php

namespace App\Http\Requests\Admin;

use App\Services\Translation\TranslationCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveTranslationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('translation.update') === true;
    }

    public function rules(): array
    {
        return ['entity_type' => ['required', Rule::in(array_keys(TranslationCatalog::ENTITIES))], 'entity_id' => ['required', 'integer', 'min:1'], 'field' => ['required', Rule::in(TranslationCatalog::FIELDS)], 'locale' => ['required', Rule::in(TranslationCatalog::LOCALES)], 'translated_text' => ['required', 'string', 'max:10000'], 'translatable_type' => ['prohibited'], 'source_text' => ['prohibited'], 'source_hash' => ['prohibited'], 'source' => ['prohibited'], 'updated_by_employee_id' => ['prohibited'], 'created_at' => ['prohibited'], 'updated_at' => ['prohibited']];
    }
}

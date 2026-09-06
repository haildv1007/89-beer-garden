<?php

namespace App\Http\Requests\Admin;

use App\Services\SystemSetting\SystemSettingCatalog;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSystemSettingGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('settings.update') === true;
    }

    public function rules(): array
    {
        $catalog = app(SystemSettingCatalog::class);
        $keys = $catalog->groups()[(string) $this->route('group')]['keys'] ?? [];
        $textKeys = [];
        $imageKeys = [];

        foreach ($keys as $key) {
            $definition = $catalog->definition($key);
            if (($definition['input'] ?? null) === 'image') {
                $imageKeys[] = $key;
            } else {
                $textKeys[] = $key;
            }
        }

        $rules = [
            'values' => ['nullable', 'array:'.implode(',', $textKeys)],
            'images' => ['nullable', 'array:'.implode(',', $imageKeys)],
            'value' => ['prohibited'],
            'image' => ['prohibited'],
            'key' => ['prohibited'],
            'type' => ['prohibited'],
            'updated_by_employee_id' => ['prohibited'],
        ];

        foreach ($textKeys as $key) {
            $definition = $catalog->definition($key);
            $rules["values.{$key}"] = ['nullable', 'string', 'max:'.($definition['max'] ?? 12000)];
        }
        foreach ($imageKeys as $key) {
            $definition = $catalog->definition($key);
            $rules["images.{$key}"] = [
                'nullable',
                'image',
                'mimes:'.implode(',', $definition['extensions']),
                'max:5120',
            ];
        }

        return $rules;
    }
}

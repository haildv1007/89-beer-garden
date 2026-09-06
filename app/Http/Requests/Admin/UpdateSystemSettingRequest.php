<?php

namespace App\Http\Requests\Admin;

use App\Services\SystemSetting\SystemSettingCatalog;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSystemSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('settings.update') === true;
    }

    public function rules(): array
    {
        $definition = app(SystemSettingCatalog::class)->definition((string) $this->route('key'));
        $image = ($definition['input'] ?? null) === 'image';

        return [
            'value' => $image ? ['prohibited'] : ['required', 'string', 'max:12000'],
            'image' => $image
                ? ['required', 'image', 'mimes:'.implode(',', $definition['extensions']), 'max:5120']
                : ['prohibited'],
            'key' => ['prohibited'],
            'type' => ['prohibited'],
            'updated_by_employee_id' => ['prohibited'],
            'created_at' => ['prohibited'],
            'updated_at' => ['prohibited'],
        ];
    }
}

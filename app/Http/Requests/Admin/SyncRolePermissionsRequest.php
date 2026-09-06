<?php

namespace App\Http\Requests\Admin;

use App\Models\Permission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncRolePermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('permission.assign') === true;
    }

    public function rules(): array
    {
        return [
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => [
                'integer',
                'distinct',
                Rule::exists('permissions', 'id')->whereIn('code', array_keys(Permission::CATALOG)),
            ],
        ];
    }
}

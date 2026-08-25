<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSystemSettingRequest;
use App\Models\SystemSetting;
use App\Services\SystemSetting\SystemSettingCatalog;
use App\Services\SystemSetting\UpdateSystemSettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SystemSettingController extends Controller
{
    public function index(SystemSettingCatalog $catalog): View
    {
        $definitions = $catalog->definitions();
        $persisted = SystemSetting::query()->with('updatedBy:id,name')
            ->whereIn('key', array_keys($definitions))->get()->keyBy('key');

        $settings = collect($definitions)->map(function (array $definition, string $key) use ($catalog, $persisted): array {
            $setting = $persisted->get($key);

            return compact('key', 'definition', 'setting') + [
                'valid' => $setting !== null && $catalog->validPersisted($key, $setting->type, $setting->value),
            ];
        });

        return view('admin.settings.index', compact('settings'));
    }

    public function update(UpdateSystemSettingRequest $request, string $key, UpdateSystemSettingService $service): RedirectResponse
    {
        $service->update($key, $request->validated('value'), $request->user());

        return redirect()->route('admin.settings.index')->with('success', __('setting.saved'));
    }
}

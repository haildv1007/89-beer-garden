<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSystemSettingGroupRequest;
use App\Http\Requests\Admin\UpdateSystemSettingRequest;
use App\Models\SystemSetting;
use App\Services\SystemSetting\SystemSettingCatalog;
use App\Services\SystemSetting\TypedSystemSettingResolver;
use App\Services\SystemSetting\UpdateSystemSettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Throwable;

class SystemSettingController extends Controller
{
    public function index(Request $request, SystemSettingCatalog $catalog, TypedSystemSettingResolver $resolver): View
    {
        $definitions = $catalog->definitions();
        $persisted = SystemSetting::query()
            ->with('updatedBy:id,name')
            ->whereIn('key', array_keys($definitions))
            ->get()
            ->keyBy('key');

        $settings = collect($definitions)->map(function (array $definition, string $key) use (
            $catalog,
            $persisted,
            $resolver,
        ): array {
            $setting = $persisted->get($key);
            $valid = $setting !== null &&
                ($definition['secret']
                    ? $resolver->value($key) !== null
                    : $catalog->validPersisted($key, $setting->type, $setting->value));
            $effectiveImageUrl = null;
            $usingDefaultImage = false;
            if (in_array(($definition['input'] ?? null), ['image', 'media'], true)) {
                $effectiveImageUrl = $valid
                    ? Storage::disk('public')->url($setting->value)
                    : match ($key) {
                        SystemSettingCatalog::SITE_LOGO,
                        SystemSettingCatalog::SITE_FAVICON => asset('images/brand/quan-89-logo.png'),
                        SystemSettingCatalog::SITE_BANNER => asset('images/beer-garden-hero.png'),
                        default => null,
                    };
                $usingDefaultImage = ! $valid && $effectiveImageUrl !== null;
            }

            return compact('key', 'definition', 'setting') + [
                'valid' => $valid,
                'effective_image_url' => $effectiveImageUrl,
                'using_default_image' => $usingDefaultImage,
            ];
        });

        $groups = $catalog->groups();
        $selectedGroup = array_key_exists((string) $request->query('tab'), $groups)
            ? (string) $request->query('tab')
            : array_key_first($groups);

        return view('admin.settings.index', compact('settings', 'groups', 'selectedGroup'));
    }

    public function updateGroup(
        UpdateSystemSettingGroupRequest $request,
        string $group,
        SystemSettingCatalog $catalog,
        UpdateSystemSettingService $service,
    ): RedirectResponse {
        $groupDefinition = $catalog->groups()[$group];
        $values = collect($request->validated('values', []))
            ->filter(fn (mixed $value): bool => is_string($value) && $value !== '')
            ->only($groupDefinition['keys'])
            ->all();
        $newPaths = [];
        $oldPaths = [];

        foreach ($request->file('images', []) as $key => $image) {
            if (! in_array($key, $groupDefinition['keys'], true)) {
                continue;
            }
            $oldPaths[$key] = SystemSetting::query()->where('key', $key)->value('value');
            $newPaths[$key] = $image->store('system-settings', 'public');
            $values[$key] = $newPaths[$key];
        }

        try {
            $service->updateMany($values, $request->user());
        } catch (Throwable $exception) {
            Storage::disk('public')->delete(array_values($newPaths));
            throw $exception;
        }

        foreach ($oldPaths as $key => $oldPath) {
            if (is_string($oldPath) && str_starts_with($oldPath, 'system-settings/') && $oldPath !== $newPaths[$key]) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        return redirect()->route('admin.settings.index', ['tab' => $group])->with('success', __('setting.saved'));
    }

    public function update(
        UpdateSystemSettingRequest $request,
        string $key,
        UpdateSystemSettingService $service,
    ): RedirectResponse {
        $definition = app(SystemSettingCatalog::class)->definition($key);
        if (! in_array(($definition['input'] ?? null), ['image', 'media'], true)) {
            $service->update($key, $request->validated('value'), $request->user());

            return redirect()->route('admin.settings.index')
                ->with('success', __('setting.saved'));
        }

        $oldPath = SystemSetting::query()->where('key', $key)->value('value');
        $newPath = $request->file('image')->store('system-settings', 'public');
        try {
            $service->update($key, $newPath, $request->user());
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($newPath);
            throw $exception;
        }
        if (is_string($oldPath) && str_starts_with($oldPath, 'system-settings/')) {
            Storage::disk('public')->delete($oldPath);
        }

        return redirect()->route('admin.settings.index')->with('success', __('setting.saved'));
    }
}

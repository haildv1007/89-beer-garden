<?php

namespace Tests\Feature;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

class AdminUiContextTest extends TestCase
{
    public function test_admin_layout_uses_only_admin_context_stylesheet(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/admin.blade.php'));

        $this->assertStringContainsString('resources/css/app.css', $layout);
        $this->assertStringContainsString('resources/css/admin.css', $layout);
        $this->assertStringNotContainsString('resources/css/customer.css', $layout);
        $this->assertStringNotContainsString('resources/css/pos.css', $layout);
        $this->assertStringNotContainsString('resources/css/kitchen.css', $layout);
        $this->assertStringNotContainsString('locale.switch', $layout);
    }

    public function test_employee_context_layouts_do_not_offer_language_switching(): void
    {
        foreach (['admin', 'pos', 'kitchen'] as $context) {
            $layout = file_get_contents(resource_path("views/layouts/{$context}.blade.php"));
            $this->assertStringNotContainsString('locale.switch', $layout, $context);
            $this->assertStringNotContainsString('supported_locales', $layout, $context);
        }

        $this->assertStringContainsString(
            'locale.switch',
            file_get_contents(resource_path('views/layouts/customer.blade.php')),
        );
    }

    public function test_sidebar_navigation_is_active_and_permission_guarded(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/admin.blade.php'));

        foreach (
            [
                'restaurant-table.manage',
                'customer.view',
                'category.manage',
                'product.manage',
                'voucher.manage',
                'inventory.view',
                'report.view',
                'employee.manage',
                'permission.assign',
                'settings.update',
            ] as $permission
        ) {
            $this->assertStringContainsString("@can('{$permission}')", $layout);
        }

        foreach (['admin.products.*', 'admin.customers.*', 'admin.inventory-items.*', 'admin.roles.*'] as $pattern) {
            $this->assertStringContainsString("request()->routeIs('{$pattern}')", $layout);
        }
    }

    public function test_admin_pages_delegate_flash_and_validation_summary_to_layout(): void
    {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(resource_path('views/admin')));

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $this->assertStringNotContainsString(
                    '$errors->any()',
                    file_get_contents($file->getPathname()),
                    $file->getPathname(),
                );
            }
        }

        $layout = file_get_contents(resource_path('views/layouts/admin.blade.php'));
        $this->assertSame(1, substr_count($layout, '$errors->any()'));
        $this->assertSame(1, preg_match("/@if\\s*\\(\\s*session\\('success'\\)\\s*\\)/", $layout));
    }

    public function test_admin_mutation_forms_keep_csrf_and_method_spoofing(): void
    {
        $forms = [
            'products/_form.blade.php' => ['@csrf', "@method('put')"],
            'employees/_form.blade.php' => ['@csrf', "@method('put')"],
            'restaurant-tables/edit.blade.php' => ['@csrf', "@method('put')"],
            'inventory/movements/create.blade.php' => ['@csrf'],
            'roles/index.blade.php' => ['@csrf', "@method('put')"],
        ];

        foreach ($forms as $path => $assertions) {
            $view = file_get_contents(resource_path('views/admin/'.$path));
            foreach ($assertions as $assertion) {
                $this->assertStringContainsString($assertion, $view, $path);
            }
        }
    }

    public function test_admin_design_system_components_are_available(): void
    {
        foreach (
            [
                'page-header',
                'filter-bar',
                'data-table',
                'status-badge',
                'detail-section',
                'form-section',
                'empty-state',
                'confirmation-dialog',
            ] as $component
        ) {
            $this->assertFileExists(resource_path("views/components/admin/{$component}.blade.php"));
        }
    }
}

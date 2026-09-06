<?php

namespace Tests\Feature;

use Tests\TestCase;

class OperationalUiContextTest extends TestCase
{
    public function test_layouts_load_only_their_context_stylesheet(): void
    {
        $customer = file_get_contents(resource_path('views/layouts/customer.blade.php'));
        $pos = file_get_contents(resource_path('views/layouts/pos.blade.php'));
        $kitchen = file_get_contents(resource_path('views/layouts/kitchen.blade.php'));

        $this->assertStringContainsString('resources/css/customer.css', $customer);
        $this->assertStringNotContainsString('resources/css/pos.css', $customer);
        $this->assertStringNotContainsString('resources/css/kitchen.css', $customer);
        $this->assertStringContainsString('resources/css/pos.css', $pos);
        $this->assertStringNotContainsString('resources/css/customer.css', $pos);
        $this->assertStringNotContainsString('resources/css/kitchen.css', $pos);
        $this->assertStringContainsString('resources/css/kitchen.css', $kitchen);
        $this->assertStringNotContainsString('resources/css/customer.css', $kitchen);
        $this->assertStringNotContainsString('resources/css/pos.css', $kitchen);
    }

    public function test_operational_pages_delegate_flash_and_validation_summary_to_layout(): void
    {
        foreach (glob(resource_path('views/pos/**/*.blade.php')) ?: [] as $view) {
            $this->assertStringNotContainsString('$errors->any()', file_get_contents($view), $view);
        }

        foreach (glob(resource_path('views/kitchen/*.blade.php')) ?: [] as $view) {
            $this->assertStringNotContainsString('$errors->any()', file_get_contents($view), $view);
        }

        $this->assertSame(
            1,
            substr_count(file_get_contents(resource_path('views/layouts/pos.blade.php')), '$errors->first()'),
        );
        $this->assertSame(
            1,
            substr_count(file_get_contents(resource_path('views/layouts/kitchen.blade.php')), '$errors->first()'),
        );
    }

    public function test_kitchen_queue_is_a_print_register_without_cook_state_controls(): void
    {
        $queue = file_get_contents(resource_path('views/kitchen/queue.blade.php'));

        $this->assertStringContainsString('Phiếu bếp', $queue);
        $this->assertStringContainsString('In phiếu', $queue);
        $this->assertStringContainsString('Điều chỉnh', $queue);
        $this->assertStringContainsString('Hủy món', $queue);
        $this->assertStringNotContainsString('Nhận món', $queue);
        $this->assertStringNotContainsString('Hoàn thành', $queue);
    }

    public function test_customer_table_ordering_links_are_not_exposed_but_internal_sessions_remain(): void
    {
        $routes = app('router')->getRoutes();

        $this->assertNull($routes->getByName('pos.dining-sessions.customer-access-link'));
        $this->assertNull($routes->getByName('customer.dining-context.bind'));
        $this->assertNull($routes->getByName('customer.cart.submit'));
        $this->assertNotNull($routes->getByName('pos.dining-sessions.show'));
        $this->assertNotNull($routes->getByName('pos.orders.store'));
        $this->assertNotNull($routes->getByName('pos.billing.open'));
    }
}

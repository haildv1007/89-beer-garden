<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Phase 3 seeds only the approved sensitive-action vocabulary. The
        // complete RBAC permission catalog is intentionally deferred.
        $roles = collect([
            'customer' => 'Customer', 'staff' => 'Staff', 'kitchen' => 'Kitchen',
            'manager' => 'Manager', 'admin' => 'Admin',
        ])->mapWithKeys(fn (string $name, string $code) => [
            $code => Role::query()->updateOrCreate(['code' => $code], ['name' => $name]),
        ]);

        $permissions = collect([
            'product.update-price' => 'Update product price', 'employee.disable' => 'Disable employee',
            'permission.assign' => 'Assign role permissions', 'order-item.cancel' => 'Cancel order item',
            'payment.complete' => 'Complete payment', 'inventory.adjust' => 'Adjust inventory',
            'translation.update' => 'Update persistent translation', 'settings.update' => 'Update system settings',
        ])->mapWithKeys(fn (string $name, string $code) => [
            $code => Permission::query()->updateOrCreate(['code' => $code], ['name' => $name]),
        ]);

        $permissionIds = fn (array $codes): array => $permissions->only($codes)->map->getKey()->values()->all();

        $roles['staff']->permissions()->sync($permissionIds(['order-item.cancel', 'payment.complete']));
        $roles['manager']->permissions()->sync($permissions->only([
            'product.update-price', 'employee.disable', 'order-item.cancel', 'payment.complete', 'inventory.adjust',
        ]));
        $roles['admin']->permissions()->sync($permissionIds($permissions->keys()->all()));
        $roles['customer']->permissions()->sync([]);
        $roles['kitchen']->permissions()->sync([]);
    }
}

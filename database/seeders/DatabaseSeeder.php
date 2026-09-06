<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    private const DEPRECATED_PERMISSIONS = ['order-item.cancel', 'inventory.adjust', 'translation.update'];

    /** @var array<string, list<string>> */
    private const ROLE_PERMISSIONS = [
        'customer' => ['customer.profile.manage-own', 'customer.order.view-own', 'customer.reservation.view-own'],
        'staff' => [
            'context.pos.access',
            'table.view',
            'table.operate',
            'dining-session.open',
            'dining-session.view',
            'reservation.manage',
            'reservation.mark-no-show',
            'order.create',
            'order.update',
            'kitchen.queue.view',
            'order-item.mark-served',
            'order-item.cancel-waiting',
            'billing.view',
            'voucher.apply',
            'payment.complete',
        ],
        'kitchen' => [
            'context.kitchen.access',
            'kitchen.queue.view',
            'order-item.mark-preparing',
            'order-item.mark-ready',
        ],
        'manager' => [
            'context.pos.access',
            'context.kitchen.access',
            'context.admin.access',
            'table.view',
            'table.operate',
            'dining-session.open',
            'dining-session.view',
            'reservation.manage',
            'reservation.mark-no-show',
            'order.create',
            'order.update',
            'kitchen.queue.view',
            'order-item.mark-preparing',
            'order-item.mark-ready',
            'order-item.mark-served',
            'order-item.cancel-waiting',
            'order-item.cancel-preparing',
            'billing.view',
            'voucher.apply',
            'payment.complete',
            'category.manage',
            'product.manage',
            'product.update-price',
            'post.manage',
            'restaurant-table.manage',
            'voucher.manage',
            'inventory.view',
            'inventory.stock-movement.create',
            'customer.view',
            'employee.manage',
            'employee.disable',
            'report.view',
        ],
        'admin' => [
            'context.pos.access',
            'context.kitchen.access',
            'context.admin.access',
            'table.view',
            'table.operate',
            'dining-session.open',
            'dining-session.view',
            'reservation.manage',
            'reservation.mark-no-show',
            'order.create',
            'order.update',
            'kitchen.queue.view',
            'order-item.mark-preparing',
            'order-item.mark-ready',
            'order-item.mark-served',
            'order-item.cancel-waiting',
            'order-item.cancel-preparing',
            'billing.view',
            'voucher.apply',
            'payment.complete',
            'category.manage',
            'product.manage',
            'product.update-price',
            'post.manage',
            'restaurant-table.manage',
            'voucher.manage',
            'inventory.view',
            'inventory.stock-movement.create',
            'customer.view',
            'employee.manage',
            'employee.disable',
            'permission.assign',
            'report.view',
            'settings.update',
        ],
    ];

    public function run(): void
    {
        DB::transaction(function (): void {
            $roles = collect([
                'customer' => 'Customer',
                'staff' => 'Staff',
                'kitchen' => 'Kitchen',
                'manager' => 'Manager',
                'admin' => 'Admin',
            ])->mapWithKeys(
                fn (string $name, string $code) => [
                    $code => Role::query()->updateOrCreate(['code' => $code], ['name' => $name]),
                ],
            );

            $deprecatedIds = Permission::query()->whereIn('code', self::DEPRECATED_PERMISSIONS)->pluck('id');

            DB::table('role_permissions')->whereIn('permission_id', $deprecatedIds)->delete();
            Permission::query()->whereIn('id', $deprecatedIds)->delete();

            $permissions = collect(Permission::CATALOG)->mapWithKeys(
                fn (string $name, string $code) => [
                    $code => Permission::query()->updateOrCreate(
                        ['code' => $code],
                        ['name' => $name, 'description' => Permission::REQUIREMENT_TRACES[$code]],
                    ),
                ],
            );

            foreach (self::ROLE_PERMISSIONS as $roleCode => $codes) {
                $roles[$roleCode]->permissions()->sync($permissions->only($codes)->map->getKey()->values()->all());
            }
        });
    }
}

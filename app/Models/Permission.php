<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    /** @var array<string, string> */
    public const INTERNAL_CONTEXT_PERMISSIONS = [
        'pos' => 'context.pos.access',
        'kitchen' => 'context.kitchen.access',
        'admin' => 'context.admin.access',
    ];

    /** @var array<string, string> */
    public const CATALOG = [
        'customer.profile.manage-own' => 'Manage own customer profile',
        'customer.order.view-own' => 'View own customer orders',
        'customer.reservation.view-own' => 'View own customer reservations',
        'table.view' => 'View restaurant tables',
        'table.operate' => 'Operate restaurant tables',
        'dining-session.open' => 'Open dining sessions',
        'dining-session.view' => 'View dining sessions',
        'reservation.manage' => 'Manage reservations',
        'reservation.mark-no-show' => 'Mark reservations as no-show',
        'order.create' => 'Create orders',
        'order.update' => 'Update orders',
        'kitchen.queue.view' => 'View kitchen queue',
        'order-item.mark-preparing' => 'Mark order items as preparing',
        'order-item.mark-ready' => 'Mark order items as ready',
        'order-item.mark-served' => 'Mark order items as served',
        'order-item.cancel-waiting' => 'Cancel waiting order items',
        'order-item.cancel-preparing' => 'Cancel preparing order items',
        'billing.view' => 'View billing information',
        'voucher.apply' => 'Apply vouchers',
        'payment.complete' => 'Complete payments',
        'category.manage' => 'Manage categories',
        'product.manage' => 'Manage products',
        'product.update-price' => 'Update product prices',
        'post.manage' => 'Manage news posts',
        'restaurant-table.manage' => 'Manage restaurant tables',
        'voucher.manage' => 'Manage vouchers',
        'inventory.view' => 'View inventory',
        'inventory.stock-movement.create' => 'Create inventory stock movements',
        'customer.view' => 'View customers for internal operations',
        'employee.manage' => 'Manage employees',
        'employee.disable' => 'Disable employees',
        'permission.assign' => 'Assign role permissions',
        'report.view' => 'View reports',
        'settings.update' => 'Update system settings',
        'context.pos.access' => 'Access the POS context',
        'context.kitchen.access' => 'Access the Kitchen context',
        'context.admin.access' => 'Access the Admin context',
    ];

    /**
     * Reference metadata only. Baseline documents remain the source of truth.
     *
     * @var array<string, string>
     */
    public const REQUIREMENT_TRACES = [
        'customer.profile.manage-own' => 'System Requirements 5.9; UC-CUS-12; DB-RULE-14.',
        'customer.order.view-own' => 'System Requirements 5.9; UC-CUS-14; DB-RULE-14.',
        'customer.reservation.view-own' => 'System Requirements 5.9; UC-CUS-17; DB-RULE-14.',
        'table.view' => 'FR-TABLE-01..02; UC-STF-02.',
        'table.operate' => 'FR-TABLE-03..08; UC-STF-03/04/07.',
        'dining-session.open' => 'FR-SESSION-01..05; UC-STF-09.',
        'dining-session.view' => 'FR-SESSION-03; FR-ORDER-12; UC-STF-10.',
        'reservation.manage' => 'FR-RES-05..10; UC-STF-05/06/07; UC-ADM-05.',
        'reservation.mark-no-show' => 'FR-RES-11..12; UC-STF-08.',
        'order.create' => 'FR-ORDER-01/03..11/13; UC-STF-11/13.',
        'order.update' => 'FR-ORDER-06; UC-STF-12; UC-ADM-06.',
        'kitchen.queue.view' => 'FR-KITCHEN-01..02/06; UC-KIT-02; UC-STF-14.',
        'order-item.mark-preparing' => 'FR-KITCHEN-03/06; UC-KIT-03.',
        'order-item.mark-ready' => 'FR-KITCHEN-04/06; UC-KIT-04.',
        'order-item.mark-served' => 'FR-KITCHEN-05..06; UC-STF-15.',
        'order-item.cancel-waiting' => 'FR-KITCHEN-07..09; UC-STF-16; PERM-ADMIN-06.',
        'order-item.cancel-preparing' => 'FR-KITCHEN-07..09; UC-STF-16; PERM-ADMIN-06.',
        'billing.view' => 'FR-PAY-01..03; UC-STF-17.',
        'voucher.apply' => 'FR-PAY-04; FR-VOUCHER-03..06; UC-STF-18.',
        'payment.complete' => 'FR-PAY-03..11; UC-STF-19/21.',
        'category.manage' => 'FR-MENU-06; UC-ADM-02.',
        'product.manage' => 'FR-MENU-07..10; UC-ADM-03.',
        'product.update-price' => 'FR-MENU-08; PERM-ADMIN-04; UC-ADM-03.',
        'post.manage' => 'Admin-managed public news and editorial content.',
        'restaurant-table.manage' => 'FR-TABLE-07; UC-ADM-04.',
        'voucher.manage' => 'FR-VOUCHER-01..02; UC-ADM-07.',
        'inventory.view' => 'FR-INV-01..08; UC-ADM-08; SYS-DEC-14.',
        'inventory.stock-movement.create' => 'FR-INV-01..08; UC-ADM-08; SYS-DEC-14.',
        'customer.view' => 'FR-CUSTOMER-01..05; UC-ADM-09.',
        'employee.manage' => 'FR-EMP-01/02/04/05; UC-ADM-10.',
        'employee.disable' => 'FR-EMP-04/05; UC-ADM-10; BR-USER-04.',
        'permission.assign' => 'FR-EMP-03; PERM-*; AUTHZ-*; UC-ADM-11.',
        'report.view' => 'FR-REPORT-01..06; UC-ADM-12.',
        'settings.update' => 'FR-CONFIG-*; UC-ADM-14; SYS-DEC-13.',
        'context.pos.access' => 'AUTHZ-*; SYS-DEC-08; POS context boundary.',
        'context.kitchen.access' => 'AUTHZ-*; SYS-DEC-08; Kitchen context boundary.',
        'context.admin.access' => 'AUTHZ-*; SYS-DEC-08; Admin context boundary.',
    ];

    protected $fillable = ['name', 'code', 'description'];

    public function scopeApproved(Builder $query): Builder
    {
        $query->whereIn('code', array_keys(self::CATALOG));

        if (! config('features.inventory')) {
            $query->whereNotIn('code', [
                'inventory.view',
                'inventory.stock-movement.create',
            ]);
        }

        return $query;
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permissions');
    }
}

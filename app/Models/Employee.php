<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use SoftDeletes;

    protected $fillable = ['employee_code', 'name', 'phone', 'position'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function openedDiningSessions(): HasMany
    {
        return $this->hasMany(DiningSession::class, 'opened_by_employee_id');
    }

    public function completedDiningSessions(): HasMany
    {
        return $this->hasMany(DiningSession::class, 'completed_by_employee_id');
    }

    public function confirmedReservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'confirmed_by_employee_id');
    }

    public function createdOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'created_by_employee_id');
    }

    public function cancelledOrderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'cancelled_by_employee_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'processed_by_employee_id');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'created_by_employee_id');
    }

    public function updatedTranslations(): HasMany
    {
        return $this->hasMany(Translation::class, 'updated_by_employee_id');
    }

    public function updatedSystemSettings(): HasMany
    {
        return $this->hasMany(SystemSetting::class, 'updated_by_employee_id');
    }
}

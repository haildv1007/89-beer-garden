<?php

namespace App\Models;

use App\Enums\DiningSessionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DiningSession extends Model
{
    protected $fillable = ['guest_count', 'note'];

    protected function casts(): array
    {
        return [
            'status' => DiningSessionStatus::class,
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'guest_count' => 'integer',
        ];
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(RestaurantTable::class, 'table_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'opened_by_employee_id');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'completed_by_employee_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function bill(): HasOne
    {
        return $this->hasOne(Bill::class);
    }
}

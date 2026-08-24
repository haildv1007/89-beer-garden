<?php

namespace App\Models;

use App\Enums\ReservationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Reservation extends Model
{
    protected $fillable = [
        'reservation_date', 'reservation_time', 'party_size', 'note',
    ];

    protected function casts(): array
    {
        return ['status' => ReservationStatus::class, 'reservation_date' => 'date', 'confirmed_at' => 'datetime', 'checked_in_at' => 'datetime', 'completed_at' => 'datetime', 'no_show_at' => 'datetime', 'cancelled_at' => 'datetime', 'party_size' => 'integer'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(RestaurantTable::class, 'table_id');
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'confirmed_by_employee_id');
    }

    public function diningSession(): HasOne
    {
        return $this->hasOne(DiningSession::class);
    }
}

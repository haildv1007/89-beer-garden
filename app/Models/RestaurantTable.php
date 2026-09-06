<?php

namespace App\Models;

use App\Enums\DiningSessionStatus;
use App\Enums\RestaurantTableStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class RestaurantTable extends Model
{
    use SoftDeletes;

    protected $fillable = ['code', 'name', 'capacity', 'location'];

    protected function casts(): array
    {
        return ['runtime_status' => RestaurantTableStatus::class, 'is_active' => 'boolean', 'capacity' => 'integer'];
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'table_id');
    }

    public function diningSessions(): HasMany
    {
        return $this->hasMany(DiningSession::class, 'table_id');
    }

    public function activeDiningSession(): HasOne
    {
        return $this->hasOne(DiningSession::class, 'table_id')->where('status', DiningSessionStatus::Active->value);
    }

    public function scopeAssignableFor(Builder $query, int $partySize): Builder
    {
        return $query
            ->where('is_active', true)
            ->where('runtime_status', RestaurantTableStatus::Available->value)
            ->where('capacity', '>=', $partySize)
            ->whereDoesntHave('activeDiningSession');
    }
}

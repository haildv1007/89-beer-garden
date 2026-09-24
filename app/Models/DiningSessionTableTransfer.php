<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiningSessionTableTransfer extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['transferred_at' => 'datetime'];
    }

    public function diningSession(): BelongsTo { return $this->belongsTo(DiningSession::class); }
    public function fromTable(): BelongsTo { return $this->belongsTo(RestaurantTable::class, 'from_table_id'); }
    public function toTable(): BelongsTo { return $this->belongsTo(RestaurantTable::class, 'to_table_id'); }
    public function transferredBy(): BelongsTo { return $this->belongsTo(Employee::class, 'transferred_by_employee_id'); }
}

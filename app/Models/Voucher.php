<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Voucher extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code', 'name', 'discount_type', 'discount_value', 'min_order_amount',
        'max_discount_amount', 'start_at', 'end_at', 'usage_limit',
    ];

    protected function casts(): array
    {
        return ['discount_value' => 'integer', 'min_order_amount' => 'integer', 'max_discount_amount' => 'integer', 'usage_limit' => 'integer', 'used_count' => 'integer', 'start_at' => 'datetime', 'end_at' => 'datetime'];
    }

    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }
}

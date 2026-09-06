<?php

namespace App\Models;

use App\Enums\OrderItemStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FulfillmentOrderItem extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['status' => OrderItemStatus::class];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(FulfillmentOrder::class, 'fulfillment_order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}

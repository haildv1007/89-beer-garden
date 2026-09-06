<?php

namespace App\Models;

use App\Enums\OrderItemStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = ['quantity', 'note', 'cancellation_reason'];

    protected function casts(): array
    {
        return [
            'status' => OrderItemStatus::class,
            'quantity' => 'integer',
            'unit_price' => 'integer',
            'line_total' => 'integer',
            'cancelled_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'cancelled_by_employee_id');
    }
}

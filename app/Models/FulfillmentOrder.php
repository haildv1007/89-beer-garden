<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FulfillmentOrder extends Model
{
    public const TYPE_PICKUP = 'pickup';

    public const TYPE_DELIVERY = 'delivery';

    public const TYPE_DINE_IN = 'dine_in';

    public const STATUS_PENDING = 'pending';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_REJECTED = 'rejected';

    public const PAYMENT_ON_RECEIPT = 'pay_on_receipt';

    public const PAYMENT_BANK_TRANSFER = 'bank_transfer';

    public const PAYMENT_UNPAID = 'unpaid';

    public const PAYMENT_PAID = 'paid';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'requested_for' => 'datetime',
            'placed_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'rejected_at' => 'datetime',
            'paid_at' => 'datetime',
            'payment_reported_at' => 'datetime',
            'received_amount' => 'integer',
            'change_amount' => 'integer',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }

    public function confirmedByEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'confirmed_by_employee_id');
    }

    public function paidByEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'paid_by_employee_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(FulfillmentOrderItem::class);
    }

    public function kitchenTickets(): HasMany
    {
        return $this->hasMany(KitchenTicket::class, 'source_id')->where('source_type', 'fulfillment_order');
    }
}

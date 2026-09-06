<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KitchenTicket extends Model
{
    public const TYPE_ORDER = 'order';

    public const TYPE_ADJUSTMENT = 'adjustment';

    public const TYPE_CANCELLATION = 'cancellation';

    public const STATUS_PENDING = 'pending';

    public const STATUS_PRINTING = 'printing';

    public const STATUS_PRINTED = 'printed';

    public const STATUS_FAILED = 'failed';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['payload' => 'array', 'printed_at' => 'datetime', 'print_attempts' => 'integer'];
    }

    public function createdByEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'created_by_employee_id');
    }
}

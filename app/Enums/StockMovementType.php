<?php

namespace App\Enums;

enum StockMovementType: string
{
    case Import = 'import';
    case Export = 'export';
    case AdjustmentIn = 'adjustment_in';
    case AdjustmentOut = 'adjustment_out';
    case Damaged = 'damaged';
    case Return = 'return';
}

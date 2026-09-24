<?php

namespace App\Enums;

enum OrderItemStatus: string
{
    case Waiting = 'waiting';
    case Preparing = 'preparing';
    case Ready = 'ready';
    case Served = 'served';
    case Cancelled = 'cancelled';
}

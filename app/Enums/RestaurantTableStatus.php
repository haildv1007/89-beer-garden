<?php

namespace App\Enums;

enum RestaurantTableStatus: string
{
    case Available = 'available';
    case Reserved = 'reserved';
    case Occupied = 'occupied';
    case Cleaning = 'cleaning';
}

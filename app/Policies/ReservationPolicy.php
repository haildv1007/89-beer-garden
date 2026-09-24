<?php

namespace App\Policies;

use App\Models\Reservation;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ReservationPolicy
{
    public function viewOwn(User $user, Reservation $reservation): Response
    {
        return $this->owns($user, $reservation);
    }

    private function owns(User $user, Reservation $reservation): Response
    {
        return $user->can('customer.reservation.view-own') &&
            $reservation->customer()->where('user_id', $user->getKey())->exists()
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}

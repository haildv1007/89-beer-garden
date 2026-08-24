<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CustomerPolicy
{
    public function viewOwn(User $user, Customer $customer): Response
    {
        return $this->owns($user, $customer);
    }

    public function updateOwn(User $user, Customer $customer): Response
    {
        return $this->owns($user, $customer);
    }

    private function owns(User $user, Customer $customer): Response
    {
        return $user->can('customer.profile.manage-own')
            && $customer->user_id === $user->getKey()
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}

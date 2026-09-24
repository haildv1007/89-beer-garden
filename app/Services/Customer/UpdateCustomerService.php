<?php

namespace App\Services\Customer;

use App\Models\Customer;
use Illuminate\Support\Facades\DB;

class UpdateCustomerService
{
    public function update(Customer $customer, array $data): Customer
    {
        return DB::transaction(function () use ($customer, $data): Customer {
            $customer = Customer::query()->lockForUpdate()->findOrFail($customer->id);
            if ($customer->user_id) {
                $customer->user()->update(['phone' => $data['phone'], 'email' => $data['email']]);
            }
            $customer->forceFill($data)->save();

            return $customer;
        });
    }
}

<?php

namespace App\Services\Customer;

use App\Models\Customer;
use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MergeCustomerService
{
    public function merge(Customer $target, Customer $source, bool $resolveAccountConflict = false): Customer
    {
        return DB::transaction(function () use ($target, $source, $resolveAccountConflict): Customer {
            $customers = Customer::query()
                ->whereIn('id', [$target->id, $source->id])
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            $target = $customers->get($target->id);
            $source = $customers->get($source->id);

            if (
                ! $target ||
                ! $source ||
                $target->is($source) ||
                PhoneNumber::normalize($target->phone) !== PhoneNumber::normalize($source->phone)
            ) {
                throw ValidationException::withMessages([
                    'source_customer_id' => 'Chỉ có thể gộp hai hồ sơ có cùng số điện thoại.',
                ]);
            }
            if (
                ! $resolveAccountConflict &&
                $target->user_id &&
                $source->user_id &&
                $target->user_id !== $source->user_id
            ) {
                throw ValidationException::withMessages([
                    'source_customer_id' => 'Hai hồ sơ đang thuộc hai tài khoản đăng nhập khác nhau. Cần kiểm tra thủ công trước khi gộp.',
                ]);
            }

            $transferredUserId = $target->user_id ?: $source->user_id;
            if (
                $resolveAccountConflict &&
                $target->user_id &&
                $source->user_id &&
                $target->user_id !== $source->user_id
            ) {
                $source->user()->update(['status' => User::STATUS_DISABLED, 'phone' => null]);
            }
            if (! $target->user_id && $source->user_id) {
                $source->forceFill(['user_id' => null])->save();
            }

            $source->reservations()->update(['customer_id' => $target->id]);
            $source->diningSessions()->update(['customer_id' => $target->id]);
            $source->orders()->update(['created_by_customer_id' => $target->id]);
            $source->fulfillmentOrders()->update(['customer_id' => $target->id]);
            $target
                ->forceFill([
                    'user_id' => $transferredUserId,
                    'email' => $target->email ?: $source->email,
                    'phone' => PhoneNumber::normalize($target->phone),
                ])
                ->save();
            $source->delete();

            return $target;
        });
    }
}

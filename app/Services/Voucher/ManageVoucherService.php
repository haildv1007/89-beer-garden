<?php

namespace App\Services\Voucher;

use App\Models\Bill;
use App\Models\Voucher;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManageVoucherService
{
    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): Voucher
    {
        return DB::transaction(function () use ($attributes): Voucher {
            $voucher = new Voucher;
            $voucher->forceFill($attributes + ['used_count' => 0])->save();

            return $voucher;
        });
    }

    /** @param array<string, mixed> $attributes */
    public function update(Voucher $voucher, array $attributes): Voucher
    {
        return DB::transaction(function () use ($voucher, $attributes): Voucher {
            $locked = Voucher::query()->lockForUpdate()->findOrFail($voucher->id);
            $billIds = Bill::query()->where('voucher_id', $voucher->id)->pluck('id');
            $linkedBills = Bill::query()->whereIn('id', $billIds)->orderBy('id')->lockForUpdate()->get(['id']);
            $this->ensureUnused($linkedBills->isNotEmpty());
            $locked->forceFill($attributes)->save();

            return $locked;
        });
    }

    public function delete(Voucher $voucher): void
    {
        DB::transaction(function () use ($voucher): void {
            $locked = Voucher::query()->lockForUpdate()->findOrFail($voucher->id);
            $billIds = Bill::query()->where('voucher_id', $voucher->id)->pluck('id');
            $linkedBills = Bill::query()->whereIn('id', $billIds)->orderBy('id')->lockForUpdate()->get(['id']);
            $this->ensureUnused($linkedBills->isNotEmpty());
            $locked->delete();
        });
    }

    private function ensureUnused(bool $hasLinkedBill): void
    {
        if ($hasLinkedBill) {
            throw ValidationException::withMessages(['voucher' => __('voucher.errors.historical')]);
        }
    }
}

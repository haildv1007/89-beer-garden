<?php

namespace App\Http\Requests\Admin;

use App\Models\Voucher;

class UpdateVoucherRequest extends StoreVoucherRequest
{
    public function rules(): array
    {
        /** @var Voucher $voucher */
        $voucher = $this->route('voucher');

        return $this->voucherRules($voucher);
    }
}

<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Services\SystemSetting\TypedSystemSettingResolver;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function __invoke(TypedSystemSettingResolver $settings): View
    {
        return view('customer.contact', ['contact' => $settings->contactInformation()]);
    }
}

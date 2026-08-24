<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CustomerIndexRequest;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(CustomerIndexRequest $request): View
    {
        $search = trim((string) ($request->validated('q') ?? ''));
        $customers = Customer::query()
            ->with('user:id,email,status,last_login_at')
            ->withCount(['reservations', 'diningSessions', 'orders'])
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('user', fn (Builder $user) => $user->where('email', 'like', "%{$search}%"));
            }))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.customers.index', compact('customers', 'search'));
    }

    public function show(Customer $customer): View
    {
        $customer->load('user:id,email,status,last_login_at')->loadCount(['reservations', 'diningSessions', 'orders']);

        return view('admin.customers.show', compact('customer'));
    }
}

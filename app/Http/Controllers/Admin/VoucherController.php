<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreVoucherRequest;
use App\Http\Requests\Admin\UpdateVoucherRequest;
use App\Http\Requests\Admin\VoucherIndexRequest;
use App\Models\Voucher;
use App\Services\Voucher\ManageVoucherService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class VoucherController extends Controller
{
    public function index(VoucherIndexRequest $request): View
    {
        $search = trim((string) ($request->validated('q') ?? ''));
        $status = $request->validated('status') ?? '';
        $vouchers = Voucher::query()->withCount('bills')
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $query) use ($search): void {
                $query->where('code', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%");
            }))
            ->when($status !== '', fn (Builder $query) => $query->where('status', $status))
            ->latest('id')->paginate(30)->withQueryString();

        return view('admin.vouchers.index', compact('vouchers', 'search', 'status'));
    }

    public function create(): View
    {
        return view('admin.vouchers.create', ['voucher' => (new Voucher)->forceFill([
            'discount_type' => Voucher::TYPE_FIXED,
            'status' => Voucher::STATUS_ACTIVE,
            'min_order_amount' => 0,
        ])]);
    }

    public function store(StoreVoucherRequest $request, ManageVoucherService $service): RedirectResponse
    {
        $voucher = $service->create($request->validated());

        return redirect()->route('admin.vouchers.edit', $voucher)->with('success', __('app.saved'));
    }

    public function edit(Voucher $voucher): View
    {
        $voucher->loadCount('bills');

        return view('admin.vouchers.edit', compact('voucher'));
    }

    public function update(UpdateVoucherRequest $request, Voucher $voucher, ManageVoucherService $service): RedirectResponse
    {
        $service->update($voucher, $request->validated());

        return redirect()->route('admin.vouchers.edit', $voucher)->with('success', __('app.saved'));
    }

    public function destroy(Voucher $voucher, ManageVoucherService $service): RedirectResponse
    {
        abort_unless(request()->user()?->can('voucher.manage'), 403);
        $service->delete($voucher);

        return redirect()->route('admin.vouchers.index')->with('success', __('app.deleted'));
    }
}

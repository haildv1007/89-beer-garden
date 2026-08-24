<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Http\Requests\POS\MarkReservationNoShowRequest;
use App\Http\Requests\POS\ProcessReservationRequest;
use App\Http\Requests\POS\ReservationIndexRequest;
use App\Models\Reservation;
use App\Services\Reservation\ConfirmReservationService;
use App\Services\Reservation\MarkReservationNoShowService;
use App\Services\Reservation\RejectReservationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ReservationController extends Controller
{
    public function index(ReservationIndexRequest $request): View
    {
        $filters = $request->validated();
        $search = trim((string) ($filters['q'] ?? ''));
        $reservations = Reservation::query()
            ->with(['customer:id,name,phone', 'table:id,code,name'])
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $query) use ($search): void {
                $query->where('reservation_code', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn (Builder $customer) => $customer
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%"));
            }))
            ->when(isset($filters['status']), fn (Builder $query) => $query->where('status', $filters['status']))
            ->when(isset($filters['date_from']), fn (Builder $query) => $query->whereDate('reservation_date', '>=', $filters['date_from']))
            ->when(isset($filters['date_to']), fn (Builder $query) => $query->whereDate('reservation_date', '<=', $filters['date_to']))
            ->orderBy('reservation_date')->orderBy('reservation_time')->paginate(30)->withQueryString();

        return view('pos.reservations.index', compact('reservations', 'filters'));
    }

    public function show(Reservation $reservation): View
    {
        $reservation->load(['customer:id,name,phone,email', 'table:id,code,name,capacity', 'confirmedBy:id,name']);

        return view('pos.reservations.show', compact('reservation'));
    }

    public function confirm(ProcessReservationRequest $request, Reservation $reservation, ConfirmReservationService $service): RedirectResponse
    {
        $service->confirm($reservation, $request->user());

        return back()->with('success', __('reservation.internal.confirmed'));
    }

    public function reject(ProcessReservationRequest $request, Reservation $reservation, RejectReservationService $service): RedirectResponse
    {
        $service->reject($reservation);

        return back()->with('success', __('reservation.internal.rejected'));
    }

    public function markNoShow(MarkReservationNoShowRequest $request, Reservation $reservation, MarkReservationNoShowService $service): RedirectResponse
    {
        $service->mark($reservation);

        return back()->with('success', __('reservation.internal.marked_no_show'));
    }
}

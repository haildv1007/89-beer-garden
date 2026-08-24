<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreReservationRequest;
use App\Models\Customer;
use App\Models\Reservation;
use App\Services\Reservation\CreateReservationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ReservationController extends Controller
{
    public function create(Request $request): View
    {
        if ($request->user() !== null && ! $request->user()->can('customer.reservation.view-own')) {
            abort(403);
        }

        $customer = $request->user()?->customer;
        abort_if($request->user() !== null && $customer === null, 404);

        return view('customer.reservations.create', compact('customer'));
    }

    public function store(StoreReservationRequest $request, CreateReservationService $service): RedirectResponse
    {
        $reservation = $service->create($request->validated(), $request->user());

        if ($request->user() !== null) {
            $request->session()->flash('success', __('reservation.customer.created'));

            return new RedirectResponse(route('customer.reservations.show', $reservation, false));
        }

        $request->session()->flash('reservation_code', $reservation->reservation_code);

        return new RedirectResponse(route('customer.reservations.confirmation', absolute: false));
    }

    public function confirmation(Request $request): View
    {
        abort_unless($request->session()->has('reservation_code'), 404);

        return view('customer.reservations.confirmation', [
            'reservationCode' => $request->session()->get('reservation_code'),
        ]);
    }

    public function index(Request $request): View
    {
        $customer = Customer::query()->where('user_id', $request->user()->id)->firstOrFail();
        $reservations = $customer->reservations()->latest('reservation_date')->latest('reservation_time')->paginate(20);

        return view('customer.reservations.index', compact('reservations'));
    }

    public function show(Reservation $reservation): View
    {
        Gate::authorize('viewOwn', $reservation);
        $reservation->load('table:id,code,name');

        return view('customer.reservations.show', compact('reservation'));
    }
}

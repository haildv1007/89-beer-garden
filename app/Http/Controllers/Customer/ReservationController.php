<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreReservationRequest;
use App\Models\Customer;
use App\Models\Reservation;
use App\Services\CustomerOrder\CustomerCartService;
use App\Services\CustomerOrder\PlaceDineInPreorderService;
use App\Services\CustomerOrder\UniversalCartCheckoutService;
use App\Services\Reservation\CreateReservationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ReservationController extends Controller
{
    public function create(Request $request, CustomerCartService $cart, UniversalCartCheckoutService $checkout): View
    {
        if ($request->user() !== null && ! $request->user()->can('customer.reservation.view-own')) {
            abort(403);
        }

        $customer = $request->user()?->customer;
        abort_if($request->user() !== null && $customer === null, 404);

        $rows = [];
        $summary = null;
        if ($request->boolean('preorder')) {
            $candidateRows = $cart->rows($request);
            $candidateSummary = $candidateRows === [] ? null : $checkout->summary($request, $candidateRows);
            if (($candidateSummary['fulfillment_type'] ?? null) === 'dine_in') {
                $rows = $candidateRows;
                $summary = $candidateSummary;
            }
        }

        return view('customer.reservations.create', compact('customer', 'rows', 'summary'));
    }

    public function store(
        StoreReservationRequest $request,
        CreateReservationService $service,
        PlaceDineInPreorderService $preorders,
        CustomerCartService $cart,
        UniversalCartCheckoutService $checkout,
    ): RedirectResponse {
        $data = $request->validated();
        $withPreorder = ($data['with_preorder'] ?? null) === '1';
        unset($data['with_preorder']);
        if ($withPreorder) {
            $rows = $cart->rows($request);
            $summary = $rows === [] ? null : $checkout->summary($request, $rows);
            abort_unless(($summary['fulfillment_type'] ?? null) === 'dine_in', 422);
            $reservation = $preorders->place(
                $data,
                $cart->itemsForSubmit($request),
                $request->user(),
                $request->session()->get(UniversalCartCheckoutService::VOUCHER_KEY),
            );
            $cart->clear($request);
        } else {
            $reservation = $service->create($data, $request->user());
        }

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
        $reservation->load(['table:id,code,name', 'preorder.items']);

        return view('customer.reservations.show', compact('reservation'));
    }
}

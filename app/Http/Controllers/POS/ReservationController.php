<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Http\Requests\POS\CheckInReservationRequest;
use App\Http\Requests\POS\MarkReservationNoShowRequest;
use App\Http\Requests\POS\ProcessReservationRequest;
use App\Http\Requests\POS\ReservationIndexRequest;
use App\Http\Requests\POS\StoreManualReservationRequest;
use App\Http\Requests\POS\UpdateReservationRequest;
use App\Models\Product;
use App\Models\Reservation;
use App\Models\RestaurantTable;
use App\Services\DiningSession\CheckInReservationService;
use App\Services\Reservation\ConfirmReservationService;
use App\Services\Reservation\CreateReservationService;
use App\Services\Reservation\MarkReservationNoShowService;
use App\Services\Reservation\RejectReservationService;
use App\Services\Reservation\UpdateReservationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReservationController extends Controller
{
    public function create(): View
    {
        $products = Product::query()
            ->publicMenu()
            ->where('is_available', true)
            ->orderBy('name')
            ->get(['id', 'name', 'price']);
        $adminContext = request()->routeIs('admin.*');

        return view('pos.reservations.create', compact('adminContext', 'products'));
    }

    public function store(
        StoreManualReservationRequest $request,
        CreateReservationService $service,
        UpdateReservationService $updates,
    ): RedirectResponse {
        $data = $request->validated();
        $reservation = DB::transaction(function () use ($data, $service, $updates) {
            $reservation = $service->create($data, null);

            return $updates->update($reservation, $data);
        });
        $route = $request->routeIs('admin.*') ? 'admin.reservations.show' : 'pos.reservations.show';

        return redirect()->route($route, $reservation)->with('success', 'Đã tạo đặt bàn thủ công.');
    }

    public function index(ReservationIndexRequest $request): View
    {
        $filters = $request->validated();
        $search = trim((string) ($filters['q'] ?? ''));
        $reservations = Reservation::query()
            ->with([
                'customer:id,name,phone,email',
                'table:id,code,name,capacity',
                'confirmedBy:id,name',
                'diningSession:id,reservation_id,session_code',
                'preorder.items.product.media',
            ])
            ->when(
                $search !== '',
                fn (Builder $query) => $query->where(function (Builder $query) use ($search): void {
                    $query
                        ->where('reservation_code', 'like', "%{$search}%")
                        ->orWhereHas(
                            'customer',
                            fn (Builder $customer) => $customer
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%"),
                        );
                }),
            )
            ->when(isset($filters['status']), fn (Builder $query) => $query->where('status', $filters['status']))
            ->when(
                isset($filters['date_from']),
                fn (Builder $query) => $query->whereDate('reservation_date', '>=', $filters['date_from']),
            )
            ->when(
                isset($filters['date_to']),
                fn (Builder $query) => $query->whereDate('reservation_date', '<=', $filters['date_to']),
            )
            ->latest('created_at')
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        $availableTables = RestaurantTable::query()
            ->assignableFor(1)
            ->orderBy('location')
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'capacity', 'location']);
        $products = Product::query()
            ->with('media')
            ->publicMenu()
            ->where('is_available', true)
            ->orderBy('name')
            ->get(['id', 'name', 'price', 'image_url']);

        $adminContext = $request->routeIs('admin.*');

        return view(
            'pos.reservations.index',
            compact('reservations', 'filters', 'adminContext', 'availableTables', 'products'),
        );
    }

    public function show(Reservation $reservation): View
    {
        $reservation->load([
            'customer:id,name,phone,email',
            'table:id,code,name,capacity',
            'confirmedBy:id,name',
            'diningSession:id,reservation_id,session_code',
            'preorder.items',
        ]);
        $availableTables = RestaurantTable::query()
            ->assignableFor($reservation->party_size)
            ->orderBy('location')
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'capacity', 'location']);

        $adminContext = request()->routeIs('admin.*');

        return view('pos.reservations.show', compact('reservation', 'availableTables', 'adminContext'));
    }

    public function confirm(
        ProcessReservationRequest $request,
        Reservation $reservation,
        ConfirmReservationService $service,
    ): RedirectResponse {
        $service->confirm($reservation, $request->user());

        return back()->with('success', __('reservation.internal.confirmed'));
    }

    public function update(
        UpdateReservationRequest $request,
        Reservation $reservation,
        UpdateReservationService $service,
    ): RedirectResponse|JsonResponse {
        $service->update($reservation, $request->validated());

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Đã cập nhật đơn đặt bàn.']);
        }

        return back()->with('success', 'Đã cập nhật đơn đặt bàn.');
    }

    public function reject(
        ProcessReservationRequest $request,
        Reservation $reservation,
        RejectReservationService $service,
    ): RedirectResponse {
        $service->reject($reservation);

        return back()->with('success', __('reservation.internal.rejected'));
    }

    public function markNoShow(
        MarkReservationNoShowRequest $request,
        Reservation $reservation,
        MarkReservationNoShowService $service,
    ): RedirectResponse {
        $service->mark($reservation);

        return back()->with('success', __('reservation.internal.marked_no_show'));
    }

    public function checkIn(
        CheckInReservationRequest $request,
        Reservation $reservation,
        CheckInReservationService $service,
    ): RedirectResponse {
        $table = RestaurantTable::query()->findOrFail((int) $request->validated('table_id'));
        $session = $service->checkIn($reservation, $table, $request->user());

        if ($request->routeIs('admin.*')) {
            return redirect()
                ->route('admin.dining-sessions.show', $session)
                ->with('success', __('dining_session.checked_in'));
        }

        return redirect()
            ->route('pos.dining-sessions.show', $session)
            ->with('success', __('dining_session.checked_in'));
    }
}

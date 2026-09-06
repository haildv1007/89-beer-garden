@extends($adminContext ?? false ? 'layouts.admin' : 'layouts.pos')
@section('title', $reservation->reservation_code)
@section('content')
    @php
        $reservationRoute = $adminContext ?? false ? 'admin.reservations' : 'pos.reservations';
    @endphp
    <div class="operation-record-page reservation-record-page">
        <header class="operation-record-header">
            <div><a class="operation-back-link" href="{{ route($reservationRoute . '.index') }}">← Danh sách đặt bàn</a>
                <h1><x-display-code :code="$reservation->reservation_code" /></h1>
            </div>
            <div>@include('partials.reservation-status-badge', ['status' => $reservation->status])</div>
        </header>
        <dl class="operation-record-facts">
            <div>
                <dt>{{ __('reservation.fields.customer') }}</dt>
                <dd>{{ $reservation->customer->name }} · {{ $reservation->customer->phone }}</dd>
            </div>
            <div>
                <dt>{{ __('reservation.fields.date_time') }}</dt>
                <dd>{{ $reservation->reservation_date->format('d/m/Y') }} ·
                    {{ substr($reservation->reservation_time, 0, 5) }}</dd>
            </div>
            <div>
                <dt>{{ __('reservation.fields.party_size') }}</dt>
                <dd>{{ $reservation->party_size }} khách</dd>
            </div>
            <div>
                <dt>{{ __('reservation.fields.table') }}</dt>
                <dd>{{ $reservation->table ? $reservation->table->code . ' · ' . $reservation->table->name : __('reservation.internal.not_assigned') }}
                </dd>
            </div>
            <div>
                <dt>{{ __('reservation.internal.confirmed_by') }}</dt>
                <dd>{{ $reservation->confirmedBy?->name ?: '—' }}</dd>
            </div>
            <div>
                <dt>{{ __('reservation.fields.note') }}</dt>
                <dd>{{ $reservation->note ?: '—' }}</dd>
            </div>
        </dl>
        @if ($reservation->preorder)
            <section class="operation-card p-3 mb-4">
                <h2 class="h5">{{ __('customer_order.preorder_summary') }}</h2>
                <div class="responsive-data">
                    <table class="table align-middle mb-0">
                        <tbody>
                            @foreach ($reservation->preorder->items as $item)
                                <tr>
                                    <td>{{ $item->product_name }}</td>
                                    <td>× {{ $item->quantity }}</td>
                                    <td class="text-end">{{ number_format($item->line_total) }} ₫</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="2">{{ __('checkout.total') }}</th>
                                <th class="text-end">{{ number_format($reservation->preorder->total_amount) }} ₫</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </section>
        @endif
        <div class="d-flex flex-wrap gap-2">
            @if ($reservation->status === \App\Enums\ReservationStatus::Pending)
                <form method="post" action="{{ route($reservationRoute . '.confirm', $reservation) }}">@csrf
                    @method('patch')<button class="btn btn-success">{{ __('reservation.internal.confirm') }}</button>
                </form>
                <form method="post" action="{{ route($reservationRoute . '.reject', $reservation) }}">@csrf
                    @method('patch')<button
                        class="btn btn-outline-danger">{{ __('reservation.internal.reject') }}</button></form>
                @endif @if ($reservation->status === \App\Enums\ReservationStatus::Confirmed && auth()->user()->can('reservation.mark-no-show'))
                    <form method="post" action="{{ route($reservationRoute . '.mark-no-show', $reservation) }}">@csrf
                        @method('patch')<button
                            class="btn btn-outline-secondary">{{ __('reservation.internal.mark_no_show') }}</button></form>
                @endif
        </div>
        @if (
            $reservation->status === \App\Enums\ReservationStatus::Confirmed &&
                auth()->user()->can('reservation.manage') &&
                auth()->user()->can('dining-session.open') &&
                auth()->user()->can('table.operate'))
            <form class="card card-body mt-4" method="post"
                action="{{ route($reservationRoute . '.check-in', $reservation) }}">@csrf
                <label class="form-label" for="table_id">{{ __('dining_session.select_table') }}</label>
                <div class="d-flex gap-2"><select class="form-select" id="table_id" name="table_id" required>
                        <option value="">{{ __('dining_session.select_table') }}</option>
                        @foreach ($availableTables as $table)
                            <option value="{{ $table->id }}">{{ $table->code }} — {{ $table->name }}
                                ({{ $table->capacity }})
                                {{ $table->location ? ' · ' . $table->location : '' }}</option>
                        @endforeach
                    </select><button class="btn btn-primary"
                        @disabled($availableTables->isEmpty())>{{ __('dining_session.check_in') }}</button></div>
                @if ($availableTables->isEmpty())
                    <small class="text-danger mt-2">{{ __('dining_session.errors.no_suitable_table') }}</small>
                @endif
            </form>
        @endif
        @if ($reservation->diningSession && auth()->user()->can('dining-session.view'))
            @php
                $sessionRoute = $adminContext ?? false ? 'admin.dining-sessions.show' : 'pos.dining-sessions.show';
            @endphp
            <p class="mt-4">
                <a class="btn btn-outline-primary" href="{{ route($sessionRoute, $reservation->diningSession) }}">
                    {{ __('dining_session.view_session', ['code' => \App\Support\DisplayCode::short($reservation->diningSession->session_code)]) }}
                </a>
            </p>
        @endif
    </div>
@endsection

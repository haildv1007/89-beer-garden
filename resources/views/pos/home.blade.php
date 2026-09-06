@extends('layouts.pos')
@section('title', __('app.contexts.pos') . ' — ' . __('app.name'))
@section('content')
    <header class="page-heading">
        <div>
            <h1>{{ __('app.contexts.pos') }}</h1>
            <p>{{ __('app.pos_lead') }}</p>
        </div>
    </header>
    <div class="operation-launcher">
        @can('table.view')
            <a
                href="{{ route('pos.tables.index') }}"><strong>{{ __('table.pos.map') }}</strong><span>{{ __('table.pos.map_lead') }}</span><b>→</b></a>
        @endcan
        @can('reservation.manage')
            <a
                href="{{ route('pos.reservations.index') }}"><strong>{{ __('reservation.internal.title') }}</strong><span>{{ __('reservation.internal.operations_lead') }}</span><b>→</b></a>
        @endcan
        @can('order.create')
            <a
                href="{{ route('pos.fulfillment-orders.index') }}"><strong>{{ __('fulfillment_order.title') }}</strong><span>{{ __('fulfillment_order.lead') }}</span><b>→</b></a>
        @endcan
        @can('dining-session.view')
            <a
                href="{{ route('pos.dining-sessions.index') }}"><strong>{{ __('dining_session.active_title') }}</strong><span>{{ __('dining_session.operations_lead') }}</span><b>→</b></a>
        @endcan
    </div>
@endsection

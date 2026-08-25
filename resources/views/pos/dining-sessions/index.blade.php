@extends('layouts.pos')
@section('title', __('dining_session.title'))
@section('content')
    <h1>{{ __('dining_session.active_title') }}</h1>
    <form class="row g-2 mb-3" method="get"><div class="col-md-9"><input class="form-control" name="q" value="{{ $search }}" placeholder="{{ __('dining_session.search') }}"></div><div class="col-md-3 d-grid"><button class="btn btn-primary">{{ __('app.search') }}</button></div></form>
    @if($sessions->isEmpty())<div class="alert alert-info">{{ __('dining_session.empty') }}</div>@else
        <div class="table-responsive"><table class="table align-middle"><thead><tr><th>{{ __('dining_session.fields.code') }}</th><th>{{ __('dining_session.fields.table') }}</th><th>{{ __('dining_session.fields.customer') }}</th><th>{{ __('dining_session.fields.guests') }}</th><th>{{ __('dining_session.fields.started_at') }}</th><th></th></tr></thead><tbody>
        @foreach($sessions as $session)<tr><td>{{ $session->session_code }}</td><td>{{ $session->table->code }} — {{ $session->table->name }}</td><td>{{ $session->customer?->name ?: __('dining_session.anonymous') }}</td><td>{{ $session->guest_count }}</td><td>{{ $session->started_at->format('d/m/Y H:i') }}</td><td><a href="{{ route('pos.dining-sessions.show', $session) }}">{{ __('app.view_details') }}</a></td></tr>@endforeach
        </tbody></table></div>{{ $sessions->links() }}
    @endif
@endsection

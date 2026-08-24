@extends('layouts.admin')
@section('title', __('customer.admin.title'))
@section('content')
    <h1>{{ __('customer.admin.title') }}</h1>
    <form class="input-group mb-3" method="get"><input class="form-control" name="q" value="{{ $search }}" placeholder="{{ __('customer.admin.search_placeholder') }}"><button class="btn btn-outline-secondary">{{ __('app.search') }}</button></form>
    @if ($customers->isEmpty())<div class="alert alert-info">{{ __('customer.admin.empty') }}</div>@else
        <div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>{{ __('customer.fields.name') }}</th><th>{{ __('customer.fields.phone') }}</th><th>{{ __('customer.fields.email') }}</th><th>{{ __('customer.admin.account') }}</th><th>{{ __('customer.history.reservations') }}</th><th>{{ __('customer.history.sessions') }}</th><th>{{ __('customer.history.orders') }}</th><th></th></tr></thead><tbody>
            @foreach ($customers as $customer)<tr><td>{{ $customer->name }}</td><td>{{ $customer->phone ?: '—' }}</td><td>{{ $customer->email ?: '—' }}</td><td><span class="badge {{ $customer->user ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $customer->user ? __('customer.admin.linked_account') : __('customer.admin.guest_profile') }}</span></td><td>{{ $customer->reservations_count }}</td><td>{{ $customer->dining_sessions_count }}</td><td>{{ $customer->orders_count }}</td><td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.customers.show', $customer) }}">{{ __('app.view_details') }}</a></td></tr>@endforeach
        </tbody></table></div>{{ $customers->links() }}
    @endif
@endsection

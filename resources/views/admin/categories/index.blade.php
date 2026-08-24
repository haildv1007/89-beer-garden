@extends('layouts.admin')
@section('title', __('app.categories.title'))
@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3"><h1>{{ __('app.categories.title') }}</h1>@can('category.manage')<a class="btn btn-primary" href="{{ route('admin.categories.create') }}">{{ __('app.create') }}</a>@endcan</div>
    <form class="input-group mb-3" method="get"><input class="form-control" name="q" value="{{ $search }}" placeholder="{{ __('app.search') }}"><button class="btn btn-outline-secondary">{{ __('app.search') }}</button></form>
    @if ($categories->isEmpty())<div class="alert alert-info">{{ __('app.categories.empty') }}</div>@else
    <div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>{{ __('app.fields.name') }}</th><th>{{ __('app.fields.slug') }}</th><th>{{ __('app.fields.status') }}</th><th>{{ __('app.products.title') }}</th><th></th></tr></thead><tbody>@foreach ($categories as $category)<tr><td>{{ $category->name }}</td><td>{{ $category->slug }}</td><td>@include('admin.partials.status-badge', ['active' => $category->status === 'active', 'label' => __('app.statuses.'.$category->status)])</td><td>{{ $category->products_count }}</td><td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.categories.show', $category) }}">{{ __('app.view_details') }}</a></td></tr>@endforeach</tbody></table></div>{{ $categories->links() }}@endif
@endsection

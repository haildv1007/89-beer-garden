@extends('layouts.admin')
@section('title', $category->name)
@section('content')
    <h1>{{ $category->name }}</h1>
    <p>{{ $category->description }}</p>
    <dl class="row">
        <dt class="col-sm-3">{{ __('app.fields.slug') }}</dt>
        <dd class="col-sm-9">{{ $category->slug }}</dd>
        <dt class="col-sm-3">{{ __('app.fields.status') }}</dt>
        <dd class="col-sm-9">{{ __('app.statuses.' . $category->status) }}</dd>
        <dt class="col-sm-3">{{ __('app.products.title') }}</dt>
        <dd class="col-sm-9">{{ $category->products_count }}</dd>
    </dl>
    <div class="d-flex gap-2"><a class="btn btn-primary"
            href="{{ route('admin.categories.edit', $category) }}">{{ __('app.edit') }}</a>
        <form method="post" action="{{ route('admin.categories.destroy', $category) }}"
            onsubmit="return confirm('{{ __('app.confirm_delete') }}')">@csrf @method('delete')<button
                class="btn btn-outline-danger">{{ __('app.delete') }}</button></form>
    </div>
@endsection

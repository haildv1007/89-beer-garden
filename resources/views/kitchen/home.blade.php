@extends('layouts.kitchen')

@section('title', __('app.contexts.kitchen') . ' — ' . __('app.name'))

@section('content')
    <h1>{{ __('app.contexts.kitchen') }}</h1>
    <p>{{ __('app.foundation_ready') }}</p>
@endsection

@extends('layouts.kitchen')

@section('title', __('kitchen.title'))

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>{{ __('kitchen.title') }}</h1>
        <a class="btn btn-outline-secondary" href="{{ route('kitchen.home') }}">{{ __('kitchen.refresh') }}</a>
    </div>
    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    @foreach ([\App\Enums\OrderItemStatus::Waiting, \App\Enums\OrderItemStatus::Preparing, \App\Enums\OrderItemStatus::Ready] as $status)
        <section class="mb-5">
            <h2>{{ __('kitchen.sections.'.$status->value) }}</h2>
            @if (($items[$status->value] ?? collect())->isEmpty())
                <div class="alert alert-secondary">{{ __('kitchen.empty') }}</div>
            @else
                <div class="row g-3">
                    @foreach ($items[$status->value] as $item)
                        <div class="col-md-6 col-xl-4">
                            <article class="card h-100"><div class="card-body d-flex flex-column">
                                <div class="d-flex justify-content-between"><h3 class="h5">{{ $item->product_name }}</h3><span class="badge text-bg-{{ $status === \App\Enums\OrderItemStatus::Waiting ? 'warning' : ($status === \App\Enums\OrderItemStatus::Preparing ? 'primary' : 'success') }}">{{ __('order.statuses.'.$status->value) }}</span></div>
                                <div>{{ __('kitchen.quantity', ['count' => $item->quantity]) }}</div>
                                <div>{{ $item->order->order_code }} · {{ $item->order->ordered_at->format('H:i d/m/Y') }}</div>
                                <div>{{ $item->order->diningSession->table->code }} — {{ $item->order->diningSession->table->name }}</div>
                                <div>{{ $item->order->diningSession->session_code }}</div>
                                @if ($item->note)<p class="mt-2 mb-0">{{ $item->note }}</p>@endif
                                @if ($status === \App\Enums\OrderItemStatus::Waiting && auth()->user()->can('order-item.mark-preparing'))
                                    <form class="mt-auto pt-3" method="post" action="{{ route('kitchen.order-items.start-preparing', $item) }}">
                                        @csrf @method('patch')
                                        <button class="btn btn-primary w-100">{{ __('kitchen.start_preparing') }}</button>
                                    </form>
                                @endif
                                @if ($status === \App\Enums\OrderItemStatus::Preparing && auth()->user()->can('order-item.mark-ready'))
                                    <form class="mt-auto pt-3" method="post" action="{{ route('kitchen.order-items.mark-ready', $item) }}">
                                        @csrf @method('patch')
                                        <button class="btn btn-success w-100">{{ __('kitchen.mark_ready') }}</button>
                                    </form>
                                @endif
                            </div></article>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    @endforeach
@endsection

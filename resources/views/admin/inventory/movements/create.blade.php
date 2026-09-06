@extends('layouts.admin')
@section('title', __('inventory.create_movement'))
@section('content')
    <h1>
        {{ __('inventory.create_movement') }} — {{ $item->sku }}</h1>
    <p>{{ __('inventory.fields.current_stock') }}: <strong>{{ number_format($item->current_stock) }}
            {{ $item->unit }}</strong></p>
    <form method="post" action="{{ route('admin.inventory-items.movements.store', $item) }}">@csrf<div class="mb-3"><label
                class="form-label">{{ __('inventory.fields.type') }}</label><select class="form-select" name="type">
                @foreach ($types as $type)
                    <option value="{{ $type->value }}" @selected(old('type') === $type->value)>
                        {{ __('inventory.types.' . $type->value) }}</option>
                @endforeach
            </select></div>
        <div class="mb-3"><label class="form-label">{{ __('inventory.fields.quantity') }}</label><input class="form-control"
                required type="number" min="1" name="quantity" value="{{ old('quantity') }}"></div>
        <div class="mb-3"><label class="form-label">{{ __('inventory.fields.note') }}</label>
            <textarea class="form-control" maxlength="2000" name="note">{{ old('note') }}</textarea>
        </div><button class="btn btn-primary">{{ __('inventory.create_movement') }}</button>
    </form>
@endsection

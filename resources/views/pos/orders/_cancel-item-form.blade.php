<form class="mt-2" method="post" action="{{ $route }}">
    @csrf @method('patch')
    <label class="visually-hidden" for="reason-{{ $item->id }}">{{ __('kitchen.cancellation_reason') }}</label>
    <div class="input-group input-group-sm">
        <input class="form-control" id="reason-{{ $item->id }}" name="cancellation_reason" maxlength="1000" placeholder="{{ __('kitchen.cancellation_reason') }}" required>
        <button class="btn btn-outline-danger">{{ __('kitchen.cancel_item') }}</button>
    </div>
</form>

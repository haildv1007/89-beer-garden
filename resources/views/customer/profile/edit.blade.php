@extends('layouts.customer')
@section('title', __('customer.profile.edit') . ' — ' . __('app.name'))
@section('content')
    <div class="account-page profile-edit-page">
        @include('customer.account._nav')
        <header class="account-heading">
            <div><span class="account-kicker">{{ __('customer_ui.account') }}</span>
                <h1>{{ __('customer.profile.edit') }}</h1>
                <p>Cập nhật ảnh đại diện và thông tin liên hệ.</p>
            </div>
        </header>
        <form class="profile-edit-layout js-submit-once" method="post"
            action="{{ route('customer.profile.update') }}" enctype="multipart/form-data">@csrf @method('put')
            <aside class="account-card profile-photo-card">
                <div class="profile-photo-preview">
                    @if ($customer->avatar_path)
                        <img data-avatar-preview src="{{ Storage::disk('public')->url($customer->avatar_path) }}"
                        alt="Ảnh đại diện hiện tại">@else<img data-avatar-preview src=""
                            alt="Ảnh đại diện xem trước" hidden><span
                            data-avatar-fallback>{{ mb_strtoupper(mb_substr($customer->name, 0, 1)) }}</span>
                    @endif
                </div>
                <h2>Ảnh đại diện</h2>
                <p>Ảnh vuông sẽ hiển thị đẹp nhất.</p>
                <label class="btn btn-outline-primary profile-photo-button" for="avatar">Chọn ảnh mới</label>
                <input class="visually-hidden @error('avatar') is-invalid @enderror" id="avatar" type="file"
                    name="avatar" accept="image/jpeg,image/png,image/webp" data-avatar-input>
                <small>JPG, PNG hoặc WebP · tối đa 3 MB</small>
                @error('avatar')
                    <div class="text-danger small mt-2">{{ $message }}</div>
                @enderror
            </aside>
            <section class="account-card profile-fields-card">
                <div>
                    <h2>Thông tin cá nhân</h2>
                    <p>Thông tin dùng cho đặt bàn và đơn hàng của bạn.</p>
                </div>
                <div class="profile-fields-grid">
                    <div class="profile-field-full"><label class="form-label"
                            for="name">{{ __('customer.fields.name') }} *</label><input
                            class="form-control @error('name') is-invalid @enderror" id="name" name="name"
                            value="{{ old('name', $customer->name) }}" required autocomplete="name">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div><label class="form-label" for="phone">{{ __('customer.fields.phone') }}</label><input
                            class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone"
                            value="{{ old('phone', $customer->phone) }}" autocomplete="tel">
                        @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div><label class="form-label" for="email">{{ __('customer.fields.email') }} *</label><input
                            class="form-control @error('email') is-invalid @enderror" id="email" type="email"
                            name="email" value="{{ old('email', $customer->email) }}" required autocomplete="email">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="profile-form-actions"><a class="btn btn-outline-secondary"
                        href="{{ route('customer.profile.show') }}">{{ __('customer.cancel') }}</a><button
                        class="btn btn-primary">{{ __('app.save') }}</button></div>
            </section>
        </form>
    </div>
@endsection
@push('scripts')
    <script>
        document.querySelector('[data-avatar-input]')?.addEventListener('change', event => {
            const file = event.target.files?.[0],
                preview = document.querySelector('[data-avatar-preview]'),
                fallback = document.querySelector('[data-avatar-fallback]');
            if (!file || !preview) return;
            preview.src = URL.createObjectURL(file);
            preview.hidden = false;
            if (fallback) fallback.hidden = true
        })
    </script>
@endpush

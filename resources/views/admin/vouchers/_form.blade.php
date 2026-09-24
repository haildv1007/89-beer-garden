<div class="voucher-editor-layout">
    <main>
        <section class="voucher-editor-card">
            <header>
                <h2>Thông tin voucher</h2>
                <p>Mã khách nhập khi thanh toán và tên chương trình để nhân viên dễ nhận biết.</p>
            </header>
            <div class="voucher-form-grid"><label><span>{{ __('voucher.fields.code') }}</span><input
                        class="form-control @error('code') is-invalid @enderror" name="code" required maxlength="255"
                        value="{{ old('code', $voucher->code) }}" placeholder="Ví dụ: MUNGLE2026">
                    @error('code')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </label>
                <label class="wide"><span>{{ __('voucher.fields.name') }}</span><input
                        class="form-control @error('name') is-invalid @enderror" name="name" required maxlength="255"
                        value="{{ old('name', $voucher->name) }}" placeholder="Tên chương trình ưu đãi">
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </label>
            </div>
        </section>
        <section class="voucher-editor-card">
            <header>
                <h2>Mức giảm và điều kiện</h2>
                <p>Xác định giá trị ưu đãi và giá trị đơn hàng tối thiểu được phép áp dụng.</p>
            </header>
            <div class="voucher-form-grid voucher-benefit-grid">
                <label><span>{{ __('voucher.fields.type') }}</span><select class="form-select" name="discount_type">
                        <option value="fixed" @selected(old('discount_type', $voucher->discount_type) === 'fixed')>{{ __('voucher.types.fixed') }}</option>
                        <option value="percentage" @selected(old('discount_type', $voucher->discount_type) === 'percentage')>{{ __('voucher.types.percentage') }}
                        </option>
                    </select></label><label><span>{{ __('voucher.fields.value') }}</span><input
                        class="form-control @error('discount_value') is-invalid @enderror" type="number" min="0"
                        name="discount_value" required value="{{ old('discount_value', $voucher->discount_value) }}">
                    @error('discount_value')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </label>
                <label><span>{{ __('voucher.fields.minimum') }} (VNĐ)</span><input
                        class="form-control @error('min_order_amount') is-invalid @enderror" type="number"
                        min="0" name="min_order_amount" required
                        value="{{ old('min_order_amount', $voucher->min_order_amount) }}">
                    @error('min_order_amount')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </label>
                <label><span>{{ __('voucher.fields.max_discount') }} (VNĐ)</span><input
                        class="form-control @error('max_discount_amount') is-invalid @enderror" type="number"
                        min="0" name="max_discount_amount"
                        value="{{ old('max_discount_amount', $voucher->max_discount_amount) }}"
                        placeholder="Để trống nếu không giới hạn">
                    @error('max_discount_amount')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </label>
            </div>
        </section>
    </main>
    <aside>
        <section class="voucher-editor-card">
            <header>
                <h2>Phát hành</h2>
                <p>Quản lý thời gian hiệu lực và tổng số lượt khách có thể sử dụng.</p>
            </header>
            <div class="voucher-publish-fields"><label><span>{{ __('voucher.fields.start') }}</span><input
                        class="form-control @error('start_at') is-invalid @enderror" type="datetime-local"
                        name="start_at" required
                        value="{{ old('start_at', $voucher->start_at?->format('Y-m-d\TH:i')) }}">
                    @error('start_at')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </label>
                <label><span>{{ __('voucher.fields.end') }}</span><input
                        class="form-control @error('end_at') is-invalid @enderror" type="datetime-local" name="end_at"
                        required value="{{ old('end_at', $voucher->end_at?->format('Y-m-d\TH:i')) }}">
                    @error('end_at')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </label>
                <label><span>{{ __('voucher.fields.usage_limit') }}</span><input
                        class="form-control @error('usage_limit') is-invalid @enderror" type="number" min="0"
                        name="usage_limit" value="{{ old('usage_limit', $voucher->usage_limit) }}"
                        placeholder="Để trống nếu không giới hạn">
                    @error('usage_limit')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </label><label><span>{{ __('voucher.fields.status') }}</span><select class="form-select"
                        name="status">
                        <option value="active" @selected(old('status', $voucher->status) === 'active')>{{ __('voucher.statuses.active') }}
                        </option>
                        <option value="inactive" @selected(old('status', $voucher->status) === 'inactive')>{{ __('voucher.statuses.inactive') }}
                        </option>
                    </select></label>
            </div>
        </section>
    </aside>
</div>
<div class="voucher-editor-actions"><a class="btn btn-outline-secondary"
        href="{{ route('admin.vouchers.index') }}">Hủy</a><button
        class="btn btn-primary">{{ __('app.save') }}</button></div>

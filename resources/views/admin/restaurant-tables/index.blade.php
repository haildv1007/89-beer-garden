@extends('layouts.admin')
@section('title', __('table.admin.title'))
@section('content')
    <div class="table-admin-page ops-page">
        <header class="admin-page-header ops-page-heading">
            <div>
                <h1>{{ __('table.admin.title') }}</h1>
                <p>Theo dõi nhanh tình trạng bàn và phiên phục vụ hiện tại; chỉnh sửa trực tiếp ngay trong danh sách.</p>
            </div>
            <div class="admin-page-actions"><a class="btn btn-primary" href="{{ route('admin.restaurant-tables.create') }}">＋
                    {{ __('table.admin.create') }}</a></div>
        </header>
        <form class="table-filter-bar ops-filter" method="get">
            <label class="table-filter-search"><span class="visually-hidden">{{ __('app.search') }}</span><input
                    class="form-control" name="q" value="{{ $search }}"
                    placeholder="{{ __('table.search_placeholder') }}"></label>
            <label><span class="visually-hidden">{{ __('table.fields.runtime_status') }}</span><select class="form-select"
                    name="status">
                    <option value="">{{ __('table.all_statuses') }}</option>
                    @foreach (\App\Enums\RestaurantTableStatus::cases() as $option)
                        <option value="{{ $option->value }}" @selected($status === $option->value)>
                            {{ __('table.statuses.' . $option->value) }}</option>
                    @endforeach
                </select>
            </label>
            <label><span class="visually-hidden">{{ __('table.minimum_capacity') }}</span><input class="form-control"
                    type="number" min="1" name="min_capacity" value="{{ $minCapacity }}"
                    placeholder="{{ __('table.minimum_capacity') }}"></label>
            <button class="btn btn-primary">{{ __('app.search') }}</button>
            @if ($search !== '' || $status !== '' || $minCapacity)
                <a class="btn btn-light" href="{{ route('admin.restaurant-tables.index') }}">Xóa lọc</a>
            @endif
        </form>
        @if ($tables->isEmpty())
            <div class="admin-empty-state"><span aria-hidden="true">▦</span><strong>{{ __('table.empty') }}</strong>
                <p>Thử thay đổi bộ lọc hoặc tạo một bàn mới.</p>
            </div>
        @else
            <div class="table-list-shell ops-data-shell">
                <table class="table table-hover align-middle table-admin-list ops-data-table">
                    <thead>
                        <tr>
                            <th>{{ __('table.fields.name') }}</th>
                            <th>{{ __('table.fields.capacity') }}</th>
                            <th>{{ __('table.fields.location') }}</th>
                            <th>Trạng thái</th>
                            <th>Phiên hiện tại</th>
                            <th class="text-end">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($tables as $table)
                            <tr>
                                <td>
                                    <div class="table-identity">
                                        <div><strong>{{ $table->name }}</strong><small>{{ $table->code }}</small></div>
                                    </div>
                                </td>
                                <td><strong>{{ $table->capacity }}</strong> khách</td>
                                <td>{{ $table->location ?: '—' }}</td>
                                <td>
                                    @if ($table->is_active)
                                        @include('partials.table-status-badge', [
                                            'status' => $table->runtime_status,
                                        ])
                                    @else
                                        <span class="admin-status-badge">{{ __('table.inactive') }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($table->activeDiningSession)
                                        <a class="table-session-link"
                                            href="{{ route('admin.dining-sessions.show', $table->activeDiningSession) }}"
                                            aria-label="Xem phiên {{ $table->activeDiningSession->session_code }}"><x-display-code
                                            :code="$table->activeDiningSession->session_code" /></a>@else<span class="table-session-empty">Chưa có
                                            phiên</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="table-row-actions"><a class="btn btn-sm btn-outline-primary"
                                            href="{{ route('admin.restaurant-tables.edit', $table) }}">{{ __('app.edit') }}</a>
                                        <form method="post"
                                            action="{{ route('admin.restaurant-tables.destroy', $table) }}"
                                            onsubmit="return confirm(@js(__('table.admin.confirm_delete'))) ">@csrf
                                            @method('delete')<button
                                                class="btn btn-sm btn-outline-danger">{{ __('app.delete') }}</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $tables->links() }}
        @endif
    </div>
@endsection

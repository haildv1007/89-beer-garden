@extends('layouts.admin')
@section('title', __('setting.title'))
@section('content')
    <div class="system-settings-page">
        @include('admin.settings.partials.heading')
        @include('admin.settings.partials.navigation', [
            'groups' => $groups,
            'selectedGroup' => $selectedGroup,
        ])

        @php($group = $groups[$selectedGroup])
        <form class="settings-tab-form settings-tab-form--{{ $selectedGroup }}" method="POST"
            action="{{ route('admin.settings.group.update', $selectedGroup) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            @include('admin.settings.partials.tab-panel', [
                'group' => $group,
                'groupId' => $selectedGroup,
            ])
            <footer class="settings-form-actions">
                <button class="btn settings-reset-button" type="reset">Làm mới</button>
                <button class="btn btn-primary" type="submit">Lưu cài đặt</button>
            </footer>
        </form>
    </div>
@endsection

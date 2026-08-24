@extends('layouts.admin')
@section('title', __('employee.roles.title'))
@section('content')
    <h1>{{ __('employee.roles.title') }}</h1><p class="text-secondary">{{ __('employee.roles.matrix_help') }}</p>
    <div class="accordion" id="role-matrix">
        @foreach($roles as $role)<div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button @if(!$loop->first) collapsed @endif" type="button" data-bs-toggle="collapse" data-bs-target="#role-{{ $role->id }}">{{ $role->name }} <span class="badge text-bg-secondary ms-2">{{ $role->code }}</span></button></h2><div id="role-{{ $role->id }}" class="accordion-collapse collapse @if($loop->first) show @endif" data-bs-parent="#role-matrix"><div class="accordion-body"><form method="post" action="{{ route('admin.roles.permissions.update', $role) }}">@csrf @method('put')<div class="row">@foreach($permissions as $permission)<div class="col-12 col-md-6 col-xl-4 mb-2"><div class="form-check"><input type="checkbox" class="form-check-input" id="r{{ $role->id }}-p{{ $permission->id }}" name="permissions[]" value="{{ $permission->id }}" @checked($role->permissions->contains($permission))><label class="form-check-label" for="r{{ $role->id }}-p{{ $permission->id }}"><code>{{ $permission->code }}</code><br><small class="text-secondary">{{ $permission->name }}</small></label></div></div>@endforeach</div><button class="btn btn-primary mt-3">{{ __('app.save') }}</button></form></div></div></div>@endforeach
    </div>
@endsection

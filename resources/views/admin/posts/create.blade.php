@extends('layouts.admin')

@section('title', 'Viết bài mới')

@section('content')
    <div class="post-editor-page">
        <a class="admin-back-link" href="{{ route('admin.posts.index') }}">Danh sách tin tức</a>
        <header class="admin-section-heading">
            <div>
                <h1>Viết bài mới</h1>
            </div>
        </header>
        <form method="post" action="{{ route('admin.posts.store') }}" enctype="multipart/form-data">
            @include('admin.posts._form')
        </form>
    </div>
@endsection

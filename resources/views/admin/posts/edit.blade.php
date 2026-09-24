@extends('layouts.admin')

@section('title', 'Chỉnh sửa bài viết')

@section('content')
    <div class="post-editor-page">
        <a class="admin-back-link" href="{{ route('admin.posts.index') }}">Danh sách tin tức</a>
        <header class="admin-section-heading">
            <div>
                <h1>Chỉnh sửa bài viết</h1>
            </div>
            @if ($post->status === 'published' && $post->published_at?->isPast())
                <a class="btn btn-outline-primary" target="_blank" href="{{ route('customer.posts.show', $post) }}">Xem trên
                    website</a>
            @endif
        </header>
        <form method="post" action="{{ route('admin.posts.update', $post) }}" enctype="multipart/form-data">
            @include('admin.posts._form')
        </form>
    </div>
@endsection

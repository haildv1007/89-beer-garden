<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PostImageController extends Controller
{
    public function store(Request $request)
    {
        $request->validate(['file' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120']]);
        $path = $request->file('file')->store('posts/content', 'public');

        return response()->json(['location' => Storage::disk('public')->url($path)]);
    }
}

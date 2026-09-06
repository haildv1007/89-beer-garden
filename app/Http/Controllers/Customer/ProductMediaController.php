<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\ProductMedia;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductMediaController extends Controller
{
    public function __invoke(ProductMedia $productMedia): StreamedResponse
    {
        abort_unless(Storage::disk('public')->exists($productMedia->path), 404);

        return Storage::disk('public')->response($productMedia->path, $productMedia->original_name, [
            'Content-Type' => $productMedia->mime_type,
            'Content-Disposition' => 'inline',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}

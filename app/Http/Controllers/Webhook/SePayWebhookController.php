<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Services\Payment\ProcessSePayWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SePayWebhookController extends Controller
{
    public function __invoke(Request $request, ProcessSePayWebhookService $service): JsonResponse
    {
        $service->process($request);

        return response()->json(['success' => true]);
    }
}

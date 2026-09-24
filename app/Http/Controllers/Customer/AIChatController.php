<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Services\SystemSetting\TypedSystemSettingResolver;
use App\Http\Requests\Customer\StoreAIChatMessageRequest;
use App\Services\AIChat\AIChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AIChatController extends Controller
{
    public function store(StoreAIChatMessageRequest $request, AIChatService $chat): JsonResponse
    {
        abort_unless(app(TypedSystemSettingResolver::class)->gemini()['enabled'], 404);

        return response()->json($chat->respond($request, $request->validated('message')));
    }

    public function reset(Request $request, AIChatService $chat): JsonResponse
    {
        abort_unless(app(TypedSystemSettingResolver::class)->gemini()['enabled'], 404);

        return response()->json($chat->reset($request));
    }

    public function state(Request $request, AIChatService $chat): JsonResponse
    {
        abort_unless(app(TypedSystemSettingResolver::class)->gemini()['enabled'], 404);

        return response()->json($chat->state($request));
    }
}

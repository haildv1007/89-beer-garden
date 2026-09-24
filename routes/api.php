<?php

use App\Http\Controllers\Webhook\SePayWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/sepay', SePayWebhookController::class)
    ->middleware('throttle:120,1')
    ->name('webhooks.sepay');

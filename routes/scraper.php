<?php

use App\Http\Controllers\Api\V1\BrvmWebhookController;

Route::post('/webhooks/brvm-sync', [BrvmWebhookController::class, 'handle'])->middleware(\App\Http\Middleware\VerifyScraperWebhookToken::class);

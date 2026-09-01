<?php

use App\Http\Controllers\TikTokShopCallbackController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'service' => 'tiktok-mcp',
        'status' => 'ok',
    ]);
});

Route::get('/tiktok/callback', TikTokShopCallbackController::class)
    ->name('tiktok.callback');

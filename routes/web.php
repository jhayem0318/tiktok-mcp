<?php

use App\Http\Controllers\TikTokShopCallbackController;
use App\Http\Controllers\TikTokShopReviewController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'service' => 'tiktok-mcp',
        'status' => 'ok',
    ]);
});

Route::get('/tiktok/callback', TikTokShopCallbackController::class)
    ->name('tiktok.callback');

Route::get('/tiktok/review', TikTokShopReviewController::class)
    ->middleware('throttle:30,1')
    ->name('tiktok.review');

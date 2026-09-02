<?php

use App\Http\Controllers\TikTokShopCallbackController;
use App\Http\Controllers\TikTokShopReviewController;
use App\Http\Controllers\TikTokShopReviewLoginController;
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

Route::get('/tiktok/review/login', [TikTokShopReviewLoginController::class, 'create'])
    ->name('tiktok.review.login');
Route::post('/tiktok/review/login', [TikTokShopReviewLoginController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('tiktok.review.login.store');
Route::post('/tiktok/review/logout', [TikTokShopReviewLoginController::class, 'destroy'])
    ->name('tiktok.review.logout');

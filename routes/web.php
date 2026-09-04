<?php

use App\Http\Controllers\RemoteMcpOAuthController;
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

Route::get('/.well-known/oauth-protected-resource', [RemoteMcpOAuthController::class, 'protectedResource'])
    ->name('remote-mcp.protected-resource');
Route::get('/.well-known/oauth-authorization-server', [RemoteMcpOAuthController::class, 'authorizationServer'])
    ->name('remote-mcp.authorization-server');
Route::match(['GET', 'POST'], '/oauth/authorize', [RemoteMcpOAuthController::class, 'authorize'])
    ->middleware('throttle:20,1')
    ->name('remote-mcp.authorize');
Route::get('/oauth/authorize/login', [RemoteMcpOAuthController::class, 'login'])
    ->name('remote-mcp.login');
Route::post('/oauth/authorize/login', [RemoteMcpOAuthController::class, 'authenticate'])
    ->middleware('throttle:10,1')
    ->name('remote-mcp.login.store');

<?php

use App\Http\Controllers\RemoteMcpOAuthController;
use App\Http\Controllers\TikTokShopMcpController;
use Illuminate\Support\Facades\Route;

Route::match(['GET', 'POST'], '/tiktok/mcp', TikTokShopMcpController::class)
    ->middleware('throttle:60,1')
    ->name('tiktok.mcp');

Route::match(['GET', 'POST'], '/tiktok/mcp/remote', TikTokShopMcpController::class)
    ->middleware('throttle:60,1')
    ->name('tiktok.mcp.remote');

Route::post('/oauth/register', [RemoteMcpOAuthController::class, 'register'])
    ->middleware('throttle:20,1')
    ->name('remote-mcp.register');
Route::post('/oauth/token', [RemoteMcpOAuthController::class, 'token'])
    ->middleware('throttle:30,1')
    ->name('remote-mcp.token');
Route::post('/oauth/revoke', [RemoteMcpOAuthController::class, 'revoke'])
    ->middleware('throttle:30,1')
    ->name('remote-mcp.revoke');

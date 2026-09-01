<?php

use App\Http\Controllers\TikTokShopMcpController;
use Illuminate\Support\Facades\Route;

Route::match(['GET', 'POST'], '/tiktok/mcp', TikTokShopMcpController::class)
    ->middleware('throttle:60,1')
    ->name('tiktok.mcp');

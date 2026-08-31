<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'service' => 'tiktok-mcp',
        'status' => 'ok',
    ]);
});

Route::get('/tiktok/callback', function () {
    return response()->json([
        'status' => 'TikTok callback ready',
    ]);
})->name('tiktok.callback');

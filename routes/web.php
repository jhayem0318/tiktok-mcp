<?php

use App\Http\Controllers\ClientAccessAdminController;
use App\Http\Controllers\ClientDashboardController;
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

Route::get('/admin/client-access/login', [ClientAccessAdminController::class, 'login'])
    ->name('client-access.admin.login');
Route::post('/admin/client-access/login', [ClientAccessAdminController::class, 'authenticate'])
    ->middleware('throttle:10,1')
    ->name('client-access.admin.login.store');
Route::get('/admin/client-access', [ClientAccessAdminController::class, 'index'])
    ->middleware('throttle:30,1')
    ->name('client-access.admin');
Route::post('/admin/client-access/clients', [ClientAccessAdminController::class, 'storeClient'])
    ->middleware('throttle:10,1')
    ->name('client-access.admin.clients.store');
Route::post('/admin/client-access/oauth-invitations', [ClientAccessAdminController::class, 'storeOauthInvitation'])
    ->middleware('throttle:10,1')
    ->name('client-access.admin.oauth-invitations.store');
Route::post('/admin/client-access/invites/{invite}/revoke', [ClientAccessAdminController::class, 'revokeInvite'])
    ->middleware('throttle:10,1')
    ->name('client-access.admin.invites.revoke');
Route::post('/admin/client-access/logout', [ClientAccessAdminController::class, 'logout'])
    ->name('client-access.admin.logout');

Route::get('/client/login', [ClientDashboardController::class, 'login'])->name('client.login');
Route::post('/client/login', [ClientDashboardController::class, 'authenticate'])
    ->middleware('throttle:10,1')
    ->name('client.login.store');
Route::get('/client/password', [ClientDashboardController::class, 'editPassword'])
    ->name('client.password.edit');
Route::post('/client/password', [ClientDashboardController::class, 'updatePassword'])
    ->middleware('throttle:10,1')
    ->name('client.password.update');
Route::get('/client/dashboard', [ClientDashboardController::class, 'dashboard'])
    ->middleware('throttle:30,1')
    ->name('client.dashboard');
Route::post('/client/dashboard/reports', [ClientDashboardController::class, 'requestReport'])
    ->middleware('throttle:10,1')
    ->name('client.dashboard.reports.store');
Route::post('/client/logout', [ClientDashboardController::class, 'logout'])
    ->name('client.logout');

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

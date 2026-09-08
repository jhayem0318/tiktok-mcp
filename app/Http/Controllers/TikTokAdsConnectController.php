<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\TikTokAdsMcpClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class TikTokAdsConnectController extends Controller
{
    public function __invoke(Request $request, TikTokAdsMcpClient $adsClient): RedirectResponse
    {
        $clientId = $request->session()->get('client_dashboard_user_id');
        $client = is_int($clientId) ? User::query()->where('is_admin', false)->find($clientId) : null;

        if ($client === null || ! $client->hasActiveClientAccess()) {
            return redirect()->route('client.login');
        }

        $state = Str::random(40);
        $codeVerifier = Str::random(64);
        $codeChallenge = rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');

        Cache::put('tiktok-ads-pkce:'.$state, ['code_verifier' => $codeVerifier, 'user_id' => $client->id], now()->addMinutes(10));

        return redirect()->away($adsClient->authorizationUrl($state, $codeChallenge));
    }
}

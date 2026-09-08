<?php

namespace App\Http\Controllers;

use App\Services\TikTokAdsMcpClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class TikTokAdsConnectController extends Controller
{
    public function __invoke(TikTokAdsMcpClient $adsClient): RedirectResponse
    {
        $state = Str::random(40);
        $codeVerifier = Str::random(64);
        $codeChallenge = rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');

        Cache::put('tiktok-ads-pkce:'.$state, $codeVerifier, now()->addMinutes(10));

        return redirect()->away($adsClient->authorizationUrl($state, $codeChallenge));
    }
}

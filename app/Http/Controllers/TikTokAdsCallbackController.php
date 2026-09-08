<?php

namespace App\Http\Controllers;

use App\Actions\StoreAuthorizedTikTokAdsAccounts;
use App\Actions\StoreTikTokAdsAuthorization;
use App\Exceptions\TikTokAuthorizationException;
use App\Services\TikTokAdsMcpClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TikTokAdsCallbackController extends Controller
{
    public function __invoke(
        Request $request,
        TikTokAdsMcpClient $adsClient,
        StoreTikTokAdsAuthorization $storeAuthorization,
        StoreAuthorizedTikTokAdsAccounts $storeAccounts,
    ): JsonResponse {
        if ($request->string('error')->isNotEmpty()) {
            return response()->json([
                'status' => 'authorization_denied',
                'message' => 'TikTok Ads authorization was not granted.',
            ], 422);
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:4096'],
            'state' => ['required', 'string', 'max:255'],
        ]);

        $codeVerifierCacheKey = 'tiktok-ads-pkce:'.$validated['state'];
        $codeVerifier = Cache::pull($codeVerifierCacheKey);

        if (! is_string($codeVerifier) || $codeVerifier === '') {
            return response()->json([
                'status' => 'invalid_callback',
                'message' => 'The TikTok Ads authorization state has expired or is invalid.',
            ], 422);
        }

        try {
            $tokenData = $adsClient->exchangeCode($validated['code'], $codeVerifier);
            $authorization = $storeAuthorization->handle($adsClient->clientId(), $tokenData['open_id'] ?? null, $tokenData);

            $advertisers = $adsClient->callTool('auth_advertiser_get', [], $authorization);
            $accounts = [];

            foreach ((array) data_get($advertisers, 'data.list', []) as $advertiser) {
                $accounts[] = [
                    'advertiser_id' => (string) data_get($advertiser, 'advertiser_id', ''),
                    'name' => data_get($advertiser, 'advertiser_name'),
                ];
            }

            $storeAccounts->handle($authorization, $accounts);
        } catch (TikTokAuthorizationException $exception) {
            Log::warning('TikTok Ads authorization failed.', [
                'reason' => $exception->getMessage(),
            ]);

            return response()->json([
                'status' => 'authorization_failed',
                'message' => $exception->getMessage(),
            ], 502);
        }

        return response()->json([
            'status' => 'authorized',
            'accounts' => array_map(static fn (array $account): array => [
                'advertiser_id' => $account['advertiser_id'],
                'name' => $account['name'],
            ], $accounts),
        ]);
    }
}

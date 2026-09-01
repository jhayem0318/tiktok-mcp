<?php

namespace App\Http\Controllers;

use App\Actions\StoreTikTokShopAuthorization;
use App\Exceptions\TikTokAuthorizationException;
use App\Services\TikTokShopOAuthClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TikTokShopCallbackController extends Controller
{
    public function __invoke(
        Request $request,
        TikTokShopOAuthClient $oauthClient,
        StoreTikTokShopAuthorization $storeAuthorization,
    ): JsonResponse {
        if ($request->string('error')->isNotEmpty()) {
            return response()->json([
                'status' => 'authorization_denied',
                'message' => 'TikTok Shop authorization was not granted.',
            ], 422);
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:4096'],
            'app_key' => ['nullable', 'string', 'max:255'],
        ]);

        $configuredAppKey = config('services.tiktok.app_key');
        $callbackAppKey = $validated['app_key'] ?? null;

        if (is_string($callbackAppKey)
            && is_string($configuredAppKey)
            && $configuredAppKey !== ''
            && ! hash_equals($configuredAppKey, $callbackAppKey)) {
            return response()->json([
                'status' => 'invalid_callback',
                'message' => 'The callback does not match the configured TikTok Shop app.',
            ], 422);
        }

        try {
            $tokenData = $oauthClient->exchangeAuthorizationCode($validated['code']);
            $authorization = $storeAuthorization->handle($tokenData);
        } catch (TikTokAuthorizationException $exception) {
            Log::warning('TikTok Shop authorization failed.', [
                'reason' => $exception->getMessage(),
            ]);

            return response()->json([
                'status' => 'authorization_failed',
                'message' => $exception->getMessage(),
            ], 502);
        }

        return response()->json([
            'status' => 'authorized',
            'seller' => [
                'name' => $authorization->seller_name,
                'region' => $authorization->seller_base_region,
            ],
        ]);
    }
}

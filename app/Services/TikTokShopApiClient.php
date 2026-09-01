<?php

namespace App\Services;

use App\Exceptions\TikTokShopApiException;
use App\Models\TikTokShopAuthorization;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class TikTokShopApiClient
{
    private const AUTHORIZED_SHOPS_PATH = '/authorization/202309/shops';

    /** @return list<array<string, mixed>> */
    public function authorizedShops(TikTokShopAuthorization $authorization): array
    {
        $appKey = config('services.tiktok.app_key');
        $appSecret = config('services.tiktok.app_secret');
        $apiUrl = config('services.tiktok.api_url');

        if (! is_string($appKey) || $appKey === ''
            || ! is_string($appSecret) || $appSecret === ''
            || ! is_string($apiUrl) || $apiUrl === '') {
            throw new TikTokShopApiException('TikTok Shop API is not configured.');
        }

        $query = [
            'app_key' => $appKey,
            'timestamp' => now()->getTimestamp(),
        ];
        $query['sign'] = $this->sign(self::AUTHORIZED_SHOPS_PATH, $query, $appSecret);

        try {
            $response = Http::acceptJson()
                ->withHeaders(['x-tts-access-token' => $authorization->access_token])
                ->connectTimeout(5)
                ->timeout(15)
                ->get(rtrim($apiUrl, '/').self::AUTHORIZED_SHOPS_PATH, $query)
                ->throw();
        } catch (ConnectionException|RequestException) {
            throw new TikTokShopApiException('TikTok Shop API request failed.');
        }

        $payload = $response->json();
        $shops = is_array($payload) ? data_get($payload, 'data.shops') : null;

        if (! is_array($payload) || ($payload['code'] ?? null) !== 0 || ! is_array($shops)) {
            throw new TikTokShopApiException('TikTok Shop API rejected the request.');
        }

        return array_values(array_filter($shops, 'is_array'));
    }

    /** @param array<string, int|string> $query */
    private function sign(string $path, array $query, string $appSecret): string
    {
        unset($query['access_token'], $query['sign']);
        ksort($query);

        $parameters = '';

        foreach ($query as $key => $value) {
            $parameters .= $key.$value;
        }

        return hash_hmac(
            'sha256',
            $appSecret.$path.$parameters.$appSecret,
            $appSecret,
        );
    }
}

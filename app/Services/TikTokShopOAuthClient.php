<?php

namespace App\Services;

use App\Exceptions\TikTokAuthorizationException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class TikTokShopOAuthClient
{
    /**
     * @return array<string, mixed>
     */
    public function exchangeAuthorizationCode(string $authorizationCode): array
    {
        return $this->requestToken(config('services.tiktok.token_url'), [
            'auth_code' => $authorizationCode,
            'grant_type' => 'authorized_code',
        ]);
    }

    /** @return array<string, mixed> */
    public function refreshAccessToken(string $refreshToken): array
    {
        return $this->requestToken(config('services.tiktok.refresh_url'), [
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        ]);
    }

    /**
     * @param  array<string, string>  $parameters
     * @return array<string, mixed>
     */
    private function requestToken(mixed $tokenUrl, array $parameters): array
    {
        $appKey = config('services.tiktok.app_key');
        $appSecret = config('services.tiktok.app_secret');

        if (! is_string($appKey) || $appKey === ''
            || ! is_string($appSecret) || $appSecret === ''
            || ! is_string($tokenUrl) || $tokenUrl === '') {
            throw new TikTokAuthorizationException('TikTok Shop OAuth is not configured.');
        }

        try {
            $response = Http::acceptJson()
                ->connectTimeout(5)
                ->timeout(15)
                ->get($tokenUrl, [
                    'app_key' => $appKey,
                    'app_secret' => $appSecret,
                    ...$parameters,
                ])
                ->throw();
        } catch (ConnectionException|RequestException) {
            throw new TikTokAuthorizationException('TikTok Shop rejected the token exchange.');
        }

        $payload = $response->json();
        $data = is_array($payload) ? ($payload['data'] ?? null) : null;

        if (! is_array($payload) || ($payload['code'] ?? null) !== 0 || ! is_array($data)) {
            throw new TikTokAuthorizationException('TikTok Shop rejected the token exchange.');
        }

        if (($data['user_type'] ?? null) !== 0) {
            throw new TikTokAuthorizationException('TikTok Shop authorization did not return a seller identity.');
        }

        foreach (['access_token', 'refresh_token', 'open_id', 'access_token_expire_in', 'refresh_token_expire_in'] as $field) {
            if (! array_key_exists($field, $data)) {
                throw new TikTokAuthorizationException('TikTok Shop returned an incomplete token response.');
            }
        }

        return $data;
    }
}

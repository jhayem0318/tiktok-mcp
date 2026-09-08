<?php

namespace App\Actions;

use App\Models\TikTokAdsAuthorization;
use Carbon\CarbonImmutable;

class StoreTikTokAdsAuthorization
{
    /**
     * @param  array<string, mixed>  $tokenData
     */
    public function handle(string $clientId, ?string $openId, array $tokenData): TikTokAdsAuthorization
    {
        return TikTokAdsAuthorization::query()->updateOrCreate(
            [
                'client_id' => $clientId,
                'open_id' => $openId,
            ],
            [
                'access_token' => (string) $tokenData['access_token'],
                'refresh_token' => (string) $tokenData['refresh_token'],
                'access_token_expires_at' => CarbonImmutable::now()->addSeconds((int) $tokenData['expires_in']),
                'refresh_token_expires_at' => CarbonImmutable::now()->addSeconds((int) $tokenData['refresh_token_expires_in']),
                'scope' => is_string($tokenData['scope'] ?? null) ? $tokenData['scope'] : null,
            ],
        );
    }
}

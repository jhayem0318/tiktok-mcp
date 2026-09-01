<?php

namespace App\Actions;

use App\Models\TikTokShopAuthorization;
use Carbon\CarbonImmutable;

class StoreTikTokShopAuthorization
{
    /**
     * @param  array<string, mixed>  $tokenData
     */
    public function handle(array $tokenData): TikTokShopAuthorization
    {
        $grantedScopes = $tokenData['granted_scopes'] ?? [];

        if (is_string($grantedScopes)) {
            $grantedScopes = array_values(array_filter(array_map('trim', explode(',', $grantedScopes))));
        }

        if (! is_array($grantedScopes)) {
            $grantedScopes = [];
        }

        return TikTokShopAuthorization::query()->updateOrCreate(
            [
                'app_key' => (string) config('services.tiktok.app_key'),
                'open_id' => (string) $tokenData['open_id'],
            ],
            [
                'seller_name' => $tokenData['seller_name'] ?? null,
                'seller_base_region' => $tokenData['seller_base_region'] ?? null,
                'user_type' => (int) $tokenData['user_type'],
                'access_token' => (string) $tokenData['access_token'],
                'refresh_token' => (string) $tokenData['refresh_token'],
                'access_token_expires_at' => CarbonImmutable::createFromTimestampUTC((int) $tokenData['access_token_expire_in']),
                'refresh_token_expires_at' => CarbonImmutable::createFromTimestampUTC((int) $tokenData['refresh_token_expire_in']),
                'granted_scopes' => $grantedScopes,
            ],
        );
    }
}

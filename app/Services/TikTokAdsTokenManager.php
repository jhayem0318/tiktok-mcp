<?php

namespace App\Services;

use App\Actions\StoreTikTokAdsAuthorization;
use App\Exceptions\TikTokAuthorizationException;
use App\Models\TikTokAdsAuthorization;
use Illuminate\Support\Facades\Cache;

class TikTokAdsTokenManager
{
    public function __construct(
        private readonly TikTokAdsMcpClient $adsClient,
        private readonly StoreTikTokAdsAuthorization $storeAuthorization,
    ) {}

    public function fresh(TikTokAdsAuthorization $authorization): TikTokAdsAuthorization
    {
        if ($authorization->access_token_expires_at->isAfter(now()->addHours(2))) {
            return $authorization;
        }

        return Cache::lock('tiktok-ads-token-refresh:'.$authorization->id, 30)
            ->block(10, function () use ($authorization): TikTokAdsAuthorization {
                $authorization->refresh();

                if ($authorization->access_token_expires_at->isAfter(now()->addHours(2))) {
                    return $authorization;
                }

                if ($authorization->refresh_token_expires_at->isPast()) {
                    throw new TikTokAuthorizationException('TikTok Ads authorization must be renewed.');
                }

                $tokenData = $this->adsClient->refresh($authorization->refresh_token);

                return $this->storeAuthorization->handle($authorization->client_id, $authorization->open_id, $tokenData);
            });
    }
}

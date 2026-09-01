<?php

namespace App\Services;

use App\Actions\StoreTikTokShopAuthorization;
use App\Exceptions\TikTokAuthorizationException;
use App\Models\TikTokShopAuthorization;
use Illuminate\Support\Facades\Cache;

class TikTokShopTokenManager
{
    public function __construct(
        private readonly TikTokShopOAuthClient $oauthClient,
        private readonly StoreTikTokShopAuthorization $storeAuthorization,
    ) {
    }

    public function fresh(TikTokShopAuthorization $authorization): TikTokShopAuthorization
    {
        if ($authorization->access_token_expires_at->isAfter(now()->addDays(2))) {
            return $authorization;
        }

        return Cache::lock('tiktok-shop-token-refresh:'.$authorization->id, 30)
            ->block(10, function () use ($authorization): TikTokShopAuthorization {
                $authorization->refresh();

                if ($authorization->access_token_expires_at->isAfter(now()->addDays(2))) {
                    return $authorization;
                }

                if ($authorization->refresh_token_expires_at->isPast()) {
                    throw new TikTokAuthorizationException('TikTok Shop authorization must be renewed.');
                }

                $tokenData = $this->oauthClient->refreshAccessToken($authorization->refresh_token);

                if ((string) ($tokenData['open_id'] ?? '') !== $authorization->open_id) {
                    throw new TikTokAuthorizationException('TikTok Shop refreshed a different seller identity.');
                }

                return $this->storeAuthorization->handle($tokenData);
            });
    }
}

<?php

namespace App\Actions;

use App\Models\TikTokShop;
use App\Models\TikTokShopAuthorization;

class StoreAuthorizedTikTokShops
{
    /**
     * @param  list<array<string, mixed>>  $shops
     * @return list<TikTokShop>
     */
    public function handle(TikTokShopAuthorization $authorization, array $shops): array
    {
        $stored = [];

        foreach ($shops as $shop) {
            if (! is_string($shop['id'] ?? null) || $shop['id'] === ''
                || ! is_string($shop['cipher'] ?? null) || $shop['cipher'] === '') {
                continue;
            }

            $stored[] = TikTokShop::query()->updateOrCreate(
                [
                    'tik_tok_shop_authorization_id' => $authorization->id,
                    'shop_id' => $shop['id'],
                ],
                [
                    'shop_code' => $shop['code'] ?? null,
                    'shop_cipher' => $shop['cipher'],
                    'name' => $shop['name'] ?? null,
                    'region' => $shop['region'] ?? null,
                    'seller_type' => $shop['seller_type'] ?? null,
                ],
            );
        }

        return $stored;
    }
}

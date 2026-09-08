<?php

namespace App\Actions;

use App\Models\TikTokAdsAccount;
use App\Models\TikTokAdsAuthorization;

class StoreAuthorizedTikTokAdsAccounts
{
    /**
     * @param  list<array<string, mixed>>  $accounts
     * @return list<TikTokAdsAccount>
     */
    public function handle(TikTokAdsAuthorization $authorization, array $accounts): array
    {
        $stored = [];

        foreach ($accounts as $account) {
            if (! is_string($account['advertiser_id'] ?? null) || $account['advertiser_id'] === '') {
                continue;
            }

            $stored[] = TikTokAdsAccount::query()->updateOrCreate(
                [
                    'tik_tok_ads_authorization_id' => $authorization->id,
                    'advertiser_id' => $account['advertiser_id'],
                ],
                [
                    'name' => $account['name'] ?? null,
                    'currency' => $account['currency'] ?? null,
                    'timezone' => $account['timezone'] ?? null,
                ],
            );
        }

        return $stored;
    }
}

<?php

namespace App\Console\Commands;

use App\Exceptions\TikTokAuthorizationException;
use App\Models\TikTokShopAuthorization;
use App\Services\TikTokShopTokenManager;
use Illuminate\Console\Command;

class RefreshTikTokShopTokens extends Command
{
    protected $signature = 'tiktok:shop:refresh';

    protected $description = 'Refresh TikTok Shop access tokens nearing expiry';

    public function handle(TikTokShopTokenManager $tokenManager): int
    {
        $authorizations = TikTokShopAuthorization::query()
            ->where('access_token_expires_at', '<=', now()->addDays(2))
            ->get();

        $failed = 0;

        foreach ($authorizations as $authorization) {
            try {
                $tokenManager->fresh($authorization);
            } catch (TikTokAuthorizationException) {
                $failed++;
            }
        }

        if ($failed > 0) {
            $this->error("Failed to refresh {$failed} TikTok Shop authorization(s).");

            return self::FAILURE;
        }

        $this->info('TikTok Shop token refresh check succeeded.');

        return self::SUCCESS;
    }
}

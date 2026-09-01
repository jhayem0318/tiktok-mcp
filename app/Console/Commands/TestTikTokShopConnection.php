<?php

namespace App\Console\Commands;

use App\Actions\StoreAuthorizedTikTokShops;
use App\Exceptions\TikTokShopApiException;
use App\Exceptions\TikTokAuthorizationException;
use App\Models\TikTokShopAuthorization;
use App\Services\TikTokShopApiClient;
use Illuminate\Console\Command;

class TestTikTokShopConnection extends Command
{
    protected $signature = 'tiktok:shop:test';

    protected $description = 'Test read-only access to authorized TikTok Shops';

    public function handle(
        TikTokShopApiClient $client,
        StoreAuthorizedTikTokShops $storeShops,
    ): int
    {
        $authorization = TikTokShopAuthorization::query()->latest('id')->first();

        if ($authorization === null) {
            $this->error('No TikTok Shop authorization is stored.');

            return self::FAILURE;
        }

        try {
            $shops = $client->authorizedShops($authorization);
            $storeShops->handle($authorization, $shops);
        } catch (TikTokAuthorizationException|TikTokShopApiException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('TikTok Shop read-only connection succeeded.');
        $this->line('Authorized shops: '.count($shops));

        foreach ($shops as $shop) {
            $this->line(sprintf(
                '- %s (%s)',
                is_string($shop['name'] ?? null) ? $shop['name'] : 'Unnamed shop',
                is_string($shop['region'] ?? null) ? $shop['region'] : 'Unknown region',
            ));
        }

        return self::SUCCESS;
    }
}

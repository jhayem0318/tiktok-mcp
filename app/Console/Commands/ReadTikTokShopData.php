<?php

namespace App\Console\Commands;

use App\Exceptions\TikTokAuthorizationException;
use App\Exceptions\TikTokShopApiException;
use App\Models\TikTokShop;
use App\Services\TikTokShopApiClient;
use Illuminate\Console\Command;

class ReadTikTokShopData extends Command
{
    protected $signature = 'tiktok:shop:data
        {dataset=all : all, analytics, orders, products, finance, returns, or promotions}
        {--days=7 : Number of completed days to query}
        {--limit=20 : Maximum first-page records per dataset}';

    protected $description = 'Read aggregated TikTok Shop operational data without exposing customer details';

    public function handle(TikTokShopApiClient $client): int
    {
        $shop = TikTokShop::query()
            ->whereNotNull('name')
            ->where('name', 'not like', 'SANDBOX\_%')
            ->latest('id')
            ->first()
            ?? TikTokShop::query()->latest('id')->first();

        if ($shop === null) {
            $this->error('No authorized TikTok Shop metadata is stored. Run tiktok:shop:test first.');

            return self::FAILURE;
        }

        $requested = strtolower((string) $this->argument('dataset'));
        $available = ['analytics', 'orders', 'products', 'finance', 'returns', 'promotions'];
        $datasets = $requested === 'all' ? $available : [$requested];

        if (array_diff($datasets, $available) !== []) {
            $this->error('Unknown dataset. Use all, analytics, orders, products, finance, returns, or promotions.');

            return self::INVALID;
        }

        $days = max(1, min(30, (int) $this->option('days')));
        $limit = max(1, min(100, (int) $this->option('limit')));
        $timezone = $shop->region === 'PH' ? 'Asia/Manila' : (string) config('app.timezone', 'UTC');
        $end = now($timezone)->startOfDay();
        $start = $end->copy()->subDays($days);
        $failed = false;

        $this->info("TikTok Shop read-only summary: {$shop->name} ({$shop->region})");
        $this->line("Period: {$start->toDateString()} to {$end->toDateString()} ({$timezone}, end exclusive)");

        foreach ($datasets as $dataset) {
            try {
                $data = match ($dataset) {
                    'analytics' => $client->analytics($shop, $start->toDateString(), $end->toDateString(), $limit),
                    'orders' => $client->orders($shop, $start->getTimestamp(), $end->getTimestamp(), $limit),
                    'products' => $client->products($shop, $limit),
                    'finance' => $client->finance($shop, $start->getTimestamp(), $end->getTimestamp(), $limit),
                    'returns' => $client->returns($shop, $start->getTimestamp(), $end->getTimestamp(), $limit),
                    'promotions' => $client->promotions($shop, $limit),
                };

                $this->line(sprintf('- %s: connected; first-page records=%d', $dataset, $this->recordCount($data)));
            } catch (TikTokAuthorizationException|TikTokShopApiException $exception) {
                $failed = true;
                $this->error("- {$dataset}: {$exception->getMessage()}");
            }
        }

        $this->newLine();
        $this->line('Source: TikTok Shop Open API; currency: LOCAL; customer-level fields suppressed.');

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    /** @param array<string, mixed> $data */
    private function recordCount(array $data): int
    {
        foreach (['orders', 'products', 'statements', 'returns', 'return_orders', 'activities'] as $key) {
            if (is_array($data[$key] ?? null)) {
                return count($data[$key]);
            }
        }

        return $data === [] ? 0 : 1;
    }
}

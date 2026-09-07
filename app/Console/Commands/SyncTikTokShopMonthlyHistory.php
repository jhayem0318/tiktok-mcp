<?php

namespace App\Console\Commands;

use App\Exceptions\TikTokAuthorizationException;
use App\Exceptions\TikTokShopApiException;
use App\Models\TikTokShop;
use App\Services\SyncShopMonthlyHistory;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Throwable;

class SyncTikTokShopMonthlyHistory extends Command
{
    protected $signature = 'tiktok:shop:sync-months
        {--from= : First month to store, in YYYY-MM format; defaults to the current month}
        {--to= : Last month to store, in YYYY-MM format; defaults to the current month}
        {--shop-id= : Authorized TikTok Shop ID; defaults to the latest production Shop}
        {--force : Refetch and recalculate saved snapshots, preserving them if the replacement is incomplete}';

    protected $description = 'Store aggregate monthly TikTok Shop history through the read-only API';

    public function handle(SyncShopMonthlyHistory $history): int
    {
        $shop = $this->shop();

        if ($shop === null) {
            $this->error('No authorized TikTok Shop is stored. Authorize a Shop first.');

            return self::FAILURE;
        }

        $timezone = $shop->region === 'PH' ? 'Asia/Manila' : (string) config('app.timezone', 'UTC');
        $defaultMonth = now($timezone)->startOfMonth()->subMonth()->format('Y-m');
        $from = $this->month((string) ($this->option('from') ?: $defaultMonth), $timezone);
        $to = $this->month((string) ($this->option('to') ?: $defaultMonth), $timezone);

        if ($from === null || $to === null || $from->isAfter($to)) {
            $this->error('Use --from and --to as YYYY-MM, with --from no later than --to.');

            return self::INVALID;
        }

        $current = $from;
        $stored = 0;

        while ($current->lessThanOrEqualTo($to)) {
            $end = $current->addMonth();
            $today = now($timezone)->startOfDay()->toImmutable();

            if ($end->isAfter($today)) {
                $end = $today;
            }

            if ($end->isAfter($current)) {
                try {
                    $metric = $history->handle($shop, $current, $end, (bool) $this->option('force'));
                    $stored++;
                    $this->line(sprintf('%s stored · orders %s · finance %s', $current->format('Y-m'), $metric->orders_complete ? 'complete' : 'incomplete', $metric->finance_available ? 'available' : 'unavailable'));
                } catch (TikTokAuthorizationException|TikTokShopApiException $exception) {
                    $this->error($current->format('Y-m').': '.$exception->getMessage());

                    return self::FAILURE;
                } catch (Throwable $exception) {
                    report($exception);
                    $this->error($current->format('Y-m').': Monthly history could not be stored.');

                    return self::FAILURE;
                }
            }

            $current = $current->addMonth();
        }

        $this->info("Stored {$stored} aggregate monthly history record(s) for {$shop->name}.");

        return self::SUCCESS;
    }

    private function shop(): ?TikTokShop
    {
        $shopId = $this->option('shop-id');

        if (is_string($shopId) && $shopId !== '') {
            return TikTokShop::query()->where('shop_id', $shopId)->first();
        }

        return TikTokShop::query()->where('name', 'not like', 'SANDBOX\_%')->latest('id')->first()
            ?? TikTokShop::query()->latest('id')->first();
    }

    private function month(string $value, string $timezone): ?CarbonImmutable
    {
        try {
            $month = CarbonImmutable::createFromFormat('!Y-m', $value, $timezone);

            return $month->format('Y-m') === $value ? $month : null;
        } catch (Throwable) {
            return null;
        }
    }
}

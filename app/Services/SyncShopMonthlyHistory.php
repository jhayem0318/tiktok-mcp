<?php

namespace App\Services;

use App\Models\ShopMonthlyMetric;
use App\Models\TikTokShop;
use Carbon\CarbonImmutable;
use Throwable;

class SyncShopMonthlyHistory
{
    public function __construct(private readonly TikTokShopMcpTools $tools) {}

    public function handle(TikTokShop $shop, CarbonImmutable $periodStart, CarbonImmutable $periodEnd, bool $force = false): ShopMonthlyMetric
    {
        $saved = ShopMonthlyMetric::query()->where('tik_tok_shop_id', $shop->id)
            ->whereDate('period_start', $periodStart->toDateString())->first();
        if (! $force && $saved && $saved->orders_complete
            && $saved->period_end->toDateString() === $periodEnd->toDateString()
            && $periodEnd->equalTo($periodStart->startOfMonth()->addMonth())
            && $periodEnd->lessThanOrEqualTo(now($periodStart->timezone))) {
            return $saved;
        }

        $orders = $this->tools->callForShop('tiktok_shop_orders', [
            'start_date' => $periodStart->toDateString(),
            'end_date' => $periodEnd->toDateString(),
            'limit' => 100,
            'include_brand_summaries' => true,
        ], $shop);

        $finance = null;

        try {
            $finance = $this->tools->callForShop('tiktok_shop_finance', [
                'start_date' => $periodStart->toDateString(),
                'end_date' => $periodEnd->toDateString(),
                'limit' => 100,
            ], $shop);
        } catch (Throwable $exception) {
            report($exception);
        }

        $analytics = null;

        try {
            $analytics = $this->tools->callForShop('tiktok_shop_analytics', [
                'start_date' => $periodStart->toDateString(),
                'end_date' => $periodEnd->toDateString(),
                'limit' => 100,
            ], $shop);
        } catch (Throwable $exception) {
            report($exception);
        }

        $orderSummary = [
            'date_range' => $orders['date_range'] ?? [],
            'order_value_summary' => data_get($orders, 'data.order_value_summary', []),
            'status_summary' => data_get($orders, 'data.status_summary', []),
            'dashboard_summary' => data_get($orders, 'data.dashboard_summary', []),
            'reconciliation' => $orders['reconciliation'] ?? [],
        ];
        $financeSummary = $finance === null ? null : [
            // Finance periods are intentionally retained independently from order periods.
            'statement_date_range' => $finance['date_range'] ?? [],
            'summary' => data_get($finance, 'data.finance_summary', []),
            'reconciliation' => $finance['reconciliation'] ?? [],
        ];
        $channelSummary = $analytics === null ? null : [
            'analytics_date_range' => $analytics['date_range'] ?? [],
            'channel_breakdown' => data_get($analytics, 'data.performance_summary.channel_breakdown', []),
            'reconciliation' => $analytics['reconciliation'] ?? [],
        ];
        $brandSummaries = data_get($orders, 'data.brand_summaries');
        $brandSummaries = is_array($brandSummaries) ? $brandSummaries : null;

        if ($saved && $force && (! (bool) data_get($orders, 'data.order_value_summary.complete', false)
            || ($saved->finance_available && $finance === null))) {
            throw new \RuntimeException('Replacement data is incomplete; the saved snapshot was preserved.');
        }

        return ShopMonthlyMetric::query()->updateOrCreate([
            'tik_tok_shop_id' => $shop->id,
            'period_start' => $periodStart->toDateString(),
        ], [
            'period_end' => $periodEnd->toDateString(),
            'currency' => data_get($orders, 'data.order_value_summary.currency'),
            'source' => $orders['source'] ?? 'TikTok Shop Open API',
            'order_summary' => $orderSummary,
            'finance_summary' => $financeSummary,
            'channel_summary' => $channelSummary,
            'brand_summaries' => $brandSummaries,
            'orders_complete' => (bool) data_get($orders, 'data.order_value_summary.complete', false),
            'finance_available' => $finance !== null,
            'synced_at' => now(),
        ]);
    }
}

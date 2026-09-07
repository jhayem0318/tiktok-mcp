<?php

namespace App\Services;

use App\Models\ShopMonthlyMetric;
use App\Models\TikTokShop;
use Carbon\CarbonImmutable;
use Throwable;

class SyncShopMonthlyHistory
{
    public function __construct(private readonly TikTokShopMcpTools $tools) {}

    public function handle(TikTokShop $shop, CarbonImmutable $periodStart, CarbonImmutable $periodEnd): ShopMonthlyMetric
    {
        $orders = $this->tools->callForShop('tiktok_shop_orders', [
            'start_date' => $periodStart->toDateString(),
            'end_date' => $periodEnd->toDateString(),
            'limit' => 100,
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

        return ShopMonthlyMetric::query()->updateOrCreate([
            'tik_tok_shop_id' => $shop->id,
            'period_start' => $periodStart->toDateString(),
        ], [
            'period_end' => $periodEnd->toDateString(),
            'currency' => data_get($orders, 'data.order_value_summary.currency'),
            'source' => $orders['source'] ?? 'TikTok Shop Open API',
            'order_summary' => $orderSummary,
            'finance_summary' => $financeSummary,
            'orders_complete' => (bool) data_get($orders, 'data.order_value_summary.complete', false),
            'finance_available' => $finance !== null,
            'synced_at' => now(),
        ]);
    }
}

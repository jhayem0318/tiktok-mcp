<?php

namespace App\Jobs;

use App\Models\ClientDashboardReport;
use App\Services\TikTokShopMcpTools;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class GenerateClientDashboardReport implements ShouldQueue
{
    use Queueable;

    public int $timeout = 300;

    public int $tries = 1;

    public function __construct(public readonly int $reportId) {}

    public function handle(TikTokShopMcpTools $tools): void
    {
        $report = ClientDashboardReport::query()->with('shop')->find($this->reportId);

        if ($report === null || $report->status !== 'pending' || $report->shop === null) {
            return;
        }

        $report->update(['status' => 'running', 'error_message' => null]);

        try {
            $response = $tools->callForShop('tiktok_shop_orders', [
                'start_date' => $report->start_date->toDateString(),
                'end_date' => $report->end_date->toDateString(),
                'limit' => 100,
            ], $report->shop);

            $finance = null;
            $financeUnavailable = null;

            try {
                $finance = $tools->callForShop('tiktok_shop_finance', [
                    'start_date' => $report->start_date->toDateString(),
                    'end_date' => $report->end_date->toDateString(),
                    'limit' => 100,
                ], $report->shop);
            } catch (Throwable $exception) {
                report($exception);
                $financeUnavailable = 'Finance statement data is not available for this Shop and period.';
            }

            $analytics = null;
            $channelPerformanceUnavailable = null;

            try {
                $analytics = $tools->callForShop('tiktok_shop_analytics', [
                    'start_date' => $report->start_date->toDateString(),
                    'end_date' => $report->end_date->toDateString(),
                    'limit' => 100,
                ], $report->shop);
            } catch (Throwable $exception) {
                report($exception);
                $channelPerformanceUnavailable = 'Channel performance data is not available for this Shop and period.';
            }

            $report->update([
                'status' => 'completed',
                'result' => [
                    'source' => $response['source'] ?? 'TikTok Shop Open API',
                    'date_range' => $response['date_range'] ?? [],
                    'attribution' => $response['attribution'] ?? null,
                    'order_value_summary' => data_get($response, 'data.order_value_summary', []),
                    'status_summary' => data_get($response, 'data.status_summary', []),
                    'dashboard_summary' => data_get($response, 'data.dashboard_summary', []),
                    'order_details' => data_get($response, 'data.orders', []),
                    'finance_summary' => data_get($finance, 'data.finance_summary', []),
                    'finance_unavailable' => $financeUnavailable,
                    'channel_performance' => data_get($analytics, 'data.performance_summary.channel_breakdown', []),
                    'channel_performance_unavailable' => $channelPerformanceUnavailable,
                    'reconciliation' => $response['reconciliation'] ?? [],
                ],
                'completed_at' => now(),
            ]);
        } catch (Throwable $exception) {
            report($exception);
            $report->update([
                'status' => 'failed',
                'error_message' => 'TikTok Shop data could not be refreshed. Please try again later.',
                'completed_at' => now(),
            ]);
        }
    }

    public function failed(Throwable $exception): void
    {
        report($exception);

        ClientDashboardReport::query()->whereKey($this->reportId)->whereIn('status', ['pending', 'running'])->update([
            'status' => 'failed',
            'error_message' => 'TikTok Shop data could not be refreshed. Please try again later.',
            'completed_at' => now(),
        ]);
    }
}

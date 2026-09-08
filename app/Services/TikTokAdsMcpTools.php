<?php

namespace App\Services;

use App\Models\TikTokAdsAccount;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class TikTokAdsMcpTools
{
    private const METRICS = [
        'spend', 'impressions', 'clicks', 'conversion',
        'cost_per_conversion', 'conversion_rate', 'complete_payment', 'complete_payment_roas',
    ];

    public function __construct(
        private readonly TikTokAdsMcpClient $adsClient,
        private readonly TikTokAdsTokenManager $tokenManager,
    ) {}

    /** @return list<array<string, mixed>> */
    public function definitions(): array
    {
        return [[
            'name' => 'tiktok_ads_performance',
            'description' => 'Read TikTok Ads-attributed paid campaign performance (spend, impressions, clicks, conversions, ROAS) via the official TikTok for Business MCP server. This is paid-advertising performance only, never seller-owned Shop revenue — never sum with tiktok_shop_* GMV/NMV.',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'advertiser_id' => [
                        'type' => 'string',
                        'description' => 'Required when more than one TikTok Ads account is connected. Use only an advertiser_id from a connected account.',
                    ],
                    'start_date' => [
                        'type' => 'string',
                        'description' => 'Start date in YYYY-MM-DD. Defaults to seven completed days ago.',
                    ],
                    'end_date' => [
                        'type' => 'string',
                        'description' => 'Exclusive end date in YYYY-MM-DD. Defaults to today.',
                    ],
                    'page' => [
                        'type' => 'integer',
                        'minimum' => 1,
                        'default' => 1,
                    ],
                    'page_size' => [
                        'type' => 'integer',
                        'minimum' => 1,
                        'maximum' => 1000,
                        'default' => 20,
                    ],
                ],
                'additionalProperties' => false,
            ],
            'annotations' => [
                'readOnlyHint' => true,
                'destructiveHint' => false,
                'idempotentHint' => true,
                'openWorldHint' => true,
            ],
        ]];
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @param  Collection<int, TikTokAdsAccount>  $accounts
     * @return array<string, mixed>
     */
    public function call(string $name, array $arguments, Collection $accounts): array
    {
        if ($name !== 'tiktok_ads_performance') {
            throw new InvalidArgumentException('Unknown TikTok Ads tool.');
        }

        $account = $this->resolveAccount($arguments, $accounts);
        $authorization = $this->tokenManager->fresh($account->authorization);

        [$start, $end] = $this->dateRange($arguments);
        $page = max(1, (int) ($arguments['page'] ?? 1));
        $pageSize = max(1, min(1000, (int) ($arguments['page_size'] ?? 20)));

        $response = $this->adsClient->callTool('report_integrated_get', [
            'advertiser_id' => $account->advertiser_id,
            'report_type' => 'BASIC',
            'data_level' => 'AUCTION_ADVERTISER',
            'dimensions' => ['stat_time_day'],
            'metrics' => self::METRICS,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'page' => $page,
            'page_size' => $pageSize,
        ], $authorization);

        $rows = (array) data_get($response, 'data.list', []);
        $summary = $this->summarize($rows);

        return [
            'source' => 'TikTok for Business MCP (official)',
            'dataset' => 'performance',
            'advertiser' => ['advertiser_id' => $account->advertiser_id, 'name' => $account->name],
            'date_range' => [
                'start' => $start->toDateString(),
                'end_exclusive' => $end->toDateString(),
            ],
            'currency' => $account->currency,
            'attribution' => 'TikTok Ads-attributed paid performance; not seller-owned Shop revenue.',
            'ads_integration' => [
                'shop_scope' => 'Total seller-owned TikTok Shop performance.',
                'ads_scope' => 'TikTok Ads-attributed performance from the official TikTok for Business MCP.',
                'combination_rule' => 'Compare and reconcile the two scopes; never add Ads-attributed revenue to total Shop revenue.',
                'join_dimensions' => ['date_range', 'timezone', 'currency', 'product_or_sku_when_available'],
            ],
            'data' => ['daily' => $rows, 'summary' => $summary],
        ];
    }

    /** @param  Collection<int, TikTokAdsAccount>  $accounts */
    private function resolveAccount(array $arguments, Collection $accounts): TikTokAdsAccount
    {
        if ($accounts->isEmpty()) {
            throw new InvalidArgumentException('No authorized TikTok Ads account is stored.');
        }

        $requestedAdvertiserId = $arguments['advertiser_id'] ?? null;

        if ($requestedAdvertiserId === null && $accounts->count() === 1) {
            return $accounts->first();
        }

        if (! is_string($requestedAdvertiserId) || $requestedAdvertiserId === '') {
            throw new InvalidArgumentException('advertiser_id is required because more than one TikTok Ads account is connected.');
        }

        $account = $accounts->first(fn (TikTokAdsAccount $account): bool => $account->advertiser_id === $requestedAdvertiserId);

        if ($account === null) {
            throw new InvalidArgumentException('That advertiser_id is not a connected TikTok Ads account.');
        }

        return $account;
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array{CarbonImmutable, CarbonImmutable}
     */
    private function dateRange(array $arguments): array
    {
        $today = CarbonImmutable::now()->startOfDay();
        $start = $this->date($arguments['start_date'] ?? null) ?? $today->subDays(7);
        $end = $this->date($arguments['end_date'] ?? null) ?? $today;

        if ($end->lessThanOrEqualTo($start)) {
            throw new InvalidArgumentException('end_date must be after start_date.');
        }

        return [$start, $end];
    }

    private function date(mixed $value): ?CarbonImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_string($value) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            throw new InvalidArgumentException('Dates must use YYYY-MM-DD.');
        }

        $date = CarbonImmutable::createFromFormat('!Y-m-d', $value);

        if ($date === false || $date->format('Y-m-d') !== $value) {
            throw new InvalidArgumentException('Dates must be valid calendar dates.');
        }

        return $date;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, mixed>
     */
    private function summarize(array $rows): array
    {
        $spend = 0.0;
        $impressions = 0;
        $clicks = 0;
        $conversions = 0.0;
        $completePayment = 0.0;

        foreach ($rows as $row) {
            $metrics = (array) data_get($row, 'metrics', []);
            $spend += (float) ($metrics['spend'] ?? 0);
            $impressions += (int) ($metrics['impressions'] ?? 0);
            $clicks += (int) ($metrics['clicks'] ?? 0);
            $conversions += (float) ($metrics['conversion'] ?? 0);
            $completePayment += (float) ($metrics['complete_payment'] ?? 0);
        }

        return [
            'spend' => round($spend, 2),
            'impressions' => $impressions,
            'clicks' => $clicks,
            'conversions' => $conversions,
            'complete_payment' => round($completePayment, 2),
            'roas' => $spend > 0 ? round($completePayment / $spend, 4) : null,
        ];
    }
}

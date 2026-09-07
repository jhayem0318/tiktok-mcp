<?php

namespace App\Services;

use App\Models\TikTokShop;
use Carbon\CarbonImmutable;
use InvalidArgumentException;

class TikTokShopMcpTools
{
    private const DATASETS = ['analytics', 'orders', 'products', 'finance', 'returns', 'promotions'];

    private const SENSITIVE_KEYS = [
        'access_token',
        'refresh_token',
        'shop_cipher',
        'open_id',
        'buyer',
        'buyer_name',
        'customer_name',
        'recipient_name',
        'contact_name',
        'full_name',
        'first_name',
        'last_name',
        'buyer_email',
        'buyer_message',
        'recipient',
        'recipient_name',
        'shipping_address',
        'address',
        'street',
        'building',
        'unit_number',
        'postal_code',
        'zip_code',
        'latitude',
        'longitude',
        'geo_location',
        'phone',
        'email',
        'tracking_number',
        'order_id',
        'order_number',
        'order_sn',
        'package_id',
        'transaction_id',
        'payment_id',
        'user_id',
    ];

    public function __construct(private readonly TikTokShopApiClient $client) {}

    /** @return list<array<string, mixed>> */
    public function definitions(): array
    {
        return array_map(fn (string $dataset): array => [
            'name' => 'tiktok_shop_'.$dataset,
            'description' => $this->description($dataset),
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'shop_id' => [
                        'type' => 'string',
                        'description' => 'Required when your client access is assigned to more than one Shop. Use only a Shop ID assigned to your access.',
                    ],
                    'start_date' => [
                        'type' => 'string',
                        'description' => 'Start date in YYYY-MM-DD. Defaults to seven completed days ago.',
                    ],
                    'end_date' => [
                        'type' => 'string',
                        'description' => 'Exclusive end date in YYYY-MM-DD. Defaults to today.',
                    ],
                    'limit' => [
                        'type' => 'integer',
                        'minimum' => 1,
                        'maximum' => 100,
                        'default' => 20,
                    ],
                    'page_token' => [
                        'type' => 'string',
                        'description' => 'Continuation token from the prior response. Omit for the first page and complete dataset summary.',
                    ],
                    'seller_center_count' => [
                        'type' => 'number',
                        'minimum' => 0,
                        'description' => 'Optional Seller Center export count for reconciliation.',
                    ],
                    'seller_center_value' => [
                        'type' => 'number',
                        'description' => 'Optional Seller Center export value for reconciliation against the dataset primary financial metric.',
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
        ], self::DATASETS);
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    public function call(string $name, array $arguments): array
    {
        $shop = TikTokShop::query()
            ->whereNotNull('name')
            ->where('name', 'not like', 'SANDBOX\_%')
            ->latest('id')
            ->first()
            ?? TikTokShop::query()->latest('id')->first();

        if ($shop === null) {
            throw new InvalidArgumentException('No authorized TikTok Shop is stored.');
        }

        return $this->callForShop($name, $arguments, $shop);
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    public function callForShop(string $name, array $arguments, TikTokShop $shop): array
    {
        return $this->callForShopWithRedaction($name, $arguments, $shop, false);
    }

    /**
     * Return reviewer evidence for a TikTok sandbox shop. Resource identifiers are
     * retained so TikTok can verify the integration; all customer fields remain
     * redacted. Production shops are intentionally rejected.
     *
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    public function callForReview(string $name, array $arguments, TikTokShop $shop): array
    {
        if (! str_starts_with(strtoupper($shop->name), 'SANDBOX')) {
            throw new InvalidArgumentException('Reviewer evidence is available only for a TikTok sandbox shop.');
        }

        return $this->callForShopWithRedaction($name, $arguments, $shop, true);
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    private function callForShopWithRedaction(
        string $name,
        array $arguments,
        TikTokShop $shop,
        bool $preserveResourceIds,
    ): array {
        $dataset = str_starts_with($name, 'tiktok_shop_') ? substr($name, 12) : '';

        if (! in_array($dataset, self::DATASETS, true)) {
            throw new InvalidArgumentException('Unknown TikTok Shop tool.');
        }

        $timezone = $shop->region === 'PH' ? 'Asia/Manila' : (string) config('app.timezone', 'UTC');
        [$start, $end] = $this->dateRange($arguments, $timezone);
        $limit = max(1, min(100, (int) ($arguments['limit'] ?? 20)));
        $pageToken = is_string($arguments['page_token'] ?? null)
            ? $arguments['page_token']
            : null;

        $data = match ($dataset) {
            'analytics' => $this->client->analytics($shop, $start->toDateString(), $end->toDateString(), $limit, $pageToken),
            'orders' => $this->client->orders($shop, $start->getTimestamp(), $end->getTimestamp(), $limit, $pageToken, $timezone),
            'products' => $this->client->products($shop, $limit, $pageToken),
            'finance' => $this->client->finance($shop, $start->getTimestamp(), $end->getTimestamp(), $limit, $pageToken),
            'returns' => $this->client->returns($shop, $start->getTimestamp(), $end->getTimestamp(), $limit, $pageToken),
            'promotions' => $this->client->promotions($shop, $limit, $pageToken),
        };
        $reconciliation = $this->reconciliation($dataset, $data, $arguments);

        return [
            'source' => 'TikTok Shop Open API',
            'dataset' => $dataset,
            'shop' => ['name' => $shop->name, 'region' => $shop->region],
            'date_range' => [
                'start' => $start->toDateString(),
                'end_exclusive' => $end->toDateString(),
                'timezone' => $timezone,
            ],
            'currency' => 'LOCAL',
            'attribution' => 'Total Shop data; not TikTok Ads-attributed revenue.',
            'comparison_period' => null,
            'reconciliation' => $reconciliation,
            'ads_integration' => [
                'shop_scope' => 'Total seller-owned TikTok Shop performance.',
                'ads_scope' => 'TikTok Ads-attributed performance from the official TikTok for Business MCP.',
                'combination_rule' => 'Compare and reconcile the two scopes; never add Ads-attributed revenue to total Shop revenue.',
                'join_dimensions' => ['date_range', 'timezone', 'currency', 'product_or_sku_when_available'],
            ],
            'data' => $this->redact($data, $dataset, $preserveResourceIds),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    private function reconciliation(string $dataset, array $data, array $arguments): array
    {
        $count = (int) ($data['total_count']
            ?? $data['status_summary']['records_scanned']
            ?? $data['pagination']['records_scanned']
            ?? 0);
        $primaryMetric = match ($dataset) {
            'analytics' => 'gmv',
            'orders' => 'calculated_gmv',
            'finance' => 'settlement_amount',
            'returns' => 'refund_amount',
            default => null,
        };
        $primaryValue = match ($dataset) {
            'analytics' => $data['performance_summary']['gmv'] ?? null,
            'orders' => $data['order_value_summary']['calculated_gmv'] ?? null,
            'finance' => $data['finance_summary']['settlement_amount'] ?? null,
            'returns' => $data['return_summary']['refund_amount'] ?? null,
            default => null,
        };
        $complete = (bool) ($data['status_summary']['complete'] ?? $data['pagination']['complete'] ?? true)
            && ($dataset !== 'orders' || (bool) ($data['order_value_summary']['complete'] ?? false));
        $exportCount = $this->optionalNumber($arguments, 'seller_center_count');
        $exportValue = $this->optionalNumber($arguments, 'seller_center_value');

        return [
            'api_complete' => $complete,
            'api_record_count' => $count,
            'seller_center_count' => $exportCount,
            'count_difference' => $exportCount === null ? null : $count - $exportCount,
            'count_matches' => $exportCount === null ? null : abs($count - $exportCount) < 0.00001,
            'primary_value_metric' => $primaryMetric,
            'api_primary_value' => $primaryValue,
            'seller_center_value' => $exportValue,
            'value_difference' => $exportValue === null || ! is_numeric($primaryValue)
                ? null
                : round((float) $primaryValue - $exportValue, 2),
            'value_matches' => $exportValue === null || ! is_numeric($primaryValue)
                ? null
                : abs((float) $primaryValue - $exportValue) < 0.01,
            'status' => ! $complete
                ? 'API pagination incomplete'
                : (($exportCount === null && $exportValue === null)
                    ? 'API complete; Seller Center export not supplied'
                    : 'Compared with supplied Seller Center values'),
        ];
    }

    /** @param array<string, mixed> $arguments */
    private function optionalNumber(array $arguments, string $key): ?float
    {
        if (! array_key_exists($key, $arguments)) {
            return null;
        }

        if (! is_numeric($arguments[$key])) {
            throw new InvalidArgumentException($key.' must be numeric.');
        }

        return (float) $arguments[$key];
    }

    private function description(string $dataset): string
    {
        return match ($dataset) {
            'analytics' => 'Read seller-owned TikTok Shop product performance and GMV metrics. This is total Shop data, not Ads-attributed revenue.',
            'orders' => 'Read seller-owned TikTok Shop orders, line-item commercial details, exact all-page status totals, and Custom GMV: SUM(line_items.sale_price + line_items.platform_discount). The order_value_summary is complete only when every page and every required line-item price was read. Use next_page_token with page_token to retrieve redacted records. Product/SKU, status, monetary, promotion, and coarse country/region/city fields are retained; customer names, contact details, exact addresses, postal codes, coordinates, payment references, and order identifiers are removed.',
            'products' => 'Read the current TikTok Shop product and SKU catalogue without changing listings.',
            'finance' => 'Read TikTok Shop seller statements, fees, commissions, subsidies, and settlement data.',
            'returns' => 'Read TikTok Shop returns and refunds for commercial aggregation. Customer and order identifiers are removed.',
            'promotions' => 'Read current TikTok Shop promotion activities without creating or changing promotions.',
        };
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array{CarbonImmutable, CarbonImmutable}
     */
    private function dateRange(array $arguments, string $timezone): array
    {
        $today = CarbonImmutable::now($timezone)->startOfDay();
        $start = $this->date($arguments['start_date'] ?? null, $timezone) ?? $today->subDays(7);
        $end = $this->date($arguments['end_date'] ?? null, $timezone) ?? $today;

        if ($end->lessThanOrEqualTo($start)) {
            throw new InvalidArgumentException('end_date must be after start_date.');
        }

        // The monthly archive uses [first day, first day of next month).
        // Permit a complete calendar month, including months with 31 days.
        $isCalendarMonth = $start->equalTo($start->startOfMonth())
            && $end->equalTo($start->addMonth());
        if ($start->diffInDays($end) > 30 && ! $isCalendarMonth) {
            throw new InvalidArgumentException('The maximum date range is 30 days or one complete calendar month.');
        }

        return [$start, $end];
    }

    private function date(mixed $value, string $timezone): ?CarbonImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_string($value) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            throw new InvalidArgumentException('Dates must use YYYY-MM-DD.');
        }

        $date = CarbonImmutable::createFromFormat('!Y-m-d', $value, $timezone);

        if ($date === false || $date->format('Y-m-d') !== $value) {
            throw new InvalidArgumentException('Dates must be valid calendar dates.');
        }

        return $date;
    }

    private function isSensitive(string $key): bool
    {
        $key = strtolower($key);

        foreach (self::SENSITIVE_KEYS as $sensitiveKey) {
            if ($key === $sensitiveKey || str_contains($key, $sensitiveKey)) {
                return true;
            }
        }

        return false;
    }

    private function redact(mixed $value, string $path = '', bool $preserveResourceIds = false): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        $clean = [];

        foreach ($value as $key => $item) {
            $keyPath = $path.'.'.(string) $key;
            $isAggregateLabel = str_contains($path, 'summary.counts');
            $isOrderRecordId = $key === 'id'
                && (str_contains($path, 'orders') || str_contains($path, 'returns'));

            $isReviewResourceId = $preserveResourceIds
                && preg_match('/^(products\.products|orders\.orders)\.\d+$/', $path) === 1
                && in_array($key, ['id', 'product_id', 'order_id'], true);

            if (! $isReviewResourceId && ((! $isAggregateLabel && is_string($key) && $this->isSensitive($key)) || $isOrderRecordId)) {
                continue;
            }

            $clean[$key] = $this->redact($item, $keyPath, $preserveResourceIds);
        }

        return $clean;
    }
}

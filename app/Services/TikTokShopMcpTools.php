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
        'buyer_email',
        'buyer_message',
        'recipient',
        'recipient_name',
        'shipping_address',
        'address',
        'phone',
        'email',
        'tracking_number',
        'order_id',
        'user_id',
    ];

    public function __construct(private readonly TikTokShopApiClient $client)
    {
    }

    /** @return list<array<string, mixed>> */
    public function definitions(): array
    {
        return array_map(fn (string $dataset): array => [
            'name' => 'tiktok_shop_'.$dataset,
            'description' => $this->description($dataset),
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
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
        $dataset = str_starts_with($name, 'tiktok_shop_') ? substr($name, 12) : '';

        if (! in_array($dataset, self::DATASETS, true)) {
            throw new InvalidArgumentException('Unknown TikTok Shop tool.');
        }

        $shop = TikTokShop::query()->latest('id')->first();

        if ($shop === null) {
            throw new InvalidArgumentException('No authorized TikTok Shop is stored.');
        }

        $timezone = $shop->region === 'PH' ? 'Asia/Manila' : (string) config('app.timezone', 'UTC');
        [$start, $end] = $this->dateRange($arguments, $timezone);
        $limit = max(1, min(100, (int) ($arguments['limit'] ?? 20)));

        $data = match ($dataset) {
            'analytics' => $this->client->analytics($shop, $start->toDateString(), $end->toDateString(), $limit),
            'orders' => $this->client->orders($shop, $start->getTimestamp(), $end->getTimestamp(), $limit),
            'products' => $this->client->products($shop, $limit),
            'finance' => $this->client->finance($shop, $start->getTimestamp(), $end->getTimestamp(), $limit),
            'returns' => $this->client->returns($shop, $start->getTimestamp(), $end->getTimestamp(), $limit),
            'promotions' => $this->client->promotions($shop, $limit),
        };

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
            'data' => $this->redact($data, $dataset),
        ];
    }

    private function description(string $dataset): string
    {
        return match ($dataset) {
            'analytics' => 'Read seller-owned TikTok Shop product performance and GMV metrics. This is total Shop data, not Ads-attributed revenue.',
            'orders' => 'Read seller-owned TikTok Shop orders for commercial aggregation. Customer, address, contact, and order identifiers are removed.',
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

        if ($start->diffInDays($end) > 30) {
            throw new InvalidArgumentException('The maximum date range is 30 days.');
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

    private function redact(mixed $value, string $path = ''): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        $clean = [];

        foreach ($value as $key => $item) {
            $keyPath = $path.'.'.(string) $key;
            $isOrderRecordId = $key === 'id'
                && (str_contains($path, 'orders') || str_contains($path, 'returns'));

            if ((is_string($key) && $this->isSensitive($key)) || $isOrderRecordId) {
                continue;
            }

            $clean[$key] = $this->redact($item, $keyPath);
        }

        return $clean;
    }
}

<?php

namespace App\Services;

use App\Exceptions\TikTokShopApiException;
use App\Models\TikTokShop;
use App\Models\TikTokShopAuthorization;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class TikTokShopApiClient
{
    private const AUTHORIZED_SHOPS_PATH = '/authorization/202309/shops';

    private const MAX_PAGES = 250;

    public function __construct(private readonly TikTokShopTokenManager $tokenManager)
    {
    }

    /** @return list<array<string, mixed>> */
    public function authorizedShops(TikTokShopAuthorization $authorization): array
    {
        $shops = $this->request($authorization, 'GET', self::AUTHORIZED_SHOPS_PATH)['shops'] ?? null;

        return is_array($shops) ? array_values(array_filter($shops, 'is_array')) : [];
    }

    /** @return array<string, mixed> */
    public function orders(TikTokShop $shop, int $start, int $end, int $pageSize = 20, ?string $pageToken = null): array
    {
        if ($pageToken !== null && $pageToken !== '') {
            return $this->ordersPage($shop, $start, $end, $pageSize, $pageToken);
        }

        $visibleLimit = max(1, min(100, $pageSize));
        $visibleOrders = [];
        $statusCounts = [];
        $pageToken = '';
        $seenTokens = [];
        $pagesFetched = 0;
        $recordsScanned = 0;
        $reportedTotal = null;
        $requestId = null;
        $complete = false;
        $continuationToken = '';
        $orderValueSummary = [
            'orders_scanned' => 0,
            'line_items_scanned' => 0,
            'line_items_with_complete_pricing' => 0,
            'line_items_missing_pricing' => 0,
            'sku_subtotal_after_discount' => 0.0,
            'sku_platform_discount' => 0.0,
            'calculated_gmv' => 0.0,
            'currencies' => [],
        ];
        $dashboardSummary = [
            'completed_orders' => 0, 'canceled_orders' => 0, 'non_canceled_orders' => 0,
            'units' => 0, 'canceled_units' => 0, 'canceled_value' => 0.0,
            'platform_subsidy' => 0.0, 'top_products' => [], 'top_cancel_skus' => [],
            'payment_methods' => [], 'locations' => [], 'cancel_reasons' => [],
        ];

        while ($pagesFetched < self::MAX_PAGES) {
            $query = ['page_size' => 100];

            if ($pageToken !== '') {
                $query['page_token'] = $pageToken;
            }

            $page = $this->shopRequest($shop, 'POST', '/order/202309/orders/search', $query, [
                'create_time_ge' => $start,
                'create_time_lt' => $end,
            ]);
            $pagesFetched++;

            if (is_int($page['total_count'] ?? null)) {
                $reportedTotal = $page['total_count'];
            }

            if (is_string($page['_request_id'] ?? null)) {
                $requestId = $page['_request_id'];
            }

            $orders = is_array($page['orders'] ?? null) ? $page['orders'] : [];

            foreach ($orders as $order) {
                if (! is_array($order)) {
                    continue;
                }

                $recordsScanned++;
                $orderValueSummary['orders_scanned']++;
                $this->addOrderLineItemValues($order, $orderValueSummary);
                $status = is_string($order['status'] ?? null) && $order['status'] !== ''
                    ? strtoupper($order['status'])
                    : 'UNKNOWN';
                $statusCounts[$status] = ($statusCounts[$status] ?? 0) + 1;
                $this->addDashboardOrder($order, $status, $dashboardSummary);

                if (count($visibleOrders) < $visibleLimit) {
                    $visibleOrders[] = $order;
                }
            }

            $nextToken = is_string($page['next_page_token'] ?? null) ? $page['next_page_token'] : '';

            if ($pagesFetched === 1) {
                $continuationToken = $nextToken;
            }

            if ($nextToken === '') {
                $complete = true;
                break;
            }

            if (isset($seenTokens[$nextToken])) {
                break;
            }

            $seenTokens[$nextToken] = true;
            $pageToken = $nextToken;
        }

        ksort($statusCounts);
        $reportedTotalMatches = $reportedTotal === null || $reportedTotal === $recordsScanned;

        $result = [
            'orders' => $visibleOrders,
            'total_count' => $reportedTotal ?? $recordsScanned,
            'status_summary' => [
                'counts' => $statusCounts,
                'delivered_or_completed' => ($statusCounts['DELIVERED'] ?? 0) + ($statusCounts['COMPLETED'] ?? 0),
                'records_scanned' => $recordsScanned,
                'pages_fetched' => $pagesFetched,
                'complete' => $complete,
                'reported_total_matches' => $reportedTotalMatches,
            ],
            'order_value_summary' => [
                'formula' => 'SUM(line_items.sale_price + line_items.platform_discount)',
                'orders_scanned' => $orderValueSummary['orders_scanned'],
                'line_items_scanned' => $orderValueSummary['line_items_scanned'],
                'line_items_with_complete_pricing' => $orderValueSummary['line_items_with_complete_pricing'],
                'line_items_missing_pricing' => $orderValueSummary['line_items_missing_pricing'],
                'reported_total_matches' => $reportedTotalMatches,
                'currency' => count($orderValueSummary['currencies']) === 1
                    ? array_key_first($orderValueSummary['currencies'])
                    : 'LOCAL',
                'sku_subtotal_after_discount' => round($orderValueSummary['sku_subtotal_after_discount'], 2),
                'sku_platform_discount' => round($orderValueSummary['sku_platform_discount'], 2),
                'calculated_gmv' => round($orderValueSummary['calculated_gmv'], 2),
                'complete' => $complete
                    && $reportedTotalMatches
                    && $orderValueSummary['line_items_missing_pricing'] === 0,
            ],
            'dashboard_summary' => $this->dashboardSummary($dashboardSummary, $orderValueSummary),
        ];

        if ($continuationToken !== '') {
            $result['next_page_token'] = $continuationToken;
        }

        if ($requestId !== null) {
            $result['_request_id'] = $requestId;
        }

        return $result;
    }

    /** @return array<string, mixed> */
    private function ordersPage(TikTokShop $shop, int $start, int $end, int $pageSize, string $pageToken): array
    {
        return $this->shopRequest($shop, 'POST', '/order/202309/orders/search', [
            'page_size' => max(1, min(100, $pageSize)),
            'page_token' => $pageToken,
        ], [
            'create_time_ge' => $start,
            'create_time_lt' => $end,
        ]);
    }

    /**
     * @param  array<string, mixed>  $order
     * @param  array<string, int|float|array<string, true>>  $summary
     */
    private function addOrderLineItemValues(array $order, array &$summary): void
    {
        $lineItems = is_array($order['line_items'] ?? null) ? $order['line_items'] : [];

        foreach ($lineItems as $lineItem) {
            if (! is_array($lineItem)) {
                continue;
            }

            $summary['line_items_scanned']++;
            $salePrice = $lineItem['sale_price'] ?? null;
            $platformDiscount = $lineItem['platform_discount'] ?? null;

            if (! is_numeric($salePrice) || ! is_numeric($platformDiscount)) {
                $summary['line_items_missing_pricing']++;

                continue;
            }

            $summary['line_items_with_complete_pricing']++;
            $summary['sku_subtotal_after_discount'] += (float) $salePrice;
            $summary['sku_platform_discount'] += (float) $platformDiscount;
            $summary['calculated_gmv'] += (float) $salePrice + (float) $platformDiscount;

            if (is_string($lineItem['currency'] ?? null) && $lineItem['currency'] !== '') {
                $summary['currencies'][$lineItem['currency']] = true;
            }
        }
    }

    /**
     * Aggregate only commercial and coarse-geography fields. Raw customer and
     * delivery data is never retained in this summary.
     *
     * @param array<string, mixed> $order
     * @param array<string, mixed> $summary
     */
    private function addDashboardOrder(array $order, string $status, array &$summary): void
    {
        $isCanceled = in_array($status, ['CANCELLED', 'CANCELED'], true);
        $isCompleted = in_array($status, ['DELIVERED', 'COMPLETED'], true);
        $summary[$isCanceled ? 'canceled_orders' : 'non_canceled_orders']++;
        if ($isCompleted) {
            $summary['completed_orders']++;
        }

        $paymentMethod = $this->firstPresentString($order, ['payment_method_name', 'payment_method', 'payment_type']);
        if ($paymentMethod !== null) {
            $summary['payment_methods'][$paymentMethod] = ($summary['payment_methods'][$paymentMethod] ?? 0) + 1;
        }

        $reason = $this->firstPresentString($order, ['cancel_reason', 'cancellation_reason']);
        if ($isCanceled && $reason !== null) {
            $summary['cancel_reasons'][$reason] = ($summary['cancel_reasons'][$reason] ?? 0) + 1;
        }

        $address = $this->firstPresentArray($order, ['shipping_address', 'recipient_address', 'address']);
        $location = $address === null ? null : $this->firstPresentString($address, ['city', 'district', 'state', 'province', 'region', 'country']);
        if ($location !== null) {
            $summary['locations'][$location] = ($summary['locations'][$location] ?? 0) + 1;
        }

        foreach (is_array($order['line_items'] ?? null) ? $order['line_items'] : [] as $lineItem) {
            if (! is_array($lineItem)) {
                continue;
            }
            $quantity = $this->firstPresentNumber($lineItem, ['quantity', 'sku_quantity', 'product_quantity']) ?? 1;
            $salePrice = $this->firstPresentNumber($lineItem, ['sale_price']) ?? 0.0;
            $platformDiscount = $this->firstPresentNumber($lineItem, ['platform_discount']) ?? 0.0;
            $value = $salePrice + $platformDiscount;
            if (! $isCanceled) {
                $summary['platform_subsidy'] += $platformDiscount;
            }
            $summary[$isCanceled ? 'canceled_units' : 'units'] += $quantity;
            if ($isCanceled) {
                $summary['canceled_value'] += $value;
            }

            $sku = $this->firstPresentString($lineItem, ['seller_sku', 'sku_id', 'sku_name', 'product_id']) ?? 'Unspecified SKU';
            $name = $this->firstPresentString($lineItem, ['product_name', 'product_title', 'sku_name']) ?? $sku;
            $bucket = $isCanceled ? 'top_cancel_skus' : 'top_products';
            $current = $summary[$bucket][$sku] ?? ['sku' => $sku, 'name' => $name, 'value' => 0.0, 'units' => 0];
            $current['value'] += $value;
            $current['units'] += $quantity;
            $summary[$bucket][$sku] = $current;
        }
    }

    /** @param array<string, mixed> $summary @param array<string, mixed> $values @return array<string, mixed> */
    private function dashboardSummary(array $summary, array $values): array
    {
        $gmv = (float) $values['calculated_gmv'];
        $nonCanceled = (int) $summary['non_canceled_orders'];
        $sort = static function (array $items): array {
            usort($items, static fn (array $left, array $right): int => $right['value'] <=> $left['value']);
            return array_slice($items, 0, 20);
        };
        arsort($summary['payment_methods']); arsort($summary['locations']); arsort($summary['cancel_reasons']);

        return [
            'nmv' => round($gmv - (float) $summary['canceled_value'], 2),
            'canceled_value' => round((float) $summary['canceled_value'], 2),
            'cancel_rate_by_value' => $gmv > 0 ? round(((float) $summary['canceled_value'] / $gmv) * 100, 2) : 0.0,
            'aov' => $nonCanceled > 0 ? round(($gmv - (float) $summary['canceled_value']) / $nonCanceled, 2) : 0.0,
            'completed_orders' => $summary['completed_orders'], 'canceled_orders' => $summary['canceled_orders'],
            'non_canceled_orders' => $nonCanceled, 'units' => $summary['units'],
            'platform_subsidy' => round((float) $summary['platform_subsidy'], 2),
            'top_products' => $sort(array_values($summary['top_products'])),
            'top_cancel_skus' => $sort(array_values($summary['top_cancel_skus'])),
            'payment_methods' => array_map(static fn (string $name, int $orders): array => ['name' => $name, 'orders' => $orders], array_keys($summary['payment_methods']), $summary['payment_methods']),
            'locations' => array_map(static fn (string $name, int $orders): array => ['name' => $name, 'orders' => $orders], array_keys($summary['locations']), $summary['locations']),
            'cancel_reasons' => array_map(static fn (string $name, int $orders): array => ['name' => $name, 'orders' => $orders], array_keys($summary['cancel_reasons']), $summary['cancel_reasons']),
        ];
    }

    /** @param array<string, mixed> $data @param list<string> $keys */
    private function firstPresentString(array $data, array $keys): ?string
    {
        foreach ($keys as $key) { if (is_string($data[$key] ?? null) && $data[$key] !== '') { return $data[$key]; } }
        return null;
    }

    /** @param array<string, mixed> $data @param list<string> $keys */
    private function firstPresentNumber(array $data, array $keys): ?float
    {
        foreach ($keys as $key) { if (is_numeric($data[$key] ?? null)) { return (float) $data[$key]; } }
        return null;
    }

    /** @param array<string, mixed> $data @param list<string> $keys */
    private function firstPresentArray(array $data, array $keys): ?array
    {
        foreach ($keys as $key) { if (is_array($data[$key] ?? null)) { return $data[$key]; } }
        return null;
    }

    /** @return array<string, mixed> */
    public function products(TikTokShop $shop, int $pageSize = 20, ?string $pageToken = null): array
    {
        if ($pageToken !== null && $pageToken !== '') {
            return $this->collectionPage($shop, 'POST', '/product/202502/products/search', 'products', $pageSize, $pageToken, [], ['status' => 'ALL']);
        }

        [$result, $products] = $this->paginatedCollection(
            $shop, 'POST', '/product/202502/products/search', 'products', $pageSize,
            [], ['status' => 'ALL'],
        );
        $result['catalog_summary'] = [
            'counts_by_status' => $this->countBy($products, 'status'),
            'product_records' => count($products),
        ];

        return $result;
    }

    /** @return array<string, mixed> */
    public function returns(TikTokShop $shop, int $start, int $end, int $pageSize = 20, ?string $pageToken = null): array
    {
        if ($pageToken !== null && $pageToken !== '') {
            return $this->collectionPage($shop, 'POST', '/return_refund/202309/returns/search', 'return_orders', $pageSize, $pageToken, [], ['create_time_ge' => $start, 'create_time_lt' => $end], false, 50);
        }

        [$result, $returns] = $this->paginatedCollection(
            $shop, 'POST', '/return_refund/202309/returns/search', 'return_orders', $pageSize,
            [], ['create_time_ge' => $start, 'create_time_lt' => $end], false, 50,
        );
        $result['return_summary'] = [
            'counts_by_status' => $this->countBy($returns, 'return_status'),
            'return_records' => count($returns),
            'currency' => $this->nestedString($returns, ['refund_amount', 'currency']) ?? 'LOCAL',
            'refund_amount' => $this->sumMoney($returns, 'refund_amount'),
        ];

        return $result;
    }

    /** @return array<string, mixed> */
    public function promotions(TikTokShop $shop, int $pageSize = 20, ?string $pageToken = null): array
    {
        if ($pageToken !== null && $pageToken !== '') {
            return $this->collectionPage($shop, 'POST', '/promotion/202309/activities/search', 'activities', $pageSize, $pageToken, [], [], true);
        }

        [$result, $activities] = $this->paginatedCollection(
            $shop, 'POST', '/promotion/202309/activities/search', 'activities', $pageSize,
            [], [], true,
        );
        $result['promotion_summary'] = [
            'counts_by_status' => $this->countBy($activities, 'status'),
            'counts_by_type' => $this->countBy($activities, 'activity_type'),
            'activity_records' => count($activities),
        ];

        return $result;
    }

    /** @return array<string, mixed> */
    public function finance(TikTokShop $shop, int $start, int $end, int $pageSize = 20, ?string $pageToken = null): array
    {
        $query = [
            'statement_time_ge' => $start,
            'statement_time_lt' => $end,
            'sort_field' => 'statement_time',
            'sort_order' => 'DESC',
        ];

        if ($pageToken !== null && $pageToken !== '') {
            return $this->collectionPage($shop, 'GET', '/finance/202309/statements', 'statements', $pageSize, $pageToken, $query);
        }

        [$result, $statements] = $this->paginatedCollection(
            $shop, 'GET', '/finance/202309/statements', 'statements', $pageSize,
            $query,
        );
        $result['finance_summary'] = [
            'statement_records' => count($statements),
            'currency' => $this->firstString($statements, 'currency') ?? 'LOCAL',
            'revenue_amount' => $this->sumNumeric($statements, 'revenue_amount'),
            'net_sales_amount' => $this->sumNumeric($statements, 'net_sales_amount'),
            'fee_amount' => $this->sumNumeric($statements, 'fee_amount'),
            'shipping_cost_amount' => $this->sumNumeric($statements, 'shipping_cost_amount'),
            'adjustment_amount' => $this->sumNumeric($statements, 'adjustment_amount'),
            'settlement_amount' => $this->sumNumeric($statements, 'settlement_amount'),
        ];

        return $result;
    }

    /** @return array<string, mixed> */
    public function analytics(TikTokShop $shop, string $startDate, string $endDate, int $pageSize = 20, ?string $pageToken = null): array
    {
        $query = [
            'start_date_ge' => $startDate,
            'end_date_lt' => $endDate,
            'currency' => 'LOCAL',
            'product_status_filter' => 'ALL',
            'sort_field' => 'gmv',
            'sort_order' => 'DESC',
        ];

        if ($pageToken !== null && $pageToken !== '') {
            return $this->collectionPage($shop, 'GET', '/analytics/202605/shop_products/performance', 'products', $pageSize, $pageToken, $query);
        }

        [$result, $products] = $this->paginatedCollection(
            $shop, 'GET', '/analytics/202605/shop_products/performance', 'products', $pageSize,
            $query,
        );
        $result['performance_summary'] = [
            'product_records' => count($products),
            'currency' => $this->nestedString($products, ['total_performance', 'gmv', 'currency']) ?? 'LOCAL',
            'gmv' => $this->sumNestedMoney($products, ['total_performance', 'gmv', 'amount']),
            'orders' => $this->sumNestedNumeric($products, ['total_performance', 'orders']),
            'items_sold' => $this->sumNestedNumeric($products, ['total_performance', 'items_sold']),
            'refund_amount' => $this->sumNestedMoney($products, ['total_performance', 'refunds', 'amount']),
            'refunded_items' => $this->sumNestedNumeric($products, ['total_performance', 'refunded_items']),
        ];

        return $result;
    }

    /**
     * @param array<string, int|string> $query
     * @param array<string, mixed> $body
     * @return array{array<string, mixed>, list<array<string, mixed>>}
     */
    private function paginatedCollection(
        TikTokShop $shop,
        string $method,
        string $path,
        string $collectionKey,
        int $visibleLimit,
        array $query = [],
        array $body = [],
        bool $paginationInBody = false,
        int $apiPageSize = 100,
    ): array {
        $visibleLimit = max(1, min(100, $visibleLimit));
        $records = [];
        $firstPageMetadata = [];
        $pageToken = '';
        $seenTokens = [];
        $pagesFetched = 0;
        $requestId = null;
        $reportedTotal = null;
        $complete = false;
        $continuationToken = '';

        while ($pagesFetched < self::MAX_PAGES) {
            $pageQuery = $query;
            $pageBody = $body;
            $pagination = ['page_size' => $apiPageSize];

            if ($paginationInBody) {
                $pagination['page_token'] = $pageToken;
            } elseif ($pageToken !== '') {
                $pagination['page_token'] = $pageToken;
            }

            if ($paginationInBody) {
                $pageBody = [...$pageBody, ...$pagination];
            } else {
                $pageQuery = [...$pageQuery, ...$pagination];
            }

            $page = $this->shopRequest($shop, $method, $path, $pageQuery, $pageBody);
            $pagesFetched++;

            if ($pagesFetched === 1) {
                $firstPageMetadata = $page;
                unset($firstPageMetadata[$collectionKey], $firstPageMetadata['next_page_token'], $firstPageMetadata['_request_id']);
            }

            if (is_int($page['total_count'] ?? null)) {
                $reportedTotal = $page['total_count'];
            }

            if (is_string($page['_request_id'] ?? null)) {
                $requestId = $page['_request_id'];
            }

            foreach (is_array($page[$collectionKey] ?? null) ? $page[$collectionKey] : [] as $record) {
                if (is_array($record)) {
                    $records[] = $record;
                }
            }

            $nextToken = is_string($page['next_page_token'] ?? null) ? $page['next_page_token'] : '';

            if ($pagesFetched === 1) {
                $continuationToken = $nextToken;
            }

            if ($nextToken === '') {
                $complete = true;
                break;
            }

            if (isset($seenTokens[$nextToken])) {
                break;
            }

            $seenTokens[$nextToken] = true;
            $pageToken = $nextToken;
        }

        $result = [
            ...$firstPageMetadata,
            $collectionKey => array_slice($records, 0, $visibleLimit),
            'total_count' => $reportedTotal ?? count($records),
            'pagination' => [
                'records_scanned' => count($records),
                'pages_fetched' => $pagesFetched,
                'complete' => $complete,
                'reported_total_matches' => $reportedTotal === null || $reportedTotal === count($records),
            ],
        ];

        if ($continuationToken !== '') {
            $result['next_page_token'] = $continuationToken;
        }

        if ($requestId !== null) {
            $result['_request_id'] = $requestId;
        }

        return [$result, $records];
    }

    /**
     * @param array<string, int|string> $query
     * @param array<string, mixed> $body
     * @return array<string, mixed>
     */
    private function collectionPage(
        TikTokShop $shop,
        string $method,
        string $path,
        string $collectionKey,
        int $pageSize,
        string $pageToken,
        array $query = [],
        array $body = [],
        bool $paginationInBody = false,
        int $apiPageSize = 100,
    ): array {
        $pagination = ['page_size' => min($apiPageSize, max(1, min(100, $pageSize))), 'page_token' => $pageToken];
        $page = $this->shopRequest(
            $shop,
            $method,
            $path,
            $paginationInBody ? $query : [...$query, ...$pagination],
            $paginationInBody ? [...$body, ...$pagination] : $body,
        );
        $records = is_array($page[$collectionKey] ?? null) ? $page[$collectionKey] : [];

        $page['pagination'] = [
            'records_scanned' => count($records),
            'pages_fetched' => 1,
            'complete' => false,
            'page_only' => true,
        ];

        return $page;
    }

    /** @param list<array<string, mixed>> $records */
    private function countBy(array $records, string $key): array
    {
        $counts = [];
        foreach ($records as $record) {
            $value = is_string($record[$key] ?? null) && $record[$key] !== ''
                ? strtoupper($record[$key])
                : 'UNKNOWN';
            $counts[$value] = ($counts[$value] ?? 0) + 1;
        }
        ksort($counts);

        return $counts;
    }

    /** @param list<array<string, mixed>> $records */
    private function sumNumeric(array $records, string $key): float
    {
        return array_reduce($records, fn (float $sum, array $record): float =>
            $sum + (is_numeric($record[$key] ?? null) ? (float) $record[$key] : 0.0), 0.0);
    }

    /** @param list<array<string, mixed>> $records */
    private function sumMoney(array $records, string $key): float
    {
        $sum = 0.0;
        foreach ($records as $record) {
            $value = $record[$key] ?? null;
            if (is_array($value)) {
                $value = $value['amount'] ?? $value['refund_total'] ?? null;
            }
            $sum += is_numeric($value) ? (float) $value : 0.0;
        }

        return round($sum, 2);
    }

    /** @param list<array<string, mixed>> $records */
    private function sumNestedMoney(array $records, array $path): float
    {
        return round($this->sumNestedNumeric($records, $path), 2);
    }

    /** @param list<array<string, mixed>> $records */
    private function sumNestedNumeric(array $records, array $path): float
    {
        $sum = 0.0;
        foreach ($records as $record) {
            $value = $record;
            foreach ($path as $key) {
                $value = is_array($value) ? ($value[$key] ?? null) : null;
            }
            $sum += is_numeric($value) ? (float) $value : 0.0;
        }

        return $sum;
    }

    /** @param list<array<string, mixed>> $records */
    private function firstString(array $records, string $key): ?string
    {
        foreach ($records as $record) {
            if (is_string($record[$key] ?? null) && $record[$key] !== '') {
                return $record[$key];
            }
        }

        return null;
    }

    /** @param list<array<string, mixed>> $records */
    private function nestedString(array $records, array $path): ?string
    {
        foreach ($records as $record) {
            $value = $record;
            foreach ($path as $key) {
                $value = is_array($value) ? ($value[$key] ?? null) : null;
            }
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    /**
     * @param  array<string, int|string>  $query
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    private function shopRequest(TikTokShop $shop, string $method, string $path, array $query = [], array $body = []): array
    {
        $authorization = TikTokShopAuthorization::query()->findOrFail($shop->tik_tok_shop_authorization_id);

        return $this->request($authorization, $method, $path, [
            ...$query,
            'shop_cipher' => $shop->shop_cipher,
        ], $body);
    }

    /**
     * @param  array<string, int|string>  $query
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    private function request(TikTokShopAuthorization $authorization, string $method, string $path, array $query = [], array $body = []): array
    {
        $authorization = $this->tokenManager->fresh($authorization);
        $appKey = config('services.tiktok.app_key');
        $appSecret = config('services.tiktok.app_secret');
        $apiUrl = config('services.tiktok.api_url');

        if (! is_string($appKey) || $appKey === '' || ! is_string($appSecret) || $appSecret === ''
            || ! is_string($apiUrl) || $apiUrl === '') {
            throw new TikTokShopApiException('TikTok Shop API is not configured.');
        }

        $query = [...$query, 'app_key' => $appKey, 'timestamp' => now()->getTimestamp()];
        $jsonBody = $body === [] ? '' : json_encode($body, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        $query['sign'] = $this->sign($path, $query, $appSecret, $jsonBody);
        $request = Http::acceptJson()->withHeaders([
            'x-tts-access-token' => $authorization->access_token,
        ])->connectTimeout(5)->timeout(20);

        try {
            $response = $method === 'POST'
                ? $request->withBody($jsonBody, 'application/json')->post(rtrim($apiUrl, '/').$path.'?'.http_build_query($query))
                : $request->get(rtrim($apiUrl, '/').$path, $query);
            $response->throw();
        } catch (ConnectionException|RequestException) {
            throw new TikTokShopApiException('TikTok Shop API request failed.');
        }

        $payload = $response->json();
        $data = is_array($payload) ? ($payload['data'] ?? null) : null;

        if (! is_array($payload) || ($payload['code'] ?? null) !== 0 || ! is_array($data)) {
            $message = is_array($payload) && is_string($payload['message'] ?? null) ? $payload['message'] : 'request rejected';
            throw new TikTokShopApiException('TikTok Shop API rejected the request: '.$message);
        }

        if (is_string($payload['request_id'] ?? null) && $payload['request_id'] !== '') {
            $data['_request_id'] = $payload['request_id'];
        }

        return $data;
    }

    /** @param array<string, int|string> $query */
    private function sign(string $path, array $query, string $appSecret, string $body = ''): string
    {
        unset($query['access_token'], $query['sign']);
        ksort($query);
        $parameters = '';

        foreach ($query as $key => $value) {
            $parameters .= $key.$value;
        }

        return hash_hmac('sha256', $appSecret.$path.$parameters.$body.$appSecret, $appSecret);
    }
}

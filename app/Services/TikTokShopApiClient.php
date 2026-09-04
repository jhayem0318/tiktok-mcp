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

    private const MAX_ORDER_PAGES = 250;

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
    public function orders(TikTokShop $shop, int $start, int $end, int $pageSize = 20): array
    {
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

        while ($pagesFetched < self::MAX_ORDER_PAGES) {
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
                $status = is_string($order['status'] ?? null) && $order['status'] !== ''
                    ? strtoupper($order['status'])
                    : 'UNKNOWN';
                $statusCounts[$status] = ($statusCounts[$status] ?? 0) + 1;

                if (count($visibleOrders) < $visibleLimit) {
                    $visibleOrders[] = $order;
                }
            }

            $nextToken = is_string($page['next_page_token'] ?? null) ? $page['next_page_token'] : '';

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

        $result = [
            'orders' => $visibleOrders,
            'total_count' => $reportedTotal ?? $recordsScanned,
            'status_summary' => [
                'counts' => $statusCounts,
                'delivered_or_completed' => $statusCounts['DELIVERED'] ?? 0,
                'records_scanned' => $recordsScanned,
                'pages_fetched' => $pagesFetched,
                'complete' => $complete,
            ],
        ];

        if (! $complete && $pageToken !== '') {
            $result['next_page_token'] = $pageToken;
        }

        if ($requestId !== null) {
            $result['_request_id'] = $requestId;
        }

        return $result;
    }

    /** @return array<string, mixed> */
    public function products(TikTokShop $shop, int $pageSize = 20): array
    {
        return $this->shopRequest($shop, 'POST', '/product/202502/products/search', ['page_size' => $pageSize], [
            'status' => 'ALL',
        ]);
    }

    /** @return array<string, mixed> */
    public function returns(TikTokShop $shop, int $start, int $end, int $pageSize = 20): array
    {
        return $this->shopRequest($shop, 'POST', '/return_refund/202309/returns/search', ['page_size' => $pageSize], [
            'create_time_ge' => $start,
            'create_time_lt' => $end,
        ]);
    }

    /** @return array<string, mixed> */
    public function promotions(TikTokShop $shop, int $pageSize = 20): array
    {
        return $this->shopRequest($shop, 'POST', '/promotion/202309/activities/search', [], [
            'page_size' => $pageSize,
            'page_token' => '',
        ]);
    }

    /** @return array<string, mixed> */
    public function finance(TikTokShop $shop, int $start, int $end, int $pageSize = 20): array
    {
        return $this->shopRequest($shop, 'GET', '/finance/202309/statements', [
            'statement_time_ge' => $start,
            'statement_time_lt' => $end,
            'page_size' => $pageSize,
            'sort_field' => 'statement_time',
            'sort_order' => 'DESC',
        ]);
    }

    /** @return array<string, mixed> */
    public function analytics(TikTokShop $shop, string $startDate, string $endDate, int $pageSize = 20): array
    {
        return $this->shopRequest($shop, 'GET', '/analytics/202605/shop_products/performance', [
            'start_date_ge' => $startDate,
            'end_date_lt' => $endDate,
            'currency' => 'LOCAL',
            'product_status_filter' => 'ALL',
            'page_size' => $pageSize,
            'sort_field' => 'gmv',
            'sort_order' => 'DESC',
        ]);
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

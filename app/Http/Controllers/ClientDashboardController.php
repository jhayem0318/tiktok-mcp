<?php

namespace App\Http\Controllers;

use App\Exceptions\TikTokAuthorizationException;
use App\Jobs\GenerateClientDashboardReport;
use App\Models\ClientDashboardReport;
use App\Models\ShopMonthlyMetric;
use App\Models\TikTokAdsAccount;
use App\Models\TikTokShop;
use App\Models\User;
use App\Services\TikTokAdsMcpTools;
use App\Services\TikTokShopApiClient;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ClientDashboardController extends Controller
{
    public function __construct(
        private readonly TikTokShopApiClient $apiClient,
        private readonly TikTokAdsMcpTools $adsTools,
    ) {}

    public function login(Request $request): View|RedirectResponse
    {
        return $this->client($request) === null ? view('client-dashboard-login') : redirect()->route('client.dashboard');
    }

    public function authenticate(Request $request): RedirectResponse
    {
        $validated = $request->validate(['username' => ['required', 'string'], 'password' => ['required', 'string']]);
        $client = User::query()->where('username', $validated['username'])->where('is_admin', false)->first();

        if ($client === null || ! $client->hasActiveClientAccess() || ! Hash::check($validated['password'], $client->password)) {
            return back()->withErrors(['username' => 'The username, password, or client access is invalid.'])->onlyInput('username');
        }

        $request->session()->regenerate();
        $request->session()->put('client_dashboard_user_id', $client->id);

        return redirect()->route($client->must_change_password ? 'client.password.edit' : 'client.dashboard');
    }

    public function editPassword(Request $request): View|RedirectResponse
    {
        $client = $this->requireClient($request);

        return view('client-password', ['mustChangePassword' => $client->must_change_password]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $client = $this->requireClient($request);
        $validated = $request->validate([
            'current_password' => [$client->must_change_password ? 'nullable' : 'required', 'string'],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);
        if (! $client->must_change_password && ! Hash::check((string) $validated['current_password'], $client->password)) {
            return back()->withErrors(['current_password' => 'Your current password is incorrect.']);
        }
        $client->update(['password' => Hash::make($validated['password']), 'must_change_password' => false]);

        return redirect()->route('client.dashboard');
    }

    public function dashboard(Request $request): View|RedirectResponse
    {
        $client = $this->requireClient($request);
        if ($client->must_change_password) {
            return redirect()->route('client.password.edit');
        }

        $shops = $this->assignedShops($client);
        $validated = $request->validate([
            'shop_id' => ['nullable', 'string'],
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'brands' => ['nullable', 'array'],
            'brands.*' => ['string'],
        ]);
        $shop = $shops->firstWhere('shop_id', $validated['shop_id'] ?? null) ?? $shops->first();
        $startDate = $validated['start_date'] ?? now('Asia/Manila')->subDays(7)->toDateString();
        $endDate = $this->normalizeEndDate($startDate, $validated['end_date'] ?? now('Asia/Manila')->toDateString());
        $selectedBrands = $this->normalizeBrands($validated['brands'] ?? []);
        $brandsKey = $selectedBrands === [] ? null : implode(',', $selectedBrands);
        $report = $shop === null ? null : ClientDashboardReport::query()
            ->where('user_id', $client->id)->where('tik_tok_shop_id', $shop->id)
            ->whereDate('start_date', $startDate)->whereDate('end_date', $endDate)
            ->where('brands', $brandsKey)->latest()->first();
        $monthlyHistory = $shop === null ? collect() : ShopMonthlyMetric::query()
            ->where('tik_tok_shop_id', $shop->id)
            ->orderBy('period_start')
            ->get();
        $snapshot = $monthlyHistory->first(fn ($month) => $month->orders_complete
            && $month->period_start->toDateString() === $startDate
            && $month->period_end->toDateString() === $endDate);

        if ($snapshot && $brandsKey === null) {
            // Whole-shop: the saved snapshot already covers this exactly.
            $report = new ClientDashboardReport([
                'status' => 'completed',
                'result' => array_merge($snapshot->order_summary, [
                    'source' => 'Saved TikTok Shop API snapshot · '.$snapshot->synced_at,
                    'finance_summary' => $snapshot->finance_summary['summary'] ?? [],
                    'finance_unavailable' => $snapshot->finance_available ? null : 'Finance was unavailable when this snapshot was saved.',
                    'channel_performance' => $snapshot->channel_summary['channel_breakdown'] ?? [],
                    'channel_performance_unavailable' => $snapshot->channel_summary === null ? 'Channel performance was unavailable when this snapshot was saved.' : null,
                ]),
            ]);
        } elseif ($snapshot && count($selectedBrands) === 1 && isset($snapshot->brand_summaries[$selectedBrands[0]])) {
            // Single brand: served from the per-brand slice stored alongside the snapshot, no live re-fetch.
            $brandData = $snapshot->brand_summaries[$selectedBrands[0]];
            $report = new ClientDashboardReport([
                'status' => 'completed',
                'result' => [
                    'source' => 'Saved TikTok Shop API snapshot ('.$selectedBrands[0].') · '.$snapshot->synced_at,
                    'date_range' => $snapshot->order_summary['date_range'] ?? [],
                    // Brand slices come from the same pagination pass as the whole-shop
                    // snapshot, so page count is inherited from it; records_scanned is
                    // this brand's own order count, not the whole-shop total.
                    'status_summary' => [
                        'records_scanned' => $brandData['order_value_summary']['orders_scanned'] ?? 0,
                        'pages_fetched' => $snapshot->order_summary['status_summary']['pages_fetched'] ?? 0,
                    ],
                    'order_value_summary' => $brandData['order_value_summary'] ?? [],
                    'dashboard_summary' => $brandData['dashboard_summary'] ?? [],
                    'finance_summary' => $snapshot->finance_summary['summary'] ?? [],
                    'finance_unavailable' => $snapshot->finance_available ? null : 'Finance was unavailable when this snapshot was saved.',
                    'channel_performance' => $snapshot->channel_summary['channel_breakdown'] ?? [],
                    'channel_performance_unavailable' => $snapshot->channel_summary === null ? 'Channel performance was unavailable when this snapshot was saved.' : null,
                ],
            ]);
        } elseif ($snapshot && count($selectedBrands) > 1 && $this->snapshotCoversBrands($snapshot, $selectedBrands)) {
            // Multiple brands: combine their stored per-brand slices instead of a live re-fetch.
            $report = new ClientDashboardReport([
                'status' => 'completed',
                'result' => $this->combineBrandSummaries($snapshot, $selectedBrands),
            ]);
        }
        // No matching snapshot/brand slice yet: fall through to the live report above.

        $adsAccounts = $client->adsAccounts()->with('authorization')->get();
        $adsStatus = $this->adsConnectionStatus($adsAccounts);
        [$adsPerformance, $adsError, $adsAccount] = $this->loadAdsPerformance($adsAccounts, $adsStatus, $shop, $startDate, $endDate);

        return view('client-dashboard', [
            'client' => $client, 'shops' => $shops, 'selectedShop' => $shop, 'report' => $report,
            'startDate' => $startDate, 'endDate' => $endDate, 'monthlyHistory' => $monthlyHistory,
            'selectedBrands' => $selectedBrands,
            'availableBrands' => $shop === null ? [] : $this->apiClient->configuredBrandNames($shop),
            'adsAccounts' => $adsAccounts, 'adsStatus' => $adsStatus,
            'adsAccount' => $adsAccount, 'adsPerformance' => $adsPerformance, 'adsError' => $adsError,
        ]);
    }

    public function requestReport(Request $request): RedirectResponse
    {
        $client = $this->requireClient($request);
        $validated = $request->validate([
            'shop_id' => ['required', 'string'],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'brands' => ['nullable', 'array'],
            'brands.*' => ['string'],
        ]);
        $shop = $this->assignedShops($client)->firstWhere('shop_id', $validated['shop_id']);
        abort_unless($shop !== null, 403, 'That Shop is not assigned to this client.');

        // The dashboard's END DATE is exclusive; a user picking the same day for
        // both fields means "just that one day", so bump it forward a day rather
        // than let "after_or_equal" silently produce a zero-width range.
        $endDate = $this->normalizeEndDate($validated['start_date'], $validated['end_date']);
        $selectedBrands = $this->normalizeBrands($validated['brands'] ?? []);
        $brandsKey = $selectedBrands === [] ? null : implode(',', $selectedBrands);
        $redirectParams = ['shop_id' => $validated['shop_id'], 'start_date' => $validated['start_date'], 'end_date' => $endDate, 'brands' => $selectedBrands];

        // Skip dispatching a live job when a saved snapshot already covers this request —
        // whole-shop, or any number of selected brands whose per-brand slices are all stored.
        $existingSnapshot = ShopMonthlyMetric::query()->where('tik_tok_shop_id', $shop->id)
            ->whereDate('period_start', $validated['start_date'])
            ->whereDate('period_end', $endDate)
            ->where('orders_complete', true)->first();
        $servedFromSnapshot = $existingSnapshot && ($brandsKey === null
            || $this->snapshotCoversBrands($existingSnapshot, $selectedBrands));

        if ($servedFromSnapshot) {
            return redirect()->route('client.dashboard', $redirectParams);
        }

        $attributes = [
            'user_id' => $client->id, 'tik_tok_shop_id' => $shop->id,
            'start_date' => $validated['start_date'], 'end_date' => $endDate,
            'brands' => $brandsKey,
        ];
        $report = ClientDashboardReport::query()->where($attributes)->first();

        if ($report === null || in_array($report->status, ['completed', 'failed'], true)) {
            $report = ClientDashboardReport::query()->updateOrCreate($attributes, [
                'status' => 'pending', 'result' => null, 'error_message' => null, 'completed_at' => null,
            ]);
            GenerateClientDashboardReport::dispatch($report->id);
        }

        return redirect()->route('client.dashboard', $redirectParams);
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('client.login');
    }

    private function client(Request $request): ?User
    {
        $id = $request->session()->get('client_dashboard_user_id');

        return is_int($id) ? User::query()->where('is_admin', false)->find($id) : null;
    }

    private function requireClient(Request $request): User
    {
        $client = $this->client($request);
        abort_unless($client?->hasActiveClientAccess(), 403, 'Client access is no longer active.');

        return $client;
    }

    /**
     * The dashboard's end date is exclusive, so a same-day start/end (the natural
     * way to ask for "today only") would otherwise select a zero-width range and
     * silently short-circuit report generation. Bump it forward one day instead.
     */
    private function normalizeEndDate(string $startDate, string $endDate): string
    {
        return $endDate === $startDate
            ? CarbonImmutable::parse($startDate)->addDay()->toDateString()
            : $endDate;
    }

    /** @return Collection<int, TikTokShop> */
    private function assignedShops(User $client): Collection
    {
        return $client->shops()->orderBy('name')->get();
    }

    /**
     * @param  Collection<int, TikTokAdsAccount>  $adsAccounts
     */
    private function adsConnectionStatus(Collection $adsAccounts): string
    {
        $liveAuthorizations = $adsAccounts->map(fn ($account) => $account->authorization)
            ->filter(fn ($authorization) => $authorization !== null && $authorization->refresh_token_expires_at->isFuture());

        if ($liveAuthorizations->isEmpty()) {
            return 'disconnected';
        }

        $needsRefreshSoon = $liveAuthorizations->contains(
            fn ($authorization) => $authorization->access_token_expires_at->isBefore(now()->addHours(2)),
        );

        return $needsRefreshSoon ? 'expiring' : 'connected';
    }

    /**
     * @param  Collection<int, TikTokAdsAccount>  $adsAccounts
     * @return array{0: ?array<string, mixed>, 1: ?string, 2: ?TikTokAdsAccount}
     */
    private function loadAdsPerformance(
        Collection $adsAccounts,
        string $adsStatus,
        ?TikTokShop $shop,
        string $startDate,
        string $endDate,
    ): array {
        if ($adsStatus === 'disconnected' || $shop === null) {
            return [null, null, null];
        }

        $account = $this->guessAdsAccount($adsAccounts, $shop);

        if ($account === null) {
            return [null, null, null];
        }

        // report_integrated_get caps the date span at 30 days; the Shop dashboard
        // allows up to a full calendar month, so clamp rather than error out.
        $clampedEnd = CarbonImmutable::parse($startDate)->addDays(30)->lessThan(CarbonImmutable::parse($endDate))
            ? CarbonImmutable::parse($startDate)->addDays(30)->toDateString()
            : $endDate;

        try {
            $performance = $this->adsTools->call('tiktok_ads_performance', [
                'advertiser_id' => $account->advertiser_id,
                'start_date' => $startDate,
                'end_date' => $clampedEnd,
            ], $adsAccounts);

            return [$performance, null, $account];
        } catch (InvalidArgumentException|TikTokAuthorizationException $exception) {
            return [null, $exception->getMessage(), $account];
        }
    }

    /** @param  Collection<int, TikTokAdsAccount>  $adsAccounts */
    private function guessAdsAccount(Collection $adsAccounts, TikTokShop $shop): ?TikTokAdsAccount
    {
        if ($adsAccounts->isEmpty()) {
            return null;
        }

        $shopTokens = collect(preg_split('/\s+/', strtolower($shop->name ?? '')) ?: [])
            ->filter(fn (string $token): bool => strlen($token) > 2)
            ->all();

        return $adsAccounts->first(
            fn (TikTokAdsAccount $account): bool => $shopTokens !== [] && Str::contains(strtolower((string) $account->name), $shopTokens),
        ) ?? $adsAccounts->first();
    }

    /**
     * @param  list<mixed>  $brands
     * @return list<string>
     */
    private function normalizeBrands(array $brands): array
    {
        return collect($brands)
            ->filter(fn (mixed $brand): bool => is_string($brand) && $brand !== '')
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /** @param  list<string>  $selectedBrands */
    private function snapshotCoversBrands(ShopMonthlyMetric $snapshot, array $selectedBrands): bool
    {
        if ($selectedBrands === []) {
            return false;
        }

        foreach ($selectedBrands as $brand) {
            if (! isset($snapshot->brand_summaries[$brand])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Combines two or more brands' stored per-brand slices (from the same
     * pagination pass as the whole-shop snapshot) into one result shaped like
     * a live multi-brand report, with no live API re-fetch. GMV/NMV/units are
     * exactly additive since every line item belongs to exactly one brand;
     * order-count fields (orders_scanned, payment methods, etc.) can be
     * marginally inflated for the rare order that mixes brands, since such an
     * order is counted once per brand slice it appears in.
     *
     * @param  list<string>  $selectedBrands
     * @return array<string, mixed>
     */
    private function combineBrandSummaries(ShopMonthlyMetric $snapshot, array $selectedBrands): array
    {
        $orderValue = [
            'orders_scanned' => 0, 'line_items_scanned' => 0,
            'line_items_with_complete_pricing' => 0, 'line_items_missing_pricing' => 0,
            'sku_subtotal_after_discount' => 0.0, 'sku_platform_discount' => 0.0, 'calculated_gmv' => 0.0,
        ];
        $currencies = [];
        $complete = true;
        $reportedTotalMatches = true;
        $canceledValue = 0.0;
        $completedOrders = 0;
        $canceledOrders = 0;
        $nonCanceledOrders = 0;
        $units = 0;
        $platformSubsidy = 0.0;
        $topProducts = [];
        $topCancelSkus = [];
        $paymentMethods = [];
        $locations = [];
        $cancelReasons = [];
        $campaignPeriods = [];
        $brandMix = [];

        foreach ($selectedBrands as $brand) {
            $ov = $snapshot->brand_summaries[$brand]['order_value_summary'] ?? [];
            $ds = $snapshot->brand_summaries[$brand]['dashboard_summary'] ?? [];

            $orderValue['orders_scanned'] += (int) ($ov['orders_scanned'] ?? 0);
            $orderValue['line_items_scanned'] += (int) ($ov['line_items_scanned'] ?? 0);
            $orderValue['line_items_with_complete_pricing'] += (int) ($ov['line_items_with_complete_pricing'] ?? 0);
            $orderValue['line_items_missing_pricing'] += (int) ($ov['line_items_missing_pricing'] ?? 0);
            $orderValue['sku_subtotal_after_discount'] += (float) ($ov['sku_subtotal_after_discount'] ?? 0);
            $orderValue['sku_platform_discount'] += (float) ($ov['sku_platform_discount'] ?? 0);
            $orderValue['calculated_gmv'] += (float) ($ov['calculated_gmv'] ?? 0);

            if (is_string($ov['currency'] ?? null) && $ov['currency'] !== '') {
                $currencies[$ov['currency']] = true;
            }

            $complete = $complete && (bool) ($ov['complete'] ?? false);
            $reportedTotalMatches = $reportedTotalMatches && (bool) ($ov['reported_total_matches'] ?? false);

            $brandCanceledValue = (float) ($ds['canceled_value'] ?? 0);
            $brandCompletedOrders = (int) ($ds['completed_orders'] ?? 0);
            $brandCanceledOrders = (int) ($ds['canceled_orders'] ?? 0);
            $canceledValue += $brandCanceledValue;
            $completedOrders += $brandCompletedOrders;
            $canceledOrders += $brandCanceledOrders;
            $nonCanceledOrders += (int) ($ds['non_canceled_orders'] ?? 0);
            $units += (int) ($ds['units'] ?? 0);
            $platformSubsidy += (float) ($ds['platform_subsidy'] ?? 0);

            $topProducts = [...$topProducts, ...($ds['top_products'] ?? [])];
            $topCancelSkus = [...$topCancelSkus, ...($ds['top_cancel_skus'] ?? [])];
            $paymentMethods = $this->mergeNamedCounts($paymentMethods, $ds['payment_methods'] ?? [], 'orders');
            $locations = $this->mergeNamedCounts($locations, $ds['locations'] ?? [], 'orders');
            $cancelReasons = $this->mergeNamedCounts($cancelReasons, $ds['cancel_reasons'] ?? [], 'orders');
            $campaignPeriods = $this->mergeSegments($campaignPeriods, $ds['campaign_periods'] ?? []);

            $brandMix[] = [
                'name' => $brand,
                'gmv' => round((float) ($ov['calculated_gmv'] ?? 0), 2),
                'nmv' => round((float) ($ds['nmv'] ?? 0), 2),
                'canceled_value' => round($brandCanceledValue, 2),
                'cancel_rate_by_value' => (float) ($ds['cancel_rate_by_value'] ?? 0),
                'platform_subsidy' => round((float) ($ds['platform_subsidy'] ?? 0), 2),
                'units' => (int) ($ds['units'] ?? 0),
                'canceled_units' => 0,
                'orders' => $brandCompletedOrders + $brandCanceledOrders,
            ];
        }

        $gmv = round($orderValue['calculated_gmv'], 2);
        $canceledValue = round($canceledValue, 2);
        $nmv = round($gmv - $canceledValue, 2);

        usort($topProducts, static fn (array $left, array $right): int => $right['value'] <=> $left['value']);
        usort($topCancelSkus, static fn (array $left, array $right): int => $right['value'] <=> $left['value']);
        usort($brandMix, static fn (array $left, array $right): int => $right['gmv'] <=> $left['gmv']);

        return [
            'source' => 'Saved TikTok Shop API snapshot ('.implode(', ', $selectedBrands).') · '.$snapshot->synced_at,
            'date_range' => $snapshot->order_summary['date_range'] ?? [],
            // Brand slices come from the same pagination pass as the whole-shop
            // snapshot, so page count is inherited from it; records_scanned is
            // the combined selected-brands order count, not the whole-shop total.
            'status_summary' => [
                'records_scanned' => $orderValue['orders_scanned'],
                'pages_fetched' => $snapshot->order_summary['status_summary']['pages_fetched'] ?? 0,
            ],
            'order_value_summary' => [
                'formula' => 'SUM(line_items.sale_price + line_items.platform_discount)',
                'orders_scanned' => $orderValue['orders_scanned'],
                'line_items_scanned' => $orderValue['line_items_scanned'],
                'line_items_with_complete_pricing' => $orderValue['line_items_with_complete_pricing'],
                'line_items_missing_pricing' => $orderValue['line_items_missing_pricing'],
                'reported_total_matches' => $reportedTotalMatches,
                'currency' => count($currencies) === 1 ? array_key_first($currencies) : 'LOCAL',
                'sku_subtotal_after_discount' => round($orderValue['sku_subtotal_after_discount'], 2),
                'sku_platform_discount' => round($orderValue['sku_platform_discount'], 2),
                'calculated_gmv' => $gmv,
                'complete' => $complete && $reportedTotalMatches && $orderValue['line_items_missing_pricing'] === 0,
            ],
            'dashboard_summary' => [
                'nmv' => $nmv,
                'canceled_value' => $canceledValue,
                'cancel_rate_by_value' => $gmv > 0 ? round($canceledValue / $gmv * 100, 2) : 0.0,
                'aov' => $nonCanceledOrders > 0 ? round($nmv / $nonCanceledOrders, 2) : 0.0,
                'completed_orders' => $completedOrders,
                'canceled_orders' => $canceledOrders,
                'non_canceled_orders' => $nonCanceledOrders,
                'units' => $units,
                'platform_subsidy' => round($platformSubsidy, 2),
                'top_products' => array_slice($topProducts, 0, 20),
                'top_cancel_skus' => array_slice($topCancelSkus, 0, 20),
                'payment_methods' => $paymentMethods,
                'locations' => $locations,
                'cancel_reasons' => $cancelReasons,
                'brand_mix' => $brandMix,
                'campaign_periods' => $campaignPeriods,
            ],
            'finance_summary' => $snapshot->finance_summary['summary'] ?? [],
            'finance_unavailable' => $snapshot->finance_available ? null : 'Finance was unavailable when this snapshot was saved.',
            'channel_performance' => $snapshot->channel_summary['channel_breakdown'] ?? [],
            'channel_performance_unavailable' => $snapshot->channel_summary === null ? 'Channel performance was unavailable when this snapshot was saved.' : null,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $existing
     * @param  list<array<string, mixed>>  $additional
     * @return list<array<string, mixed>>
     */
    private function mergeNamedCounts(array $existing, array $additional, string $countKey): array
    {
        $byName = [];

        foreach ([...$existing, ...$additional] as $item) {
            $name = (string) $item['name'];
            $byName[$name] = ($byName[$name] ?? 0) + (int) $item[$countKey];
        }

        arsort($byName);

        return array_map(
            static fn (string $name, int $count): array => ['name' => $name, $countKey => $count],
            array_keys($byName),
            array_values($byName),
        );
    }

    /**
     * Merges formatSegment()-shaped rows (campaign periods, brand mix) by
     * name, summing the additive fields and recomputing the derived ones.
     *
     * @param  list<array<string, mixed>>  $existing
     * @param  list<array<string, mixed>>  $additional
     * @return list<array<string, mixed>>
     */
    private function mergeSegments(array $existing, array $additional): array
    {
        $byName = [];

        foreach ([...$existing, ...$additional] as $segment) {
            $name = (string) $segment['name'];
            $byName[$name] ??= ['gmv' => 0.0, 'canceled_value' => 0.0, 'platform_subsidy' => 0.0, 'units' => 0, 'canceled_units' => 0, 'orders' => 0];
            $byName[$name]['gmv'] += (float) $segment['gmv'];
            $byName[$name]['canceled_value'] += (float) $segment['canceled_value'];
            $byName[$name]['platform_subsidy'] += (float) $segment['platform_subsidy'];
            $byName[$name]['units'] += (int) $segment['units'];
            $byName[$name]['canceled_units'] += (int) $segment['canceled_units'];
            $byName[$name]['orders'] += (int) $segment['orders'];
        }

        $merged = [];

        foreach ($byName as $name => $segment) {
            $merged[] = [
                'name' => $name,
                'gmv' => round($segment['gmv'], 2),
                'nmv' => round($segment['gmv'] - $segment['canceled_value'], 2),
                'canceled_value' => round($segment['canceled_value'], 2),
                'cancel_rate_by_value' => $segment['gmv'] > 0 ? round($segment['canceled_value'] / $segment['gmv'] * 100, 2) : 0.0,
                'platform_subsidy' => round($segment['platform_subsidy'], 2),
                'units' => $segment['units'],
                'canceled_units' => $segment['canceled_units'],
                'orders' => $segment['orders'],
            ];
        }

        return $merged;
    }
}

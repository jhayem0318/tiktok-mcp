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
        }
        // Multiple brands selected, or no matching snapshot/brand slice yet: fall through to the live report above.

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
        // either whole-shop, or (since commit adding brand_summaries) exactly one selected brand.
        $existingSnapshot = ShopMonthlyMetric::query()->where('tik_tok_shop_id', $shop->id)
            ->whereDate('period_start', $validated['start_date'])
            ->whereDate('period_end', $endDate)
            ->where('orders_complete', true)->first();
        $servedFromSnapshot = $existingSnapshot && ($brandsKey === null
            || (count($selectedBrands) === 1 && isset($existingSnapshot->brand_summaries[$selectedBrands[0]])));

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
}

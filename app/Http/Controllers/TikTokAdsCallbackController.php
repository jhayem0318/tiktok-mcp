<?php

namespace App\Http\Controllers;

use App\Actions\StoreAuthorizedTikTokAdsAccounts;
use App\Actions\StoreTikTokAdsAuthorization;
use App\Exceptions\TikTokAuthorizationException;
use App\Models\User;
use App\Services\TikTokAdsMcpClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TikTokAdsCallbackController extends Controller
{
    public function __invoke(
        Request $request,
        TikTokAdsMcpClient $adsClient,
        StoreTikTokAdsAuthorization $storeAuthorization,
        StoreAuthorizedTikTokAdsAccounts $storeAccounts,
    ): RedirectResponse {
        if ($request->string('error')->isNotEmpty()) {
            return redirect()->route('client.dashboard')->with('ads_connection_error', 'TikTok Ads authorization was not granted.');
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:4096'],
            'state' => ['required', 'string', 'max:255'],
        ]);

        $pending = Cache::pull('tiktok-ads-pkce:'.$validated['state']);
        $codeVerifier = is_array($pending) ? ($pending['code_verifier'] ?? null) : null;
        $clientUserId = is_array($pending) ? ($pending['user_id'] ?? null) : null;

        if (! is_string($codeVerifier) || $codeVerifier === '' || ! is_int($clientUserId)) {
            return redirect()->route('client.dashboard')->with('ads_connection_error', 'The TikTok Ads authorization state has expired or is invalid.');
        }

        $client = User::query()->where('is_admin', false)->find($clientUserId);

        if ($client === null) {
            return redirect()->route('client.login');
        }

        try {
            $tokenData = $adsClient->exchangeCode($validated['code'], $codeVerifier);
            $authorization = $storeAuthorization->handle($adsClient->clientId(), $tokenData['open_id'] ?? null, $tokenData);

            $advertisers = $adsClient->callTool('auth_advertiser_get', [], $authorization);
            $advertiserIds = [];
            $names = [];

            foreach ((array) data_get($advertisers, 'data.list', []) as $advertiser) {
                $advertiserId = (string) data_get($advertiser, 'advertiser_id', '');

                if ($advertiserId === '') {
                    continue;
                }

                $advertiserIds[] = $advertiserId;
                $names[$advertiserId] = data_get($advertiser, 'advertiser_name');
            }

            $details = $advertiserIds === []
                ? []
                : $adsClient->callTool('advertiser_info_get', ['advertiser_ids' => $advertiserIds], $authorization);
            $detailsById = collect((array) data_get($details, 'data.list', []))
                ->keyBy(fn ($detail) => (string) data_get($detail, 'advertiser_id', ''));

            $accounts = array_map(fn (string $advertiserId): array => [
                'advertiser_id' => $advertiserId,
                'name' => $names[$advertiserId] ?? $detailsById->get($advertiserId)['name'] ?? null,
                'currency' => $detailsById->get($advertiserId)['currency'] ?? null,
                'timezone' => $detailsById->get($advertiserId)['timezone'] ?? null,
            ], $advertiserIds);

            $storedAccounts = $storeAccounts->handle($authorization, $accounts);
            $client->adsAccounts()->syncWithoutDetaching(array_map(
                static fn ($account): int => $account->id,
                $storedAccounts,
            ));
        } catch (TikTokAuthorizationException $exception) {
            Log::warning('TikTok Ads authorization failed.', [
                'reason' => $exception->getMessage(),
            ]);

            return redirect()->route('client.dashboard')->with('ads_connection_error', $exception->getMessage());
        }

        return redirect()->route('client.dashboard')->with('ads_connection_success', true);
    }
}

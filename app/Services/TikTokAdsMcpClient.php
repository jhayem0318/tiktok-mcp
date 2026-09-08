<?php

namespace App\Services;

use App\Exceptions\TikTokAuthorizationException;
use App\Models\TikTokAdsAuthorization;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Talks to TikTok's own hosted "TikTok for Business MCP Server" — a
 * different product and transport from the TikTok Shop Open API
 * (OAuth 2.0 + PKCE + Dynamic Client Registration, tool calls are
 * JSON-RPC over HTTP rather than plain REST). See:
 * https://business-api.tiktok.com/portal/docs/how-to-connect-a-custom-agent-to-tiktok-for-business-mcp-server/v1.3
 */
class TikTokAdsMcpClient
{
    private const CACHE_KEY_CONFIG = 'tiktok_ads.oauth_config';

    private const CACHE_KEY_CLIENT_ID = 'tiktok_ads.client_id';

    /** @return array<string, mixed> */
    public function discoverConfig(): array
    {
        return Cache::remember(self::CACHE_KEY_CONFIG, now()->addDay(), function (): array {
            try {
                $response = Http::acceptJson()->connectTimeout(5)->timeout(15)
                    ->get('https://business-api.tiktok.com/open_mcp/'.$this->server().'/oauth/.well-known/openid-configuration')
                    ->throw();
            } catch (ConnectionException|RequestException) {
                throw new TikTokAuthorizationException('TikTok Ads OAuth discovery failed.');
            }

            $config = $response->json();

            if (! is_array($config) || ! is_string($config['authorization_endpoint'] ?? null) || ! is_string($config['token_endpoint'] ?? null)) {
                throw new TikTokAuthorizationException('TikTok Ads OAuth discovery returned an unexpected response.');
            }

            return $config;
        });
    }

    /**
     * Dynamic Client Registration is meant to run once per application, not
     * per user — the resulting client_id is cached indefinitely and reused
     * for every future authorization. Losing the cache only means a fresh
     * (harmless) registration on the next connect attempt; it never
     * invalidates tokens already issued to users.
     */
    public function clientId(): string
    {
        return Cache::rememberForever(self::CACHE_KEY_CLIENT_ID, function (): string {
            $config = $this->discoverConfig();
            $redirectUri = $this->redirectUri();

            try {
                $response = Http::acceptJson()->connectTimeout(5)->timeout(15)
                    ->post((string) $config['registration_endpoint'], [
                        'client_name' => (string) config('app.name', 'GoCommerce'),
                        'redirect_uris' => [$redirectUri],
                        'token_endpoint_auth_method' => 'none',
                        'grant_types' => ['authorization_code', 'refresh_token'],
                        'response_types' => ['code'],
                    ])
                    ->throw();
            } catch (ConnectionException|RequestException) {
                throw new TikTokAuthorizationException('TikTok Ads client registration failed.');
            }

            $data = $response->json();

            if (! is_array($data) || ! is_string($data['client_id'] ?? null) || $data['client_id'] === '') {
                throw new TikTokAuthorizationException('TikTok Ads client registration returned an unexpected response.');
            }

            return $data['client_id'];
        });
    }

    public function authorizationUrl(string $state, string $codeChallenge): string
    {
        $config = $this->discoverConfig();

        $query = http_build_query([
            'response_type' => 'code',
            'resource' => $this->resource(),
            'client_id' => $this->clientId(),
            'redirect_uri' => $this->redirectUri(),
            'scope' => 'mcp:tt4b',
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
            'state' => $state,
        ]);

        return $config['authorization_endpoint'].'?'.$query;
    }

    /** @return array<string, mixed> */
    public function exchangeCode(string $code, string $codeVerifier): array
    {
        return $this->requestToken([
            'grant_type' => 'authorization_code',
            'code' => $code,
            'code_verifier' => $codeVerifier,
            'redirect_uri' => $this->redirectUri(),
        ]);
    }

    /** @return array<string, mixed> */
    public function refresh(string $refreshToken): array
    {
        return $this->requestToken([
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
        ]);
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    public function callTool(string $tool, array $arguments, TikTokAdsAuthorization $authorization): array
    {
        try {
            $response = Http::withToken($authorization->access_token)
                ->acceptJson()
                ->connectTimeout(5)
                ->timeout(20)
                ->post($this->resource(), [
                    'jsonrpc' => '2.0',
                    'id' => 1,
                    'method' => 'tools/call',
                    'params' => ['name' => $tool, 'arguments' => $arguments],
                ])
                ->throw();
        } catch (ConnectionException|RequestException) {
            throw new TikTokAuthorizationException('TikTok Ads tool call failed.');
        }

        $payload = $response->json();
        $text = data_get($payload, 'result.content.0.text');

        if (! is_string($text) || $text === '') {
            $errorMessage = data_get($payload, 'error.message');
            throw new TikTokAuthorizationException(
                is_string($errorMessage) ? 'TikTok Ads tool call failed: '.$errorMessage : 'TikTok Ads tool call returned an unexpected response.',
            );
        }

        $decoded = json_decode($text, true);

        if (! is_array($decoded)) {
            throw new TikTokAuthorizationException('TikTok Ads tool call returned an invalid payload.');
        }

        return $decoded;
    }

    /**
     * @param  array<string, string>  $parameters
     * @return array<string, mixed>
     */
    private function requestToken(array $parameters): array
    {
        $config = $this->discoverConfig();

        try {
            $response = Http::asForm()->acceptJson()->connectTimeout(5)->timeout(15)
                ->post((string) $config['token_endpoint'], $parameters)
                ->throw();
        } catch (ConnectionException|RequestException) {
            throw new TikTokAuthorizationException('TikTok Ads rejected the token exchange.');
        }

        $data = $response->json();

        foreach (['access_token', 'refresh_token', 'expires_in', 'refresh_token_expires_in'] as $field) {
            if (! is_array($data) || ! array_key_exists($field, $data)) {
                throw new TikTokAuthorizationException('TikTok Ads returned an incomplete token response.');
            }
        }

        return $data;
    }

    private function server(): string
    {
        $server = config('services.tiktok_ads.server', 'tt-ads-mcp-layer');

        return is_string($server) && $server !== '' ? $server : 'tt-ads-mcp-layer';
    }

    private function resource(): string
    {
        return 'https://business-api.tiktok.com/open_mcp/'.$this->server();
    }

    private function redirectUri(): string
    {
        $redirectUri = config('services.tiktok_ads.redirect_uri');

        if (! is_string($redirectUri) || $redirectUri === '') {
            throw new TikTokAuthorizationException('TikTok Ads redirect_uri is not configured.');
        }

        return $redirectUri;
    }
}

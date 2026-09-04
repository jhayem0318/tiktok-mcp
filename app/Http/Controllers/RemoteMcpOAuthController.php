<?php

namespace App\Http\Controllers;

use App\Models\RemoteMcpAccessToken;
use App\Models\RemoteMcpAuthorizationCode;
use App\Models\RemoteMcpClient;
use App\Models\RemoteMcpInvite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RemoteMcpOAuthController extends Controller
{
    private const Scope = 'tiktok_shop.read';

    public function protectedResource(): JsonResponse
    {
        return response()->json([
            'resource' => url('/api/tiktok/mcp/remote'),
            'authorization_servers' => [url('/')],
            'scopes_supported' => [self::Scope],
            'bearer_methods_supported' => ['header'],
        ]);
    }

    public function authorizationServer(): JsonResponse
    {
        return response()->json([
            'issuer' => url('/'),
            'authorization_endpoint' => route('remote-mcp.authorize'),
            'token_endpoint' => route('remote-mcp.token'),
            'registration_endpoint' => route('remote-mcp.register'),
            'revocation_endpoint' => route('remote-mcp.revoke'),
            'response_types_supported' => ['code'],
            'grant_types_supported' => ['authorization_code'],
            'code_challenge_methods_supported' => ['S256'],
            'token_endpoint_auth_methods_supported' => ['none'],
            'scopes_supported' => [self::Scope],
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'client_name' => ['nullable', 'string', 'max:255'],
            'redirect_uris' => ['required', 'array', 'min:1', 'max:10'],
            'redirect_uris.*' => ['required', 'string', 'url', 'max:2048'],
        ]);

        $redirectUris = array_values(array_unique($validated['redirect_uris']));

        foreach ($redirectUris as $redirectUri) {
            if (! $this->isAllowedRedirectUri($redirectUri)) {
                return $this->oauthError('invalid_client_metadata', 'Only HTTPS and loopback redirect URIs are allowed.', 400);
            }
        }

        $client = RemoteMcpClient::query()->create([
            'client_id' => (string) Str::uuid(),
            'name' => $validated['client_name'] ?? null,
            'redirect_uris' => $redirectUris,
        ]);

        return response()->json([
            'client_id' => $client->client_id,
            'client_id_issued_at' => now()->getTimestamp(),
            'redirect_uris' => $redirectUris,
            'token_endpoint_auth_method' => 'none',
        ], 201);
    }

    public function authorize(Request $request): View|RedirectResponse|JsonResponse
    {
        if (! (bool) config('services.tiktok.remote_mcp_enabled')) {
            return $this->oauthError('temporarily_unavailable', 'Remote MCP access is not enabled.', 503);
        }

        if ($request->isMethod('POST')) {
            return $this->approve($request);
        }

        $validated = $request->validate([
            'response_type' => ['required', 'in:code'],
            'client_id' => ['required', 'string'],
            'redirect_uri' => ['required', 'string', 'url', 'max:2048'],
            'code_challenge' => ['required', 'string', 'min:43', 'max:128'],
            'code_challenge_method' => ['required', 'in:S256'],
            'scope' => ['nullable', 'string'],
            'state' => ['nullable', 'string', 'max:2048'],
        ]);

        $client = RemoteMcpClient::query()->where('client_id', $validated['client_id'])->first();

        if ($client === null || ! in_array($validated['redirect_uri'], $client->redirect_uris, true)) {
            return $this->oauthError('invalid_request', 'The client or redirect URI is not registered.', 400);
        }

        if (! $this->hasReadScope($validated['scope'] ?? '')) {
            return $this->oauthError('invalid_scope', 'The tiktok_shop.read scope is required.', 400);
        }

        $request->session()->put('remote_mcp_authorize', $validated);

        if (! is_int($request->session()->get('remote_mcp_invite_id'))) {
            return redirect()->route('remote-mcp.login');
        }

        return view('remote-mcp-authorize', ['client' => $client]);
    }

    public function login(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('remote_mcp_authorize')) {
            return redirect()->route('remote-mcp.authorize');
        }

        return view('remote-mcp-login');
    }

    public function authenticate(Request $request): RedirectResponse
    {
        $validated = $request->validate(['invite_code' => ['required', 'string', 'max:255']]);
        $invite = RemoteMcpInvite::query()
            ->whereNull('revoked_at')
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->get()
            ->first(fn (RemoteMcpInvite $candidate): bool => Hash::check($validated['invite_code'], $candidate->code_hash));

        if ($invite === null) {
            return back()->withErrors(['invite_code' => 'That invitation is invalid, expired, or revoked.']);
        }

        $request->session()->regenerate();
        $request->session()->put('remote_mcp_invite_id', $invite->id);

        return redirect()->route('remote-mcp.authorize', $request->session()->get('remote_mcp_authorize', []));
    }

    public function token(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'grant_type' => ['required', 'in:authorization_code'],
            'code' => ['required', 'string'],
            'client_id' => ['required', 'string'],
            'redirect_uri' => ['required', 'string', 'url', 'max:2048'],
            'code_verifier' => ['required', 'string', 'min:43', 'max:128'],
        ]);
        $code = RemoteMcpAuthorizationCode::query()
            ->where('code_hash', hash('sha256', $validated['code']))
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();

        if ($code === null || ! $this->matchesCodeRequest($code, $validated)) {
            return $this->oauthError('invalid_grant', 'The authorization code is invalid or expired.', 400);
        }

        $code->update(['used_at' => now()]);
        $accessToken = Str::random(96);
        $ttl = max(300, min(86_400, (int) config('services.tiktok.remote_mcp_access_token_ttl', 3600)));

        RemoteMcpAccessToken::query()->create([
            'remote_mcp_client_id' => $code->remote_mcp_client_id,
            'remote_mcp_invite_id' => $code->remote_mcp_invite_id,
            'token_hash' => hash('sha256', $accessToken),
            'scopes' => explode(' ', $code->scope),
            'expires_at' => now()->addSeconds($ttl),
        ]);

        return response()->json([
            'access_token' => $accessToken,
            'token_type' => 'Bearer',
            'expires_in' => $ttl,
            'scope' => $code->scope,
        ]);
    }

    public function revoke(Request $request): JsonResponse
    {
        $validated = $request->validate(['token' => ['required', 'string']]);

        RemoteMcpAccessToken::query()
            ->where('token_hash', hash('sha256', $validated['token']))
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);

        return response()->json((object) []);
    }

    private function approve(Request $request): RedirectResponse|JsonResponse
    {
        $parameters = $request->session()->get('remote_mcp_authorize');
        $inviteId = $request->session()->get('remote_mcp_invite_id');

        if (! is_array($parameters) || ! is_int($inviteId)) {
            return $this->oauthError('invalid_request', 'The authorization session has expired.', 400);
        }

        $client = RemoteMcpClient::query()->where('client_id', $parameters['client_id'] ?? null)->first();
        $invite = RemoteMcpInvite::query()->whereKey($inviteId)->whereNull('revoked_at')->first();

        if ($client === null || $invite === null || ! in_array($parameters['redirect_uri'] ?? '', $client->redirect_uris, true)) {
            return $this->oauthError('invalid_request', 'The authorization request is no longer valid.', 400);
        }

        if ($request->input('decision') !== 'approve') {
            return $this->redirectWithError($parameters, 'access_denied');
        }

        $plainCode = Str::random(96);
        $ttl = max(60, min(900, (int) config('services.tiktok.remote_mcp_authorization_code_ttl', 300)));
        RemoteMcpAuthorizationCode::query()->create([
            'remote_mcp_client_id' => $client->id,
            'remote_mcp_invite_id' => $invite->id,
            'code_hash' => hash('sha256', $plainCode),
            'redirect_uri' => $parameters['redirect_uri'],
            'code_challenge' => $parameters['code_challenge'],
            'scope' => self::Scope,
            'expires_at' => now()->addSeconds($ttl),
        ]);
        $request->session()->forget(['remote_mcp_authorize', 'remote_mcp_invite_id']);

        $query = ['code' => $plainCode];
        if (is_string($parameters['state'] ?? null) && $parameters['state'] !== '') {
            $query['state'] = $parameters['state'];
        }

        return redirect()->away($parameters['redirect_uri'].'?'.http_build_query($query));
    }

    /** @param array<string, mixed> $validated */
    private function matchesCodeRequest(RemoteMcpAuthorizationCode $code, array $validated): bool
    {
        $client = RemoteMcpClient::query()->find($code->remote_mcp_client_id);
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $validated['code_verifier'], true)), '+/', '-_'), '=');

        return $client !== null
            && hash_equals($client->client_id, $validated['client_id'])
            && hash_equals($code->redirect_uri, $validated['redirect_uri'])
            && hash_equals($code->code_challenge, $challenge);
    }

    private function hasReadScope(string $scope): bool
    {
        return in_array(self::Scope, preg_split('/\s+/', trim($scope)) ?: [], true);
    }

    private function isAllowedRedirectUri(string $redirectUri): bool
    {
        $parts = parse_url($redirectUri);
        $scheme = $parts['scheme'] ?? null;
        $host = $parts['host'] ?? null;

        return $scheme === 'https' || ($scheme === 'http' && in_array($host, ['127.0.0.1', 'localhost'], true));
    }

    private function oauthError(string $error, string $description, int $status): JsonResponse
    {
        return response()->json(['error' => $error, 'error_description' => $description], $status);
    }

    /** @param array<string, mixed> $parameters */
    private function redirectWithError(array $parameters, string $error): RedirectResponse
    {
        $query = ['error' => $error];
        if (is_string($parameters['state'] ?? null) && $parameters['state'] !== '') {
            $query['state'] = $parameters['state'];
        }

        return redirect()->away($parameters['redirect_uri'].'?'.http_build_query($query));
    }
}

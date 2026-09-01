<?php

namespace Tests\Feature;

use App\Models\TikTokShopAuthorization;
use App\Models\TikTokShop;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ServiceEndpointsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_service_root_returns_status_payload(): void
    {
        $response = $this->get('/');

        $response->assertOk()->assertExactJson([
            'service' => 'tiktok-mcp',
            'status' => 'ok',
        ]);
    }

    public function test_tiktok_callback_returns_422_when_code_is_missing(): void
    {
        $response = $this->getJson('/tiktok/callback');

        $response->assertUnprocessable()->assertJsonValidationErrors('code');
    }

    public function test_tiktok_callback_exchanges_code_and_stores_encrypted_seller_tokens(): void
    {
        $this->configureTikTokShop();
        Http::preventStrayRequests();
        Http::fake([
            'https://auth.tiktok-shops.com/api/v2/token/get*' => Http::response([
                'code' => 0,
                'message' => 'success',
                'data' => [
                    'access_token' => 'access-token-value',
                    'access_token_expire_in' => 1788872400,
                    'refresh_token' => 'refresh-token-value',
                    'refresh_token_expire_in' => 1820408400,
                    'open_id' => 'seller-open-id',
                    'seller_name' => 'Anker Philippines',
                    'seller_base_region' => 'PH',
                    'user_type' => 0,
                    'granted_scopes' => [
                        'seller.authorization.info',
                        'data.shop_analytics.public.read',
                    ],
                ],
            ]),
        ]);

        $response = $this->getJson('/tiktok/callback?code=one-time-code&app_key=test-app-key');

        $response->assertOk()->assertExactJson([
            'status' => 'authorized',
            'seller' => [
                'name' => 'Anker Philippines',
                'region' => 'PH',
            ],
        ]);
        $this->assertDatabaseHas('tik_tok_shop_authorizations', [
            'app_key' => 'test-app-key',
            'open_id' => 'seller-open-id',
            'seller_name' => 'Anker Philippines',
        ]);

        $authorization = TikTokShopAuthorization::query()->sole();
        $this->assertSame('access-token-value', $authorization->access_token);
        $this->assertSame('refresh-token-value', $authorization->refresh_token);
        $this->assertNotSame(
            'access-token-value',
            DB::table('tik_tok_shop_authorizations')->value('access_token'),
        );
        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://auth.tiktok-shops.com/api/v2/token/get'
                && $request['app_key'] === 'test-app-key'
                && $request['app_secret'] === 'test-app-secret'
                && $request['auth_code'] === 'one-time-code'
                && $request['grant_type'] === 'authorized_code';
        });
    }

    public function test_tiktok_callback_returns_422_when_app_key_does_not_match(): void
    {
        $this->configureTikTokShop();
        Http::preventStrayRequests();

        $response = $this->getJson('/tiktok/callback?code=one-time-code&app_key=another-app');

        $response->assertUnprocessable()->assertExactJson([
            'status' => 'invalid_callback',
            'message' => 'The callback does not match the configured TikTok Shop app.',
        ]);
        $this->assertDatabaseCount('tik_tok_shop_authorizations', 0);
    }

    public function test_tiktok_shop_connection_command_calls_authorized_shops_read_only(): void
    {
        $this->configureTikTokShop();
        config()->set('services.tiktok.api_url', 'https://open-api.tiktokglobalshop.com');
        CarbonImmutable::setTestNow('2026-09-01 12:00:00 UTC');

        TikTokShopAuthorization::query()->create([
            'app_key' => 'test-app-key',
            'open_id' => 'seller-open-id',
            'seller_name' => 'Anker Philippines',
            'seller_base_region' => 'PH',
            'user_type' => 0,
            'access_token' => 'access-token-value',
            'refresh_token' => 'refresh-token-value',
            'access_token_expires_at' => now()->addWeek(),
            'refresh_token_expires_at' => now()->addYear(),
        ]);

        Http::preventStrayRequests();
        Http::fake([
            'https://open-api.tiktokglobalshop.com/authorization/202309/shops*' => Http::response([
                'code' => 0,
                'message' => 'Success',
                'data' => [
                    'shops' => [[
                        'id' => 'shop-id',
                        'name' => 'Anker Philippines',
                        'region' => 'PH',
                        'cipher' => 'secret-shop-cipher',
                    ]],
                ],
            ]),
        ]);

        $this->artisan('tiktok:shop:test')
            ->expectsOutput('TikTok Shop read-only connection succeeded.')
            ->expectsOutput('Authorized shops: 1')
            ->expectsOutput('- Anker Philippines (PH)')
            ->assertSuccessful();

        $shop = TikTokShop::query()->sole();
        $this->assertSame('secret-shop-cipher', $shop->shop_cipher);
        $this->assertNotSame(
            'secret-shop-cipher',
            DB::table('tik_tok_shops')->value('shop_cipher'),
        );

        Http::assertSent(function (Request $request): bool {
            $timestamp = CarbonImmutable::now()->getTimestamp();
            $parameters = 'app_keytest-app-keytimestamp'.$timestamp;
            $message = 'test-app-secret/authorization/202309/shops'.$parameters.'test-app-secret';
            $expectedSign = hash_hmac('sha256', $message, 'test-app-secret');

            return $request->method() === 'GET'
                && $request->hasHeader('x-tts-access-token', 'access-token-value')
                && $request['app_key'] === 'test-app-key'
                && $request['timestamp'] === $timestamp
                && $request['sign'] === $expectedSign;
        });
    }

    public function test_tiktok_shop_refresh_command_rotates_expiring_tokens(): void
    {
        $this->configureTikTokShop();
        config()->set('services.tiktok.refresh_url', 'https://auth.tiktok-shops.com/api/v2/token/refresh');

        $authorization = TikTokShopAuthorization::query()->create([
            'app_key' => 'test-app-key',
            'open_id' => 'seller-open-id',
            'seller_name' => 'Anker Philippines',
            'seller_base_region' => 'PH',
            'user_type' => 0,
            'access_token' => 'expiring-access-token',
            'refresh_token' => 'current-refresh-token',
            'access_token_expires_at' => now()->subMinute(),
            'refresh_token_expires_at' => now()->addYear(),
        ]);

        Http::preventStrayRequests();
        Http::fake([
            'https://auth.tiktok-shops.com/api/v2/token/refresh*' => Http::response([
                'code' => 0,
                'message' => 'success',
                'data' => [
                    'access_token' => 'new-access-token',
                    'access_token_expire_in' => now()->addWeek()->getTimestamp(),
                    'refresh_token' => 'new-refresh-token',
                    'refresh_token_expire_in' => now()->addYear()->getTimestamp(),
                    'open_id' => 'seller-open-id',
                    'seller_name' => 'Anker Philippines',
                    'seller_base_region' => 'PH',
                    'user_type' => 0,
                    'granted_scopes' => ['seller.authorization.info'],
                ],
            ]),
        ]);

        $this->artisan('tiktok:shop:refresh')
            ->expectsOutput('TikTok Shop token refresh check succeeded.')
            ->assertSuccessful();

        $authorization->refresh();
        $this->assertSame('new-access-token', $authorization->access_token);
        $this->assertSame('new-refresh-token', $authorization->refresh_token);
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://auth.tiktok-shops.com/api/v2/token/refresh'
            && $request['app_key'] === 'test-app-key'
            && $request['app_secret'] === 'test-app-secret'
            && $request['refresh_token'] === 'current-refresh-token'
            && $request['grant_type'] === 'refresh_token');
    }

    public function test_tiktok_shop_data_command_reads_products_without_printing_sensitive_fields(): void
    {
        $this->configureTikTokShop();
        config()->set('services.tiktok.api_url', 'https://open-api.tiktokglobalshop.com');

        $authorization = TikTokShopAuthorization::query()->create([
            'app_key' => 'test-app-key',
            'open_id' => 'seller-open-id',
            'seller_name' => 'Anker Philippines',
            'seller_base_region' => 'PH',
            'user_type' => 0,
            'access_token' => 'access-token-value',
            'refresh_token' => 'refresh-token-value',
            'access_token_expires_at' => now()->addWeek(),
            'refresh_token_expires_at' => now()->addYear(),
        ]);
        TikTokShop::query()->create([
            'tik_tok_shop_authorization_id' => $authorization->id,
            'shop_id' => 'shop-id',
            'shop_cipher' => 'secret-shop-cipher',
            'name' => 'Anker Philippines',
            'region' => 'PH',
        ]);

        Http::preventStrayRequests();
        Http::fake([
            'https://open-api.tiktokglobalshop.com/product/202502/products/search*' => Http::response([
                'code' => 0,
                'message' => 'Success',
                'data' => ['products' => [['id' => 'product-id'], ['id' => 'product-id-2']]],
            ]),
        ]);

        $this->artisan('tiktok:shop:data products --days=7 --limit=20')
            ->expectsOutputToContain('- products: connected; first-page records=2')
            ->doesntExpectOutputToContain('secret-shop-cipher')
            ->doesntExpectOutputToContain('access-token-value')
            ->assertSuccessful();
    }

    public function test_tiktok_shop_mcp_requires_bearer_authentication(): void
    {
        config()->set('services.tiktok.mcp_bearer_token', 'mcp-secret');

        $this->postJson('/api/tiktok/mcp', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/list',
        ])->assertUnauthorized();
    }

    public function test_tiktok_shop_mcp_lists_read_only_tools(): void
    {
        config()->set('services.tiktok.mcp_bearer_token', 'mcp-secret');

        $response = $this->withToken('mcp-secret')->postJson('/api/tiktok/mcp', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/list',
        ]);

        $response->assertOk()
            ->assertJsonPath('jsonrpc', '2.0')
            ->assertJsonCount(6, 'result.tools')
            ->assertJsonPath('result.tools.0.annotations.readOnlyHint', true)
            ->assertJsonPath('result.tools.0.annotations.destructiveHint', false);
    }

    public function test_tiktok_shop_mcp_returns_redacted_commercial_data(): void
    {
        $this->configureTikTokShop();
        config()->set([
            'services.tiktok.api_url' => 'https://open-api.tiktokglobalshop.com',
            'services.tiktok.mcp_bearer_token' => 'mcp-secret',
        ]);

        $authorization = TikTokShopAuthorization::query()->create([
            'app_key' => 'test-app-key',
            'open_id' => 'seller-open-id',
            'seller_name' => 'Anker Philippines',
            'seller_base_region' => 'PH',
            'user_type' => 0,
            'access_token' => 'access-token-value',
            'refresh_token' => 'refresh-token-value',
            'access_token_expires_at' => now()->addWeek(),
            'refresh_token_expires_at' => now()->addYear(),
        ]);
        TikTokShop::query()->create([
            'tik_tok_shop_authorization_id' => $authorization->id,
            'shop_id' => 'shop-id',
            'shop_cipher' => 'secret-shop-cipher',
            'name' => 'Anker Philippines',
            'region' => 'PH',
        ]);

        Http::preventStrayRequests();
        Http::fake([
            'https://open-api.tiktokglobalshop.com/product/202502/products/search*' => Http::response([
                'code' => 0,
                'message' => 'Success',
                'data' => [
                    'products' => [[
                        'id' => 'product-id',
                        'title' => 'Power Bank',
                        'buyer_email' => 'customer@example.com',
                        'shipping_address' => 'private address',
                    ]],
                ],
            ]),
        ]);

        $response = $this->withToken('mcp-secret')->postJson('/api/tiktok/mcp', [
            'jsonrpc' => '2.0',
            'id' => 2,
            'method' => 'tools/call',
            'params' => [
                'name' => 'tiktok_shop_products',
                'arguments' => ['limit' => 20],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('result.isError', false)
            ->assertJsonPath('result.structuredContent.source', 'TikTok Shop Open API')
            ->assertJsonPath('result.structuredContent.data.products.0.title', 'Power Bank')
            ->assertJsonMissing(['buyer_email' => 'customer@example.com'])
            ->assertJsonMissing(['shipping_address' => 'private address']);

        $this->assertStringNotContainsString('access-token-value', $response->getContent());
        $this->assertStringNotContainsString('secret-shop-cipher', $response->getContent());
    }

    public function test_tiktok_callback_returns_502_when_identity_is_not_a_seller(): void
    {
        $this->configureTikTokShop();
        Http::preventStrayRequests();
        Http::fake([
            'https://auth.tiktok-shops.com/api/v2/token/get*' => Http::response([
                'code' => 0,
                'message' => 'success',
                'data' => [
                    'access_token' => 'creator-access-token',
                    'access_token_expire_in' => 1788872400,
                    'refresh_token' => 'creator-refresh-token',
                    'refresh_token_expire_in' => 1820408400,
                    'open_id' => 'creator-open-id',
                    'user_type' => 1,
                ],
            ]),
        ]);

        $response = $this->getJson('/tiktok/callback?code=one-time-code&app_key=test-app-key');

        $response->assertBadGateway()->assertExactJson([
            'status' => 'authorization_failed',
            'message' => 'TikTok Shop authorization did not return a seller identity.',
        ]);
        $this->assertDatabaseCount('tik_tok_shop_authorizations', 0);
    }

    private function configureTikTokShop(): void
    {
        config()->set([
            'app.key' => 'base64:'.base64_encode(str_repeat('a', 32)),
            'services.tiktok.app_key' => 'test-app-key',
            'services.tiktok.app_secret' => 'test-app-secret',
            'services.tiktok.token_url' => 'https://auth.tiktok-shops.com/api/v2/token/get',
            'services.tiktok.refresh_url' => 'https://auth.tiktok-shops.com/api/v2/token/refresh',
        ]);
    }
}

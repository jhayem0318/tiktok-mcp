# TikTok MCP

Read-only TikTok Shop OAuth and Open API backend.

## Current endpoints

- `GET /` returns the service status.
- `GET /up` is Laravel's deployment health check.
- `GET /tiktok/callback` exchanges a one-time seller authorization code and stores encrypted tokens.
- Signed Open API requests retrieve authorized shops, analytics, orders, products, finance, returns, and promotions.
- Access tokens refresh automatically before expiry.

## Local setup

Requirements: PHP 8.3+, Composer, and the PHP extensions required by Laravel.

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
php artisan serve
```

Open `http://localhost:8000/tiktok/callback` to verify the callback.

Run the checks with:

```bash
composer test
```

## TikTok OAuth configuration

The application reads TikTok settings from environment variables through `config/services.php`:

```dotenv
TIKTOK_CLIENT_KEY=
TIKTOK_CLIENT_SECRET=
TIKTOK_REDIRECT_URI="https://your-domain.example/tiktok/callback"
TIKTOK_AUTHORIZATION_URL=
TIKTOK_TOKEN_URL=
TIKTOK_REFRESH_URL=
TIKTOK_API_URL=
TIKTOK_SCOPES=
```

Do not commit real credentials. Set them in the deployment platform's secret or environment-variable settings.

## Read-only commands

```bash
php artisan tiktok:shop:test
php artisan tiktok:shop:refresh
php artisan tiktok:shop:data all --days=7 --limit=20
php artisan tiktok:shop:data analytics --days=7
php artisan tiktok:shop:data orders --days=7
php artisan tiktok:shop:data products
php artisan tiktok:shop:data finance --days=7
php artisan tiktok:shop:data returns --days=7
php artisan tiktok:shop:data promotions
```

Commands output aggregated connection and record-count information only. They do not print tokens, shop ciphers, or customer-level fields.

## Codex MCP connection

The authenticated Streamable HTTP endpoint is `POST /api/tiktok/mcp`. It provides six read-only tools for analytics, orders, products, finance, returns, and promotions. Customer, address, contact, token, shop-cipher, and order-identifying fields are removed from tool results.

Set a unique high-entropy value for `TIKTOK_MCP_BEARER_TOKEN` in the deployment environment. Store that same value locally in an environment variable, then connect Codex:

```bash
codex mcp add tiktok-shop \
  --url https://your-domain.example/api/tiktok/mcp \
  --bearer-token-env-var TIKTOK_SHOP_MCP_TOKEN
```

Never commit either bearer-token value. Restart or open a new Codex session after adding the server so its tools can be loaded.

## Deployment checklist

1. Configure the web root as the repository's `public/` directory.
2. Use PHP 8.3 or newer and install dependencies with `composer install --no-dev --optimize-autoloader`.
3. Copy the environment-variable names from `.env.example`; set `APP_ENV=production`, `APP_DEBUG=false`, the public HTTPS `APP_URL`, and a unique `APP_KEY` generated with `php artisan key:generate --show`.
4. Use persistent production services for the database, cache, sessions, and queues before enabling OAuth token storage or background jobs.
5. Run `php artisan migrate --force` during release and `php artisan optimize` after environment variables are available.
6. Point the platform health check to `/up` and verify `/tiktok/callback` over HTTPS.
7. Register the exact HTTPS callback URL in TikTok's developer console.

The standard Laravel `.gitignore` excludes `.env`, vendor packages, local databases, caches, and generated assets. Repository visibility remains controlled in Bitbucket and should stay private.

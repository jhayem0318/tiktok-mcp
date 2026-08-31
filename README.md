# TikTok MCP

Minimal Laravel service that will become the TikTok Shop OAuth and MCP backend.

## Current endpoints

- `GET /` returns the service status.
- `GET /up` is Laravel's deployment health check.
- `GET /tiktok/callback` returns `{"status":"TikTok callback ready"}`.

The callback is intentionally a readiness endpoint for now. OAuth code exchange and token storage should be added only after the TikTok application credentials and required scopes are confirmed.

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
TIKTOK_SCOPES=
```

Do not commit real credentials. Set them in the deployment platform's secret or environment-variable settings. The authorization and token URLs are placeholders because TikTok Shop endpoints can depend on the application's market and approved API version.

## Deployment checklist

1. Configure the web root as the repository's `public/` directory.
2. Use PHP 8.3 or newer and install dependencies with `composer install --no-dev --optimize-autoloader`.
3. Copy the environment-variable names from `.env.example`; set `APP_ENV=production`, `APP_DEBUG=false`, the public HTTPS `APP_URL`, and a unique `APP_KEY` generated with `php artisan key:generate --show`.
4. Use persistent production services for the database, cache, sessions, and queues before enabling OAuth token storage or background jobs.
5. Run `php artisan migrate --force` during release and `php artisan optimize` after environment variables are available.
6. Point the platform health check to `/up` and verify `/tiktok/callback` over HTTPS.
7. Register the exact HTTPS callback URL in TikTok's developer console.

The standard Laravel `.gitignore` excludes `.env`, vendor packages, local databases, caches, and generated assets. Repository visibility remains controlled in Bitbucket and should stay private.

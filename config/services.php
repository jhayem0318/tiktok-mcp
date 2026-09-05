<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'tiktok' => [
        'app_key' => env('TIKTOK_APP_KEY', env('TIKTOK_CLIENT_KEY')),
        'app_secret' => env('TIKTOK_APP_SECRET', env('TIKTOK_CLIENT_SECRET')),
        'redirect_uri' => env('TIKTOK_REDIRECT_URI'),
        'authorization_url' => env('TIKTOK_AUTHORIZATION_URL'),
        'token_url' => env('TIKTOK_TOKEN_URL', 'https://auth.tiktok-shops.com/api/v2/token/get'),
        'refresh_url' => env('TIKTOK_REFRESH_URL', 'https://auth.tiktok-shops.com/api/v2/token/refresh'),
        'api_url' => env('TIKTOK_API_URL', 'https://open-api.tiktokglobalshop.com'),
        'scopes' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('TIKTOK_SCOPES', '')),
        ))),
        'mcp_bearer_token' => env('TIKTOK_MCP_BEARER_TOKEN'),
        'review_username' => env('TIKTOK_REVIEW_USERNAME'),
        'review_password' => env('TIKTOK_REVIEW_PASSWORD'),
        'admin_username' => env('TIKTOK_ADMIN_USERNAME'),
        'admin_password' => env('TIKTOK_ADMIN_PASSWORD'),
        'remote_mcp_enabled' => env('TIKTOK_REMOTE_MCP_ENABLED', false),
        'remote_mcp_access_token_ttl' => (int) env('TIKTOK_REMOTE_MCP_ACCESS_TOKEN_TTL', 3600),
        'remote_mcp_authorization_code_ttl' => (int) env('TIKTOK_REMOTE_MCP_AUTHORIZATION_CODE_TTL', 300),
    ],

];

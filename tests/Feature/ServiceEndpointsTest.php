<?php

namespace Tests\Feature;

use Tests\TestCase;

class ServiceEndpointsTest extends TestCase
{
    public function test_service_root_returns_status_payload(): void
    {
        $response = $this->get('/');

        $response->assertOk()->assertExactJson([
            'service' => 'tiktok-mcp',
            'status' => 'ok',
        ]);
    }

    public function test_tiktok_callback_returns_readiness_payload(): void
    {
        $response = $this->get('/tiktok/callback');

        $response->assertOk()->assertExactJson([
            'status' => 'TikTok callback ready',
        ]);
    }
}

<?php

namespace Tests\Feature;

use Tests\TestCase;

class CorsConfigurationTest extends TestCase
{
    public function test_configured_frontend_origin_receives_cors_headers(): void
    {
        $allowedOrigin = config('cors.allowed_origins')[0];

        $this->assertSame('api/*', config('cors.paths')[0]);
        $this->assertNotContains('*', config('cors.allowed_origins'));
        $this->assertFalse(config('cors.supports_credentials'));

        $this->withHeader('Origin', $allowedOrigin)
            ->getJson('/api/me')
            ->assertUnauthorized()
            ->assertHeader('Access-Control-Allow-Origin', $allowedOrigin);
    }

    public function test_unconfigured_origin_does_not_receive_cors_permission(): void
    {
        $disallowedOrigin = 'https://not-allowed.example';
        $this->assertNotContains($disallowedOrigin, config('cors.allowed_origins'));

        $response = $this->withHeader('Origin', $disallowedOrigin)
            ->getJson('/api/me')
            ->assertUnauthorized();

        $this->assertNotSame(
            $disallowedOrigin,
            $response->headers->get('Access-Control-Allow-Origin')
        );
    }

    public function test_preflight_allows_authorized_api_headers_and_methods(): void
    {
        $allowedOrigin = config('cors.allowed_origins')[0];

        $response = $this->withHeaders([
            'Origin' => $allowedOrigin,
            'Access-Control-Request-Method' => 'PATCH',
            'Access-Control-Request-Headers' => 'authorization,content-type',
        ])->options('/api/me')
            ->assertNoContent()
            ->assertHeader('Access-Control-Allow-Origin', $allowedOrigin)
            ->assertHeader('Access-Control-Max-Age', '600');

        $this->assertFalse($response->headers->has('Access-Control-Allow-Credentials'));

        $this->assertStringContainsString('PATCH', $response->headers->get('Access-Control-Allow-Methods'));
        $allowedHeaders = strtolower($response->headers->get('Access-Control-Allow-Headers'));
        $this->assertStringContainsString('authorization', $allowedHeaders);
        $this->assertStringContainsString('content-type', $allowedHeaders);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class SwaggerDocumentationTest extends TestCase
{
    public function test_can_access_swagger_ui_page(): void
    {
        $response = $this->get('/docs');

        $response->assertStatus(200)
            ->assertSee('SwaggerUIBundle')
            ->assertSee('/api/v1/openapi.json');
    }

    public function test_can_fetch_openapi_json_specification(): void
    {
        $response = $this->get('/api/v1/openapi.json');

        $response->assertStatus(200);
        $this->assertStringContainsString('application/json', $response->headers->get('content-type') ?? '');

        $json = $response->json();
        $this->assertEquals('3.0.3', $json['openapi']);
        $this->assertEquals('UII Event Management API', $json['info']['title']);
        $this->assertArrayHasKey('/api/v1/auth/login', $json['paths']);
        $this->assertArrayHasKey('/api/v1/organizer/upload-poster', $json['paths']);
        $this->assertArrayHasKey('/api/v1/attendance/check-in', $json['paths']);
    }
}

<?php

namespace Tests\Feature;

use Tests\TestCase;

class HomePageTest extends TestCase
{
    public function test_home_page_renders_private_tet_archive_shell_without_secrets(): void
    {
        config([
            'app.key' => 'base64:QUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUE=',
            'database.connections.pgsql.host' => 'sensitive-db-host.example',
            'filesystems.disks.r2.endpoint' => 'https://sensitive-r2-endpoint.example',
        ]);

        $response = $this->get('/');
        $html = $response->getContent();

        $response->assertOk()->assertSeeText('Ký ức Tết');
        $this->assertStringContainsString('/build/assets/app-', $html);
        $this->assertStringContainsString('.css', $html);
        $this->assertStringContainsString('.js', $html);
        $this->assertStringNotContainsString('QUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUE=', $html);
        $this->assertStringNotContainsString('sensitive-db-host.example', $html);
        $this->assertStringNotContainsString('sensitive-r2-endpoint.example', $html);
        $this->assertStringNotContainsString('Stack trace', $html);
    }

    public function test_health_endpoint_is_available(): void
    {
        $this->get('/up')->assertOk();
    }
}

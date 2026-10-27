<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
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
        $this->assertStringNotContainsString('QUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUE=', $html);
        $this->assertStringNotContainsString('sensitive-db-host.example', $html);
        $this->assertStringNotContainsString('sensitive-r2-endpoint.example', $html);
        $this->assertStringNotContainsString('Stack trace', $html);
    }

    public function test_health_endpoint_is_available(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_vercel_forwarded_https_headers_generate_secure_asset_urls(): void
    {
        Route::get('/proxy-probe', fn (): string => asset('build/assets/app.js'));

        $response = $this->withHeaders([
            'X-Forwarded-Host' => 'family.example',
            'X-Forwarded-Proto' => 'https',
        ])->get('/proxy-probe');

        $response->assertOk();
        $response->assertSeeText('https://family.example/build/assets/app.js');
    }
}

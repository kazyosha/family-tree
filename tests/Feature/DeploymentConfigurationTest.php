<?php

namespace Tests\Feature;

use Tests\TestCase;

class DeploymentConfigurationTest extends TestCase
{
    public function test_vercel_container_contract_is_declared(): void
    {
        $dockerfile = file_get_contents(base_path('Dockerfile.vercel'));
        $caddyfile = file_get_contents(base_path('Caddyfile'));
        $home = file_get_contents(resource_path('views/home.blade.php'));
        $vercel = json_decode(file_get_contents(base_path('vercel.json')), true, flags: JSON_THROW_ON_ERROR);

        $this->assertStringContainsString('dunglas/frankenphp:1-php8.5-trixie', $dockerfile);
        $this->assertStringContainsString('npm ci', $dockerfile);
        $this->assertStringContainsString('composer install', $dockerfile);
        $this->assertStringContainsString('pdo_pgsql', $dockerfile);
        $this->assertStringContainsString('USER www-data', $dockerfile);
        $this->assertStringContainsString('CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]', $dockerfile);

        $this->assertStringContainsString(':{$PORT:80}', $caddyfile);
        $this->assertStringContainsString('root * /app/public', $caddyfile);
        $this->assertStringContainsString('php_server', $caddyfile);
        $this->assertStringContainsString('auto_https off', $caddyfile);
        $this->assertStringContainsString('@dotfiles path_regexp', $caddyfile);
        $this->assertStringContainsString("@vite(['resources/css/app.css', 'resources/js/app.js'])", $home);

        $this->assertSame('https://openapi.vercel.sh/vercel.json', $vercel['$schema']);
    }

    public function test_docker_context_excludes_secrets_and_local_artifacts(): void
    {
        $dockerignore = file_get_contents(base_path('.dockerignore'));

        foreach (['.env*', '.git', 'node_modules', 'vendor', 'tests', 'storage/logs', 'outputs', 'work'] as $entry) {
            $this->assertStringContainsString($entry, $dockerignore);
        }

        $this->assertStringContainsString('!.env.example', $dockerignore);
    }

    public function test_secret_scan_checks_history_and_the_example_environment_file(): void
    {
        $workflow = file_get_contents(base_path('.github/workflows/ci.yml'));

        $this->assertStringContainsString('git rev-list --all', $workflow);
        $this->assertStringNotContainsString(':!.env.example', $workflow);
        $this->assertStringContainsString('docker://ghcr.io/gitleaks/gitleaks@sha256:691af3c7c5a48b16f187ce3446d5f194838f91238f27270ed36eef6359a574d9', $workflow);
        $this->assertStringContainsString('args: git --redact --verbose /github/workspace', $workflow);
        $this->assertStringNotContainsString('gitleaks/gitleaks-action', $workflow);
    }
}

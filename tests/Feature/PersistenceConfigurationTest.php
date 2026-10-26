<?php

namespace Tests\Feature;

use Dotenv\Repository\Adapter\ArrayAdapter;
use Dotenv\Repository\RepositoryBuilder;
use Illuminate\Support\Env;
use ReflectionClass;
use Tests\TestCase;

class PersistenceConfigurationTest extends TestCase
{
    /** @var array<string, mixed> */
    private mixed $originalRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalRepository = Env::getRepository();
    }

    protected function tearDown(): void
    {
        $this->replaceEnvironmentRepository($this->originalRepository);

        parent::tearDown();
    }

    public function test_production_uses_pgsql_database_sessions_and_database_cache(): void
    {
        $this->setEnvironment([
            'APP_ENV' => 'production',
            'APP_DEBUG' => null,
            'DB_CONNECTION' => null,
            'SESSION_DRIVER' => null,
            'CACHE_STORE' => null,
            'LOG_CHANNEL' => null,
        ]);

        $this->assertFalse($this->loadConfig('app')['debug']);
        $this->assertSame('pgsql', $this->loadConfig('database')['default']);
        $this->assertSame('database', $this->loadConfig('session')['driver']);
        $this->assertSame('database', $this->loadConfig('cache')['default']);
        $this->assertSame('stderr', $this->loadConfig('logging')['default']);
        $this->assertNotSame('file', $this->loadConfig('session')['driver']);
        $this->assertNotSame('file', $this->loadConfig('cache')['default']);
    }

    public function test_pgsql_requires_tls(): void
    {
        $this->setEnvironment(['DB_SSLMODE' => 'require']);

        $this->assertSame('require', $this->loadConfig('database')['connections']['pgsql']['sslmode']);
    }

    public function test_local_test_environment_remains_isolated(): void
    {
        $this->assertSame('testing', app()->environment());
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->assertSame('array', config('session.driver'));
        $this->assertSame('array', config('cache.default'));
    }

    /** @param array<string, string|null> $values */
    private function setEnvironment(array $values): void
    {
        $repository = RepositoryBuilder::createWithNoAdapters()
            ->addAdapter(ArrayAdapter::class)
            ->make();

        foreach ($values as $key => $value) {
            $value === null ? $repository->clear($key) : $repository->set($key, $value);
        }

        $this->replaceEnvironmentRepository($repository);
    }

    /** @return array<string, mixed> */
    private function loadConfig(string $name): array
    {
        return require base_path("config/{$name}.php");
    }

    private function replaceEnvironmentRepository(mixed $repository): void
    {
        $property = (new ReflectionClass(Env::class))->getProperty('repository');
        $property->setValue(null, $repository);
    }
}

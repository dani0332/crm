<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Facade;
use Spatie\Permission\PermissionRegistrar;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        // Force SQLite for tests regardless of environment variables
        // This ensures tests use in-memory database even when Doppler is active
        if (app()->environment('testing')) {
            config(['database.default' => 'sqlite']);
            config(['database.connections.sqlite' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ]]);

            // Clear any existing connections to force reconnection with new config
            app('db')->purge('mysql');
            app('db')->purge('sqlite');

            // Reconnect to ensure clean state
            app('db')->reconnect('sqlite');
        }
    }

    protected function tearDown(): void
    {
        // Clear permission cache after each test to prevent state pollution
        if (class_exists(PermissionRegistrar::class)) {
            app()[PermissionRegistrar::class]->forgetCachedPermissions();
        }

        // Close Mockery to prevent mock state pollution between tests
        \Mockery::close();

        // Clear any resolved Facade instances to prevent state pollution
        Facade::clearResolvedInstances();

        parent::tearDown();
    }
}

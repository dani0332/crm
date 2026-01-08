<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

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
        }
    }
}

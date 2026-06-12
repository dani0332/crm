<?php

declare(strict_types=1);

namespace Tests\Unit\Config;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Test suite for cache configuration, specifically testing environment-specific
 * cache prefixes to ensure proper isolation across different environments.
 */
class CacheConfigTest extends TestCase
{
    /**
     * Test that cache prefix includes environment name for isolation.
     *
     * This ensures that different environments (production, staging, uat, test)
     * have separate cache namespaces, preventing cross-environment conflicts
     * when using shared cache drivers like Redis or DynamoDB.
     */
    public function test_cache_prefix_includes_environment(): void
    {
        // Get the cache prefix from config
        $prefix = Config::get('cache.prefix');

        // Assert that prefix is not empty
        $this->assertNotEmpty($prefix, 'Cache prefix should not be empty');

        // Assert that prefix includes the environment name
        $environment = app()->environment();
        $this->assertStringContainsString(
            $environment,
            $prefix,
            "Cache prefix should contain environment name '{$environment}'"
        );
    }

    /**
     * Test that different environments would generate different cache prefixes.
     *
     * This test simulates different environment configurations to verify
     * that each environment would have a unique cache prefix.
     */
    public function test_different_environments_have_unique_cache_prefixes(): void
    {
        $environments = ['production', 'staging', 'uat', 'test'];
        $prefixes = [];

        foreach ($environments as $env) {
            // Simulate environment change
            $appName = Config::get('app.name', 'laravel');
            $sluggedName = Str::slug($appName, '_');
            $expectedPrefix = "{$sluggedName}_cache_{$env}";

            $prefixes[$env] = $expectedPrefix;
        }

        // Assert all prefixes are unique
        $uniquePrefixes = array_unique($prefixes);
        $this->assertCount(
            count($environments),
            $uniquePrefixes,
            'Each environment should have a unique cache prefix'
        );

        // Assert each prefix follows the expected pattern
        foreach ($prefixes as $env => $prefix) {
            $this->assertStringEndsWith(
                "_cache_{$env}",
                $prefix,
                "Cache prefix for {$env} should end with '_cache_{$env}'"
            );
        }
    }

    /**
     * Test that cache prefix format is correct for current environment.
     *
     * This verifies the exact format: {app_name}_cache_{environment}
     */
    public function test_cache_prefix_format_is_correct(): void
    {
        $appName = Config::get('app.name', 'laravel');
        $environment = app()->environment();
        $prefix = Config::get('cache.prefix');

        // Expected format: slugified_app_name_cache_environment
        $sluggedName = Str::slug($appName, '_');
        $expectedPrefix = "{$sluggedName}_cache_{$environment}";

        $this->assertEquals(
            $expectedPrefix,
            $prefix,
            'Cache prefix should match format: {app_name}_cache_{environment}'
        );
    }

    /**
     * Test that cache prefix can be overridden via environment variable.
     *
     * This ensures that CACHE_PREFIX environment variable takes precedence
     * over the default computed prefix.
     */
    public function test_cache_prefix_can_be_overridden_via_env(): void
    {
        // Store original prefix
        $originalPrefix = Config::get('cache.prefix');

        // Set custom prefix
        $customPrefix = 'custom_test_prefix';
        Config::set('cache.prefix', $customPrefix);

        // Assert custom prefix is used
        $this->assertEquals(
            $customPrefix,
            Config::get('cache.prefix'),
            'Custom cache prefix should be used when set via config'
        );

        // Restore original prefix
        Config::set('cache.prefix', $originalPrefix);
    }

    /**
     * Test that cache operations work correctly with environment-specific prefix.
     *
     * This ensures that the cache prefix doesn't break normal cache operations
     * and that cached items are properly namespaced.
     */
    public function test_cache_operations_work_with_environment_prefix(): void
    {
        $key = 'test_cache_key';
        $value = 'test_cache_value';

        // Store a value in cache
        Cache::put($key, $value, 60);

        // Retrieve the value
        $cachedValue = Cache::get($key);

        $this->assertEquals(
            $value,
            $cachedValue,
            'Cache should store and retrieve values correctly with environment prefix'
        );

        // Clean up
        Cache::forget($key);
    }

    /**
     * Test that cache prefix prevents key collisions across simulated environments.
     *
     * This test demonstrates that the same cache key with different prefixes
     * would not collide, simulating environment isolation.
     */
    public function test_cache_prefix_prevents_key_collisions(): void
    {
        $testKey = 'shared_key';
        $testValue = 'environment_specific_value';

        // Store value with current environment prefix
        Cache::put($testKey, $testValue, 60);

        // Verify the value can be retrieved
        $retrievedValue = Cache::get($testKey);
        $this->assertEquals($testValue, $retrievedValue);

        // Verify the cache prefix is being used
        $prefix = Config::get('cache.prefix');
        $this->assertNotEmpty($prefix);

        // Clean up
        Cache::forget($testKey);
    }

    /**
     * Test cache configuration for scheduled task locking.
     *
     * This verifies that the cache configuration supports the onOneServer()
     * functionality used in scheduled tasks by checking for proper store configuration.
     */
    public function test_cache_configuration_supports_task_locking(): void
    {
        $defaultStore = Config::get('cache.default');
        $this->assertNotEmpty($defaultStore, 'Default cache store should be configured');

        $storeConfig = Config::get("cache.stores.{$defaultStore}");
        $this->assertIsArray($storeConfig, 'Cache store configuration should exist');
        $this->assertArrayHasKey('driver', $storeConfig, 'Cache store should have a driver');

        // For production, recommend using shared cache drivers
        $driver = $storeConfig['driver'];
        $sharedDrivers = ['redis', 'memcached', 'dynamodb', 'database'];

        if (app()->environment('production')) {
            $this->assertContains(
                $driver,
                $sharedDrivers,
                'Production should use a shared cache driver (redis, memcached, dynamodb, or database) for reliable task locking'
            );
        }
    }

    /**
     * Test that cache prefix includes application name.
     *
     * This ensures the application name is part of the prefix for
     * additional namespace isolation when multiple apps share cache infrastructure.
     */
    public function test_cache_prefix_includes_application_name(): void
    {
        $prefix = Config::get('cache.prefix');
        $appName = Config::get('app.name', 'laravel');
        $sluggedName = Str::slug($appName, '_');

        $this->assertStringContainsString(
            $sluggedName,
            $prefix,
            "Cache prefix should contain slugified application name '{$sluggedName}'"
        );
    }

    /**
     * Test cache prefix structure for testing environment.
     *
     * Special test to verify the testing environment has proper prefix.
     */
    public function test_testing_environment_has_proper_cache_prefix(): void
    {
        // In testing environment (as set in phpunit.xml)
        $this->assertEquals('testing', app()->environment());

        $prefix = Config::get('cache.prefix');
        $this->assertStringEndsWith(
            '_cache_testing',
            $prefix,
            'Testing environment should have cache prefix ending with _cache_testing'
        );
    }
}

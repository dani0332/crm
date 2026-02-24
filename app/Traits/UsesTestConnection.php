<?php

namespace App\Traits;

/**
 * Trait for models that need to use the default database connection in testing environment.
 *
 * This trait allows models with hardcoded database connections (e.g., 'mysql', 'mongodb')
 * to automatically use the default connection (SQLite) when running tests, while maintaining
 * their production connection in other environments.
 *
 * Usage:
 * ```php
 * class MyModel extends Model
 * {
 *     use UsesTestConnection;
 *
 *     protected $connection = 'mysql'; // or 'mongodb', etc.
 * }
 * ```
 */
trait UsesTestConnection
{
    /**
     * Get the database connection name for the model.
     * In testing environment, use the default connection (SQLite) instead of hardcoded connection.
     *
     * @return string|null
     */
    public function getConnectionName()
    {
        // In testing environment, use the default connection (SQLite)
        if (app()->environment('testing')) {
            return config('database.default');
        }

        // In production/other environments, use the model's configured connection
        return $this->connection;
    }
}

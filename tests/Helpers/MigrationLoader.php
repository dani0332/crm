<?php

namespace Tests\Helpers;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class MigrationLoader
{
    /**
     * Load migrations from external path if configured.
     *
     * @return void
     */
    public static function loadExternalMigrations(): void
    {
        $externalPath = config('testing.external_migrations_path');

        if (! $externalPath || ! File::exists($externalPath)) {
            return;
        }

        // Get all migration files from external path
        $migrationFiles = File::glob($externalPath.'/*.php');

        if (empty($migrationFiles)) {
            return;
        }

        // Sort migrations by filename to ensure proper order
        sort($migrationFiles);

        // Use Laravel's migration system
        // First, ensure migrations table exists
        if (! \Illuminate\Support\Facades\Schema::connection('sqlite')->hasTable('migrations')) {
            Artisan::call('migrate:install', ['--database' => 'sqlite']);
        }

        // Get the migrator instance
        $migrator = app('migrator');
        $migrator->setConnection('sqlite');

        // Run each migration file
        foreach ($migrationFiles as $migrationFile) {
            $migrationName = basename($migrationFile, '.php');
            
            // Skip files with double .php extension (corrupted filenames)
            if (str_ends_with($migrationFile, '.php.php')) {
                continue;
            }
            
            // Check if already migrated
            $exists = \Illuminate\Support\Facades\DB::connection('sqlite')
                ->table('migrations')
                ->where('migration', $migrationName)
                ->exists();
            
            if (! $exists) {
                try {
                    // Get the migration class
                    require_once $migrationFile;
                    
                    // Extract class name from file
                    $className = self::getMigrationClassName($migrationFile);
                    if ($className && class_exists($className)) {
                        $migration = new $className;
                        $migration->up();
                        
                        // Record in migrations table
                        \Illuminate\Support\Facades\DB::connection('sqlite')
                            ->table('migrations')
                            ->insert([
                                'migration' => $migrationName,
                                'batch' => 1,
                            ]);
                    }
                } catch (\Exception $e) {
                    // Skip migrations that fail (e.g., trying to alter non-existent tables)
                    // This is expected for some migrations that depend on tables not needed for tests
                    continue;
                }
            }
        }
    }

    /**
     * Get migration class name from file path.
     *
     * @param  string  $filePath
     * @return string|null
     */
    protected static function getMigrationClassName(string $filePath): ?string
    {
        $content = File::get($filePath);

        if (preg_match('/class\s+(\w+)\s+extends/', $content, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Run migrations from a specific directory.
     *
     * @param  string  $path
     * @return void
     */
    public static function runMigrationsFromPath(string $path): void
    {
        if (! File::exists($path)) {
            return;
        }

        $migrationFiles = File::glob($path.'/*.php');
        sort($migrationFiles);

        foreach ($migrationFiles as $migrationFile) {
            Artisan::call('migrate', [
                '--path' => $migrationFile,
                '--force' => true,
            ]);
        }
    }
}


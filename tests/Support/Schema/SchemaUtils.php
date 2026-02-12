<?php

namespace Tests\Support\Schema;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Facades\Schema;

class SchemaUtils
{
    private const CONNECTION = 'sqlite';

    public static function builder(): Builder
    {
        return Schema::connection(self::CONNECTION);
    }

    public static function ensureTables(array $definitions): void
    {
        foreach ($definitions as $table => $definition) {
            self::ensureTable($table, $definition);
        }
    }

    public static function ensureTable(string $table, callable $definition): void
    {
        $schema = self::builder();

        if (! $schema->hasTable($table)) {
            $schema->create($table, $definition);
        }
    }

    public static function addColumnIfMissing(string $table, string $column, callable $callback): void
    {
        $schema = self::builder();

        if (! $schema->hasColumn($table, $column)) {
            $schema->table($table, function (Blueprint $table) use ($callback) {
                $callback($table);
            });
        }
    }
    public static function ensureColumns(array $definitions): void
    {
        foreach ($definitions as $table => $columns) {
            foreach ($columns as $column => $callback) {
                self::addColumnIfMissing($table, $column, $callback);
            }
        }
    }
}

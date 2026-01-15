<?php

declare(strict_types=1);

/**
 * Validates the integrity of TestSchemaCreator.php.
 *
 * These tests ensure the test infrastructure itself is valid
 * before running any tests that depend on it.
 */
describe('TestSchemaCreator Validation', function () {
    test('must not have duplicate table creation entries', function () {
        $schemaFiles = [
            dirname(__DIR__).'/Support/Schema/CoreSchema.php',
            dirname(__DIR__).'/Support/Schema/RenewalsSchema.php',
        ];

        $allTableNames = [];

        foreach ($schemaFiles as $filePath) {
            expect(file_exists($filePath))->toBeTrue("Schema file {$filePath} should exist");

            $content = file_get_contents($filePath);

            // Match all table creation patterns: 'table_name' => function
            preg_match_all("/'([^']+)'\s*=>\s*function/", $content, $matches);

            $tableNames = $matches[1] ?? [];
            $allTableNames = array_merge($allTableNames, $tableNames);
        }

        expect($allTableNames)->not->toBeEmpty('Should have table creation statements');

        // Find duplicates
        $counts = array_count_values($allTableNames);
        $duplicates = array_filter($counts, fn ($count) => $count > 1);
        expect($duplicates)
            ->toBeEmpty('Duplicate table entries found: '.implode(', ', array_keys($duplicates)));
    });

    test('all table names should be valid identifiers', function () {
        $schemaFiles = [
            dirname(__DIR__).'/Support/Schema/CoreSchema.php',
            dirname(__DIR__).'/Support/Schema/RenewalsSchema.php',
        ];

        $allTableNames = [];

        foreach ($schemaFiles as $filePath) {
            $content = file_get_contents($filePath);

            // Match all table creation patterns: 'table_name' => function
            preg_match_all("/'([^']+)'\s*=>\s*function/", $content, $matches);

            $tableNames = $matches[1] ?? [];
            $allTableNames = array_merge($allTableNames, $tableNames);
        }

        foreach ($allTableNames as $tableName) {
            // Table names should be lowercase, use underscores, and start with a letter
            expect($tableName)->toMatch(
                '/^[a-z][a-z0-9_]*$/',
                "Table name '{$tableName}' should be lowercase with underscores"
            );
        }
    });
});

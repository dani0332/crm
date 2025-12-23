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
        $filePath = dirname(__DIR__).'/Helpers/TestSchemaCreator.php';

        expect(file_exists($filePath))->toBeTrue('TestSchemaCreator.php should exist');

        $content = file_get_contents($filePath);

        // Match all table creation patterns: ->create('table_name', ...)
        preg_match_all('/->create\s*\(\s*[\'"]([^\'"]+)[\'"]\s*,/', $content, $matches);

        $tableNames = $matches[1] ?? [];

        expect($tableNames)->not->toBeEmpty('Should have table creation statements');

        // Find duplicates
        $counts = array_count_values($tableNames);
        $duplicates = array_filter($counts, fn ($count) => $count > 1);

        expect($duplicates)
            ->toBeEmpty('Duplicate table entries found: '.implode(', ', array_keys($duplicates)));
    });

    test('all table names should be valid identifiers', function () {
        $filePath = dirname(__DIR__).'/Helpers/TestSchemaCreator.php';
        $content = file_get_contents($filePath);

        preg_match_all('/->create\s*\(\s*[\'"]([^\'"]+)[\'"]\s*,/', $content, $matches);

        $tableNames = $matches[1] ?? [];

        foreach ($tableNames as $tableName) {
            // Table names should be lowercase, use underscores, and start with a letter
            expect($tableName)->toMatch(
                '/^[a-z][a-z0-9_]*$/',
                "Table name '{$tableName}' should be lowercase with underscores"
            );
        }
    });
});

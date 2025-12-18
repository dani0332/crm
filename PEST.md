# Article
https://medium.com/@zulfikarditya/mastering-testing-in-laravel-with-pest-php-a-comprehensive-guide-0d1a599f79f5

# Installation
```bash
composer require pestphp/pest pestphp/pest-plugin-laravel --dev --with-all-dependencies
php artisan pest:test
./vendor/bin/pest  # Verify installation
```

# Creating Tests

## Create Unit Test
```bash
php artisan make:test UserTest --unit
```

## Create Feature Test
```bash
php artisan make:test UserTest --feature
```

**Note:** Unit tests are placed in `tests/Unit/` and Feature tests in `tests/Feature/`

# Running Tests

## Run All Tests
```bash
# Run all tests in all test files
doppler run -- php artisan test
```

## Run Individual Tests
```bash
# Run specific test file
doppler run -- php artisan test tests/Feature/TestRepositoryTest.php

# Run specific test by name/filter
doppler run -- php artisan test --filter "generates correct username"

# Run tests in specific directory
doppler run -- php artisan test tests/Feature/

# Run tests in specific suite (Unit or Feature)
doppler run -- php artisan test --testsuite=Feature

# Run unit test
doppler run -- php artisan test tests/Unit/UserTest.php

# Run all unit tests
doppler run -- php artisan test --testsuite=Unit
```

**Note:** You may see a warning about XML configuration schema. To fix it, run:
```bash
doppler run -- php artisan test --migrate-configuration
```

# Database Schema in Tests

Tests use an **in-memory SQLite database** (`:memory:`) configured in `phpunit.xml`.

**For each test case:**
- `beforeEach()` runs → creates fresh database schema
- Test executes → uses that fresh schema
- Test completes → database is destroyed

**Example with 4 tests:**
- Test 1: Fresh schema → Execute → Destroy
- Test 2: Fresh schema → Execute → Destroy
- Test 3: Fresh schema → Execute → Destroy
- Test 4: Fresh schema → Execute → Destroy

**Example with 10 feature files × 10 test cases = 100 tests:**
- Feature File 1: 10 tests (each gets fresh schema)
- Feature File 2: 10 tests (each gets fresh schema)
- Feature File 3: 10 tests (each gets fresh schema)
- ... (continues for all 10 feature files)
- Feature File 10: 10 tests (each gets fresh schema)

**Total:** 100 database creations and 100 database destructions - one for each test case.

**Result:** Each test is isolated with a clean database. No data from previous tests affects the current test. This applies to all tests across all feature files.

```php
beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
});
```

# Common Expectations
```php
expect($value)->toBe($expected)
expect($value)->toEqual($expected)
expect($value)->toBeTrue()
expect($value)->toBeNull()
```

**Note:** Ensure `.env` file exists for tests to run properly.
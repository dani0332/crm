# Installation
```bash
composer require pestphp/pest pestphp/pest-plugin-laravel --dev --with-all-dependencies
php artisan pest:test
./vendor/bin/pest  # Verify installation
```

# Running Tests
```bash
# Run all tests
doppler run -- php artisan test

# Run specific test file
doppler run -- php artisan test tests/Feature/TestRepositoryTest.php
```

**Note:** You may see a warning about XML configuration schema. To fix it, run:
```bash
doppler run -- php artisan test --migrate-configuration
```

# Common Expectations
```php
expect($value)->toBe($expected)
expect($value)->toEqual($expected)
expect($value)->toBeTrue()
expect($value)->toBeNull()
```

**Note:** Ensure `.env` file exists for tests to run properly.
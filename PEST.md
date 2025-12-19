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

# Test Structure: Arrange-Act-Assert Pattern

All tests should follow the **Arrange-Act-Assert (AAA)** pattern for clarity and maintainability:

1. **ARRANGE**: Set up test data and prepare the environment
2. **ACT**: Execute the code being tested
3. **ASSERT**: Verify the results and expected outcomes

## Example Structure

```php
test('payment should be created via endpoint', function () {
    // ============================================
    // 1. ARRANGE: Prepare test data and payload
    // ============================================
    
    // Build the request payload using helper method
    $requestPayload = PaymentTestHelper::buildPaymentCreationPayload(
        carQuote: $this->carQuote,
        planId: $this->carPlan->id,
        insuranceProviderId: $this->insuranceProvider->id
    );
    
    // ============================================
    // 2. ACT: Execute the endpoint request
    // ============================================
    
    // Make POST request to payment creation endpoint using route name
    $response = $this->post(route('payment-create', ['quoteType' => 'Car']), $requestPayload);
    
    // ============================================
    // 3. ASSERT: Verify the results
    // ============================================
    
    // Assert that the endpoint returned a successful redirect response
    $response->assertStatus(302);
    
    // Retrieve and assert payment was created correctly
    $createdPayment = PaymentTestHelper::getPaymentByQuoteCode($this->carQuote->code);
    expect($createdPayment)->not->toBeNull();
    PaymentTestHelper::assertPaymentCreatedCorrectly(
        payment: $createdPayment,
        carQuote: $this->carQuote,
        expectedPlanId: $this->carPlan->id,
        expectedInsuranceProviderId: $this->insuranceProvider->id,
        expectedUserId: $this->user->id
    );
});
```

## Benefits of AAA Pattern

- **Clarity**: Easy to understand what each section does
- **Maintainability**: Changes to one section don't affect others
- **Readability**: Clear separation of concerns
- **Debugging**: Easier to identify where issues occur

## Best Practices

- Use helper classes for complex data preparation (e.g., `PaymentTestHelper::setupTestData()`)
- Use helper methods for assertions (e.g., `PaymentTestHelper::assertPaymentCreatedCorrectly()`)
- Use route names instead of hardcoded URLs (e.g., `route('payment-create')`)
- Add clear comments separating each section
- Keep each section focused on its specific purpose

# Common Expectations
```php
expect($value)->toBe($expected)
expect($value)->toEqual($expected)
expect($value)->toBeTrue()
expect($value)->toBeNull()
```

**Note:** Ensure `.env` file exists for tests to run properly.
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
    // Initialize test database schema
    TestSchemaCreator::createMinimalSchema();
    
    // Create VAT_VALUE record using factory
    ApplicationStorageFactory::createVatValueForSqlite('5');

    // Set up authenticated user
    $this->user = TestDataSeeder::createAdminUser();
    $this->actingAs($this->user);
    
    // Set up payment-related permissions (required for payment approval/decline operations)
    PaymentTestDataHelper::setupPaymentPermissions($this->user);
    
    // Set up test data: InsuranceProvider, CarPlan, and CarQuote
    // This helper method creates all necessary test data for payment testing
    $testData = PaymentTestDataHelper::setupTestData();
    // Assign test data to test properties for easy access
    $this->insuranceProvider = $testData['insuranceProvider'];
    $this->carPlan = $testData['carPlan'];
    $this->carQuote = $testData['carQuote'];
    $this->quoteCode = $testData['quoteCode'];
    $this->quoteUuid = $testData['quoteUuid'];
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
    $requestPayload = PaymentTestPayloadHelper::buildPaymentCreationPayload(
        carQuote: $this->carQuote,
        planId: $this->carPlan->id,
        insuranceProviderId: $this->insuranceProvider->id
    );
    
    // ============================================
    // 2. ACT: Execute the endpoint request
    // ============================================
    
    // Make POST request to payment creation endpoint using route name
    $response = $this->post(route('payment-create', ['quoteType' => QuoteTypes::CAR->value]), $requestPayload);
    
    // ============================================
    // 3. ASSERT: Verify the results
    // ============================================
    
    // Assert that the endpoint returned a successful redirect response
    $response->assertStatus(302);
    
    // Retrieve and assert payment was created correctly
    $createdPayment = PaymentTestQueryHelper::getPaymentByQuoteCode($this->carQuote->code);
    expect($createdPayment)->not->toBeNull();
    PaymentTestAssertionHelper::assertPaymentCreatedCorrectly(
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

### Helper Classes Organization

Tests use specialized helper classes organized by concern:

- **`PaymentTestDataHelper`** - Setup and data creation
  - `setupTestData()` - Creates InsuranceProvider, CarPlan, and CarQuote
  - `setupPaymentPermissions()` - Sets up payment-related permissions

- **`PaymentTestCreationHelper`** - Entity creation
  - `createPayment()` - Creates payment with automatic refresh
  - `createPaymentSplit()` - Creates payment split with automatic refresh
  - `createPaymentWithSplit()` - Creates both payment and split together
  - `createPaymentWithCapturedAmount()` - Creates payment with captured_amount initialization
  - `createPaymentWithSplitAndCapturedAmount()` - Creates both with captured_amount initialization

- **`PaymentTestPayloadHelper`** - Request payload building
  - `buildPaymentCreationPayload()` - Builds payload for payment creation endpoint
  - `buildPaymentUpdatePayload()` - Builds payload for payment update endpoint
  - `buildApprovePaymentPayload()` - Builds payload for payment approval endpoint

- **`PaymentTestQueryHelper`** - Data retrieval
  - `getPaymentByQuoteCode()` - Retrieves payment by quote code
  - `getPaymentSplitByCodeAndSerial()` - Retrieves payment split by code and serial number

- **`PaymentTestAssertionHelper`** - All assertions
  - `assertPaymentCreatedCorrectly()` - Asserts payment creation
  - `assertPaymentSplitCreatedCorrectly()` - Asserts payment split creation
  - `assertObserversRanSuccessfully()` - Asserts VAT calculations
  - `assertPaymentUpdatedCorrectly()` - Asserts payment update
  - `assertPaymentSplitUpdatedCorrectly()` - Asserts payment split update
  - `assertPaymentSplitApprovedCorrectly()` - Asserts payment split approval
  - `assertPaymentCapturedAmountUpdatedCorrectly()` - Asserts captured amount update
  - `assertInsurerReceiptNumberNullValidationError()` - Asserts validation error
  - `assertInsurerReceiptNumberDoesNotExist()` - Asserts receipt number doesn't exist
  - `assertInsurerReceiptNumberAlreadyExists()` - Asserts receipt number exists

### General Best Practices

- Use specialized helper classes for each concern (data, creation, payload, query, assertion)
- Use helper methods for complex data preparation (e.g., `PaymentTestDataHelper::setupTestData()`)
- Use helper methods for assertions (e.g., `PaymentTestAssertionHelper::assertPaymentCreatedCorrectly()`)
- Use route names instead of hardcoded URLs (e.g., `route('payment-create')`)
- Add clear comments separating each section
- Keep each section focused on its specific purpose
- Follow separation of concerns - each helper class has a single responsibility

# Common Expectations
```php
expect($value)->toBe($expected)
expect($value)->toEqual($expected)
expect($value)->toBeTrue()
expect($value)->toBeNull()
```

**Note:** Ensure `.env` file exists for tests to run properly.
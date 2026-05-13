# SQLite Connection for Test Factories - Implementation Guide

## Overview

All Laravel factories are configured to automatically use SQLite connection when running in the testing environment. This ensures test isolation, safety, and performance.

---

## Implementation

### Factory Configuration Pattern

Each factory includes a `configure()` method that automatically sets the SQLite connection:

```php
public function configure()
{
    return $this->afterMaking(function ($model) {
        // Use SQLite connection for tests
        if (app()->environment('testing')) {
            $model->setConnection('sqlite');
        }
    });
}
```

### How It Works

1. **`afterMaking()` Hook**: Called after the model is instantiated but before it's saved
2. **Environment Check**: Only applies in testing environment
3. **Connection Override**: Sets the model to use SQLite connection
4. **Automatic**: No manual intervention needed in tests

---

## Factory Implementations

### 1. InsuranceProviderFactory

```php
<?php

namespace Database\Factories;

use App\Models\InsuranceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;

class InsuranceProviderFactory extends Factory
{
    protected $model = InsuranceProvider::class;

    /**
     * Configure the model factory.
     */
    public function configure()
    {
        return $this->afterMaking(function (InsuranceProvider $insuranceProvider) {
            // Use SQLite connection for tests
            if (app()->environment('testing')) {
                $insuranceProvider->setConnection('sqlite');
            }
        });
    }

    public function definition()
    {
        return [
            'code' => $this->faker->unique()->lexify('???'),
            'text' => $this->faker->company(),
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function rsa()
    {
        return $this->state(fn (array $attributes) => [
            'code' => \App\Enums\InsuranceProvidersEnum::RSA,
            'text' => 'RSA Insurance',
        ]);
    }

    public function axa()
    {
        return $this->state(fn (array $attributes) => [
            'code' => \App\Enums\InsuranceProvidersEnum::AXA,
            'text' => 'AXA Insurance',
        ]);
    }
}
```

### 2. CarPlanFactory

```php
<?php

namespace Database\Factories;

use App\Models\CarPlan;
use App\Models\InsuranceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;

class CarPlanFactory extends Factory
{
    protected $model = CarPlan::class;

    /**
     * Configure the model factory.
     */
    public function configure()
    {
        return $this->afterMaking(function (CarPlan $carPlan) {
            // Use SQLite connection for tests
            if (app()->environment('testing')) {
                $carPlan->setConnection('sqlite');
            }
        });
    }

    public function definition()
    {
        return [
            'insurance_provider_id' => InsuranceProvider::factory(),
            'plan_name' => $this->faker->words(3, true),
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function forInsuranceProvider($insuranceProviderId)
    {
        return $this->state(fn (array $attributes) => [
            'insurance_provider_id' => $insuranceProviderId,
        ]);
    }
}
```

### 3. CarQuoteFactory

```php
<?php

namespace Database\Factories;

use App\Enums\CarRegistrationType;
use App\Models\CarQuote;
use Illuminate\Database\Eloquent\Factories\Factory;

class CarQuoteFactory extends Factory
{
    protected $model = CarQuote::class;

    /**
     * Configure the model factory.
     */
    public function configure()
    {
        return $this->afterMaking(function (CarQuote $carQuote) {
            // Use SQLite connection for tests
            if (app()->environment('testing')) {
                $carQuote->setConnection('sqlite');
            }
        });
    }

    public function definition()
    {
        $uuid = $this->faker->unique()->uuid();

        return [
            'uuid' => $uuid,
            'code' => 'CQ-TEST-'.strtoupper(substr($uuid, 0, 8)),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'mobile_no' => '+971'.$this->faker->numerify('#########'),
            'dob' => $this->faker->date('Y-m-d', '-25 years'),
            'registration_type' => CarRegistrationType::PERSONAL,
            'policy_issuance_automation_enabled' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function withAutomationEnabled()
    {
        return $this->state(fn (array $attributes) => [
            'policy_issuance_automation_enabled' => true,
        ]);
    }

    public function withAutomationDisabled()
    {
        return $this->state(fn (array $attributes) => [
            'policy_issuance_automation_enabled' => false,
        ]);
    }

    public function forCarPlan($carPlanId)
    {
        return $this->state(fn (array $attributes) => [
            'plan_id' => $carPlanId,
        ]);
    }

    public function withoutCarPlan()
    {
        return $this->state(fn (array $attributes) => [
            'plan_id' => null,
        ]);
    }
}
```

---

## Benefits

### 1. **Automatic Connection Management**

**Before** (Manual):

```php
$db = DB::connection('sqlite');
$db->table('car_quote_request')->insert([...]);
```

**After** (Automatic):

```php
$carQuote = CarQuote::factory()->createOneQuietly();
// ✅ Automatically uses SQLite!
```

### 2. **Test Isolation**

- ✅ All test data goes to SQLite in-memory database
- ✅ No risk of polluting production MySQL database
- ✅ Tests are completely isolated from each other
- ✅ Database is wiped after each test run

### 3. **Safety**

- ✅ Environment check prevents accidental production usage
- ✅ Only activates in testing environment
- ✅ Production code unaffected
- ✅ No configuration needed in tests

### 4. **Performance**

- ✅ SQLite in-memory is extremely fast
- ✅ No network latency
- ✅ Tests run significantly faster
- ✅ Ideal for CI/CD pipelines

### 5. **Developer Experience**

- ✅ No manual connection setup in tests
- ✅ Consistent across all factories
- ✅ Less code to write and maintain
- ✅ Follows Laravel best practices

---

## Usage in Tests

### Basic Usage

```php
test('example test', function () {
    // Simply use the factory - connection is automatic
    $provider = InsuranceProvider::factory()->rsa()->createOneQuietly();
    $carPlan = CarPlan::factory()->forInsuranceProvider($provider->id)->createOneQuietly();
    $carQuote = CarQuote::factory()->forCarPlan($carPlan->id)->createOneQuietly();

    // All models automatically use SQLite connection!
    expect($provider->getConnectionName())->toBe('sqlite');
    expect($carPlan->getConnectionName())->toBe('sqlite');
    expect($carQuote->getConnectionName())->toBe('sqlite');
});
```

### Test Setup (beforeEach)

```php
beforeEach(function () {
    $this->user = TestDataSeeder::createAdminUser();
    $this->actingAs($this->user);

    // Factories automatically use SQLite - no configuration needed
    $this->insuranceProvider = InsuranceProvider::factory()->rsa()->createOneQuietly();
    $this->carPlan = CarPlan::factory()
        ->forInsuranceProvider($this->insuranceProvider->id)
        ->createOneQuietly();
});
```

### Individual Test

```php
test('can create car quote', function () {
    // Factory automatically uses SQLite
    $carQuote = CarQuote::factory()
        ->withAutomationDisabled()
        ->forCarPlan($this->carPlan->id)
        ->createOneQuietly();

    // Verify it's using SQLite
    expect($carQuote->getConnectionName())->toBe('sqlite');

    // Test logic...
});
```

---

## Verification

### How to Verify Connection

You can verify that models are using SQLite connection:

```php
test('verifies sqlite connection', function () {
    $carQuote = CarQuote::factory()->createOneQuietly();

    // Check connection name
    expect($carQuote->getConnectionName())->toBe('sqlite');

    // Check connection type
    expect($carQuote->getConnection())->toBeInstanceOf(\Illuminate\Database\SQLiteConnection::class);
});
```

---

## Environment Configuration

### Testing Environment

The `configure()` method checks for testing environment:

```php
if (app()->environment('testing')) {
    $model->setConnection('sqlite');
}
```

### phpunit.xml Configuration

Ensure your `phpunit.xml` has:

```xml
<php>
    <env name="APP_ENV" value="testing"/>
    <env name="DB_CONNECTION" value="sqlite"/>
    <env name="DB_DATABASE" value=":memory:"/>
</php>
```

---

## Best Practices

### 1. **Always Use `createOneQuietly()` or `createQuietly()`**

```php
// Suppresses model events (recommended for tests)
$model = Model::factory()->createOneQuietly();

// Multiple models
$models = Model::factory()->count(5)->createQuietly();
```

### 2. **Let Factory Handle Connection**

```php
// ✅ Good - Factory handles connection
$model = Model::factory()->createOneQuietly();

// ❌ Bad - Manual connection override (unnecessary)
$model = Model::factory()->createOneQuietly();
$model->setConnection('sqlite');
```

### 3. **Use in All Test Factories**

Apply the `configure()` pattern to all your factories:

```php
public function configure()
{
    return $this->afterMaking(function ($model) {
        if (app()->environment('testing')) {
            $model->setConnection('sqlite');
        }
    });
}
```

### 4. **Test Schema Setup**

Ensure `TestSchemaCreator` creates tables in SQLite:

```php
Schema::connection('sqlite')->create('table_name', function ($table) {
    // Table definition
});
```

---

## Troubleshooting

### Issue: Models Not Using SQLite

**Symptom**: Tests fail with MySQL connection errors

**Solution**: Ensure factory has `configure()` method:

```php
public function configure()
{
    return $this->afterMaking(function ($model) {
        if (app()->environment('testing')) {
            $model->setConnection('sqlite');
        }
    });
}
```

### Issue: Related Models Use Wrong Connection

**Symptom**: Parent model uses SQLite but child uses MySQL

**Solution**: Ensure ALL factories have `configure()` method, including related models

### Issue: Environment Not Detected

**Symptom**: `app()->environment('testing')` returns false

**Solution**: Check `phpunit.xml` has `<env name="APP_ENV" value="testing"/>`

---

## Migration Checklist

When adding new factories:

- [ ] Add `configure()` method with SQLite connection logic
- [ ] Test that models use SQLite connection
- [ ] Verify related models also use SQLite
- [ ] Update test documentation
- [ ] Run full test suite to verify

---

## Comparison: Before vs After

### Before (Manual Connection)

```php
// Had to manually specify connection everywhere
$db = DB::connection('sqlite');

$providerId = $db->table('insurance_provider')->insertGetId([
    'code' => 'RSA',
    'text' => 'RSA Insurance',
    'is_active' => 1,
    'created_at' => now(),
    'updated_at' => now(),
]);

$carPlanId = $db->table('car_plan')->insertGetId([
    'insurance_provider_id' => $providerId,
    'plan_name' => 'Test Plan',
    'is_active' => 1,
    'created_at' => now(),
    'updated_at' => now(),
]);

// Returns arrays, not models
// No type safety
// Lots of boilerplate
```

### After (Automatic Connection)

```php
// Factory automatically uses SQLite
$provider = InsuranceProvider::factory()->rsa()->createOneQuietly();
$carPlan = CarPlan::factory()->forInsuranceProvider($provider->id)->createOneQuietly();

// Returns Eloquent models
// Full type safety
// Clean and readable
// Connection handled automatically
```

---

## Conclusion

The automatic SQLite connection configuration in factories provides:

- ✅ **Zero configuration** in tests
- ✅ **Complete isolation** from production database
- ✅ **Better performance** with in-memory database
- ✅ **Improved safety** with environment checks
- ✅ **Cleaner code** with automatic connection handling
- ✅ **Laravel standard** approach

All tests run faster and safer with this implementation! 🎉

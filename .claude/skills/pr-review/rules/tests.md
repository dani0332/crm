# Test Review Rules

## Coverage Requirement

Every PR that adds or changes public behavior must include tests. Flag PRs with:

- New controller actions without feature tests
- New jobs without tests covering the `handle()` and `failed()` paths
- New validation rules without tests for valid and invalid cases
- New service/action classes without unit or feature tests

## Pest Syntax

All tests must use Pest. Flag any PHPUnit-style class-based tests added to a project already using Pest.

```php
// Bad — PHPUnit style
class OrderTest extends TestCase
{
    public function test_it_creates_an_order() { }
}

// Good — Pest style
it('creates an order', function () { });
```

## Specific Assertions

Flag `assertStatus(200)` — use `assertSuccessful()`.
Flag `assertStatus(404)` — use `assertNotFound()`.
Flag `assertStatus(403)` — use `assertForbidden()`.
Flag `assertStatus(422)` — use `assertUnprocessable()`.

## Factories Over Manual Creation

Flag `Model::create([...])` inside tests. Always use factories.

```php
// Bad
$user = User::create(['name' => 'Test', 'email' => 'test@example.com', ...]);

// Good
$user = User::factory()->create();
```

## No DB Facade in Tests

Flag any `DB::` calls inside test files. Use model factories, Eloquent methods, or Pest assertion helpers instead.

```php
// Bad
DB::table('users')->insert([...]);
DB::table('orders')->where('id', $id)->first();

// Good
User::factory()->create([...]);
$this->assertModelExists($order);
```

## No Repeated Manual Service Resolution

Flag service classes resolved manually more than once across tests in the same file. Resolve once in `beforeEach()` and share via a closure variable.

```php
// Bad — resolving on every test
it('does X', function () {
    $service = app(BookingService::class);
    ...
});

it('does Y', function () {
    $service = app(BookingService::class); // duplicate resolution
    ...
});

// Good — resolve once
beforeEach(function () {
    $this->service = app(BookingService::class);
});

it('does X', function () {
    $this->service->doX();
});

it('does Y', function () {
    $this->service->doY();
});
```

## Fakes After Factory Setup

Flag `Event::fake()`, `Queue::fake()`, `Mail::fake()` called before factory or model setup. Fakes must come before the action under test, but after any prerequisite data is created.

## `LazilyRefreshDatabase`

Flag use of `RefreshDatabase` trait in feature tests. Prefer `LazilyRefreshDatabase` for speed — it only resets if the test actually touches the database.

## No Deleted Tests

Never delete tests without explicit approval from the user. Flag any test file deletions in the diff.

## assertModelExists Over assertDatabaseHas

```php
// Bad
$this->assertDatabaseHas('orders', ['id' => $order->id]);

// Good
$this->assertModelExists($order);
```

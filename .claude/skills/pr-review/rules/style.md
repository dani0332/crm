# Style & Conventions Review Rules

## Naming Conventions

| Entity                       | Convention           | Example                         |
| ---------------------------- | -------------------- | ------------------------------- |
| Classes, Models, Controllers | PascalCase           | `OrderController`, `UserPolicy` |
| Methods, variables           | camelCase            | `$isActive`, `placeOrder()`     |
| Database columns, migrations | snake_case           | `created_at`, `user_id`         |
| Routes (URL slugs)           | kebab-case           | `/payment-methods`              |
| Config keys                  | snake_case           | `retry_after`                   |
| Constants                    | SCREAMING_SNAKE_CASE | `MAX_RETRY_ATTEMPTS`            |

Flag deviations from these conventions in new or changed code.

## Descriptive Names

Flag vague or abbreviated names in new code:

```php
// Bad
$d, $res, $u, $tmp, $data

// Good
$deliveryDate, $apiResponse, $currentUser, $processedPayload
```

Also flag boolean names that don't read as questions:

```php
// Bad
$discount(), $cache

// Good
$isRegisteredForDiscounts, $shouldCache
```

## No Static (Magic) Strings

Hardcoded string literals scattered through business logic are untrackable and error-prone. Flag any static string that represents a domain concept, status, type, or constant value.

**Status / type literals → PHP 8.1 Enums:**

```php
// Bad
if ($order->status === 'pending') { }
Order::where('status', 'active')->get();

// Good
if ($order->status === OrderStatus::Pending) { }
Order::where('status', OrderStatus::Active)->get();
```

**Queue / event / channel names → constants or config:**

```php
// Bad
dispatch(new ProcessOrder($order))->onQueue('high-priority');
broadcast(new OrderUpdated($order))->toChannel('orders.123');

// Good — queue name from config
dispatch(new ProcessOrder($order))->onQueue(config('queue.priorities.high'));
```

**Error / success messages → lang files:**

```php
// Bad
return response()->json(['message' => 'Order placed successfully.'], 201);

// Good
return response()->json(['message' => __('orders.placed')], 201);
```

**Repeated numeric thresholds → named constants or config:**

```php
// Bad
if ($cart->total > 500) { $cart->applyFreeShipping(); }

// Good — threshold from config so it can change without a code deployment
if ($cart->total > config('shop.free_shipping_threshold')) { $cart->applyFreeShipping(); }
```

Severity: **High** when the literal is a domain status value used across multiple files. **Medium** when it appears in a single file. **Low** for one-off display strings.

## Laravel Helpers Over Raw PHP

Flag raw PHP functions when a Laravel helper exists:

| Raw PHP               | Laravel equivalent            |
| --------------------- | ----------------------------- |
| `strlen($str)`        | `Str::length($str)`           |
| `strtolower($str)`    | `Str::lower($str)`            |
| `implode(',', $arr)`  | `collect($arr)->implode(',')` |
| `array_map(fn, $arr)` | `collect($arr)->map(fn)`      |

## Comments

Flag comments that describe _what_ the code does — well-named code is self-documenting. Only comments explaining _why_ (a non-obvious constraint, a workaround) are acceptable.

Flag multi-line comment blocks on new code unless they document a public API or a genuinely complex algorithm.

## No Logic in Blade/Vue Templates

Flag `@if` chains longer than 2–3 conditions in Blade. Extract to a computed property or a view model.
Flag database queries or service calls inside Blade templates.

## Pint Formatting

All changed PHP files must be formatted with Pint. If the diff shows formatting inconsistencies (inconsistent spacing, brace placement, etc.), note that `vendor/bin/pint --dirty` should be run.

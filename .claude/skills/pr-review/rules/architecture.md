# Architecture Review Rules

## SRP — Single Responsibility Principle

Every class must have exactly one reason to change. Flag classes that mix unrelated concerns:

```php
// Bad — fetches, transforms, persists, and sends a notification in one class
class OrderService
{
    public function process(array $data): Order
    {
        $order = Order::create($data);          // persistence
        $total = $this->recalculateTotals($order); // business logic
        $order->update(['total' => $total]);    // persistence again
        Mail::to($order->user)->send(new OrderConfirmed($order)); // notification
        return $order;
    }
}

// Good — each class owns one concern
class CreateOrderAction    { public function execute(array $data): Order { } }
class CalculateOrderTotal  { public function execute(Order $order): float { } }
// Notification dispatched via event listener, not here
```

Flag these SRP violations specifically:

- A model with complex business-logic methods (move to Actions)
- A job that also contains data transformation logic (extract to a dedicated class)
- A controller that calls multiple services and assembles a response manually (extract to an Action)
- A Form Request that also performs non-validation side effects

## Single-Purpose Action Classes

Flag service methods or controller methods that do more than one thing. Business logic should live in single-purpose Action classes with an `execute()` or `handle()` method.

## Dependency Injection

Flag use of `app()`, `resolve()`, or `App::make()` inside class methods. Dependencies must be injected via the constructor or method signature.

```php
// Bad
public function handle(): void
{
    $service = app(PaymentService::class);
}

// Good
public function __construct(private PaymentService $paymentService) { }
```

## No New Top-Level Directories

Flag creation of directories outside the established structure (`app/`, `database/`, `resources/`, `tests/`, etc.) without prior approval.

## Constructor Property Promotion

Flag old-style constructor property assignment in new PHP 8+ code.

```php
// Bad
private PaymentService $payment;
public function __construct(PaymentService $payment)
{
    $this->payment = $payment;
}

// Good
public function __construct(private PaymentService $payment) { }
```

## Empty Constructors

Flag zero-parameter `public function __construct() { }` — remove it unless the constructor is private (singleton pattern).

## `defer()` for Post-Response Work

Flag non-critical work (sending notifications, logging analytics) done synchronously inside a request lifecycle. Suggest `defer()` or dispatching to a queue.

## Return Type & Parameter Type Hints

All public methods on new classes must declare parameter types and return types. Flag missing type hints on any new or changed method signature.

## DRY — Don't Repeat Yourself

Flag logic that appears more than once across the diff. Every piece of knowledge must have a single authoritative representation.

**Duplicated query constraints** — extract to a local scope:

```php
// Bad — same WHERE clause in three controllers
User::where('status', 'active')->where('verified', true)->get();

// Good — single local scope
User::active()->verified()->get();
```

**Duplicated validation rules across Form Requests** — extract shared rules to a base class or trait:

```php
// Bad — same address rules copy-pasted into ShippingRequest and BillingRequest
// Good
trait HasAddressRules
{
    protected function addressRules(): array
    {
        return [
            'street'  => ['required', 'string', 'max:255'],
            'city'    => ['required', 'string', 'max:100'],
            'country' => ['required', 'string', 'size:2'],
        ];
    }
}
```

**Duplicated conditional logic** — extract to a method, helper, or value object:

```php
// Bad — same if-chain in a controller and a job
if ($order->status === 'pending' && $order->created_at->diffInHours() > 24) { ... }

// Good — named method on the model or a dedicated class
if ($order->isOverdue()) { ... }
```

**Duplicated response shaping** — extract to an API Resource or a shared method.

DRY violations to flag at **High** severity when the duplicated logic contains business rules. Flag at **Medium** when it is structural boilerplate.

## PHP 8 Syntax

Flag old-style patterns when PHP 8+ equivalents exist:

- `isset($x) ? $x : $default` → `$x ?? $default`
- `is_null($x)` → `$x === null`
- String concatenation for multi-part strings → `sprintf()` or interpolation

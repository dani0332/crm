# Jobs & Queue Review Rules

## `failed()` Method

Every job class must implement `failed(Throwable $exception): void`. Flag any job missing it.

## Timeout vs. `retry_after`

`retry_after` (in `config/queue.php` or the job's `$timeout` property) must be greater than the job's `timeout`. If the job sets `$timeout`, confirm `retry_after` exceeds it.

```php
// Bad — retry_after too short, job will be re-queued while still running
public int $timeout = 120;
// retry_after = 60 in config

// Good
public int $timeout = 120;
// retry_after = 180 in config
```

## Exponential Backoff

Jobs that call external APIs must define `backoff()` with exponential delays.

```php
public function backoff(): array
{
    return [1, 5, 10];
}
```

Flag jobs with `public int $tries = 3` but no `backoff()` when they interact with third-party services.

## Uniqueness

Flag jobs that could be dispatched multiple times for the same resource without `ShouldBeUnique`. Common case: jobs triggered by webhooks or user actions.

## `retryUntil` + `$tries`

Flag any job that uses `retryUntil()` without setting `$tries = 0`. Without it, `$tries` takes precedence.

## Dispatching in Transactions

Flag `dispatch()` calls inside a database transaction without `->afterCommit()` or `ShouldDispatchAfterCommit`. The job may run before the transaction commits.

```php
// Bad
DB::transaction(function () {
    $order = Order::create([...]);
    ProcessOrder::dispatch($order); // may run before commit
});

// Good
ProcessOrder::dispatch($order)->afterCommit();
// or implement ShouldDispatchAfterCommit on the job
```

## Rate Limiting for External APIs

Flag jobs calling external APIs (HTTP client, third-party SDKs) without `RateLimited` middleware.

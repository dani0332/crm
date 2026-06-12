# Config-Driven Values Review Rules

## No Hardcoded Date Formats

Date and datetime format strings must never appear as literals in application code. They must be centralised in a config file so they can be changed in one place.

```php
// Bad — format strings scattered across the codebase
$order->created_at->format('d/m/Y');
Carbon::createFromFormat('Y-m-d H:i:s', $input);
$report->date = now()->format('d M Y, h:i A');

// Good — format comes from config
$order->created_at->format(config('app.date_format'));
Carbon::createFromFormat(config('app.datetime_format'), $input);
$report->date = now()->format(config('app.report_date_format'));
```

Recommended config structure in `config/app.php` (or a dedicated `config/formats.php`):

```php
return [
    'date_format'        => env('DATE_FORMAT', 'd/m/Y'),
    'datetime_format'    => env('DATETIME_FORMAT', 'd/m/Y H:i'),
    'report_date_format' => env('REPORT_DATE_FORMAT', 'd M Y, h:i A'),
    'api_date_format'    => env('API_DATE_FORMAT', 'Y-m-d'),
];
```

Flag at **High** severity when the same format string appears in more than one file. Flag at **Medium** when it appears only once but is not config-driven.

## `env()` Only in Config Files

`env()` must never be called in application code (controllers, models, jobs, services, middleware). Application code must use `config()`.

```php
// Bad
$key = env('STRIPE_SECRET');
$timeout = env('HTTP_TIMEOUT', 30);

// Good — read once in a config file, accessed everywhere else via config()
// config/services.php
'stripe' => ['secret' => env('STRIPE_SECRET')],
// config/http.php
'timeout' => env('HTTP_TIMEOUT', 30),

// In application code
$key     = config('services.stripe.secret');
$timeout = config('http.timeout');
```

Flag as **Critical** when `env()` is called for a secret/credential in non-config code.
Flag as **Medium** when `env()` is called for a non-secret setting.

## Timezone Strings

Hardcoded timezone strings must come from `config('app.timezone')`.

```php
// Bad
Carbon::now('Asia/Dubai');
Carbon::now('UTC');

// Good
Carbon::now(config('app.timezone'));
```

## Pagination Limits and Thresholds

Page sizes, retry counts, expiry durations, and similar numeric thresholds must be config-driven, not hardcoded.

```php
// Bad
$users = User::paginate(15);
Cache::put($key, $value, 3600);
$otp->expires_at = now()->addMinutes(10);

// Good
$users = User::paginate(config('app.pagination.per_page'));
Cache::put($key, $value, config('cache.ttl.default'));
$otp->expires_at = now()->addMinutes(config('auth.otp.expires_in_minutes'));
```

## URL and Domain Strings

Never hardcode base URLs, domains, or scheme strings. Use `config('app.url')` or named routes.

```php
// Bad
$link = 'https://myapp.com/verify/' . $token;

// Good
$link = route('verification.verify', ['token' => $token]);
// or
$link = config('app.url') . '/verify/' . $token;
```

## Queue and Connection Names

Queue names, connection names, and driver identifiers must come from config rather than being duplicated as string literals across multiple jobs or commands.

```php
// Bad
ProcessOrder::dispatch($order)->onQueue('critical');
SendWelcomeEmail::dispatch($user)->onQueue('emails');

// Good — queue name is the job's own concern, defined once per class
class ProcessOrder implements ShouldQueue
{
    public string $queue = 'critical'; // defined once, not repeated at call sites
}

// Or, when the queue name varies by environment:
public string $queue = ''; // set in constructor
public function __construct() {
    $this->queue = config('queue.names.critical');
}
```

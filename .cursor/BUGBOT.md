# Bugbot Rules for Laravel Backend API

This file contains automated code review rules for Laravel PHP applications using Eloquent ORM, and various Laravel ecosystem packages.

## Eloquent ORM Rules

### Database Query Performance

- **Rule**: Flag N+1 query patterns in Eloquent
- **Pattern**: Multiple model queries inside loops or using `get()` followed by property access
- **Fix**: Use `with()` for eager loading or `load()` for lazy eager loading
- **Severity**: High
- **Example**:

  ```php
  // ❌ Bad - N+1 query
  $users = User::all();
  foreach ($users as $user) {
      $orders = $user->orders; // This triggers a new query for each user
  }

  // ✅ Good - Eager loading
  $users = User::with('orders')->get();
  foreach ($users as $user) {
      $orders = $user->orders; // No additional queries
  }

  // ✅ Also Good - Lazy eager loading
  $users = User::all();
  $users->load('orders');
  ```

### Query Builder Optimization

- **Rule**: Use query builder methods instead of collection methods for database operations
- **Pattern**: Using `get()->filter()`, `get()->map()`, `get()->where()` on database queries
- **Fix**: Use database-level filtering with `where()`, `select()`, `orderBy()`
- **Severity**: High
- **Example**:

  ```php
  // ❌ Bad - Filtering in PHP memory
  $activeUsers = User::all()->where('status', 'active');
  $userNames = User::all()->pluck('name');

  // ✅ Good - Database-level filtering
  $activeUsers = User::where('status', 'active')->get();
  $userNames = User::pluck('name');
  ```

### Selective Field Retrieval

- **Rule**: Always specify columns instead of selecting all fields
- **Pattern**: `get()`, `find()`, `first()` without `select()` method
- **Fix**: Use `select()` method to specify only needed columns
- **Severity**: Medium
- **Example**:

  ```php
  // ❌ Bad - Selects all columns
  $users = User::where('status', 'active')->get();

  // ✅ Good - Select specific columns only
  $users = User::select(['id', 'name', 'email', 'status'])
      ->where('status', 'active')
      ->get();

  // ✅ Also Good - Using pluck for single column
  $userEmails = User::where('status', 'active')->pluck('email');
  ```

### Chunk Processing

- **Rule**: Use chunk processing for large datasets
- **Pattern**: `all()` or `get()` on potentially large result sets
- **Fix**: Use `chunk()` or `chunkById()` for memory efficiency
- **Severity**: Medium
- **Example**:

  ```php
  // ❌ Bad - Loading all records into memory
  $users = User::all();
  foreach ($users as $user) {
      // Process user
  }

  // ✅ Good - Chunk processing
  User::chunk(1000, function ($users) {
      foreach ($users as $user) {
          // Process user
      }
  });
  ```

## Database Transaction Rules

### Transaction Usage

- **Rule**: Wrap multiple database operations in transactions
- **Pattern**: Multiple model `create()`, `update()`, `delete()` calls without DB transaction
- **Fix**: Use `DB::transaction()` or `DB::beginTransaction()`
- **Severity**: High
- **Example**:

  ```php
  // ❌ Bad - No transaction
  $user = User::create($userData);
  $profile = Profile::create(['user_id' => $user->id] + $profileData);
  $settings = UserSettings::create(['user_id' => $user->id] + $settingsData);

  // ✅ Good - Using transaction closure
  DB::transaction(function () use ($userData, $profileData, $settingsData) {
      $user = User::create($userData);
      $profile = Profile::create(['user_id' => $user->id] + $profileData);
      $settings = UserSettings::create(['user_id' => $user->id] + $settingsData);
      return $user;
  });

  // ✅ Also Good - Manual transaction control
  DB::beginTransaction();
  try {
      $user = User::create($userData);
      $profile = Profile::create(['user_id' => $user->id] + $profileData);
      $settings = UserSettings::create(['user_id' => $user->id] + $settingsData);
      DB::commit();
      return $user;
  } catch (Exception $e) {
      DB::rollBack();
      throw $e;
  }
  ```

### Nested Transaction Handling

- **Rule**: Handle nested transactions properly
- **Pattern**: Nested `DB::transaction()` calls without savepoints
- **Fix**: Use savepoints or restructure to avoid nesting

## Model Rules

### Mass Assignment Protection

- **Rule**: Define `$fillable` or `$guarded` properties on all models
- **Pattern**: Models without mass assignment protection
- **Fix**: Add `$fillable` array with allowed fields or `$guarded` with protected fields
- **Severity**: High

### Relationship Definitions

- **Rule**: Use proper return types for relationship methods
- **Pattern**: Relationship methods without proper return type hints
- **Fix**: Add return type hints for `HasMany`, `BelongsTo`, etc.
- **Example**:

  ```php
  // ❌ Bad - No return type
  public function orders()
  {
      return $this->hasMany(Order::class);
  }

  // ✅ Good - With return type
  public function orders(): HasMany
  {
      return $this->hasMany(Order::class);
  }
  ```

### Accessor/Mutator Usage

- **Rule**: Use modern accessor/mutator syntax (Laravel 9+)
- **Pattern**: Old `getAttributeAttribute()` syntax
- **Fix**: Use `Attribute` class with `get()` and `set()` methods
- **Example**:

  ```php
  // ❌ Bad - Old syntax (Laravel 8 and below)
  public function getFullNameAttribute()
  {
      return $this->first_name . ' ' . $this->last_name;
  }

  // ✅ Good - New syntax (Laravel 9+)
  protected function fullName(): Attribute
  {
      return Attribute::make(
          get: fn ($value, $attributes) => $attributes['first_name'] . ' ' . $attributes['last_name'],
      );
  }
  ```

### Model Events

- **Rule**: Use model observers instead of event listeners in models
- **Pattern**: Model event logic directly in model methods
- **Fix**: Create dedicated observer classes

## Controller Rules

### Single Responsibility

- **Rule**: Controllers should only handle HTTP concerns
- **Pattern**: Business logic directly in controller methods
- **Fix**: Move business logic to service classes or form requests
- **Severity**: High

### Resource Controllers

- **Rule**: Use resource controllers for standard CRUD operations
- **Pattern**: Custom methods that could be standard resource methods
- **Fix**: Follow RESTful conventions with resource controllers

### Request Validation

- **Rule**: Use Form Requests for validation instead of inline validation
- **Pattern**: `$request->validate()` calls in controller methods
- **Fix**: Create dedicated Form Request classes
- **Severity**: High
- **Example**:

  ```php
  // ❌ Bad - Inline validation
  public function store(Request $request)
  {
      $request->validate([
          'name' => 'required|string|max:255',
          'email' => 'required|email|unique:users',
          'password' => 'required|min:8',
      ]);

      return User::create($request->all());
  }

  // ✅ Good - Form Request
  public function store(StoreUserRequest $request)
  {
      return User::create($request->validated());
  }

  // In app/Http/Requests/StoreUserRequest.php
  class StoreUserRequest extends FormRequest
  {
      public function rules(): array
      {
          return [
              'name' => 'required|string|max:255',
              'email' => 'required|email|unique:users',
              'password' => 'required|min:8',
          ];
      }
  }
  ```

### Response Consistency

- **Rule**: Use consistent API response structure
- **Pattern**: Different response formats across endpoints
- **Fix**: Use API Resources or standardized response methods
- **Example**:

  ```php
  // ❌ Bad - Inconsistent responses
  return response()->json($user);
  return ['data' => $users, 'status' => 'success'];

  // ✅ Good - Using API Resources
  return new UserResource($user);
  return UserResource::collection($users);
  ```

## Service Layer Rules

### Business Logic Encapsulation

- **Rule**: Encapsulate complex business logic in service classes
- **Pattern**: Complex operations directly in controllers or models
- **Fix**: Create service classes in `app/Services/` directory
- **Severity**: Medium

### Service Provider Registration

- **Rule**: Register services in service providers
- **Pattern**: Direct class instantiation instead of dependency injection
- **Fix**: Bind services in `AppServiceProvider` or dedicated service providers

### Interface Implementation

- **Rule**: Use interfaces for service contracts
- **Pattern**: Direct service class dependencies
- **Fix**: Create interfaces and bind implementations in service providers

## Validation Rules

### Custom Validation Rules

- **Rule**: Create custom validation rules for domain-specific validations
- **Pattern**: Complex validation logic in form requests
- **Fix**: Extract to custom validation rule classes

### Validation Messages

- **Rule**: Provide clear, user-friendly validation messages
- **Pattern**: Default Laravel validation messages for business-specific rules
- **Fix**: Override `messages()` method in Form Requests

### Conditional Validation

- **Rule**: Use proper conditional validation
- **Pattern**: Multiple validation arrays for different conditions
- **Fix**: Use `sometimes()` and `required_if()` rules

## Middleware Rules

### Authentication Middleware

- **Rule**: Apply authentication middleware to protected routes
- **Pattern**: Routes accessing user data without `auth` middleware
- **Fix**: Apply `auth:sanctum` or appropriate auth middleware

### CORS Configuration

- **Rule**: Configure CORS properly for API endpoints
- **Pattern**: Wildcard CORS settings in production
- **Fix**: Specify allowed origins, methods, and headers

### Rate Limiting

- **Rule**: Implement rate limiting for public API endpoints
- **Pattern**: API routes without throttle middleware
- **Fix**: Apply `throttle:api` or custom rate limiting middleware

## Security Rules

### Mass Assignment Protection

- **Rule**: Never use `create($request->all())` without validation
- **Pattern**: Direct request data passed to model methods
- **Fix**: Use `$request->validated()` or `$request->only()`
- **Severity**: High
- **Example**:

  ```php
  // ❌ Bad - Potential mass assignment vulnerability
  User::create($request->all());

  // ✅ Good - Using validated data
  User::create($request->validated());

  // ✅ Also Good - Explicit field selection
  User::create($request->only(['name', 'email', 'password']));
  ```

### SQL Injection Prevention

- **Rule**: Use parameter binding for raw queries
- **Pattern**: String concatenation in `DB::raw()` or raw SQL
- **Fix**: Use parameter binding or query builder methods
- **Severity**: Critical

### CSRF Protection

- **Rule**: Ensure CSRF protection is enabled for state-changing operations
- **Pattern**: POST/PUT/DELETE routes without CSRF middleware
- **Fix**: Include `web` middleware group or `csrf` middleware

### XSS Prevention

- **Rule**: Escape output in Blade templates
- **Pattern**: Using `{!! !!}` for user-generated content
- **Fix**: Use `{{ }}` for auto-escaping or explicit `e()` helper

## Caching Rules

### Query Result Caching

- **Rule**: Cache expensive or frequently accessed queries
- **Pattern**: Complex queries executed repeatedly without caching
- **Fix**: Use `remember()` method or Cache facade
- **Example**:

  ```php
  // ❌ Bad - No caching for expensive query
  public function getPopularProducts()
  {
      return Product::with(['category', 'reviews'])
          ->where('status', 'active')
          ->orderBy('views', 'desc')
          ->limit(10)
          ->get();
  }

  // ✅ Good - Cached result
  public function getPopularProducts()
  {
      return Cache::remember('popular_products', 3600, function () {
          return Product::with(['category', 'reviews'])
              ->where('status', 'active')
              ->orderBy('views', 'desc')
              ->limit(10)
              ->get();
      });
  }
  ```

### Cache Invalidation

- **Rule**: Implement proper cache invalidation strategies
- **Pattern**: Cached data without invalidation on updates
- **Fix**: Use cache tags or invalidate specific keys on model events

### Configuration Caching

- **Rule**: Cache configuration files in production
- **Pattern**: Production deployment without config caching
- **Fix**: Run `php artisan config:cache` in deployment

## Queue & Job Rules

### Job Design

- **Rule**: Jobs should be idempotent and handle failures gracefully
- **Pattern**: Jobs without proper error handling or retry logic
- **Fix**: Implement `failed()` method and proper exception handling

### Queue Worker Memory

- **Rule**: Prevent memory leaks in long-running queue workers
- **Pattern**: Large object accumulation in job processing
- **Fix**: Use `--max-jobs` and `--max-time` options, implement proper cleanup

### Job Serialization

- **Rule**: Be careful with model serialization in jobs
- **Pattern**: Passing entire models to job constructors
- **Fix**: Pass model IDs and re-fetch in job, or use `SerializesModels` trait carefully

## Route Rules

### Route Model Binding

- **Rule**: Use route model binding instead of manual model retrieval
- **Pattern**: Manual `Model::find()` calls in controllers for route parameters
- **Fix**: Use implicit or explicit route model binding
- **Example**:

  ```php
  // ❌ Bad - Manual model retrieval
  public function show($id)
  {
      $user = User::findOrFail($id);
      return view('users.show', compact('user'));
  }

  // ✅ Good - Route model binding
  public function show(User $user)
  {
      return view('users.show', compact('user'));
  }
  ```

### Route Grouping

- **Rule**: Group related routes with common middleware and prefixes
- **Pattern**: Repeated middleware and prefix definitions
- **Fix**: Use `Route::group()` with shared attributes

### API Versioning

- **Rule**: Implement proper API versioning strategy
- **Pattern**: API routes without version namespace
- **Fix**: Use route groups with version prefixes

## Error Handling Rules

### Exception Handling

- **Rule**: Use proper exception handling with meaningful error messages
- **Pattern**: Generic `catch (Exception $e)` blocks without specific handling
- **Fix**: Catch specific exceptions and provide contextual error messages
- **Severity**: High

### Custom Exceptions

- **Rule**: Create custom exceptions for domain-specific errors
- **Pattern**: Throwing generic exceptions for business logic errors
- **Fix**: Create custom exception classes in `app/Exceptions/`

### Error Logging

- **Rule**: Log errors with sufficient context for debugging
- **Pattern**: Simple `Log::error($e->getMessage())` calls
- **Fix**: Include relevant context like user ID, request data, stack trace
- **Example**:

  ```php
  // ❌ Bad - Minimal error logging
  try {
      $order = Order::create($orderData);
  } catch (Exception $e) {
      Log::error($e->getMessage());
      throw $e;
  }

  // ✅ Good - Contextual error logging
  try {
      $order = Order::create($orderData);
  } catch (Exception $e) {
      Log::error('Order creation failed', [
          'user_id' => auth()->id(),
          'order_data' => $orderData,
          'error' => $e->getMessage(),
          'stack_trace' => $e->getTraceAsString(),
          'request_id' => request()->header('X-Request-ID'),
      ]);
      throw $e;
  }
  ```

## Service Container & Dependency Injection Rules

### Constructor Injection

- **Rule**: Use constructor injection instead of service locator pattern
- **Pattern**: Using `app()` helper or `resolve()` within class methods
- **Fix**: Inject dependencies through constructor
- **Example**:

  ```php
  // ❌ Bad - Service locator pattern
  class OrderService
  {
      public function createOrder($data)
      {
          $paymentService = app(PaymentService::class);
          $notificationService = resolve(NotificationService::class);
          // ...
      }
  }

  // ✅ Good - Constructor injection
  class OrderService
  {
      public function __construct(
          private PaymentService $paymentService,
          private NotificationService $notificationService
      ) {}

      public function createOrder($data)
      {
          // Use $this->paymentService and $this->notificationService
      }
  }
  ```

### Interface Binding

- **Rule**: Bind interfaces to implementations in service providers
- **Pattern**: Direct class dependencies instead of interfaces
- **Fix**: Create interfaces and bind them in service providers

## Configuration Rules

### Environment Variables

- **Rule**: Use environment variables for all configuration
- **Pattern**: Hardcoded values in config files or code
- **Fix**: Use `env()` helper in config files only, never in application code
- **Severity**: High

### Config Caching

- **Rule**: Ensure configuration is cacheable
- **Pattern**: Using `env()` helper outside of config files
- **Fix**: Move environment variable access to config files

### Sensitive Data

- **Rule**: Never commit sensitive data to version control
- **Pattern**: API keys, passwords, or secrets in code or config files
- **Fix**: Use environment variables and proper `.env` management

## Testing Rules

### Feature vs Unit Tests

- **Rule**: Use appropriate test types for different scenarios
- **Pattern**: Testing controller methods with unit tests instead of feature tests
- **Fix**: Use feature tests for HTTP endpoints, unit tests for isolated logic

### Database Testing

- **Rule**: Use database transactions or RefreshDatabase trait in tests
- **Pattern**: Tests that don't clean up database state
- **Fix**: Use `RefreshDatabase` trait or database transactions

### Factory Usage

- **Rule**: Use model factories instead of manual model creation in tests
- **Pattern**: Manual `Model::create()` calls in test methods
- **Fix**: Create and use model factories

## Performance Rules

### Database Indexes

- **Rule**: Ensure proper database indexing for frequently queried columns
- **Pattern**: `where()` clauses on non-indexed columns
- **Fix**: Add database indexes via migrations

### Eager Loading

- **Rule**: Always eager load relationships that will be accessed
- **Pattern**: Accessing model relationships without eager loading
- **Fix**: Use `with()` method in queries

### Collection vs Query Builder

- **Rule**: Use query builder for database operations, collections for in-memory operations
- **Pattern**: Using collection methods on query builder results when database methods exist
- **Fix**: Use appropriate database methods

### Session Storage

- **Rule**: Use appropriate session drivers for production
- **Pattern**: File-based sessions in production with multiple servers
- **Fix**: Use Redis or database session driver

## Code Organization Rules

### Service Layer

- **Rule**: Use service classes for complex business logic
- **Pattern**: Fat controllers with business logic
- **Fix**: Extract logic to service classes

### Repository Pattern

- **Rule**: Consider repository pattern for complex data access
- **Pattern**: Direct Eloquent usage in controllers for complex queries
- **Fix**: Create repository classes for data access abstraction

### Action Classes

- **Rule**: Use single-action classes for complex operations
- **Pattern**: Large controller methods with multiple responsibilities
- **Fix**: Extract to dedicated action classes

## Event & Listener Rules

### Event Usage

- **Rule**: Use events for decoupled communication between components
- **Pattern**: Direct service calls for side effects
- **Fix**: Dispatch events and create listeners

### Listener Performance

- **Rule**: Queue listeners for time-consuming operations
- **Pattern**: Synchronous listeners performing slow operations
- **Fix**: Implement `ShouldQueue` interface on listeners

## Mail & Notification Rules

### Mailable Classes

- **Rule**: Use Mailable classes instead of raw mail functions
- **Pattern**: Using `mail()` helper or `Mail::raw()`
- **Fix**: Create dedicated Mailable classes

### Queue Email Sending

- **Rule**: Queue email sending to improve response times
- **Pattern**: Synchronous email sending in request cycle
- **Fix**: Implement `ShouldQueue` interface on Mailables

### Notification Channels

- **Rule**: Use appropriate notification channels
- **Pattern**: Hardcoded notification delivery methods
- **Fix**: Use Laravel's notification system with proper channels

## File Storage Rules

### Storage Facade

- **Rule**: Use Storage facade instead of direct file operations
- **Pattern**: Direct PHP file functions (`file_get_contents`, `file_put_contents`)
- **Fix**: Use `Storage::get()`, `Storage::put()` methods

### File Upload Validation

- **Rule**: Validate file uploads properly
- **Pattern**: File uploads without size, type, or security validation
- **Fix**: Use validation rules like `file`, `mimes`, `max`

## Authorization Rules

### Policy Usage

- **Rule**: Use policies for authorization logic
- **Pattern**: Authorization logic directly in controllers
- **Fix**: Create policy classes and use `authorize()` method
- **Example**:

  ```php
  // ❌ Bad - Authorization in controller
  public function update(Request $request, Post $post)
  {
      if (auth()->user()->id !== $post->user_id && !auth()->user()->isAdmin()) {
          abort(403);
      }
      // Update logic
  }

  // ✅ Good - Using policy
  public function update(UpdatePostRequest $request, Post $post)
  {
      $this->authorize('update', $post);
      // Update logic
  }
  ```

### Gate Definitions

- **Rule**: Define gates for simple authorization checks
- **Pattern**: Repeated authorization logic across controllers
- **Fix**: Define gates in `AuthServiceProvider`

## Logging Rules

### Structured Logging

- **Rule**: Use structured logging with context
- **Pattern**: Simple string messages without context
- **Fix**: Include relevant context data in log messages
- **Severity**: High
- **Example**:

  ```php
  // ❌ Bad - Minimal logging
  Log::info('User logged in');
  Log::error('Payment failed');

  // ✅ Good - Structured logging with context
  Log::info('User login successful', [
      'user_id' => $user->id,
      'ip_address' => request()->ip(),
      'user_agent' => request()->userAgent(),
      'timestamp' => now(),
  ]);

  Log::error('Payment processing failed', [
      'user_id' => $user->id,
      'order_id' => $order->id,
      'payment_amount' => $amount,
      'payment_method' => $paymentMethod,
      'error' => $exception->getMessage(),
      'stack_trace' => $exception->getTraceAsString(),
  ]);
  ```

### Log Levels

- **Rule**: Use appropriate log levels
- **Pattern**: Using `info()` for errors or `error()` for debug information
- **Fix**: Use correct log levels: `emergency`, `alert`, `critical`, `error`, `warning`, `notice`, `info`, `debug`

## Laravel-Specific Performance Rules

### Config Caching

- **Rule**: Cache configuration in production
- **Pattern**: Production deployment without cached config
- **Fix**: Run `php artisan config:cache` during deployment

### Route Caching

- **Rule**: Cache routes in production
- **Pattern**: Production deployment without cached routes
- **Fix**: Run `php artisan route:cache` during deployment

### View Caching

- **Rule**: Compile views in production
- **Pattern**: Production deployment without compiled views
- **Fix**: Run `php artisan view:cache` during deployment

### OpCache Configuration

- **Rule**: Enable and configure PHP OpCache properly
- **Pattern**: OpCache disabled or misconfigured in production
- **Fix**: Configure OpCache settings appropriately

## API Resource Rules

### Resource Usage

- **Rule**: Use API Resources for JSON responses
- **Pattern**: Direct model serialization in API responses
- **Fix**: Create dedicated Resource classes
- **Severity**: High

### Resource Collections

- **Rule**: Use Resource Collections for array responses
- **Pattern**: Manual array transformation of model collections
- **Fix**: Use `ResourceCollection` or `Resource::collection()`

### Conditional Attributes

- **Rule**: Use conditional attributes in API Resources
- **Pattern**: Including sensitive data based on manual checks
- **Fix**: Use `when()` and `mergeWhen()` methods in Resources

## Background Job Rules

### Job Failure Handling

- **Rule**: Implement proper job failure handling
- **Pattern**: Jobs without failure handling or retry logic
- **Fix**: Implement `failed()` method and configure retry attempts

### Job Timeout

- **Rule**: Set appropriate timeouts for jobs
- **Pattern**: Jobs without timeout configuration
- **Fix**: Set `$timeout` property on job classes

### Queue Connection

- **Rule**: Use appropriate queue connections for different job types
- **Pattern**: All jobs using default queue connection
- **Fix**: Use different connections (sync, database, redis) based on job requirements

## Laravel Naming Conventions

### File and Class Naming

- **Rule**: Follow Laravel naming conventions
- **Pattern**: Inconsistent naming across files and classes
- **Fix**: Use singular model names, plural table names, PascalCase for classes

### Method Naming

- **Rule**: Use descriptive method names following Laravel conventions
- **Pattern**: Unclear or non-standard method names
- **Fix**: Use Laravel's conventional method names and clear descriptive names

## Environment-Specific Rules

### Debug Mode

- **Rule**: Ensure debug mode is disabled in production
- **Pattern**: `APP_DEBUG=true` in production environment
- **Fix**: Set `APP_DEBUG=false` in production

### Error Reporting

- **Rule**: Configure appropriate error reporting for environment
- **Pattern**: Detailed error messages exposed in production
- **Fix**: Use proper error handling and logging configuration

### Asset Compilation

- **Rule**: Compile and version assets for production
- **Pattern**: Development assets in production
- **Fix**: Run `npm run production` and use `mix()` helper for versioning

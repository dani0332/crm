# Customer Portal API Service Blueprint

## Overview

The Customer Portal API Service (`CustomerPortalApiService`) is designed to handle all integrations with the external Customer Portal API system. This service manages customer account operations, policy synchronization, notifications, and preferences management for the customer-facing portal.

## Architecture

### Service Structure

```
app/Services/CustomerPortalApiService.php    - Main service class
app/Providers/CustomerPortalApiProvider.php  - Service provider for DI
app/Facades/CustomerPortalApiFacade.php      - Facade for static access
config/constants.php                         - Configuration constants
```

### Design Patterns

- **Service Layer Pattern**: Encapsulates all Customer Portal API logic
- **Facade Pattern**: Provides static access via `CustomerPortalApi` facade
- **Dependency Injection**: Registered as singleton for performance
- **Error Handling**: Comprehensive logging and graceful error handling

## Configuration

### Environment Variables

Add these to your `.env` file:

```env
# Customer Portal API Configuration
CUSTOMER_PORTAL_API_ENDPOINT=https://api.customerportal.example.com
CUSTOMER_PORTAL_API_USER=your_api_username
CUSTOMER_PORTAL_API_PWD=your_api_password
CUSTOMER_PORTAL_API_TOKEN=your_api_token
CUSTOMER_PORTAL_API_TIMEOUT=30
```

### Constants Configuration

Located in `config/constants.php`:

```php
/* Customer Portal API */
'CUSTOMER_PORTAL_API_ENDPOINT' => env('CUSTOMER_PORTAL_API_ENDPOINT', ''),
'CUSTOMER_PORTAL_API_USER' => env('CUSTOMER_PORTAL_API_USER', ''),
'CUSTOMER_PORTAL_API_PWD' => env('CUSTOMER_PORTAL_API_PWD', ''),
'CUSTOMER_PORTAL_API_TOKEN' => env('CUSTOMER_PORTAL_API_TOKEN', ''),
'CUSTOMER_PORTAL_API_TIMEOUT' => env('CUSTOMER_PORTAL_API_TIMEOUT', 30),
```

## Service Registration

### Service Provider

The service is registered in `app/Providers/CustomerPortalApiProvider.php`:

```php
public function register()
{
    App::bind('CustomerPortalApiService', function () {
        return new CustomerPortalApiService;
    });
}
```

### Application Registration

Add to `config/app.php` providers array:

```php
App\Providers\CustomerPortalApiProvider::class,
```

### Facade Registration

Add to `config/app.php` aliases array:

```php
'CustomerPortalApi' => \App\Facades\CustomerPortalApiFacade::class,
```

## API Methods

### Core Request Method

#### `request(string $path, string $method = 'post', array $data = [], bool $isCustomerOperation = false): object`

Base method for all API communications.

**Parameters:**

- `$path`: API endpoint path (e.g., '/api/v1/customers')
- `$method`: HTTP method ('get', 'post', 'put', 'delete')
- `$data`: Request payload array
- `$isCustomerOperation`: Flag for customer-facing operations (affects logging)

**Returns:** Object containing API response

**Example:**

```php
$response = CustomerPortalApi::request('/api/v1/customers', 'get');
```

### Customer Management Methods

#### `createCustomerAccount(array $customerData): object`

Creates a new customer account in the portal.

**Parameters:**

- `$customerData`: Array containing customer information

**Example:**

```php
$customerData = [
    'email' => 'customer@example.com',
    'first_name' => 'John',
    'last_name' => 'Doe',
    'mobile_no' => '+971501234567',
    'date_of_birth' => '1990-01-01',
];

$response = CustomerPortalApi::createCustomerAccount($customerData);
```

#### `updateCustomerAccount(string $customerId, array $customerData): object`

Updates existing customer account information.

**Parameters:**

- `$customerId`: Unique customer identifier
- `$customerData`: Array containing updated customer data

**Example:**

```php
$response = CustomerPortalApi::updateCustomerAccount('12345', [
    'mobile_no' => '+971507654321',
    'address' => 'New address',
]);
```

#### `getCustomerInfo(string $customerId): object`

Retrieves customer information from the portal.

**Parameters:**

- `$customerId`: Unique customer identifier

**Example:**

```php
$customerInfo = CustomerPortalApi::getCustomerInfo('12345');
```

### Policy Management Methods

#### `syncCustomerPolicies(string $customerId, array $policies): object`

Synchronizes customer policies with the portal.

**Parameters:**

- `$customerId`: Unique customer identifier
- `$policies`: Array of policy data

**Example:**

```php
$policies = [
    [
        'policy_number' => 'POL-123456',
        'quote_type' => 'Car',
        'status' => 'Active',
        'premium' => 1500.00,
        'start_date' => '2024-01-01',
        'end_date' => '2024-12-31',
    ]
];

$response = CustomerPortalApi::syncCustomerPolicies('12345', $policies);
```

### Communication Methods

#### `sendCustomerNotification(string $customerId, array $notificationData): object`

Sends notifications to customers via the portal.

**Parameters:**

- `$customerId`: Unique customer identifier
- `$notificationData`: Notification content and metadata

**Example:**

```php
$notificationData = [
    'type' => 'policy_renewal',
    'title' => 'Policy Renewal Reminder',
    'message' => 'Your policy is due for renewal in 30 days.',
    'priority' => 'medium',
    'channels' => ['email', 'sms', 'push'],
];

$response = CustomerPortalApi::sendCustomerNotification('12345', $notificationData);
```

### Preferences Management

#### `updateCustomerPreferences(string $customerId, array $preferences): object`

Updates customer portal preferences and settings.

**Parameters:**

- `$customerId`: Unique customer identifier
- `$preferences`: Array of preference settings

**Example:**

```php
$preferences = [
    'email_notifications' => true,
    'sms_notifications' => false,
    'language' => 'en',
    'timezone' => 'Asia/Dubai',
    'communication_frequency' => 'weekly',
];

$response = CustomerPortalApi::updateCustomerPreferences('12345', $preferences);
```

### Health Check Methods

#### `ping(): bool`

Quick health check to verify API availability.

**Returns:** Boolean indicating if the API is responsive

**Example:**

```php
if (CustomerPortalApi::ping()) {
    // API is available
    echo "Customer Portal API is online";
} else {
    // API is down
    echo "Customer Portal API is unavailable";
}
```

#### `getHealthStatus(): array`

Detailed health status information.

**Returns:** Array with health status details

**Example:**

```php
$health = CustomerPortalApi::getHealthStatus();
// Returns:
// [
//     'status' => 'healthy|unhealthy',
//     'response' => {...},
//     'timestamp' => '2024-01-01T12:00:00.000000Z'
// ]
```

## Usage Examples

### Basic Usage (Dependency Injection)

```php
<?php

namespace App\Http\Controllers;

use App\Services\CustomerPortalApiService;

class CustomerController extends Controller
{
    public function __construct(
        private CustomerPortalApiService $customerPortalApiService
    ) {}

    public function syncToPortal($customerId)
    {
        $customerData = [
            'email' => 'customer@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ];

        $response = $this->customerPortalApiService->updateCustomerAccount(
            $customerId,
            $customerData
        );

        return response()->json($response);
    }
}
```

### Facade Usage

```php
<?php

use App\Facades\CustomerPortalApiFacade as CustomerPortalApi;

class CustomerSyncJob
{
    public function handle()
    {
        // Quick health check
        if (!CustomerPortalApi::ping()) {
            throw new Exception('Customer Portal API is unavailable');
        }

        // Sync customer data
        $response = CustomerPortalApi::createCustomerAccount([
            'email' => 'customer@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        Log::info('Customer synced to portal', ['response' => $response]);
    }
}
```

### Service Integration Pattern

```php
<?php

namespace App\Services;

use App\Facades\CustomerPortalApiFacade as CustomerPortalApi;
use App\Models\Customer;

class CustomerSyncService
{
    /**
     * Sync customer to portal after registration
     */
    public function syncNewCustomer(Customer $customer): bool
    {
        try {
            $customerData = [
                'email' => $customer->email,
                'first_name' => $customer->first_name,
                'last_name' => $customer->last_name,
                'mobile_no' => $customer->mobile_no,
                'date_of_birth' => $customer->date_of_birth,
            ];

            $response = CustomerPortalApi::createCustomerAccount($customerData);

            // Store portal customer ID for future reference
            $customer->update(['portal_customer_id' => $response->customerId]);

            return true;
        } catch (\Exception $e) {
            LoggerService::error('Failed to sync customer to portal', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Sync customer policies to portal
     */
    public function syncCustomerPolicies(Customer $customer): bool
    {
        try {
            $policies = $customer->quotes()
                ->where('status', 'active')
                ->get()
                ->map(function ($quote) {
                    return [
                        'policy_number' => $quote->policy_number,
                        'quote_type' => $quote->quoteType->text,
                        'status' => $quote->status,
                        'premium' => $quote->premium,
                        'start_date' => $quote->policy_start_date,
                        'end_date' => $quote->policy_end_date,
                    ];
                })
                ->toArray();

            CustomerPortalApi::syncCustomerPolicies(
                $customer->portal_customer_id,
                $policies
            );

            return true;
        } catch (\Exception $e) {
            LoggerService::error('Failed to sync customer policies to portal', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
```

## Error Handling

### Exception Handling

The service implements comprehensive error handling:

1. **HTTP Errors**: 4XX and 5XX responses trigger detailed logging
2. **Customer Operations**: All customer-facing operations are logged regardless of status
3. **Timeout Handling**: Configurable timeout with automatic retry logic
4. **Authentication Errors**: Basic auth and token validation

### Logging Strategy

- **Info Level**: Successful requests and responses
- **Warning Level**: Service unavailability (ping failures)
- **Error Level**: API errors, authentication failures, timeouts

### Error Response Format

```php
// Successful Response
{
    "status": "success",
    "data": {...},
    "message": "Operation completed successfully"
}

// Error Response
{
    "status": "error",
    "error": "Error message",
    "code": "ERROR_CODE",
    "details": {...}
}
```

## Integration Patterns

### Observer Integration

```php
<?php

namespace App\Observers;

use App\Facades\CustomerPortalApiFacade as CustomerPortalApi;
use App\Models\Customer;

class CustomerObserver
{
    public function created(Customer $customer): void
    {
        // Sync new customer to portal
        dispatch(function () use ($customer) {
            CustomerPortalApi::createCustomerAccount([
                'email' => $customer->email,
                'first_name' => $customer->first_name,
                'last_name' => $customer->last_name,
            ]);
        })->afterResponse();
    }

    public function updated(Customer $customer): void
    {
        if ($customer->wasChanged(['email', 'first_name', 'last_name'])) {
            // Sync customer updates to portal
            dispatch(function () use ($customer) {
                CustomerPortalApi::updateCustomerAccount(
                    $customer->portal_customer_id,
                    $customer->only(['email', 'first_name', 'last_name'])
                );
            })->afterResponse();
        }
    }
}
```

### Job Queue Integration

```php
<?php

namespace App\Jobs;

use App\Facades\CustomerPortalApiFacade as CustomerPortalApi;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class SyncCustomerToPortalJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private int $customerId,
        private array $customerData
    ) {}

    public function handle(): void
    {
        try {
            CustomerPortalApi::updateCustomerAccount(
                (string) $this->customerId,
                $this->customerData
            );
        } catch (\Exception $e) {
            // Handle failure - maybe retry or notify admins
            $this->fail($e);
        }
    }
}
```

## Security Considerations

### Authentication

- **Basic Auth**: Username/password authentication
- **API Token**: Additional token-based security layer
- **HTTPS Only**: All communications over secure connections

### Data Protection

- **Input Sanitization**: All data is JSON-encoded and validated
- **Sensitive Data**: Customer PII is handled with care
- **Audit Logging**: All customer operations are logged for compliance

### Rate Limiting

- **Timeout Configuration**: Configurable request timeouts
- **Retry Logic**: Built-in retry mechanisms for transient failures
- **Circuit Breaker**: Service availability checks before operations

## Testing Strategy

### Unit Tests

```php
<?php

namespace Tests\Unit\Services;

use App\Services\CustomerPortalApiService;
use Tests\TestCase;

class CustomerPortalApiServiceTest extends TestCase
{
    public function test_can_instantiate_service()
    {
        $service = app(CustomerPortalApiService::class);
        $this->assertInstanceOf(CustomerPortalApiService::class, $service);
    }

    public function test_ping_returns_boolean()
    {
        $service = app(CustomerPortalApiService::class);
        $result = $service->ping();
        $this->assertIsBool($result);
    }
}
```

### Integration Tests

```php
<?php

namespace Tests\Feature\Services;

use App\Facades\CustomerPortalApiFacade as CustomerPortalApi;
use Tests\TestCase;

class CustomerPortalApiIntegrationTest extends TestCase
{
    public function test_can_create_customer_account()
    {
        $customerData = [
            'email' => 'test@example.com',
            'first_name' => 'Test',
            'last_name' => 'User',
        ];

        $response = CustomerPortalApi::createCustomerAccount($customerData);

        $this->assertIsObject($response);
        $this->assertObjectHasProperty('customerId', $response);
    }
}
```

## Performance Considerations

### Optimization Strategies

1. **Singleton Registration**: Service is registered as singleton for reuse
2. **Connection Pooling**: HTTP client reuses connections
3. **Chunked Processing**: Large data sets are processed in chunks
4. **Async Operations**: Use job queues for non-critical operations

### Monitoring

- **Response Times**: Log request/response times for performance monitoring
- **Error Rates**: Track API error rates and patterns
- **Availability**: Monitor service availability via ping checks

## Best Practices

### Usage Guidelines

1. **Always use try-catch blocks** when calling API methods
2. **Log important operations** for audit and debugging
3. **Use job queues** for non-critical synchronization
4. **Implement fallback strategies** for API unavailability
5. **Validate data** before sending to API

### Code Examples

#### Controller Usage

```php
public function syncCustomer(Request $request, Customer $customer)
{
    try {
        $response = CustomerPortalApi::updateCustomerAccount(
            $customer->portal_customer_id,
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Customer synced successfully',
            'portal_response' => $response
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to sync customer'
        ], 500);
    }
}
```

#### Service Layer Usage

```php
public function handleCustomerUpdate(Customer $customer, array $updates)
{
    // Update local database first
    $customer->update($updates);

    // Then sync to portal asynchronously
    dispatch(new SyncCustomerToPortalJob(
        $customer->id,
        $updates
    ))->afterResponse();
}
```

## API Endpoints Reference

### Customer Endpoints

- `POST /api/v1/customers` - Create customer account
- `GET /api/v1/customers/{id}` - Get customer information
- `PUT /api/v1/customers/{id}` - Update customer account
- `PUT /api/v1/customers/{id}/preferences` - Update preferences

### Policy Endpoints

- `POST /api/v1/customers/policies` - Sync customer policies

### Communication Endpoints

- `POST /api/v1/notifications` - Send customer notifications

### System Endpoints

- `GET /api/v1/ping` - Service availability check
- `GET /api/v1/health` - Detailed health status

## Troubleshooting

### Common Issues

#### Service Instantiation Error

```
Target class [CustomerPortalApiService] does not exist.
```

**Solution:** Ensure the service provider is registered in `config/app.php`

#### Timeout Errors

```
TypeError: timeout(): Argument #1 must be of type int|float, string given
```

**Solution:** Ensure `CUSTOMER_PORTAL_API_TIMEOUT` is a numeric value in `.env`

#### Authentication Failures

```
401 Unauthorized
```

**Solution:** Verify API credentials in environment configuration

### Debug Mode

Enable detailed logging by setting log level to `debug` in `config/logging.php`:

```php
'level' => env('LOG_LEVEL', 'debug'),
```

## Future Enhancements

### Planned Features

1. **Webhook Support**: Handle incoming webhooks from Customer Portal
2. **Batch Operations**: Support for bulk customer/policy operations
3. **Real-time Sync**: WebSocket integration for real-time updates
4. **Advanced Caching**: Redis-based response caching
5. **Metrics Collection**: Detailed performance and usage metrics

### Extensibility

The service is designed to be easily extended with additional methods following the established patterns:

```php
public function newCustomerPortalMethod(string $param, array $data): object
{
    $payload = [
        'param' => $param,
        'data' => $data,
        'timestamp' => now()->toISOString(),
    ];

    return $this->request('/api/v1/new-endpoint', 'post', $payload, true);
}
```

## Maintenance

### Regular Tasks

1. **Monitor API health** using ping and health status methods
2. **Review error logs** for patterns and issues
3. **Update API credentials** as needed
4. **Performance monitoring** of request times and success rates

### Version Management

- Follow semantic versioning for service updates
- Maintain backward compatibility for existing integrations
- Document breaking changes in release notes

# Available Plans API Integration

## Overview

This document provides detailed information about the API integration for fetching Cyber Insurance available plans, including endpoint details, request/response formats, and error handling.

## API Endpoint

### Route

**Endpoint**: `POST /quotes/cyber/available-plans/{uuid}`  
**Controller**: `CentralController@loadAvailablePlans`  
**Service**: `CyberQuoteService@getAvailablePlans`

**Route Definition**: `routes/web.php:625`

```php
Route::post('/{quoteType}/available-plans/{id}', [CentralController::class, 'loadAvailablePlans']);
```

### URL Parameters

- `{uuid}`: Cyber quote UUID (required)

### Request Body

**Format**: JSON

**Body**:

```json
{
  "jsonData": true
}
```

**Optional Parameters**:

- `getLatestRating`: Boolean (default: false)

### Request Example

```http
POST /quotes/cyber/available-plans/abc123-def456-ghi789 HTTP/1.1
Host: example.com
Content-Type: application/json
X-CSRF-TOKEN: {token}

{
  "jsonData": true,
  "getLatestRating": false
}
```

## Backend Processing

### Controller Method

**Location**: `app/Http/Controllers/V2/CentralController.php:346-351`

**Note**: The method signature is `loadAvailablePlans($type, $id)` where `$type` is the quote type string (e.g., 'cyber') and `$id` is the quote UUID.

**Code**:

```php
public function loadAvailablePlans($type, $id)
{
    $getLatestRating = request()->input('getLatestRating', false);

    return (new CentralService)->loadAvailablePlans($type, $id, false, false, $getLatestRating);
}
```

### Central Service Routing

**Location**: `app/Services/CentralService.php:341`

**Cyber Case**:

```php
case quoteTypeCode::CYBER:
    return app(CyberQuoteService::class)->getAvailablePlans($id);
```

### Service Method Chain

```
CentralController::loadAvailablePlans()
    ↓
CentralService::loadAvailablePlans() (routes to Cyber)
    ↓
CyberQuoteService::getAvailablePlans()
    ↓
CyberQuoteService::listQuotePlans()
    ↓
CyberQuoteService::getQuotePlans() (KEN API call)
```

## KEN API Integration

### External API Endpoint

**Endpoint**: `/cyber/get-quote-plans`  
**Base URL**: `config('constants.KEN_API_ENDPOINT')`  
**Method**: POST  
**Authentication**: Basic Auth + API Token

### Request Configuration

**Location**: `app/Services/Quotes/CyberQuoteService.php:169-199`

**Request Payload**:

```json
{
  "quoteUID": "abc123-def456-ghi789",
  "lang": "en",
  "getLatestRating": false,
  "callSource": "imcrm"
}
```

**Headers**:

```json
{
  "Content-Type": "application/json",
  "Accept": "application/json",
  "x-api-token": "{KEN_API_TOKEN}",
  "Authorization": "Basic {base64(username:password)}"
}
```

**Timeout**: `config('constants.KEN_API_TIMEOUT')` seconds

### Code Reference

**Location**: `app/Services/Quotes/CyberQuoteService.php:169-239`

**Method**: `getQuotePlans($id, bool $getLatestRating = false)`

## Response Formats

### Success Response

**Structure**:

```json
{
  "quotes": {
    "plans": [
      {
        "id": 123,
        "name": "Cyber Shield Plan",
        "providerName": "ABC Insurance",
        "coverage": "1000000",
        "insurerQuoteNo": "QUOTE-12345",
        "discountPremium": "500.00",
        "vat": "25.00",
        "benefits": {
          "INCLUSION": [
            {
              "code": "BEN001",
              "text": "Data Breach Coverage"
            }
          ]
        },
        "policyWordings": [
          {
            "id": 1,
            "text": "Policy Document.pdf"
          }
        ]
      }
    ]
  }
}
```

### Error Response

**Structure**:

```json
{
  "message": "Error message here"
}
```

**Or**:

```json
{
  "error": "Error description"
}
```

## Response Processing

### Service Layer Processing

**Location**: `app/Services/Quotes/CyberQuoteService.php:151-167`

**Method**: `listQuotePlans($id)`

**Logic**:

```php
public function listQuotePlans($id)
{
    $listQuotePlans = '';
    $quotePlans = $this->getQuotePlans($id);

    // Check for error message
    if (isset($quotePlans->message) && $quotePlans->message != '') {
        $listQuotePlans = $quotePlans->message;
    } else {
        // Extract plans array
        if (gettype($quotePlans) != 'string') {
            $listQuotePlans = $quotePlans->quotes->plans ?? [];
        } else {
            $listQuotePlans = $quotePlans;
        }
    }

    return $listQuotePlans;
}
```

### Frontend Processing

**Location**: `resources/js/inertia/Pages/CyberQuote/Show.vue:377-411`

**Response Handling**:

```javascript
if (typeof res.data === 'string') {
  // Error message
  availablePlansTable.data = res.data;
} else if (res.data?.quotes?.plans && Array.isArray(res.data.quotes.plans)) {
  // Success: Plans in quotes.plans
  availablePlansTable.data = res.data.quotes.plans.map(plan => ({
    ...plan,
    coverageUpTo: plan.coverage ?? '-',
    quoteNumber: plan.insurerQuoteNo ?? '-',
    priceWithoutVat: plan.discountPremium ?? '0',
    vat: plan.vat ?? '0',
    priceWithVat: (
      parseFloat(plan.discountPremium ?? 0) + parseFloat(plan.vat ?? 0)
    ).toFixed(2),
  }));
} else if (Array.isArray(res.data) && res.data.length > 0) {
  // Success: Direct array
  availablePlansTable.data = res.data.map(plan => ({
    // Same mapping
  }));
} else {
  // No plans
  availablePlansTable.data = [];
}
```

## Error Handling

### Exception Types

**Location**: `app/Services/Quotes/CyberQuoteService.php:210-236`

1. **BadResponseException**

   - HTTP error responses (4xx, 5xx)
   - Extracts error message from response body
   - Returns error message string

2. **ConnectException**

   - Network connection failures
   - Returns: `"Connection error occurred."`

3. **RequestException**

   - Request-related errors
   - Returns: `"Request error occurred."`

4. **Exception**
   - General exceptions
   - Returns: `"An unexpected error occurred."`

### Error Response Handling

**Code**:

```php
catch (\GuzzleHttp\Exception\BadResponseException $e) {
    $response = $e->getResponse();
    $contents = (string) $response->getBody();
    $response = json_decode($contents);

    if (isset($response->message)) {
        $responseBodyAsString = $response->message;
    } elseif (isset($response->error)) {
        $responseBodyAsString = $response->error;
    } else {
        $responseBodyAsString = $contents;
    }

    return $responseBodyAsString;
}
```

### Frontend Error Handling

**Location**: `resources/js/inertia/Pages/CyberQuote/Show.vue:413-416`

**Error Display**:

- Error messages displayed in table if API returns string
- Empty array if API call fails
- Console logging for debugging

## Configuration

### Environment Variables

**KEN API Configuration** (in `config/constants.php`):

- `KEN_API_ENDPOINT`: Base URL for KEN API
- `KEN_API_TOKEN`: API token for authentication
- `KEN_API_TIMEOUT`: Request timeout in seconds
- `KEN_API_USER`: Basic authentication username
- `KEN_API_PWD`: Basic authentication password

### Authentication

**Method**: Basic Authentication + API Token

**Basic Auth**:

```
Authorization: Basic {base64(username:password)}
```

**API Token**:

```
x-api-token: {KEN_API_TOKEN}
```

## API Request Flow

```
Frontend: POST /quotes/cyber/available-plans/{uuid}
    ↓
CentralController::loadAvailablePlans()
    ↓
CentralService::loadAvailablePlans() (routes to Cyber)
    ↓
CyberQuoteService::getAvailablePlans()
    ↓
CyberQuoteService::listQuotePlans()
    ↓
CyberQuoteService::getQuotePlans() (KEN API call)
    ↓
KEN API: POST /cyber/get-quote-plans
    ↓
Response Processing
    ↓
Return to Frontend
```

## Response Timeout

**Timeout Configuration**: `config('constants.KEN_API_TIMEOUT')`

**Default**: Typically 30-60 seconds

**Handling**: Request fails if timeout exceeded, returns error message

## Related Files

- **Controller**: `app/Http/Controllers/V2/CentralController.php`
- **Service**: `app/Services/Quotes/CyberQuoteService.php`
- **Central Service**: `app/Services/CentralService.php`
- **Frontend**: `resources/js/inertia/Pages/CyberQuote/Show.vue`
- **Route**: `routes/web.php:625`
- **Config**: `config/constants.php`

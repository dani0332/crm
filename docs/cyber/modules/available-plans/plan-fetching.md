# Plan Fetching Documentation

## Overview

This document explains how Cyber Insurance plans are fetched from the KEN API, including the API integration, request/response handling, and error management.

## API Integration

### KEN API Endpoint

**Endpoint**: `/cyber/get-quote-plans`  
**Base URL**: Configured in `config('constants.KEN_API_ENDPOINT')`  
**Method**: POST  
**Authentication**: Basic Auth + API Token

### Request Configuration

**Location**: `app/Services/Quotes/CyberQuoteService.php:169-199`

**Request Parameters**:

```php
$plansDataArr = [
    'quoteUID' => $id,                    // Quote UUID
    'lang' => 'en',                        // Language
    'getLatestRating' => $getLatestRating, // Get latest rating flag
    'callSource' => strtolower(LeadSourceEnum::IMCRM), // Call source
];
```

**Headers**:

```php
[
    'Content-Type' => 'application/json',
    'Accept' => 'application/json',
    'x-api-token' => $plansApiToken,
    'Authorization' => 'Basic ' . base64_encode($username . ':' . $password),
]
```

**Timeout**: Configured in `config('constants.KEN_API_TIMEOUT')`

### Code Reference

**Location**: `app/Services/Quotes/CyberQuoteService.php:169-239`

**Method**:

```php
public function getQuotePlans($id, bool $getLatestRating = false)
{
    $plansApiEndPoint = config('constants.KEN_API_ENDPOINT').'/cyber/get-quote-plans';
    $plansApiToken = config('constants.KEN_API_TOKEN');
    $plansApiTimeout = config('constants.KEN_API_TIMEOUT');
    $plansApiUserName = config('constants.KEN_API_USER');
    $plansApiPassword = config('constants.KEN_API_PWD');
    $authBasic = base64_encode($plansApiUserName.':'.$plansApiPassword);

    $plansDataArr = [
        'quoteUID' => $id,
        'lang' => 'en',
        'getLatestRating' => $getLatestRating,
        'callSource' => strtolower(LeadSourceEnum::IMCRM),
    ];

    $client = new \GuzzleHttp\Client;

    try {
        $kenRequest = $client->post(
            $plansApiEndPoint,
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'x-api-token' => $plansApiToken,
                    'Authorization' => 'Basic '.$authBasic,
                ],
                'body' => json_encode($plansDataArr),
                'timeout' => $plansApiTimeout,
            ]
        );

        $getStatusCode = $kenRequest->getStatusCode();

        if ($getStatusCode == 200) {
            $getContents = (string) $kenRequest->getBody();
            $getdecodeContents = json_decode($getContents);

            return $getdecodeContents;
        }
    } catch (\GuzzleHttp\Exception\BadResponseException $e) {
        // Error handling
    }

    return 'Failed to fetch quote plans.';
}
```

## Service Layer Methods

### getAvailablePlans()

**Location**: `app/Services/Quotes/CyberQuoteService.php:146-149`

**Purpose**: Public method to get available plans for a quote

**Code**:

```php
public function getAvailablePlans($uuid)
{
    return $this->listQuotePlans($uuid);
}
```

### listQuotePlans()

**Location**: `app/Services/Quotes/CyberQuoteService.php:151-167`

**Purpose**: Processes API response and extracts plan array

**Code**:

```php
public function listQuotePlans($id)
{
    $listQuotePlans = '';
    $quotePlans = $this->getQuotePlans($id);

    if (isset($quotePlans->message) && $quotePlans->message != '') {
        $listQuotePlans = $quotePlans->message;
    } else {
        if (gettype($quotePlans) != 'string') {
            $listQuotePlans = $quotePlans->quotes->plans ?? [];
        } else {
            $listQuotePlans = $quotePlans;
        }
    }

    return $listQuotePlans;
}
```

**Response Handling**:

- If `message` exists → Return message string (error message)
- If object → Extract `quotes.plans` array
- If string → Return string directly

## API Response Structure

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

## Error Handling

### Exception Types

**Location**: `app/Services/Quotes/CyberQuoteService.php:210-236`

1. **BadResponseException**: API returned error response

   - Extracts error message from response
   - Returns error message string

2. **ConnectException**: Connection error

   - Returns: `"Connection error occurred."`

3. **RequestException**: Request error

   - Returns: `"Request error occurred."`

4. **Exception**: General exception
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

## Frontend Integration

### API Call

**Location**: `resources/js/inertia/Pages/CyberQuote/Show.vue:369-420`

**Method**: `onLoadAvailablePlansData()`

**Code**:

```javascript
const onLoadAvailablePlansData = async () => {
  availablePlansTable.isLoading = true;
  let data = {
    jsonData: true,
  };
  let url = `/quotes/cyber/available-plans/${page.props.quote.uuid}`;
  axios
    .post(url, data)
    .then(res => {
      // Process response
      if (typeof res.data === 'string') {
        availablePlansTable.data = res.data; // Error message
      } else if (
        res.data?.quotes?.plans &&
        Array.isArray(res.data.quotes.plans)
      ) {
        availablePlansTable.data = res.data.quotes.plans.map(plan => ({
          ...plan,
          isManualUpdate: plan.isManualUpdate ?? false,
          isDisabled: plan.isDisabled ?? false,
          coverageUpTo: plan.coverage ?? '-',
          quoteNumber: plan.insurerQuoteNo ?? '-',
          priceWithoutVat: plan.discountPremium ?? '0',
          vat: plan.vat ?? '0',
          priceWithVat: (
            parseFloat(plan.discountPremium ?? 0) + parseFloat(plan.vat ?? 0)
          ).toFixed(2),
        }));
      } else if (Array.isArray(res.data) && res.data.length > 0) {
        availablePlansTable.data = res.data.map(plan => ({
          // Same mapping as above
        }));
      } else {
        availablePlansTable.data = [];
      }
    })
    .catch(err => {
      console.log(err);
      availablePlansTable.data = [];
    })
    .finally(() => {
      availablePlansTable.isLoading = false;
    });
};
```

## Route Definition

**Location**: `routes/web.php:625`

**Route**:

```php
Route::post('/{quoteType}/available-plans/{id}', [CentralController::class, 'loadAvailablePlans']);
```

**Usage**: `POST /quotes/cyber/available-plans/{uuid}`

**Controller Method**: `app/Http/Controllers/V2/CentralController.php:346-351`

```php
public function loadAvailablePlans($type, $id)
{
    $getLatestRating = request()->input('getLatestRating', false);

    return (new CentralService)->loadAvailablePlans($type, $id, false, false, $getLatestRating);
}
```

## Central Service Routing

**Location**: `app/Services/CentralService.php:291-341`

**Cyber Case**:

```php
case quoteTypeCode::CYBER:
    return app(CyberQuoteService::class)->getAvailablePlans($id);
```

## Configuration

### Environment Variables

**KEN API Configuration** (in `config/constants.php`):

- `KEN_API_ENDPOINT`: Base API URL
- `KEN_API_TOKEN`: API token for authentication
- `KEN_API_TIMEOUT`: Request timeout in seconds
- `KEN_API_USER`: Basic auth username
- `KEN_API_PWD`: Basic auth password

## Response Processing

### Plan Data Transformation

**Frontend Processing**:

```javascript
plan => ({
  ...plan,
  isManualUpdate: plan.isManualUpdate ?? false,
  isDisabled: plan.isDisabled ?? false,
  coverageUpTo: plan.coverage ?? '-',
  quoteNumber: plan.insurerQuoteNo ?? '-',
  priceWithoutVat: plan.discountPremium ?? '0',
  vat: plan.vat ?? '0',
  priceWithVat: (
    parseFloat(plan.discountPremium ?? 0) + parseFloat(plan.vat ?? 0)
  ).toFixed(2),
});
```

**Fields Mapped**:

- `coverage` → `coverageUpTo`
- `insurerQuoteNo` → `quoteNumber`
- `discountPremium` → `priceWithoutVat`
- `vat` → `vat`
- Calculated → `priceWithVat`

## Related Files

- **Service**: `app/Services/Quotes/CyberQuoteService.php`
- **Controller**: `app/Http/Controllers/V2/CentralController.php`
- **Central Service**: `app/Services/CentralService.php`
- **Frontend**: `resources/js/inertia/Pages/CyberQuote/Show.vue`
- **Route**: `routes/web.php:625`

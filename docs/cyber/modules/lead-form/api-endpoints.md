# API Endpoints Documentation

## Overview

This document provides comprehensive documentation for all API endpoints related to the Cyber Insurance lead form module.

## Base URL

All endpoints are prefixed with `/personal-quotes/cyber` and require authentication.

**Note**: The routes are defined under the `personal-quotes` prefix group in `routes/web.php:305-307`.

## Authentication

All endpoints require:

- User authentication (logged in)
- Appropriate permissions (see Permissions section)

## Permissions

### Required Permissions

- `CYBER_QUOTES_CREATE`: Create new quotes
- `CYBER_QUOTES_EDIT`: Edit existing quotes
- `CYBER_QUOTES_SHOW`: View quote details
- `CYBER_QUOTES_LIST`: List quotes
- `VIEW_ALL_LEADS`: View all leads (admin override)

## Endpoints

### 1. Get Create Form

**Endpoint**: `GET /personal-quotes/cyber/create`  
**Route Name**: `cyber-quotes-create`  
**Controller Method**: `CyberQuoteController@create`  
**Permission**: `CYBER_QUOTES_CREATE`

**Description**: Returns the form page with lookup data (nationalities, emirates) for creating a new Cyber quote.

**Response**: Inertia.js response with form component and data

**Response Data**:

```json
{
  "lookUpData": {
    "nationality": [
      {
        "id": 1,
        "text": "Emirati"
      }
    ],
    "emiratesOfRegistration": [
      {
        "id": 1,
        "text": "Dubai"
      }
    ]
  }
}
```

**Code Reference**: `app/Http/Controllers/V2/CyberQuoteController.php:61-66`

---

### 2. Store New Quote

**Endpoint**: `POST /personal-quotes/cyber`  
**Route Name**: `cyber-quotes-store`  
**Controller Method**: `CyberQuoteController@store`  
**Permission**: `CYBER_QUOTES_CREATE`  
**Request Validation**: `CyberQuoteRequest`

**Description**: Creates a new Cyber Insurance quote with customer information.

**Request Body**:

```json
{
  "first_name": "John",
  "last_name": "Doe",
  "email": "john.doe@example.com",
  "mobile_no": "+971501234567",
  "dob": "1990-01-15",
  "nationality_id": 56,
  "emirate_of_registration_id": 2
}
```

**Request Validation Rules**:

- `first_name`: required|between:1,20|regex:/^[a-zA-Z\s\-]+$/
- `last_name`: required|between:1,50|regex:/^[a-zA-Z\s\-]+$/
- `email`: required|email
- `mobile_no`: required|string
- `dob`: required|date
- `nationality_id`: required|exists:nationalities,id
- `emirate_of_registration_id`: required|exists:emirates,id

**Success Response**:

- **Status**: 302 Redirect
- **Location**: `/personal-quotes/cyber/{uuid}` (route name: `cyber-quotes-show`)
- **Message**: "Quote is created successfully."

**Error Response**:

- **Status**: 422 Unprocessable Entity
- **Body**: Validation errors

```json
{
  "errors": {
    "first_name": ["The first name field is required."],
    "email": ["The email must be a valid email address."]
  }
}
```

**Code Reference**:

- Controller: `app/Http/Controllers/V2/CyberQuoteController.php:68-77`
- Request: `app/Http/Requests/Cyber/CyberQuoteRequest.php`

**Process Flow**:

1. Validate request data
2. Call external Capi API (`/api/cyber/create`) to create quote
3. External API creates `PersonalQuote` and `CyberQuote` records
4. Call `selfAssign()` if `advisorId` was set (non-admin users)
5. Redirect to quote show page

**Note**: ILA (Instant Lead Allocation) is triggered separately, not during form submission.

---

### 3. Get Edit Form

**Endpoint**: `GET /personal-quotes/cyber/{uuid}/edit`  
**Route Name**: `cyber-quotes-edit`  
**Controller Method**: `CyberQuoteController@edit`  
**Permission**: `CYBER_QUOTES_EDIT` or `VIEW_ALL_LEADS`

**Description**: Returns the edit form page with existing quote data pre-filled.

**URL Parameters**:

- `uuid`: Quote UUID (required)

**Response**: Inertia.js response with form component and quote data

**Response Data**:

```json
{
  "quote": {
    "uuid": "abc123-def456-ghi789",
    "first_name": "John",
    "last_name": "Doe",
    "email": "john.doe@example.com",
    "mobile_no": "+971501234567",
    "dob": "1990-01-15",
    "nationality_id": 56,
    "cyber_quote": {
      "emirate_of_registration_id": 2
    }
  },
  "lookUpData": {
    "nationality": [...],
    "emiratesOfRegistration": [...]
  }
}
```

**Code Reference**: `app/Http/Controllers/V2/CyberQuoteController.php:79-87`

---

### 4. Update Existing Quote

**Endpoint**: `PUT /personal-quotes/cyber/{uuid}` or `PATCH /personal-quotes/cyber/{uuid}`  
**Route Name**: `cyber-quotes-update`  
**Controller Method**: `CyberQuoteController@update`  
**Permission**: `CYBER_QUOTES_EDIT` or `VIEW_ALL_LEADS`  
**Request Validation**: `CyberQuoteRequest`

**Description**: Updates an existing Cyber Insurance quote.

**URL Parameters**:

- `uuid`: Quote UUID (required)

**Request Body**:

```json
{
  "first_name": "Jane",
  "last_name": "Doe",
  "email": "john.doe@example.com",
  "mobile_no": "+971501234567",
  "dob": "1990-01-15",
  "nationality_id": 56,
  "emirate_of_registration_id": 2
}
```

**Note**: `email` and `mobile_no` are disabled in edit mode and should match existing values.

**Request Validation Rules**: Same as Store endpoint

**Success Response**:

- **Status**: 302 Redirect
- **Location**: `/personal-quotes/cyber/{uuid}` (route name: `cyber-quotes-show`)
- **Message**: "Quote is updated successfully."

**Error Response**:

- **Status**: 422 Unprocessable Entity
- **Body**: Validation errors (same format as Store)

**Code Reference**: `app/Http/Controllers/V2/CyberQuoteController.php:89-94`

**Process Flow**:

1. Find quote by UUID
2. Validate request data
3. Update `PersonalQuote` record
4. Update `CyberQuote` record
5. Redirect to quote show page

---

### 5. Show Quote Details

**Endpoint**: `GET /personal-quotes/cyber/{uuid}`  
**Route Name**: `cyber-quotes-show`  
**Controller Method**: `CyberQuoteController@show`  
**Permission**: `CYBER_QUOTES_SHOW` or `VIEW_ALL_LEADS`

**Description**: Displays detailed information about a Cyber quote.

**URL Parameters**:

- `uuid`: Quote UUID (required)

**Response**: Inertia.js response with quote show component and data

**Response Data**: Comprehensive quote data including:

- Customer information
- Quote status
- Assigned advisor
- Payment information
- Plan details
- Related records

**Code Reference**: `app/Http/Controllers/V2/CyberQuoteController.php:96-101`

---

## Error Responses

### Validation Errors (422)

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "field_name": ["Error message 1", "Error message 2"]
  }
}
```

### Unauthorized (403)

```json
{
  "message": "This action is unauthorized."
}
```

### Not Found (404)

```json
{
  "message": "Quote not found."
}
```

### Server Error (500)

```json
{
  "message": "Server Error"
}
```

## Request/Response Examples

### Create Quote Example

**Request**:

```http
POST /personal-quotes/cyber HTTP/1.1
Host: example.com
Content-Type: application/json
Authorization: Bearer {token}

{
  "first_name": "John",
  "last_name": "Doe",
  "email": "john.doe@example.com",
  "mobile_no": "+971501234567",
  "dob": "1990-01-15",
  "nationality_id": 56,
  "emirate_of_registration_id": 2
}
```

**Success Response**:

```http
HTTP/1.1 302 Found
Location: /personal-quotes/cyber/abc123-def456-ghi789
```

### Update Quote Example

**Request**:

```http
PUT /personal-quotes/cyber/abc123-def456-ghi789 HTTP/1.1
Host: example.com
Content-Type: application/json
Authorization: Bearer {token}

{
  "first_name": "Jane",
  "last_name": "Doe",
  "email": "john.doe@example.com",
  "mobile_no": "+971501234567",
  "dob": "1990-01-15",
  "nationality_id": 56,
  "emirate_of_registration_id": 2
}
```

**Success Response**:

```http
HTTP/1.1 302 Found
Location: /personal-quotes/cyber/abc123-def456-ghi789
```

## Route Definitions

**Location**: `routes/web.php:305-307`

```php
Route::prefix('personal-quotes')->group(function () {
    Route::resource('/cyber', CyberQuoteController::class)
        ->names(generateRouteNames('cyber-quotes'));
});
```

**Generated Routes** (with `personal-quotes` prefix):

- `GET /personal-quotes/cyber` → `index` → `cyber-quotes-list`
- `GET /personal-quotes/cyber/create` → `create` → `cyber-quotes-create`
- `POST /personal-quotes/cyber` → `store` → `cyber-quotes-store`
- `GET /personal-quotes/cyber/{uuid}` → `show` → `cyber-quotes-show`
- `GET /personal-quotes/cyber/{uuid}/edit` → `edit` → `cyber-quotes-edit`
- `PUT /personal-quotes/cyber/{uuid}` → `update` → `cyber-quotes-update`
- `DELETE /personal-quotes/cyber/{uuid}` → `destroy` → `cyber-quotes-delete`

**Code Reference**:

- Route definition: `routes/web.php:305-307`
- Route name generator: `app/helpers/Helper.php:479-491`

## Related Files

- **Controller**: `app/Http/Controllers/V2/CyberQuoteController.php`
- **Request Validation**: `app/Http/Requests/Cyber/CyberQuoteRequest.php`
- **Service**: `app/Services/Quotes/CyberQuoteService.php`
- **Routes**: `routes/web.php`

## Notes

- All endpoints use Inertia.js for responses (except API-only endpoints)
- CSRF protection is enabled for all POST/PUT/DELETE requests
- All endpoints require authentication
- Permission checks are performed via middleware
- Validation errors are returned in a consistent format

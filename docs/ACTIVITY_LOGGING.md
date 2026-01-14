# Activity Logging System Documentation

## Overview

This document explains how the Activity Logging system works in the application. The system uses Spatie Activity Log package with custom enhancements for automatic request-based batching, old/new value tracking, and activity grouping.

---

## How It Works - Complete Flow

### System Architecture Diagram

```
┌─────────────────────────────────────────────────────────────────────────┐
│                         HTTP REQUEST LIFECYCLE                          │
└─────────────────────────────────────────────────────────────────────────┘

1. REQUEST ARRIVES
   │
   ├─► ActivityLogBatchMiddleware::handle()
   │   │
   │   └─► LogBatch::startBatch()
   │       └─► Spatie generates UUID (batch_uuid) and sets batch context
   │
   │
2. REQUEST PROCESSING
   │
   ├─► Controller/Service executes business logic
   │   │
   │   ├─► Model with SpatieActivityLog trait is modified
   │   │   │
   │   │   └─► Eloquent Event Fired (created/updated/deleted)
   │   │       │
   │   │       └─► Spatie Package intercepts event
   │   │           │
   │   │           └─► Calls model->getActivitylogOptions()
   │   │               │
   │   │               └─► SpatieActivityLog trait
   │   │                   ├─► Determines log name
   │   │                   ├─► Configures what to log
   │   │                   └─► Creates ActivityLog model instance
   │   │                       │
   │   │                       └─► Calls model->tapActivity()
   │   │                           │
   │   │                           └─► SpatieActivityLog::tapActivity()
   │   │                               ├─► Sets: url, feature, ip_address, code
   │   │                               ├─► For 'updated' events:
   │   │                               │   ├─► Extract old values (getOriginal)
   │   │                               │   ├─► Extract new values (getChanges)
   │   │                               │   └─► Store in properties: {old: {...}, attributes: {...}}
   │   │                               └─► ActivityLog model ready
   │   │                                   │
   │   │                                   └─► Spatie automatically sets batch_uuid (from LogBatch context)
   │   │                                   │
   │   │                                   └─► ActivityLog->save() called
   │   │                                       └─► Saves to database immediately with batch_uuid
   │   │
   │   └─► Multiple models can be modified in same request
   │       └─► Each activity automatically gets same batch_uuid
   │
   │
3. REQUEST COMPLETES
   │
   └─► ActivityLogBatchMiddleware::handle() finally block
       │
       └─► LogBatch::endBatch()
           └─► Spatie closes batch context (all activities already saved with batch_uuid)
```

---

## Component Breakdown

### 1. ActivityLogBatchMiddleware

**Purpose:** Automatically starts and ends Spatie's LogBatch for each HTTP request.

**Location:** `app/Http/Middleware/ActivityLogBatchMiddleware.php`

**How it works:**

- Registered in `RouteServiceProvider` for all web and API routes
- Runs at the **start** of every request
- Uses `try...finally` to ensure batch always ends, even if exceptions occur
- Uses **Spatie's LogBatch** facade for automatic batch UUID management

**Code Example:**

```php
public function handle(Request $request, Closure $next): Response
{
    // Start Spatie's LogBatch - all activities will share the same batch_uuid
    LogBatch::startBatch();

    try {
        $response = $next($request);
    } finally {
        // Always end batch when request completes (even if exception occurs)
        // This ensures all activities are properly saved with the batch_uuid
        LogBatch::endBatch();
    }

    return $response;
}
```

**How Spatie's LogBatch Works:**

- `LogBatch::startBatch()` generates a UUID and stores it in context
- All activities created while the batch is open automatically get the same `batch_uuid`
- Spatie's `ActivityLogger` automatically sets `batch_uuid` on each activity before saving
- `LogBatch::endBatch()` closes the batch context
- Activities save individually but share the same `batch_uuid` for grouping

**Why `try...finally`?**

- The `finally` block **always executes**, regardless of:
  - Successful request completion
  - Exceptions thrown during request processing
  - Errors in controllers/services
- This ensures `LogBatch::endBatch()` is always called, properly closing the batch context
- Without `finally`, if an exception occurs, the batch context might remain open

**Flow:**

```
Request arrives
  ↓
LogBatch::startBatch() → Spatie generates UUID and sets batch context
  ↓
try {
  Process request (controller, services, models)
  ↓
  Model modified → Spatie creates ActivityLog
  ↓
  Spatie automatically sets batch_uuid (from LogBatch context)
  ↓
  ActivityLog->save() → Saves to database with batch_uuid
  ↓
  More activities → All get same batch_uuid automatically
  ↓
  Response ready
} finally {
  ↓
  LogBatch::endBatch() → Close Spatie's batch context
}
```

---

### 2. ActivityLog Model

**Purpose:** Extends Spatie's Activity model with custom fillable attributes.

**Location:** `app/Models/ActivityLog.php`

**Key Features:**

- Extends Spatie's `Activity` model
- Adds custom fillable attributes: `url`, `feature`, `ip_address`, `code`
- Spatie automatically handles `batch_uuid` assignment when `LogBatch` is open
- No custom `save()` override needed

---

### 3. SpatieActivityLog Trait

**Purpose:** Configures what and how to log for models using this trait.

**Location:** `app/Traits/SpatieActivityLog.php`

**Key Methods:**

#### `getActivitylogOptions()`

- Configures Spatie package:
  - `logAll()` - Log all attributes
  - `logOnlyDirty()` - Only log changed attributes
  - `useLogName()` - Custom log name
  - `setDescriptionForEvent()` - Human-readable description

#### `tapActivity(Activity $activity, string $eventName)`

Called **before** activity is saved. Enriches activity with:

- `url` - Current request URI
- `feature` - From Context (set by LoggerService)
- `ip_address` - Client IP address
- `code` - From Context (set by LoggerService)
- `properties` - For 'updated' events:
  ```json
  {
    "old": {
      "status": "pending",
      "amount": 1000
    },
    "attributes": {
      "status": "approved",
      "amount": 1500
    }
  }
  ```

**Old/New Value Tracking:**

- Uses `getOriginal()` to get old values
- Uses `getChanges()` to get new values
- Stores both in `properties` JSON column

#### `getActivityLogName()`

Determines log name with priority:

1. `$activityLogName` property (if exists)
2. `getModelActivityLogName()` method (if exists)
3. Convert class name: `PaymentSplits` → `"Payment Splits"`

---

## Complete Example Flow

### Scenario: User updates a Payment model

```
1. HTTP Request: PUT /api/payments/123
   │
   ├─► ActivityLogBatchMiddleware starts
   │   └─► LogBatch::startBatch()
   │       └─► Spatie generates UUID: "550e8400-e29b-41d4-a716-446655440000"
   │       └─► Batch context is now open
   │
2. Controller: PaymentController@update
   │
   ├─► Payment model loaded (has SpatieActivityLog trait)
   │
   ├─► Payment->status = 'approved'
   │   Payment->amount = 1500
   │   Payment->save()
   │
   ├─► Eloquent fires 'updated' event
   │
   ├─► Spatie intercepts event
   │   └─► Creates ActivityLog instance
   │       ├─► log_name: "Payment" (from trait)
   │       ├─► description: "User 'John Doe' performed the 'updated' action on Payment - Changed fields: status, amount"
   │       ├─► subject_type: "App\Models\Payment"
   │       ├─► subject_id: 123
   │       ├─► causer_type: "App\Models\User"
   │       ├─► causer_id: 5
   │       └─► event: "updated"
   │
   ├─► tapActivity() called
   │   └─► Sets:
   │       ├─► url: "/api/payments/123"
   │       ├─► feature: "payment_management"
   │       ├─► ip_address: "192.168.1.1"
   │       ├─► code: "PAY_UPDATE"
   │       └─► properties:
   │           {
   │             "old": {
   │               "status": "pending",
   │               "amount": 1000
   │             },
   │             "attributes": {
   │               "status": "approved",
   │               "amount": 1500
   │             }
   │           }
   │
   ├─► Spatie automatically sets batch_uuid
   │   └─► batch_uuid: "550e8400-e29b-41d4-a716-446655440000" (from LogBatch context)
   │
   ├─► ActivityLog->save() called
   │   └─► Saves to database immediately with batch_uuid
   │
3. More operations in same request...
   │
   ├─► Another model updated
   │   └─► Spatie creates another ActivityLog
   │   └─► Automatically gets same batch_uuid: "550e8400-..."
   │   └─► Saves to database immediately
   │
4. Request completes
   │
   └─► ActivityLogBatchMiddleware ends
       └─► LogBatch::endBatch()
           └─► Spatie closes batch context
           └─► All activities already saved with same batch_uuid
```

---

## Key Benefits

### 1. **Request Grouping**

- All activities in same request automatically share same `batch_uuid`
- Easy to query: `Activity::forBatch($batchUuid)->get()`
- Useful for debugging and auditing
- Example: When a user deletes an Author, all cascading Book deletions share the same batch_uuid

### 2. **Old/New Value Tracking**

- For updates, both old and new values are stored
- Stored in `properties` JSON column
- Frontend can display side-by-side comparison

### 3. **Automatic**

- No manual batching code needed
- Works automatically for all models with `SpatieActivityLog` trait
- Middleware handles batch lifecycle automatically
- Spatie's LogBatch handles batch UUID assignment automatically

### 4. **Simple & Clean**

- Uses Spatie's built-in batching functionality
- Minimal code, maximum functionality

---

## Configuration

### Config File: `config/activitylog.php`

The main configuration option:

```php
'enabled' => env('ACTIVITY_LOGGER_ENABLED', true),  // Enable/disable globally
```

### Environment Variables

```env
ACTIVITY_LOGGER_ENABLED=true
```

**Note:** Batching is handled automatically by the middleware using Spatie's `LogBatch`. No additional configuration needed.

---

## Database Schema

### activity_log Table

```sql
- id (bigint, primary key)
- log_name (varchar) - Category/type of log
- description (text) - Human-readable description
- subject_type (varchar) - Model class (e.g., "App\Models\Payment")
- subject_id (bigint) - Model ID
- event (varchar) - "created", "updated", "deleted"
- causer_type (varchar) - User model class
- causer_id (bigint) - User ID
- properties (json) - Old/new values, metadata
- batch_uuid (uuid) - Groups activities from same request
- url (varchar) - Request URI
- feature (varchar) - Feature name
- ip_address (varchar) - Client IP
- code (varchar) - Activity code
- created_at (timestamp)
- updated_at (timestamp)
```

---

## Visual Flow Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                    HTTP REQUEST ARRIVES                     │
└─────────────────────────────────────────────────────────────┘
                          │
                          ▼
        ┌─────────────────────────────────────┐
        │  ActivityLogBatchMiddleware          │
        │  ┌───────────────────────────────┐  │
        │  │ LogBatch::startBatch()         │  │
        │  │ • Spatie generates UUID       │  │
        │  │ • Sets batch context          │  │
        │  └───────────────────────────────┘  │
        └─────────────────────────────────────┘
                          │
                          ▼
        ┌─────────────────────────────────────┐
        │  Controller/Service                 │
        │  • Business logic                   │
        │  • Model operations                 │
        └─────────────────────────────────────┘
                          │
                          ▼
        ┌─────────────────────────────────────┐
        │  Model with SpatieActivityLog       │
        │  ┌───────────────────────────────┐  │
        │  │ Eloquent Event (updated)      │  │
        │  └───────────────────────────────┘  │
        │              │                       │
        │              ▼                       │
        │  ┌───────────────────────────────┐  │
        │  │ Spatie Package                 │  │
        │  │ • Creates ActivityLog          │  │
        │  │ • Sets batch_uuid (automatic)  │  │
        │  │ • Calls tapActivity()          │  │
        │  └───────────────────────────────┘  │
        │              │                       │
        │              ▼                       │
        │  ┌───────────────────────────────┐  │
        │  │ tapActivity()                 │  │
        │  │ • Sets url, feature, IP       │  │
        │  │ • Stores old/new values       │  │
        │  └───────────────────────────────┘  │
        └─────────────────────────────────────┘
                          │
                          ▼
        ┌─────────────────────────────────────┐
        │  ActivityLog->save()                  │
        │  ┌───────────────────────────────┐  │
        │  │ Saves to database             │  │
        │  │ • With batch_uuid             │  │
        │  │ • Immediately                 │  │
        │  └───────────────────────────────┘  │
        └─────────────────────────────────────┘
                          │
                          ▼
        ┌─────────────────────────────────────┐
        │  Request Processing Continues...      │
        │  • More activities created           │
        │  • All get same batch_uuid           │
        └─────────────────────────────────────┘
                          │
                          ▼
        ┌─────────────────────────────────────┐
        │  ActivityLogBatchMiddleware          │
        │  ┌───────────────────────────────┐  │
        │  │ LogBatch::endBatch()          │  │
        │  │ • Close batch context         │  │
        │  └───────────────────────────────┘  │
        └─────────────────────────────────────┘
                          │
                          ▼
        ┌─────────────────────────────────────┐
        │  Database: activity_log              │
        │  • All activities saved              │
        │  • Same batch_uuid                   │
        └─────────────────────────────────────┘
```

---

## Summary

The Activity Logging system works in these key steps:

1. **Request Start:** Middleware calls `LogBatch::startBatch()` - Spatie generates UUID and sets batch context
2. **Model Changes:** Spatie intercepts Eloquent events, creates activities
3. **Enrichment:** `tapActivity()` adds metadata (URL, IP, old/new values)
4. **Automatic Batching:** Spatie automatically sets `batch_uuid` on each activity from the LogBatch context
5. **Immediate Save:** Each activity saves to database immediately with `batch_uuid`
6. **Request End:** Middleware calls `LogBatch::endBatch()` - closes batch context
7. **Grouping:** All activities share same `batch_uuid` for easy querying

This approach provides:

- ✅ Request-level grouping (all activities share same batch_uuid)
- ✅ Complete audit trail with old/new values
- ✅ Zero manual intervention needed
- ✅ Simple implementation using Spatie's built-in functionality
- ✅ Easy querying: `Activity::forBatch($batchUuid)->get()`

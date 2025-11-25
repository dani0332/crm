# Activity Logging System Documentation

## Overview

This document explains how the Activity Logging system works in the application. The system uses Spatie Activity Log package with custom enhancements for automatic batching, old/new value tracking, and request-based activity grouping.

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
   │   └─► ActivityLogBatchHandler::startBatch()
   │       ├─► Generate UUID (batch_uuid)
   │       ├─► Set isOpen = true
   │       └─► Initialize empty batch array []
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
   │   │                                   └─► ActivityLog->save() called
   │   │                                       │
   │   │                                       └─► ActivityLog::save() override
   │   │                                           │
   │   │                                           ├─► Check: batch_enabled config?
   │   │                                           │   ├─► NO → parent::save() (immediate insert)
   │   │                                           │   └─► YES → Continue
   │   │                                           │
   │   │                                           └─► Check: ActivityLogBatchHandler::isOpen()?
   │   │                                               ├─► NO → parent::save() (immediate insert)
   │   │                                               └─► YES → ActivityLogBatchHandler::addToBatch()
   │   │                                                   │
   │   │                                                   └─► formatActivity() converts to array
   │   │                                                       └─► Add to in-memory batch array
   │   │                                                           └─► Return true (queued, not saved yet)
   │   │
   │   └─► Multiple models can be modified in same request
   │       └─► Each activity added to same batch array
   │
   │
3. REQUEST COMPLETES
   │
   └─► ActivityLogBatchMiddleware::handle() finally block
       │
       └─► ActivityLogBatchHandler::endBatch()
           │
           ├─► Check: batch is open and not empty?
           │   ├─► NO → Reset state (doReset)
           │   └─► YES → Continue
           │
           └─► insertBatch()
               │
               ├─► Ensure all activities have batch_uuid
               ├─► Bulk insert all activities using Query Builder
               │   └─► INSERT INTO activity_log (...) VALUES (...), (...), (...)
               │
               └─► doReset()
                   ├─► Clear batch array
                   ├─► Clear batch_uuid
                   └─► Set isOpen = false
```

---

## Component Breakdown

### 1. ActivityLogBatchMiddleware

**Purpose:** Automatically starts and ends batches for each HTTP request.

**Location:** `app/Http/Middleware/ActivityLogBatchMiddleware.php`

**How it works:**
- Registered in `RouteServiceProvider` for all web and API routes
- Runs at the **start** of every request
- Uses `try...finally` to ensure batch always ends, even if exceptions occur

**Code Example:**
```php
public function handle(Request $request, Closure $next): Response
{
    // Start batch at the beginning of request
    ActivityLogBatchHandler::startBatch();

    try {
        $response = $next($request);
    } finally {
        // Always end batch when request completes (even if exception occurs)
        ActivityLogBatchHandler::endBatch();
    }

    return $response;
}
```

**Why `try...finally`?**
- The `finally` block **always executes**, regardless of:
  - Successful request completion
  - Exceptions thrown during request processing
  - Errors in controllers/services
- This ensures activities are **never lost** - they're always inserted when the request ends
- Without `finally`, if an exception occurs, `endBatch()` wouldn't be called and activities would remain in memory

**Flow:**
```
Request arrives
  ↓
startBatch() → Generate UUID, initialize batch
  ↓
try {
  Process request (controller, services, models)
  ↓
  Activities added to batch array
  ↓
  Response ready
} finally {
  ↓
  endBatch() → Bulk insert all activities (ALWAYS executes)
  ↓
  Reset batch state
}
```

---

### 2. ActivityLogBatchHandler (Singleton)

**Purpose:** Manages the in-memory batch of activities during a request.

**Location:** `app/Services/ActivityLogBatchHandler.php`

**Key Properties:**
- `$batchUuid` - Unique identifier for all activities in this request
- `$batch` - Array of activity data waiting to be inserted
- `$isOpen` - Boolean flag indicating if batch is active

**Key Methods:**

#### `startBatch()`
- Generates a new UUID for the batch
- Sets `isOpen = true`
- Initializes empty batch array
- Called automatically by middleware

#### `isOpen()`
- Returns whether a batch is currently open
- Used by `ActivityLog` model to decide batching vs immediate save

#### `addToBatch(ActivityLog $activity)`
- Converts ActivityLog model to array format
- Adds to in-memory batch array
- If batch is not open, saves immediately instead

#### `endBatch()`
- Bulk inserts all activities in batch array
- Uses Query Builder for performance (bypasses Eloquent)
- Resets batch state
- Called automatically by middleware

#### `formatActivity(ActivityLog $activity, bool $includeBatchUuid)`
- Converts model attributes to array
- Ensures properties JSON is encoded
- Adds timestamps if missing
- Optionally includes batch_uuid

---

### 3. ActivityLog Model

**Purpose:** Extends Spatie's Activity model to intercept saves for batching.

**Location:** `app/Models/ActivityLog.php`

**Key Override:**

```php
public function save(array $options = []): bool
{
    // If batching disabled → save immediately
    if (!config('activitylog.batch_enabled', true)) {
        return parent::save($options);
    }

    // If batch open → add to batch (don't save yet)
    if (ActivityLogBatchHandler::isOpen()) {
        ActivityLogBatchHandler::addToBatch($this);
        return true; // Pretend it's saved
    }

    // Batch not open → save immediately
    return parent::save($options);
}
```

**Why override `save()`?**
- Spatie package calls `$activity->save()` internally
- We intercept this to check if batching is enabled
- If yes, we queue it instead of saving immediately

---

### 4. SpatieActivityLog Trait

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
   │   └─► Batch UUID: "550e8400-e29b-41d4-a716-446655440000"
   │   └─► Batch array: []
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
   ├─► ActivityLog->save() called
   │   └─► Check: batch_enabled? YES
   │   └─► Check: isOpen()? YES
   │   └─► ActivityLogBatchHandler::addToBatch()
   │       └─► Format to array, add to batch
   │       └─► Batch array now: [activity1_data]
   │
3. More operations in same request...
   │
   ├─► Another model updated
   │   └─► Another activity added to batch
   │   └─► Batch array: [activity1_data, activity2_data]
   │
4. Request completes
   │
   └─► ActivityLogBatchMiddleware ends
       └─► ActivityLogBatchHandler::endBatch()
           └─► Bulk INSERT:
               INSERT INTO activity_log 
               (batch_uuid, log_name, description, ..., properties, created_at, updated_at)
               VALUES
               ('550e8400-...', 'Payment', 'User...', ..., '{"old":{...}}', NOW(), NOW()),
               ('550e8400-...', 'Invoice', 'User...', ..., '{"old":{...}}', NOW(), NOW())
           └─► Reset batch state
```

---

## Key Benefits

### 1. **Performance**
- **Before:** Each activity = 1 database INSERT (N queries for N activities)
- **After:** All activities in request = 1 bulk INSERT (1 query for N activities)
- **Result:** Significant reduction in database queries

### 2. **Request Grouping**
- All activities in same request share same `batch_uuid`
- Easy to query: "Show all activities from this request"
- Useful for debugging and auditing

### 3. **Old/New Value Tracking**
- For updates, both old and new values are stored
- Stored in `properties` JSON column
- Frontend can display side-by-side comparison

### 4. **Automatic**
- No manual batching code needed
- Works automatically for all models with `SpatieActivityLog` trait
- Middleware handles everything

---

## Configuration

### Config File: `config/activitylog.php`

```php
'enabled' => env('ACTIVITY_LOGGER_ENABLED', true),        // Enable/disable globally
'batch_enabled' => env('ACTIVITY_LOGGER_BATCH_ENABLED', true),  // Enable/disable batching
```

### Environment Variables

```env
ACTIVITY_LOGGER_ENABLED=true
ACTIVITY_LOGGER_BATCH_ENABLED=true
```

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
        │  │ startBatch()                   │  │
        │  │ • Generate UUID                │  │
        │  │ • isOpen = true                │  │
        │  │ • batch = []                   │  │
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
        │  │ Check: batch_enabled?         │  │
        │  │ Check: isOpen()?              │  │
        │  │ → addToBatch()                │  │
        │  └───────────────────────────────┘  │
        └─────────────────────────────────────┘
                          │
                          ▼
        ┌─────────────────────────────────────┐
        │  ActivityLogBatchHandler             │
        │  ┌───────────────────────────────┐  │
        │  │ formatActivity()              │  │
        │  │ • Convert to array            │  │
        │  │ • Add batch_uuid              │  │
        │  │ • Add to batch[]              │  │
        │  └───────────────────────────────┘  │
        └─────────────────────────────────────┘
                          │
                          ▼
        ┌─────────────────────────────────────┐
        │  Request Processing Continues...      │
        │  • More activities added to batch    │
        └─────────────────────────────────────┘
                          │
                          ▼
        ┌─────────────────────────────────────┐
        │  ActivityLogBatchMiddleware          │
        │  ┌───────────────────────────────┐  │
        │  │ endBatch()                    │  │
        │  │ • Bulk INSERT                 │  │
        │  │ • Reset state                 │  │
        │  └───────────────────────────────┘  │
        └─────────────────────────────────────┘
                          │
                          ▼
        ┌─────────────────────────────────────┐
        │  Database: activity_log              │
        │  • All activities inserted          │
        │  • Same batch_uuid                  │
        └─────────────────────────────────────┘
```

---

## Summary

The Activity Logging system works in these key steps:

1. **Request Start:** Middleware opens a batch (generates UUID)
2. **Model Changes:** Spatie intercepts Eloquent events, creates activities
3. **Enrichment:** `tapActivity()` adds metadata (URL, IP, old/new values)
4. **Batching:** Activities are queued in memory instead of saving immediately
5. **Request End:** Middleware bulk inserts all activities in one query
6. **Grouping:** All activities share same `batch_uuid` for easy querying

This approach provides:
- ✅ Better performance (bulk inserts)
- ✅ Request-level grouping
- ✅ Complete audit trail with old/new values
- ✅ Zero manual intervention needed


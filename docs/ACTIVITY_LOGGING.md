# Activity Logging Implementation Guide

This guide explains how to implement activity logging for new and existing models using the `SpatieActivityLog` trait.

## Overview

The activity logging system uses the [Spatie Activity Log](https://github.com/spatie/laravel-activitylog) package with custom enhancements to track model changes. It automatically logs `created`, `updated`, and `deleted` events with both old and new values.

## Features

- ✅ Automatic logging of model changes (created, updated, deleted)
- ✅ Tracks both old and new values for updates
- ✅ Custom log names per model
- ✅ Automatic user tracking
- ✅ IP address and URL tracking
- ✅ Feature and code context support
- ✅ Batched inserts for performance
- ✅ Configurable event logging

## Implementation Steps

### 1. Add the Trait to Your Model

Add the `SpatieActivityLog` trait to your model:

```php
<?php

namespace App\Models;

use App\Traits\SpatieActivityLog;
use Illuminate\Database\Eloquent\Model;

class YourModel extends Model
{
    use SpatieActivityLog;
    
    // ... rest of your model
}
```

### 2. Set Custom Log Name (Optional)

You can customize the log name in two ways:

#### Option A: Using Property (Recommended)

```php
class Payment extends Model
{
    use SpatieActivityLog;
    
    protected $activityLogName = 'Payment';
}
```

#### Option B: Using Method

```php
class Payment extends Model
{
    use SpatieActivityLog;
    
    protected function getModelActivityLogName(): string
    {
        return 'Payment';
    }
}
```

**Priority Order:**
1. `$activityLogName` property (if set)
2. `getModelActivityLogName()` method (if exists)
3. Auto-generated from model class name (e.g., `PaymentSplits` → `Payment Splits`)

### 3. Configure Events to Log (Optional)

By default, the package logs `created`, `updated`, and `deleted` events. You can modify this behavior:

```php
class YourModel extends Model
{
    use SpatieActivityLog;
    
    // Only log deleted events
    protected static $recordEvents = ['deleted'];
    
    // Log only created and updated
    protected static $recordEvents = ['created', 'updated'];
    
    // Log only updated
    protected static $recordEvents = ['updated'];
}
```

**Available Events:**
- `created` - When a new model is created
- `updated` - When a model is updated
- `deleted` - When a model is deleted

### 4. Add Extra Columns (If Needed)

If you need to add custom data to activity logs, override the `tapActivity` method in your model:

```php
use Spatie\Activitylog\Contracts\Activity;

class YourModel extends Model
{
    use SpatieActivityLog;
    
    public function tapActivity(Activity $activity, string $eventName): void
    {
        // Call parent to maintain default behavior
        parent::tapActivity($activity, $eventName);
        
        // Add your custom columns
        $activity->custom_field = $this->someValue;
        $activity->another_field = $this->anotherValue;
    }
}
```

**Note:** The trait already sets these columns automatically:
- `url` - Current request URI
- `feature` - Feature from context (set via `LoggerService::startFeatureLogging()`)
- `ip_address` - Client IP address
- `code` - Code from context

## Complete Example

Here's a complete example for a `Payment` model:

```php
<?php

namespace App\Models;

use App\Traits\SpatieActivityLog;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use SpatieActivityLog;
    
    // Custom log name
    protected $activityLogName = 'Payment';
    
    // Only log updates and deletes (skip created events)
    protected static $recordEvents = ['updated', 'deleted'];
    
    protected $fillable = [
        'amount',
        'status',
        // ... other fields
    ];
    
    // Optional: Add custom data to activity logs
    public function tapActivity(Activity $activity, string $eventName): void
    {
        parent::tapActivity($activity, $eventName);
        
        // Add payment-specific data
        $activity->payment_code = $this->code;
    }
}
```

## How It Works

### Automatic Logging

When you perform operations on a model with the trait:

```php
// Create - logs 'created' event
$payment = Payment::create([...]);

// Update - logs 'updated' event with old and new values
$payment->update(['status' => 'completed']);

// Delete - logs 'deleted' event
$payment->delete();
```

### Properties Structure

For `updated` events, the properties are stored as:

```json
{
  "old": {
    "status": "pending",
    "amount": 1000
  },
  "attributes": {
    "status": "completed",
    "amount": 1000
  }
}
```

### Description Format

Activity descriptions are automatically generated:

```
User 'John Doe' performed the 'updated' action on Payment - Changed fields: status, amount
```

## Configuration

### Environment Variables

Set these in your `.env` file:

```env
# Enable/disable activity logging
ACTIVITY_LOGGER_ENABLED=true

# Table name (default: activity_log)
ACTIVITY_LOGGER_TABLE_NAME=activity_log

# Enable batching (recommended for performance)
ACTIVITY_LOGGER_BATCH_ENABLED=true

# Batch size (number of activities before inserting)
ACTIVITY_LOGGER_BATCH_SIZE=5
```

### Config File

Configuration is in `config/activitylog.php`. Key settings:

- `enabled` - Enable/disable activity logging globally
- `batch_enabled` - Enable batched inserts
- `batch_size` - Number of activities to batch before insert

## Context Support

You can set feature and code context for better tracking:

```php
use Illuminate\Support\Facades\Context;

// Set feature context
Context::put('feature', 'Payment Processing');

// Set code context
Context::put('code', 'PAY-12345');

// Now all activity logs will include this context
$payment->update(['status' => 'completed']);
```

## Viewing Activity Logs

Activity logs can be viewed in the admin panel:

1. Navigate to **Admin → Activity Logs** (requires Engineering or Admin role)
2. Filter by user, log name, feature, event, date range, etc.
3. Click "View Details" to see full activity information including old/new values

## Best Practices

1. **Use descriptive log names**: Set `$activityLogName` for clarity
2. **Limit events when needed**: Use `$recordEvents` to avoid unnecessary logs
3. **Set context**: Use `Context::put()` to add feature/code context
4. **Keep batching enabled**: Improves performance for high-volume operations
5. **Review logs regularly**: Monitor activity logs for audit and debugging

## Troubleshooting

### Activity logs not being created

1. Check `ACTIVITY_LOGGER_ENABLED` is `true`
2. Verify the trait is added to the model
3. Check database connection and table exists
4. Review Laravel logs for errors

### Missing old values

- Old values are automatically captured for `updated` events
- Ensure you're using the latest version of the trait
- Check that `getOriginal()` is available on the model

### Performance issues

- Enable batching: `ACTIVITY_LOGGER_BATCH_ENABLED=true`
- Adjust batch size based on your needs
- Consider indexing the `activity_log` table

## Migration

If you need to add activity logging to an existing model:

1. Add the trait: `use SpatieActivityLog;`
2. Set log name (optional): `protected $activityLogName = 'ModelName';`
3. Configure events (optional): `protected static $recordEvents = ['updated'];`
4. Test with a sample update/delete operation
5. Verify logs appear in the admin panel

## Related Files

- Trait: `app/Traits/SpatieActivityLog.php`
- Model: `app/Models/ActivityLog.php`
- Service: `app/Services/ActivityLogService.php`
- Controller: `app/Http/Controllers/V2/ActivityLogController.php`
- Config: `config/activitylog.php`
- Migration: `database/migrations/2025_11_24_102343_create_activity_log_table.php`


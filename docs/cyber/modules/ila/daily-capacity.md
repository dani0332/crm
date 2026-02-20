# Daily Capacity Management

## Overview

Cyber Insurance advisors have a daily capacity cap of 200 leads per day. This document explains how the capacity system works, how it's managed, and how it affects lead allocation.

## Capacity Configuration

### Default Capacity

**Value**: 200 leads per day per Cyber advisor

**Location**: `database/seeders/CyberLeadAllocationSeeder.php:15`

**Constant**:

```php
private const CYBER_ADVISOR_MAX_CAPACITY = 200;
```

### Storage

**Table**: `lead_allocations`

**Fields**:

- `max_capacity`: Maximum number of leads per day (default: 200 for Cyber)
- `allocation_count`: Current number of leads allocated today
- `quote_type_id`: Quote type ID for Cyber
- `user_id`: Advisor user ID

### Seeder Setup

**Location**: `database/seeders/CyberLeadAllocationSeeder.php`

**Process**:

1. Finds all users with `CyberAdvisor` role
2. Creates/updates `LeadAllocation` records
3. Sets `max_capacity` to 200 for Cyber quote type

**Code Reference**:

```php
LeadAllocation::updateOrCreate(
    [
        'user_id' => $advisor->id,
        'quote_type_id' => $cyberQuoteTypeId,
    ],
    [
        'max_capacity' => self::CYBER_ADVISOR_MAX_CAPACITY,
        'allocation_count' => 0,
        'reset_cap' => 1,
    ]
);
```

## Capacity Check During Allocation

### Capacity Validation

**Location**: `app/Services/AllocationService.php:419-438`

**Method**: `isMaxCapReached(User $advisor, $quoteTypeId): bool`

**Logic**:

```php
public function isMaxCapReached(User $advisor, $quoteTypeId): bool
{
    $leadAllocation = $advisor->getFirstFromLeadAllocation($quoteTypeId);

    if (!$leadAllocation) {
        return true; // No allocation record = unavailable
    }

    $allocationCount = $leadAllocation->allocation_count;
    $maxCapacity = $leadAllocation->max_capacity;

    // Advisor available if count < max OR max = -1 (unlimited)
    $isAdvisorAvailable = $allocationCount < $maxCapacity || $maxCapacity == -1;

    return !$isAdvisorAvailable;
}
```

**Behavior**:

- If `allocation_count >= max_capacity` → Advisor unavailable
- If `max_capacity == -1` → Unlimited capacity (always available)
- If no `LeadAllocation` record → Advisor unavailable

### Query Integration

**Location**: `app/Pipes/Allocation/Common/BaseAllocationPipe.php:getAdvisorBaseQuery()`

**Filter Applied**:

- Checks `isMaxCapReached()` before including advisor in results
- Only advisors under capacity limit are returned

## Daily Reset

### Reset Command

**Location**: `app/Console/Commands/ResetLeadAllocationCounts.php`

**Method**: `resetNormalLeadAllocationCapacity()`

**Process**:

1. Resets `allocation_count` to 0 for all Cyber advisors
2. Updates `max_capacity` to 200 for Cyber advisors
3. Runs daily via scheduled task

**Code Reference**:

```php
$cyberCount = LeadAllocation::query()
    ->where('reset_cap', 1)
    ->where('quote_type_id', $cyberQuoteTypeId)
    ->update([
        'max_capacity' => 200,
    ]);
```

### Scheduled Reset

**Schedule**: Daily reset (typically at midnight)

**Command**: `php artisan lead-allocation:reset-counts`

## Manual Capacity Management

### Admin Control

**Requirement**: Cap facility should be manually updateable by admin.

**Implementation**:

- Admins can update `max_capacity` via Lead Allocation management interface
- Changes take effect immediately
- No code deployment required

### Update Process

1. Navigate to Lead Allocation management
2. Find Cyber advisor record
3. Update `max_capacity` field
4. Save changes

**Note**: Manual updates persist until next daily reset (if `reset_cap = 1`)

## Capacity Behavior

### When Capacity Reached

**Scenario**: Advisor has reached 200 leads for the day

**Behavior**:

- Advisor excluded from allocation query results
- System moves to next available advisor (backup)
- If all advisors at capacity → Allocation fails
- Lead marked with `lead_allocation_failed_at` timestamp

### Unlimited Capacity

**Special Case**: `max_capacity = -1`

**Behavior**:

- Advisor always available regardless of allocation count
- Used for special cases or testing

## Capacity Tracking

### Allocation Count Increment

**Location**: `app/Pipes/Allocation/Common/BaseAllocationPipe.php:assignLeadToUserAndGetQuote()`

**Process**:

1. Lead assigned to advisor
2. `allocation_count` incremented in `lead_allocations` table
3. Count persists until daily reset

### Count Reset

**Trigger**: Daily scheduled command

**Action**: Sets `allocation_count = 0` for all advisors with `reset_cap = 1`

## Related Files

- **Seeder**: `database/seeders/CyberLeadAllocationSeeder.php`
- **Reset Command**: `app/Console/Commands/ResetLeadAllocationCounts.php`
- **Capacity Check**: `app/Services/AllocationService.php:419-438`
- **Allocation Query**: `app/Pipes/Allocation/Common/BaseAllocationPipe.php`

## Configuration Summary

| Setting          | Value              | Location                           |
| ---------------- | ------------------ | ---------------------------------- |
| Default Capacity | 200 leads/day      | `CyberLeadAllocationSeeder.php:15` |
| Storage Table    | `lead_allocations` | Database                           |
| Capacity Field   | `max_capacity`     | `lead_allocations` table           |
| Count Field      | `allocation_count` | `lead_allocations` table           |
| Reset Schedule   | Daily              | Scheduled command                  |
| Manual Update    | Yes                | Admin interface                    |

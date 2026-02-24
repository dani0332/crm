# ILA Production Mode

## Overview

Production Mode implements the primary/backup advisor allocation strategy for Cyber Insurance. All leads are assigned to the primary advisor (Smitha Chandran) by default, with automatic failover to backup advisors when the primary advisor is on leave (SICK or LEAVE status). Each advisor has a daily capacity cap of 200 leads per day.

## Purpose

- **Primary Advisor Priority**: All leads assigned to primary advisor (Smitha) by default
- **Automatic Failover**: Seamless transition to backup advisors when primary is unavailable
- **Leave Detection**: Automatically detects SICK (4) or LEAVE (5) status
- **Multiple Backup Support**: Supports unlimited backup advisors with round-robin distribution
- **Status-Based Routing**: Works with ONLINE, OFFLINE, and UNAVAILABLE statuses

## Configuration

### Production Advisor Emails

**App Storage Key**: `CYBER_ADVISORS`  
**Value**: Comma-separated email addresses

**Format**: `primary@email.com,backup1@email.com,backup2@email.com`

**Current Configuration**:

```
smitha.chandran@insurancemarket.ae,neil.rama@insurancemarket.ae
```

**Primary Advisor**: First email in the list  
**Backup Advisors**: All remaining emails

**Note**: Different environments (UAT/Stage vs Production) can set different emails in this key as needed.

## Production Logic

### Flow Diagram

```
Lead Created
    ↓
getAdvisorEmails()
    ↓
Fetch CYBER_ADVISORS from app storage
    ↓
Parse emails: [primary, backup1, backup2, ...]
    ↓
Extract primary = emails[0]
Extract backups = emails[1...]
    ↓
Check primary advisor leave status
    ↓
    ├─ On Leave (SICK/LEAVE) → Return backup emails
    │                            ↓
    │                            Query advisors with backup emails
    │                            ↓
    │                            Round-robin distribution
    │
    └─ Available → Return [primary]
                     ↓
                     Query advisor with primary email
                     ↓
                     Assign to primary
```

### Code Reference

**Location**: `app/Pipes/Allocation/Cyber/FetchAvailableAdvisorPipe.php:115-148`

**Method**:

```php
private function getAdvisorEmails(): array
{
    $emails = $this->parseAdvisorEmails(
        ApplicationStorageEnums::CYBER_ADVISORS,
        self::WARNING_NO_ADVISORS_FOUND
    );

    if (empty($emails)) {
        LoggerService::warning(self::class.' - '.self::WARNING_NO_VALID_ADVISOR_EMAILS);

        return [];
    }

    $primaryEmail = $emails[0];
    $backupEmails = array_slice($emails, 1);

    $isOnLeave = $this->isUserOnLeave($primaryEmail, addUnavailable: true);

    if ($isOnLeave) {
        LoggerService::info(self::class.' - Primary advisor is on SICK or LEAVE, assigning to backups', extra: [
            'primaryEmail' => $primaryEmail,
            'backupCount' => count($backupEmails),
            'backupEmails' => $backupEmails,
        ]);

        return $backupEmails;
    }

    LoggerService::info(self::class.' - Assigning to primary advisor', extra: [
        'primaryEmail' => $primaryEmail,
    ]);

    return [$primaryEmail];
}
```

## Primary Advisor Logic

### Default Behavior

**All leads assigned to primary advisor** when:

- Primary advisor status is `ONLINE` (1)
- Primary advisor status is `OFFLINE` (2)
- Primary advisor status is `UNAVAILABLE` (3)

**No round-robin** - Primary advisor gets 100% of leads when available.

### Example

**Configuration**:

```
CYBER_ADVISORS = 'smitha.chandran@insurancemarket.ae,neil.rama@insurancemarket.ae'
```

**Scenario**: Smitha is ONLINE

- Lead 1 → Smitha
- Lead 2 → Smitha
- Lead 3 → Smitha
- Lead 4 → Smitha
- (All leads to Smitha)

## Backup Advisor Logic

### Trigger Conditions

Backup advisors are used when:

- Primary advisor status is `SICK` (4)
- Primary advisor status is `LEAVE` (5)

### Single Backup

**Configuration**:

```
CYBER_ADVISORS = 'smitha.chandran@insurancemarket.ae,neil.rama@insurancemarket.ae'
```

**Scenario**: Smitha is on LEAVE

- Lead 1 → Neil
- Lead 2 → Neil
- Lead 3 → Neil
- (All leads to Neil)

### Multiple Backups

**Configuration**:

```
CYBER_ADVISORS = 'smitha.chandran@insurancemarket.ae,neil.rama@insurancemarket.ae,alex.smith@insurancemarket.ae,tina.jones@insurancemarket.ae'
```

**Scenario**: Smitha is on LEAVE

- Lead 1 → Neil (oldest last_allocated)
- Lead 2 → Alex
- Lead 3 → Tina
- Lead 4 → Neil (round-robin continues)

**Round-Robin**: Automatic via `orderBy('la.last_allocated', 'asc')` in query

## Leave Status Management

### Setting Advisor on Leave

**Put Primary Advisor on Leave**:

```sql
UPDATE users
SET status = 5  -- LEAVE
WHERE email = 'smitha.chandran@insurancemarket.ae';
```

**Status Values**:

- `1` = ONLINE
- `2` = OFFLINE
- `3` = UNAVAILABLE (still gets leads)
- `4` = SICK (on leave)
- `5` = LEAVE (on leave)

### Bringing Advisor Back

**Set Advisor Back to Online**:

```sql
UPDATE users
SET status = 1  -- ONLINE
WHERE email = 'smitha.chandran@insurancemarket.ae';
```

**Result**: System automatically switches back to primary advisor

## Leave Detection Method

**Location**: `app/Services/AllocationService.php:460-480`

**Method**: `isUserOnLeave($email, addUnavailable: true)`

**Logic**:

```php
public function isUserOnLeave(string $email, bool $addUnavailable = false): bool
{
    $user = User::where('email', $email)->activeUser()->first();

    if (!$user) {
        return false;
    }

    // Valid statuses when addUnavailable=true: [1, 2, 3]
    $validStatuses = [UserStatusEnum::ONLINE, UserStatusEnum::OFFLINE, UserStatusEnum::UNAVAILABLE];

    // If status NOT in valid statuses → on leave
    $isOnLeave = !in_array($user->status, $validStatuses);

    return $isOnLeave;
}
```

**Valid Statuses** (not on leave):

- `ONLINE` (1)
- `OFFLINE` (2)
- `UNAVAILABLE` (3)

**Leave Statuses** (on leave):

- `SICK` (4)
- `LEAVE` (5)

## Adding More Backup Advisors

### Via Database

```sql
UPDATE application_storages
SET value = 'smitha.chandran@insurancemarket.ae,neil.rama@insurancemarket.ae,alex.smith@insurancemarket.ae,tina.jones@insurancemarket.ae'
WHERE key_name = 'CYBER_ADVISORS';
```

### Via UI

1. Navigate to Application Storage management
2. Find `CYBER_ADVISORS` key
3. Update value with comma-separated emails
4. Save

**No code changes required** - System automatically handles multiple backups

## Status Priority

When querying advisors, the system tries statuses in order:

1. **ONLINE** (1) - Highest priority
2. **OFFLINE** (2) - Second priority
3. **UNAVAILABLE** (3) - Third priority

**Example**:

- If primary advisor is ONLINE → Gets the lead
- If primary advisor is OFFLINE → Gets the lead
- If primary advisor is UNAVAILABLE → Gets the lead
- If primary advisor is SICK/LEAVE → Backup gets the lead

## Important Notes

- **No Round-Robin for Primary**: Primary advisor gets ALL leads when available (up to daily capacity)
- **Daily Capacity**: 200 leads per day per advisor (manually updateable by admin)
- **Automatic Failover**: No manual intervention needed when primary goes on leave
- **Status-Based**: Uses user status field, not custom leave flags
- **OFFLINE Status**: If primary is OFFLINE, leads still assigned to primary (not backup)
- **Multiple Backups**: Supports unlimited backup advisors
- **Round-Robin for Backups**: Automatic distribution among backups
- **Capacity Check**: Advisors at capacity are excluded from allocation

## Seeder Configuration

**Location**: `database/seeders/ApplicationStorageSeeder.php`

**Seeder Method**: `seedCyberConfigurations()`

**Default Configuration**:

```php
ApplicationStorage::firstOrCreate(
    ['key_name' => ApplicationStorageEnums::CYBER_ADVISORS],
    [
        'value' => 'smitha.chandran@insurancemarket.ae,neil.rama@insurancemarket.ae',
        'created_at' => now(),
        'updated_at' => now(),
        'is_active' => 1,
    ],
);
```

## Related Files

- **Advisor Email Method**: `app/Pipes/Allocation/Cyber/FetchAvailableAdvisorPipe.php:115-148`
- **Leave Check Method**: `app/Services/AllocationService.php:460-480`
- **Status Enum**: `app/Enums/UserStatusEnum.php`
- **Storage Enum**: `app/Enums/ApplicationStorageEnums.php`

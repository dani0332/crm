# ILA App Storage Keys

## Overview

This document describes all application storage keys used by the Cyber Insurance ILA (Instant Lead Allocation) system. These keys are stored in the `application_storages` database table and can be managed via the admin UI.

## Storage Keys

### CYBER_ADVISORS

**Key Name**: `CYBER_ADVISORS`  
**Purpose**: Advisor email list for Cyber Insurance allocation  
**Type**: String (comma-separated emails)  
**Used In**: Advisor selection logic

**Format**: `primary@email.com,backup1@email.com,backup2@email.com`

**Current Value**:

```
smitha.chandran@insurancemarket.ae,neil.rama@insurancemarket.ae
```

**Structure**:

- **First Email**: Primary advisor (gets all leads when available)
- **Remaining Emails**: Backup advisors (used when primary is on leave)

**Usage**:

- Fetched in `getAdvisorEmails()`
- Parsed as comma-separated string
- Emails validated and filtered
- Primary extracted as `emails[0]`
- Backups extracted as `emails[1...]`
- Leave status checked for primary advisor

**Code Reference**: `app/Pipes/Allocation/Cyber/FetchAvailableAdvisorPipe.php:115`

**Update Example**:

```sql
UPDATE application_storages
SET value = 'smitha.chandran@insurancemarket.ae,neil.rama@insurancemarket.ae,alex.smith@insurancemarket.ae'
WHERE key_name = 'CYBER_ADVISORS';
```

---

## Storage Key Constants

**Location**: `app/Enums/ApplicationStorageEnums.php`

**Constants**:

```php
public const CYBER_ADVISORS = 'CYBER_ADVISORS';
```

**Code Reference**: `app/Enums/ApplicationStorageEnums.php:263`

---

## Seeder Configuration

**Location**: `database/seeders/ApplicationStorageSeeder.php`

**Seeder Method**: `seedCyberConfigurations()`

**Default Values**:

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

**Note**: Different environments (UAT/Stage vs Production) can set different emails in this key as needed.

---

## Retrieval Methods

### Helper Function

**Function**: `getAppStorageValueByKey($key, $useCache = true)`

**Usage**:

```php
$emails = getAppStorageValueByKey(ApplicationStorageEnums::CYBER_ADVISORS, useCache: true);
```

**Caching**: Values are cached for performance (default: `useCache: true`)

---

## Email Format Requirements

### Valid Format

- Comma-separated email addresses
- Spaces are trimmed automatically
- Invalid emails are filtered out

**Examples**:

```
smitha.chandran@insurancemarket.ae,neil.rama@insurancemarket.ae
smitha.chandran@insurancemarket.ae, neil.rama@insurancemarket.ae
smitha.chandran@insurancemarket.ae,neil.rama@insurancemarket.ae,alex.smith@insurancemarket.ae
```

### Processing

**Code Reference**: `app/Pipes/Allocation/Cyber/FetchAvailableAdvisorPipe.php:128-131`

**Steps**:

1. `explode(',', $emails)` - Split by comma
2. `array_map('trim', $emails)` - Trim whitespace
3. `array_filter($emails, fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL))` - Validate emails
4. `array_values($emails)` - Re-index array

**Example**:

```
Input: 'smitha@example.com, neil@test.com ,invalid-email,alex@example.com'
Output: ['smitha@example.com', 'neil@test.com', 'alex@example.com']
```

---

## Management

### Via Database

**View Cyber Storage Keys**:

```sql
SELECT key_name, value, is_active
FROM application_storages
WHERE key_name = 'CYBER_ADVISORS';
```

**Update Advisors**:

```sql
UPDATE application_storages
SET value = 'smitha.chandran@insurancemarket.ae,neil.rama@insurancemarket.ae,alex.smith@insurancemarket.ae',
    updated_at = NOW()
WHERE key_name = 'CYBER_ADVISORS';
```

### Via UI

1. Navigate to Application Storage management page
2. Search for `CYBER_ADVISORS`
3. Update value field with comma-separated email addresses
4. Save changes

**Note**: Changes take effect immediately (cache may need clearing)

---

## Validation

### Email Validation

Invalid emails are automatically filtered:

- Empty strings
- Invalid email format
- Missing @ symbol
- Invalid domain

**Code Reference**: `app/Pipes/Allocation/Cyber/FetchAvailableAdvisorPipe.php:150-165`

### Empty Value Handling

If storage key is empty or not found:

- Warning logged
- Empty array returned
- Allocation fails gracefully

**Code Reference**: `app/Pipes/Allocation/Cyber/FetchAvailableAdvisorPipe.php:115-148`

---

## Related Files

- **Storage Enum**: `app/Enums/ApplicationStorageEnums.php`
- **Seeder**: `database/seeders/ApplicationStorageSeeder.php`
- **Allocation Pipe**: `app/Pipes/Allocation/Cyber/FetchAvailableAdvisorPipe.php`
- **Helper Function**: Global `getAppStorageValueByKey()` function

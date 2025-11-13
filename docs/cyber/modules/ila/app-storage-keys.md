# ILA App Storage Keys

## Overview

This document describes all application storage keys used by the Cyber Insurance ILA (Instant Lead Allocation) system. These keys are stored in the `application_storages` database table and can be managed via the admin UI.

## Storage Keys

### 1. CYBER_ADVISORS

**Key Name**: `CYBER_ADVISORS`  
**Purpose**: Production advisor email list for Cyber Insurance allocation  
**Type**: String (comma-separated emails)  
**Used In**: Production mode advisor selection

**Format**: `primary@email.com,backup1@email.com,backup2@email.com`

**Current Value**:
```
smitha.chandran@insurancemarket.ae,neil.rama@insurancemarket.ae
```

**Structure**:
- **First Email**: Primary advisor (gets all leads when available)
- **Remaining Emails**: Backup advisors (used when primary is on leave)

**Usage**:
- Fetched in `getProductionModeAdvisorEmails()`
- Parsed as comma-separated string
- Emails validated and filtered
- Primary extracted as `emails[0]`
- Backups extracted as `emails[1...]`

**Code Reference**: `app/Pipes/Allocation/Cyber/FetchAvailableAdvisorPipe.php:143`

**Update Example**:
```sql
UPDATE application_storages 
SET value = 'smitha.chandran@insurancemarket.ae,neil.rama@insurancemarket.ae,alex.smith@insurancemarket.ae' 
WHERE key_name = 'CYBER_ADVISORS';
```

---

### 2. CYBER_ADVISORS_TEST

**Key Name**: `CYBER_ADVISORS_TEST`  
**Purpose**: Test advisor email list for Cyber Insurance allocation  
**Type**: String (comma-separated emails)  
**Used In**: Test mode advisor selection

**Format**: `test1@email.com,test2@email.com`

**Current Value**:
```
fahadhussain2020@gmail.com
```

**Usage**:
- Fetched in `getTestModeAdvisorEmails()`
- Parsed as comma-separated string
- Emails validated and filtered
- All emails returned (no primary/backup distinction)

**Code Reference**: `app/Pipes/Allocation/Cyber/FetchAvailableAdvisorPipe.php:120`

**Update Example**:
```sql
UPDATE application_storages 
SET value = 'fahadhussain2020@gmail.com,test.advisor@example.com' 
WHERE key_name = 'CYBER_ADVISORS_TEST';
```

---

### 3. CYBER_ALLOCATION_TEST_MODE

**Key Name**: `CYBER_ALLOCATION_TEST_MODE`  
**Purpose**: Toggle between test and production allocation modes  
**Type**: String (`'0'` or `'1'`)  
**Used In**: Mode selection logic

**Values**:
- `'0'` = Production mode (uses `CYBER_ADVISORS`)
- `'1'` = Test mode (uses `CYBER_ADVISORS_TEST`)

**Current Value**:
```
0
```

**Usage**:
- Checked in `getAdvisorEmails()`
- Determines which storage key to use
- Controls allocation behavior

**Code Reference**: `app/Pipes/Allocation/Cyber/FetchAvailableAdvisorPipe.php:109`

**Update Example**:
```sql
-- Enable test mode
UPDATE application_storages 
SET value = '1' 
WHERE key_name = 'CYBER_ALLOCATION_TEST_MODE';

-- Enable production mode
UPDATE application_storages 
SET value = '0' 
WHERE key_name = 'CYBER_ALLOCATION_TEST_MODE';
```

---

## Storage Key Constants

**Location**: `app/Enums/ApplicationStorageEnums.php`

**Constants**:
```php
public const CYBER_ADVISORS = 'CYBER_ADVISORS';
public const CYBER_ADVISORS_TEST = 'CYBER_ADVISORS_TEST';
public const CYBER_ALLOCATION_TEST_MODE = 'CYBER_ALLOCATION_TEST_MODE';
```

**Code Reference**: `app/Enums/ApplicationStorageEnums.php:250-252`

---

## Seeder Configuration

**Location**: `database/seeders/ApplicationStorageSeeder.php:1070-1101`

**Seeder Method**: `seedCyberAdvisors()`

**Default Values**:
```php
// Production advisors
ApplicationStorage::firstOrCreate(
    ['key_name' => ApplicationStorageEnums::CYBER_ADVISORS],
    [
        'value' => 'smitha.chandran@insurancemarket.ae,neil.rama@insurancemarket.ae',
        'is_active' => 1,
    ],
);

// Test advisors
ApplicationStorage::firstOrCreate(
    ['key_name' => ApplicationStorageEnums::CYBER_ADVISORS_TEST],
    [
        'value' => 'fahadhussain2020@gmail.com',
        'is_active' => 1,
    ],
);

// Test mode flag (default: production)
ApplicationStorage::firstOrCreate(
    ['key_name' => ApplicationStorageEnums::CYBER_ALLOCATION_TEST_MODE],
    [
        'value' => '0',
        'is_active' => 1,
    ],
);
```

---

## Retrieval Methods

### Helper Function

**Function**: `getAppStorageValueByKey($key, $useCache = true)`

**Usage**:
```php
$emails = getAppStorageValueByKey(ApplicationStorageEnums::CYBER_ADVISORS, useCache: true);
$testMode = getAppStorageValueByKey(ApplicationStorageEnums::CYBER_ALLOCATION_TEST_MODE);
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

**View All Cyber Storage Keys**:
```sql
SELECT key_name, value, is_active 
FROM application_storages 
WHERE key_name LIKE 'CYBER%';
```

**Update Production Advisors**:
```sql
UPDATE application_storages 
SET value = 'smitha.chandran@insurancemarket.ae,neil.rama@insurancemarket.ae,alex.smith@insurancemarket.ae',
    updated_at = NOW()
WHERE key_name = 'CYBER_ADVISORS';
```

**Update Test Mode**:
```sql
UPDATE application_storages 
SET value = '1',
    updated_at = NOW()
WHERE key_name = 'CYBER_ALLOCATION_TEST_MODE';
```

### Via UI

1. Navigate to Application Storage management page
2. Search for `CYBER_ADVISORS`, `CYBER_ADVISORS_TEST`, or `CYBER_ALLOCATION_TEST_MODE`
3. Update value field
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

**Code Reference**: `app/Pipes/Allocation/Cyber/FetchAvailableAdvisorPipe.php:130`

### Empty Value Handling

If storage key is empty or not found:
- Warning logged
- Empty array returned
- Allocation fails gracefully

**Code Reference**: `app/Pipes/Allocation/Cyber/FetchAvailableAdvisorPipe.php:122-126`

---

## Related Files

- **Storage Enum**: `app/Enums/ApplicationStorageEnums.php`
- **Seeder**: `database/seeders/ApplicationStorageSeeder.php`
- **Allocation Pipe**: `app/Pipes/Allocation/Cyber/FetchAvailableAdvisorPipe.php`
- **Helper Function**: Global `getAppStorageValueByKey()` function


# ILA Test Mode

## Overview

Test Mode allows you to test Cyber Insurance lead allocation with specific test advisors before deploying to production. This is useful for development, QA testing, and staging environments.

## Purpose

- **Isolated Testing**: Test allocation logic without affecting production advisors
- **Multiple Test Advisors**: Support for multiple test advisors with round-robin distribution
- **Easy Toggle**: Switch between test and production via app storage flag
- **Flexible Configuration**: Add/remove test advisors via UI without code changes

## Configuration

### Enable Test Mode

**App Storage Key**: `CYBER_ALLOCATION_TEST_MODE`  
**Value**: `1` (enabled) or `0` (disabled)

**Update via Database**:
```sql
UPDATE application_storages 
SET value = '1' 
WHERE key_name = 'CYBER_ALLOCATION_TEST_MODE';
```

**Update via UI**: Navigate to Application Storage management and update the value.

### Configure Test Advisors

**App Storage Key**: `CYBER_ADVISORS_TEST`  
**Value**: Comma-separated email addresses

**Example**:
```
fahadhussain2020@gmail.com
```

**Multiple Test Advisors**:
```
fahadhussain2020@gmail.com,test.advisor1@example.com,test.advisor2@example.com
```

**Update via Database**:
```sql
UPDATE application_storages 
SET value = 'fahadhussain2020@gmail.com,test.advisor@example.com' 
WHERE key_name = 'CYBER_ADVISORS_TEST';
```

## How Test Mode Works

### Flow

```
Lead Created
    ↓
FetchAvailableAdvisorPipe::handle()
    ↓
Check CYBER_ALLOCATION_TEST_MODE
    ↓
If testMode == 1
    ↓
getTestModeAdvisorEmails()
    ↓
Fetch CYBER_ADVISORS_TEST from app storage
    ↓
Parse comma-separated emails
    ↓
Validate email format
    ↓
Return test advisor emails
    ↓
Query advisors by email
    ↓
Assign first available advisor
```

### Code Reference

**Location**: `app/Pipes/Allocation/Cyber/FetchAvailableAdvisorPipe.php:107-139`

**Main Method**:
```php
private function getAdvisorEmails(): array
{
    $testMode = getAppStorageValueByKey(ApplicationStorageEnums::CYBER_ALLOCATION_TEST_MODE);
    
    if ($testMode == 1) {
        return $this->getTestModeAdvisorEmails();
    }
    
    return $this->getProductionModeAdvisorEmails();
}
```

**Test Mode Method**:
```php
private function getTestModeAdvisorEmails(): array
{
    $emails = getAppStorageValueByKey(ApplicationStorageEnums::CYBER_ADVISORS_TEST, useCache: true);
    
    if (empty($emails)) {
        LoggerService::warning(self::class.' - No test Cyber advisors found in app storage');
        return [];
    }
    
    $emails = explode(',', $emails);
    $emails = array_map('trim', $emails);
    $emails = array_filter($emails, fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL));
    $emails = array_values($emails);
    
    LoggerService::info(self::class.' - TEST MODE: Cyber advisors fetched', extra: [
        'emailCount' => count($emails),
        'emails' => $emails,
    ]);
    
    return $emails;
}
```

## Features

### Single Test Advisor

**Configuration**:
```
CYBER_ADVISORS_TEST = 'fahadhussain2020@gmail.com'
```

**Result**: All test leads assigned to Fahad

### Multiple Test Advisors

**Configuration**:
```
CYBER_ADVISORS_TEST = 'fahadhussain2020@gmail.com,test.advisor1@example.com,test.advisor2@example.com'
```

**Result**: Leads distributed round-robin among test advisors

**Distribution**:
- Lead 1 → Fahad
- Lead 2 → Test Advisor 1
- Lead 3 → Test Advisor 2
- Lead 4 → Fahad (round-robin continues)

### Email Validation

Invalid emails are automatically filtered out:
- Empty strings removed
- Invalid email format removed
- Array re-indexed after filtering

**Example**:
```
Input: 'fahad@example.com,invalid-email,another@test.com,'
Output: ['fahad@example.com', 'another@test.com']
```

## Usage Scenarios

### Development Testing
1. Set `CYBER_ALLOCATION_TEST_MODE = 1`
2. Add developer email to `CYBER_ADVISORS_TEST`
3. Create test leads
4. Verify allocation to developer

### QA Testing
1. Set `CYBER_ALLOCATION_TEST_MODE = 1`
2. Add QA team emails to `CYBER_ADVISORS_TEST`
3. Test various scenarios
4. Verify round-robin distribution

### Staging Environment
1. Set `CYBER_ALLOCATION_TEST_MODE = 1`
2. Add staging advisor emails
3. Test before production deployment

## Switching to Production

**Disable Test Mode**:
```sql
UPDATE application_storages 
SET value = '0' 
WHERE key_name = 'CYBER_ALLOCATION_TEST_MODE';
```

**Result**: System switches to production mode automatically

## Important Notes

- **No Leave Checking**: Test mode does NOT check advisor leave status
- **All Advisors Used**: All test advisors are considered available
- **Round-Robin**: Distribution happens automatically via query ordering
- **Cache**: App storage values are cached for performance
- **Validation**: Invalid emails are filtered automatically

## Seeder Configuration

**Location**: `database/seeders/ApplicationStorageSeeder.php:1070-1101`

**Default Values**:
```php
ApplicationStorage::firstOrCreate(
    ['key_name' => ApplicationStorageEnums::CYBER_ADVISORS_TEST],
    [
        'value' => 'fahadhussain2020@gmail.com',
        'is_active' => 1,
    ],
);

ApplicationStorage::firstOrCreate(
    ['key_name' => ApplicationStorageEnums::CYBER_ALLOCATION_TEST_MODE],
    [
        'value' => '0', // Default: Production mode
        'is_active' => 1,
    ],
);
```

## Related Files

- **Allocation Pipe**: `app/Pipes/Allocation/Cyber/FetchAvailableAdvisorPipe.php`
- **Test Mode Method**: `app/Pipes/Allocation/Cyber/FetchAvailableAdvisorPipe.php:118-139`
- **Storage Enum**: `app/Enums/ApplicationStorageEnums.php`
- **Seeder**: `database/seeders/ApplicationStorageSeeder.php`


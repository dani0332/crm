# ILA Allocation Logic

## Overview

This document explains the detailed logic behind how Cyber Insurance leads are allocated to advisors, including the decision-making process, advisor selection criteria, status-based routing, and capacity management.

## Business Context

- **All Leads**: All Cyber leads are SIC (Self-Initiated Customer)
- **Primary Advisor**: Smitha Chandran (`smitha.chandran@insurancemarket.ae`) gets all leads
- **Backup Advisor**: Neil Rama (`neil.rama@insurancemarket.ae`) when primary is on leave
- **Daily Capacity**: 200 leads per day per advisor
- **Payment Handling**: Paid leads always go to HAPEX team which is the HAPPINESS_SUPPORT_USER_EMAIL
- **No Auto-Reassignment**: Manual reassignment only

## Allocation Decision Tree

```
Lead Created/Updated
    ↓
Is lead paid? (Payment Authorized/Declined)
    ↓ YES → Assign to Happiness Support User
    ↓ NO
    ↓
Is SIC advisor requested OR has retry flag?
    ↓ YES → Proceed with advisor allocation
    ↓ NO → Stop allocation (lead not eligible)
    ↓
Check Test Mode Flag
    ↓
    ├─ Test Mode (1) → Use CYBER_ADVISORS_TEST
    │                    ↓
    │                    Get test advisor emails
    │                    ↓
    │                    Query advisors by status
    │                    ↓
    │                    Return first available
    │
    └─ Production Mode (0) → Use CYBER_ADVISORS
                              ↓
                              Get production advisor emails
                              ↓
                              Primary = emails[0]
                              Backups = emails[1...]
                              ↓
                              Check primary advisor leave status
                              ↓
                              ├─ On Leave (SICK/LEAVE) → Use backup emails
                              │                          ↓
                              │                          Query advisors by status
                              │                          ↓
                              │                          Round-robin distribution
                              │
                              └─ Available → Use primary email only
                                             ↓
                                             Query advisor by status
                                             ↓
                                             Assign to primary
```

## Detailed Logic

### 1. Paid Lead Detection

**Location**: `app/Pipes/Allocation/Cyber/FetchAvailableAdvisorPipe.php:22`

**Condition**: `$this->allocationRequest->shouldAssignToHappinessUser()`

**Logic**:
- Checks if lead payment is authorized or declined
- If true, assigns to Happiness Support User
- Email: `happiness@support.insurancemarket.ae`

**Code Reference**:
```php
if ($this->allocationRequest->shouldAssignToHappinessUser()) {
    $advisor = $this->getHappinessUser();
    $this->allocationRequest->setAdvisor($advisor);
    return $next($request);
}
```

### 2. Team Evaluation

**Location**: `app/Pipes/Allocation/Cyber/EvaluateTeamPipe.php`

**Conditions Checked**:
1. **Payment Status**: Is payment authorized or declined?
2. **SIC Advisor Requested**: Does `cyber_quote_request.sic_advisor_requested` = true?
3. **Retry Flag**: Does lead have `lead_allocation_failed_at` set?

**Decision Logic**:
```php
// Paid lead → Happiness User
if ($isPaymentAuthorizedOrDeclined) {
    return false; // No team, assign to happiness user
}

// SIC requested OR retry flag → Hardcoded advisors
if ($sicAdvisorRequested || $hasRetryFlag) {
    return false; // No team, use hardcoded advisors
}

// Otherwise → Stop allocation
$this->stop('sic advisor requested is false for cyber lead');
```

**Code Reference**: `app/Pipes/Allocation/Cyber/EvaluateTeamPipe.php:34-89`

### 3. Test Mode vs Production Mode

**Location**: `app/Pipes/Allocation/Cyber/FetchAvailableAdvisorPipe.php:107-116`

**Test Mode Check**:
```php
$testMode = getAppStorageValueByKey(ApplicationStorageEnums::CYBER_ALLOCATION_TEST_MODE);

if ($testMode == 1) {
    return $this->getTestModeAdvisorEmails();
}

return $this->getProductionModeAdvisorEmails();
```

#### Test Mode Flow

**Location**: `app/Pipes/Allocation/Cyber/FetchAvailableAdvisorPipe.php:118-139`

**Process**:
1. Fetch emails from `CYBER_ADVISORS_TEST` app storage
2. Parse comma-separated string
3. Validate email format
4. Return array of test advisor emails

**Example**:
- Storage Value: `fahadhussain2020@gmail.com,test.advisor@example.com`
- Returns: `['fahadhussain2020@gmail.com', 'test.advisor@example.com']`

#### Production Mode Flow

**Location**: `app/Pipes/Allocation/Cyber/FetchAvailableAdvisorPipe.php:141-182`

**Process**:
1. Fetch emails from `CYBER_ADVISORS` app storage
2. Parse comma-separated string
3. Validate email format
4. Extract primary (first email) and backups (remaining emails)
5. Check primary advisor leave status using `isUserOnLeave()`
6. Return appropriate email array

**Example**:
- Storage Value: `smitha.chandran@insurancemarket.ae,neil.rama@insurancemarket.ae`
- Primary: `smitha.chandran@insurancemarket.ae`
- Backups: `['neil.rama@insurancemarket.ae']`

### 4. Leave Status Checking

**Location**: `app/Pipes/Allocation/Cyber/FetchAvailableAdvisorPipe.php:165`

**Method**: `$this->isUserOnLeave($primaryEmail, addUnavailable: true)`

**Parent Method**: `app/Services/AllocationService.php:460-480`

**Logic**:
```php
public function isUserOnLeave(string $email, bool $addUnavailable = false): bool
{
    $user = User::where('email', $email)->activeUser()->first();
    
    if (!$user) {
        return false;
    }
    
    // Get valid advisor statuses
    $validStatuses = $this->getValidAdvisorStatuses($addUnavailable);
    // With addUnavailable=true: [ONLINE, OFFLINE, UNAVAILABLE] = [1, 2, 3]
    
    // If user status is NOT in valid statuses → on leave
    $isOnLeave = !in_array($user->status, $validStatuses);
    
    return $isOnLeave;
}
```

**Valid Statuses** (when `addUnavailable: true`):
- `ONLINE` (1)
- `OFFLINE` (2)
- `UNAVAILABLE` (3)

**Leave Statuses**:
- `SICK` (4) → On leave
- `LEAVE` (5) → On leave

**Code Reference**: `app/Services/AllocationService.php:440-458`

### 5. Advisor Query Execution

**Location**: `app/Pipes/Allocation/Cyber/FetchAvailableAdvisorPipe.php:72-105`

**Query Logic**:
```php
$advisorRecord = $this->getAdvisorBaseQuery(
    onlineStatus: $onlineStatus,
    teamId: null,
    roles: [RolesEnum::CyberAdvisor],
    isBuyLead: false
)
    ->whereIn('users.email', $emails)
    ->logRawSql()
    ->first();
```

**Base Query Filters**:
- User status matches requested status
- User has `CyberAdvisor` role
- User is active
- **Capacity Check**: Allocation count < max capacity (200 for Cyber) OR max_capacity = -1 (unlimited)
- Quote type = Cyber
- Email in provided email list

**Capacity Logic**:
- Default capacity: 200 leads per day per Cyber advisor
- If advisor has reached capacity → Excluded from query results
- Capacity checked via `isMaxCapReached()` method
- Location: `app/Services/AllocationService.php:419-438`

**Status Order**:
1. `ONLINE` (1) - Tried first
2. `OFFLINE` (2) - Tried if no online advisors
3. `UNAVAILABLE` (3) - Tried if no online/offline advisors

**Ordering**: `orderBy('la.last_allocated', 'asc')` - Round-robin among same status

**Code Reference**: `app/Pipes/Allocation/Common/BaseAllocationPipe.php:149-191`

### 6. Lead Assignment

**Location**: `app/Pipes/Allocation/Cyber/AssignLeadPipe.php`

**Process**:
1. Get selected advisor from allocation request
2. Update `personal_quotes.advisor_id`
3. Update `personal_quote_details.advisor_assigned_date`
4. Set assignment type
5. Log assignment

**Code Reference**: `app/Pipes/Allocation/Cyber/AssignLeadPipe.php:13-37`

## Scenarios

### Scenario 1: Paid Lead
```
Lead Payment Status: Authorized
    ↓
EvaluateTeamPipe detects paid status
    ↓
Sets assignToHappinessUser = true
    ↓
FetchAvailableAdvisorPipe assigns to Happiness User
    ↓
Assignment Complete
```

### Scenario 2: Test Mode Lead
```
CYBER_ALLOCATION_TEST_MODE = 1
    ↓
Fetch test advisor emails from CYBER_ADVISORS_TEST
    ↓
Query advisors with test emails
    ↓
Assign first available advisor
```

### Scenario 3: Production - Primary Available
```
CYBER_ALLOCATION_TEST_MODE = 0
    ↓
Get production emails: [smitha, neil]
    ↓
Primary = smitha
    ↓
Check smitha leave status → Available (status 1, 2, or 3)
    ↓
Return [smitha]
    ↓
Query advisor with smitha email
    ↓
Assign to Smitha
```

### Scenario 4: Production - Primary on Leave
```
CYBER_ALLOCATION_TEST_MODE = 0
    ↓
Get production emails: [smitha, neil]
    ↓
Primary = smitha
    ↓
Check smitha leave status → On Leave (status 4 or 5)
    ↓
Return [neil] (backup emails)
    ↓
Query advisors with neil email
    ↓
Assign to Neil
```

### Scenario 5: Multiple Backups
```
Production emails: [smitha, neil, alex, tina]
    ↓
Primary = smitha (on leave)
    ↓
Backups = [neil, alex, tina]
    ↓
Return backup emails
    ↓
Query advisors with backup emails
    ↓
Round-robin distribution among backups
```

## Error Handling

### No Advisor Found
- Allocation marked as failed
- `lead_allocation_failed_at` timestamp set
- Lead remains unassigned
- Can be retried manually

### No Emails Configured
- Warning logged
- Empty array returned
- Allocation fails gracefully

### User Not Found
- Warning logged
- `isUserOnLeave()` returns `false`
- System continues with primary advisor

## Logging

All allocation decisions are logged:
- Test/Production mode detection
- Advisor email selection
- Leave status checks
- Query execution
- Assignment results

**Log Location**: `storage/logs/laravel-YYYY-MM-DD.log`

## Related Files

- **Allocation Pipe**: `app/Pipes/Allocation/Cyber/FetchAvailableAdvisorPipe.php`
- **Team Evaluation**: `app/Pipes/Allocation/Cyber/EvaluateTeamPipe.php`
- **Lead Assignment**: `app/Pipes/Allocation/Cyber/AssignLeadPipe.php`
- **Base Service**: `app/Services/AllocationService.php`
- **Allocation Strategy**: `app/Strategies/Allocations/CyberAllocation.php`


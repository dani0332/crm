# Instant Lead Allocation (ILA) Module

## Overview

The Instant Lead Allocation (ILA) module for Cyber Insurance automatically assigns incoming leads to available advisors based on predefined rules, advisor availability, and leave status. This ensures efficient lead distribution and optimal advisor utilization.

## Purpose

The ILA system for Cyber handles:

- **Automatic Advisor Assignment**: Assigns leads to advisors without manual intervention
- **Leave Management**: Automatically routes leads to backup advisors when primary advisor is on leave
- **CHS Advisor Assignment**: Routes automation-completed/failed leads to CHS advisors
- **SIC Lead Handling**: Routes SIC (Self-Initiated Customer) leads to appropriate advisors

## Key Features

- **Primary/Backup Advisor Logic**: Primary advisor (Smitha) gets all leads unless on leave
- **Leave Status Detection**: Automatically detects SICK (4) or LEAVE (5) status
- **Daily Capacity Cap**: 200 leads per day per advisor (manually updateable by admin)
- **CHS Advisor Assignment**: Automation-completed/failed leads assigned to CHS advisors
- **SIC Lead Handling**: All leads are SIC; allocation triggered when advisor requested
- **Smart Routing**: Primary/backup logic with leave checking
- **Multiple Backup Support**: Supports unlimited backup advisors with round-robin distribution
- **Manual Reassignment**: Available via admin interface (auto-reassignment not required)

## Components

### Allocation Strategy

- **File**: `app/Strategies/Allocations/CyberAllocation.php`
- **Purpose**: Main allocation orchestrator using Laravel Pipeline pattern

### Allocation Pipes

1. **FetchLeadPipe**: Retrieves the Cyber quote/lead
2. **VerifyLeadPreChecksPipe**: Validates lead eligibility
3. **VerifyAlreadyInProgressAllocationPipe**: Prevents duplicate allocations
4. **EvaluateTeamPipe**: Determines team assignment logic
5. **FetchAvailableAdvisorPipe**: Finds and selects available advisor
6. **AssignLeadPipe**: Assigns advisor to lead
7. **MakeResponsePipe**: Returns allocation response

## Allocation Flow

```
Quote Created/Updated
    ↓
CyberAllocation::execute()
    ↓
Pipeline Execution
    ↓
FetchLeadPipe → Get Cyber quote
    ↓
VerifyLeadPreChecksPipe → Validate lead
    ↓
VerifyAlreadyInProgressAllocationPipe → Check duplicates
    ↓
EvaluateTeamPipe → Determine team logic
    ↓
FetchAvailableAdvisorPipe → Find advisor
    ↓
AssignLeadPipe → Assign advisor
    ↓
MakeResponsePipe → Return response
```

## Configuration

### Application Storage Keys

The ILA system uses one app storage key:

1. **CYBER_ADVISORS**: Advisor emails (comma-separated)

   - First email = Primary advisor
   - Remaining emails = Backup advisors
   - Example: `smitha.chandran@insurancemarket.ae,neil.rama@insurancemarket.ae`
   - Different environments can set different emails in this key (UAT/Stage vs Production)

### Seeder Location

- **File**: `database/seeders/ApplicationStorageSeeder.php`
- **Method**: `seedCyberConfigurations()`

## Related Files

### Backend

- `app/Strategies/Allocations/CyberAllocation.php` - Main allocation strategy
- `app/Pipes/Allocation/Cyber/FetchAvailableAdvisorPipe.php` - Advisor fetching logic
- `app/Pipes/Allocation/Cyber/EvaluateTeamPipe.php` - Team evaluation logic
- `app/Pipes/Allocation/Cyber/AssignLeadPipe.php` - Lead assignment logic
- `app/Pipes/Allocation/Cyber/VerifyLeadPreChecksPipe.php` - Lead validation
- `app/Services/AllocationService.php` - Base allocation service with `isUserOnLeave()` method

### Configuration

- `app/Enums/ApplicationStorageEnums.php` - Storage key constants
- `database/seeders/ApplicationStorageSeeder.php` - Seeder for app storage keys

## Business Requirements

For complete business requirements and functional specifications:

- [Business Requirements](./business-requirements.md) - Complete FR documentation

## Next Steps

For detailed information about each component:

- [Allocation Logic](./allocation-logic.md) - How advisors are selected
- [Production Mode](./production-mode.md) - Allocation rules and behavior
- [Daily Capacity](./daily-capacity.md) - Daily capacity cap management (200 leads/day)
- [App Storage Keys](./app-storage-keys.md) - Storage key documentation

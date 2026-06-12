# ILA Business Requirements

## Overview

This document outlines the business requirements and functional specifications for Cyber Insurance Instant Lead Allocation (ILA) system based on the functional requirements.

## Core Requirements

### Primary Advisor Assignment

**Requirement**: All Cyber Insurance leads are assigned to the primary advisor (Smitha Chandran).

**Implementation**:

- Primary advisor: `smitha.chandran@insurancemarket.ae`
- All leads assigned to primary advisor by default
- No category-based assignment - all leads go to primary advisor
- Location: `app/Pipes/Allocation/Cyber/FetchAvailableAdvisorPipe.php`

### Backup Advisor Assignment

**Requirement**: In case of primary advisor absence, leads should be assigned to backup advisor.

**Implementation**:

- Backup advisor: `neil.rama@insurancemarket.ae`
- Trigger: Primary advisor status is SICK (4) or LEAVE (5)
- Note: If primary advisor is OFFLINE, leads are still assigned to primary advisor
- Location: `app/Pipes/Allocation/Cyber/FetchAvailableAdvisorPipe.php:141-182`

### Daily Capacity Cap

**Requirement**: Cap facility available and manually updateable: 200 leads per day (controlled by admin).

**Implementation**:

- Default capacity: 200 leads per day per Cyber advisor
- Stored in: `lead_allocations` table (`max_capacity` field)
- Reset daily via scheduled command: `ResetLeadAllocationCounts`
- Location: `database/seeders/CyberLeadAllocationSeeder.php:15`
- Code Reference: `app/Console/Commands/ResetLeadAllocationCounts.php:96-103`

**Configuration**:

```php
private const CYBER_ADVISOR_MAX_CAPACITY = 200;
```

### CHS Advisor Assignment (Automation Flow)

**Requirement**: Leads that have completed or failed AWNI Cyber automation are assigned to CHS advisors.

**Implementation**:

- Trigger: AWNI Cyber automation completed or failed
- Set via: `isCHSAdvisor` flag in `VerifyLeadPreChecksPipe`
- Location: `app/Pipes/Allocation/Cyber/FetchAvailableAdvisorPipe.php:25-45`
- Code Reference: `app/Pipes/Allocation/Cyber/VerifyLeadPreChecksPipe.php:87-89`

**Note**: Paid leads in normal allocation flow are handled through SIC advisor assignment, not through Happiness User assignment. Happiness User assignment is only used in automation scenarios (handled separately).

### SIC Lead Handling

**Requirement**: All leads are SIC (Self-Initiated Customer). Customer can request for an advisor.

**Implementation**:

- SIC advisor request flag: `cyber_quote.sic_advisor_requested`
- If SIC advisor requested OR retry flag set → Proceed with allocation
- Otherwise → Stop allocation (lead not eligible)
- Location: `app/Pipes/Allocation/Cyber/EvaluateTeamPipe.php:68-81`

### Manual Reassignment

**Requirement**: Manual reassignment option should be available. Auto-reassignment is not required.

**Implementation**:

- Manual reassignment available via admin interface
- Auto-reassignment: Not implemented (as per requirement)
- Override advisor ID supported via allocation request

### Cyber Manager Role

**Requirement**: Cyber Manager: `sandeep.sharma@insurancemarket.ae`

**Note**: Cyber Manager role exists but is not directly used in allocation logic. Used for permission checks (e.g., `canAddBatchNumber`).

**Location**: `app/Services/Quotes/CyberQuoteService.php:141`

### Product and Team Assignment

**Requirement**: Product and team to be assigned to the advisor as well as HAPEX during assignment logic.

**Implementation**:

- Product assignment handled during lead assignment
- Team assignment handled during lead assignment
- Location: `app/Pipes/Allocation/Common/BaseAllocationPipe.php:assignLeadToUserAndGetQuote()`

## Additional Allocation Triggers

### Follow-up Assignment

**Requirement**: If the lead has no update after 2nd follow-up, the advisor gets assigned.

**Status**: Implementation details to be verified in follow-up workflow system.

### API Failure Assignment

**Requirement**: If the API fails at the quotes page and no plan is displayed to the customer, the advisor is assigned.

**Status**: Implementation details to be verified in API error handling system.

### Document Upload Deadline

**Requirement**: If the customer has not uploaded any document after 24 hours of payment being authorized, assign an advisor.

**Status**: Implementation details to be verified in document upload tracking system.

### Age Restriction

**Requirement**: If the customer's age is below 18, as per age restriction, the thank-you page appears.

**Status**: Frontend validation - not part of ILA allocation logic.

## Customer Request Mechanisms

### InstantAlfred Integration

**Requirement**: The customer can request for an advisor only via InstantAlfred - should be logged in audit log.

**Status**: Implementation details to be verified in InstantAlfred integration.

### E-commerce Request Button

**Requirement**: Before InstantAlfred is live, the request for advisor button should be part of the E-com journey. Follow existing same functionality in motor, request button and AIG logic.

**Status**: Implementation details to be verified in E-commerce journey.

## Lead Allocation Dashboard

**Requirement**: Lead allocation dashboard for Cyber should be there as it is in the existing LOBs.

**Status**: Dashboard exists for all LOBs including Cyber. Location: Lead Allocation management interface.

## Advisor Mapping

**Requirement**: Advisors should be mapped to Cyber Insurance LOB so that they can handle these specific leads. Should allow other LOB lead allocation to same user if team role and products are mapped.

**Implementation**:

- Advisors mapped via `lead_allocations` table
- Role requirement: `CyberAdvisor` role
- Multiple LOB support: Same user can handle multiple LOBs if roles and products mapped
- Location: `database/seeders/CyberLeadAllocationSeeder.php`

## Related Files

- **Allocation Logic**: `app/Pipes/Allocation/Cyber/FetchAvailableAdvisorPipe.php`
- **Team Evaluation**: `app/Pipes/Allocation/Cyber/EvaluateTeamPipe.php`
- **Lead Allocation Seeder**: `database/seeders/CyberLeadAllocationSeeder.php`
- **Capacity Reset**: `app/Console/Commands/ResetLeadAllocationCounts.php`
- **Allocation Strategy**: `app/Strategies/Allocations/CyberAllocation.php`

# Lead Details Module

## Overview

The Lead Details module provides Cyber Insurance Advisors with a comprehensive view of all lead information, including customer details, available plans, payments, documents, policy details, and booking information.

## Purpose

- **Complete Lead View**: View all information about a Cyber Insurance lead
- **Plan Management**: View and select available insurance plans
- **Payment Tracking**: Monitor payment status and history
- **Document Management**: Upload and view customer documents
- **Policy Management**: Track policy booking and issuance
- **Activity Tracking**: View lead history and activities

## Key Features

- **Comprehensive Information Display**: All lead data in one place
- **Available Plans Section**: View and select insurance plans
- **Payment Management**: View payment status and history
- **Document Upload**: Upload and manage customer documents
- **Status Management**: Update lead status and track changes
- **Activity Log**: Complete history of lead activities
- **Notes Management**: Add and view lead notes

## Access Control

### Permissions Required

- **View Details**: `CYBER_QUOTES_SHOW` or `VIEW_ALL_LEADS`
- **Edit Lead**: `CYBER_QUOTES_EDIT` or `VIEW_ALL_LEADS`
- **Add Batch Number**: `CYBER_MANAGER` role required

### Roles

- **Cyber Manager**: Full access (view, edit, add batch numbers)
- **Cyber Advisor**: View and edit access
- **Admin**: Full access via `VIEW_ALL_LEADS` permission

## Components

### Frontend Component

- **File**: `resources/js/inertia/Pages/CyberQuote/Show.vue`
- **Framework**: Vue.js 3.5 with Composition API
- **Sections**: Multiple sections for different lead aspects

### Backend Controller

- **File**: `app/Http/Controllers/V2/CyberQuoteController.php`
- **Method**: `show($uuid)`

### Service Layer

- **File**: `app/Services/Quotes/CyberQuoteService.php`
- **Method**: `getShowData($uuid)`

## Page Sections

### 1. Lead Information

**Displays**:

- Customer details (name, email, mobile, DOB, nationality)
- Emirate of registration
- Lead reference ID (CYB-xxxxxx)
- Lead status
- Created date
- Last modified date

**Edit Capability**:

- Editable until status = "Policy Booked"
- Email and mobile are immutable after creation

### 2. Available Plans

**Purpose**: View and select available Cyber Insurance plans

**Features**:

- Plan table with provider, plan name, coverage, pricing
- Plan details modal (Plan Details, Included Benefits, Policy Wordings)
- Plan selection functionality
- Price display (with/without VAT)

**API Endpoint**: `POST /quotes/cyber/available-plans/{uuid}`

**Related Documentation**: [Available Plans Module](./../available-plans/README.md)

### 3. Payment Information

**Displays**:

- Payment status
- Payment history
- Payment methods
- Transaction details
- Payment due dates

**Components**: `QuotePayments` partial component

### 4. Documents

**Purpose**: Manage customer documents

**Features**:

- Upload documents
- View document list
- Document type categorization
- OCR logs (if applicable)

**Note**: Documents uploaded after customer clicks "Verify and proceed" button after OCR completion

### 5. Policy Details

**Displays**:

- Policy number
- Policy booking date
- Policy expiry date
- Insurance provider
- Plan details

### 6. Booking Details

**Displays**:

- Booking information
- Policy issuance status
- Related booking data

### 7. Activities & History

**Purpose**: Track all lead activities

**Features**:

- Activity timeline
- Status change history
- Payment history
- Document upload history
- Notes history

**Components**:

- `QuoteActivities` partial
- `LeadHistory` partial

### 8. Notes

**Purpose**: Add and view lead notes

**Features**:

- Add notes
- View note history
- Note timestamps
- Note author tracking

**Component**: `LeadNotes` component

## Edit Restrictions

### When Editing is Blocked

**Condition**: Lead status = "Policy Booked"

**Behavior**:

- Edit button disabled
- Form fields disabled
- Same behavior as other LOBs

### Immutable Fields

**After Creation**:

- Email (disabled in edit mode)
- Mobile Number (disabled in edit mode)

**Reason**: Prevent data inconsistency

## API Integration

### Plan Fetching

**Trigger**: User clicks "Load Available Plans" or page loads

**API**: KEN API endpoint `/cyber/get-quote-plans`

**Process**:

1. Frontend calls `/quotes/cyber/available-plans/{uuid}`
2. Backend calls KEN API
3. Plans displayed in table
4. User can view plan details and select plans

**Related Documentation**: [Available Plans API Integration](./../available-plans/api-integration.md)

## Data Loading

### Initial Load

**Data Fetched**:

- Lead information
- Quote request details
- Payment information
- Document list
- Activity history
- Available plans (on demand)

### Relationships Loaded

- `cyberQuote`
- `quoteStatus`
- `advisor`
- `nationality`
- `insuranceProviderPlan`
- `payments` (with nested relationships)
- `documents`
- `quoteDetail`

## Related Files

### Backend

- **Controller**: `app/Http/Controllers/V2/CyberQuoteController.php:96-101`
- **Service**: `app/Services/Quotes/CyberQuoteService.php:133-144`
- **Model**: `app/Models/PersonalQuote.php`

### Frontend

- **Component**: `resources/js/inertia/Pages/CyberQuote/Show.vue`
- **Partials**:
  - `SelectPlan.vue`
  - `QuotePayments.vue`
  - `QuoteActivities.vue`
  - `LeadHistory.vue`
  - `AdditionalContacts.vue`

## Special Features

### Stale Lead Indicator

**Display**: Red badge showing "Stale for X days"

**Calculation**: Days since `stale_at` timestamp

**Purpose**: Highlight leads that need attention

### Legacy Policy Link

**Condition**: If `quote_detail.insly_id` exists

**Display**: "View Legacy policy" button

**Purpose**: Link to legacy policy system

### Duplicate Lead

**Feature**: Create duplicate lead functionality

**Modal**: Duplicate lead modal with LOB selection

**Purpose**: Create copy of lead for different LOB

## Related Documentation

- [Lead Form](./../lead-form/README.md) - Creating and editing leads
- [Available Plans](./../available-plans/README.md) - Plan selection
- [ILA Module](./../ila/README.md) - Advisor allocation
- [Permissions & Roles](./../permissions-roles.md) - Access control

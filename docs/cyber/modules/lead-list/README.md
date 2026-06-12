# Lead List Module

## Overview

The Lead List module provides Cyber Managers and Advisors with a comprehensive view of all Cyber Insurance leads coming from E-commerce. It displays leads in a table format with filtering and sorting capabilities.

## Purpose

- **Lead Management**: View and manage all Cyber Insurance leads in one place
- **Filtering**: Filter leads by various criteria (status, advisor, date ranges, etc.)
- **Search**: Search leads by reference ID, name, email, mobile number
- **Navigation**: Quick access to lead details and creation

## Key Features

- **List View Only**: Table view for lead listing (cards view not available)
- **Comprehensive Filters**: Multiple filter options for lead search
- **Pagination**: 10 leads per page with navigation
- **Sorting**: Sort by any column (default: created_at desc)
- **Quick Actions**: Direct links to lead details and creation

## Access Control

### Permissions Required

- **View List**: `CYBER_QUOTES_LIST` or `VIEW_ALL_LEADS`
- **Create Lead**: `CYBER_QUOTES_CREATE`
- **View Details**: `CYBER_QUOTES_SHOW` or `VIEW_ALL_LEADS`

### Roles

- **Cyber Manager**: Full access (view, create, edit all leads)
- **Cyber Advisor**: View access to assigned leads
- **Admin**: Full access via `VIEW_ALL_LEADS` permission

## Components

### Frontend Component

- **File**: `resources/js/inertia/Pages/CyberQuote/Index.vue`
- **Framework**: Vue.js 3.5 with Composition API
- **UI Components**: DataTable, filters

### Backend Controller

- **File**: `app/Http/Controllers/V2/CyberQuoteController.php`
- **Method**: `index()`

### Service Layer

- **File**: `app/Services/Quotes/CyberQuoteService.php`
- **Method**: `getData()`

## Table Columns

1. **REF ID**: Lead reference ID (UUID)
2. **FIRST NAME**: Customer first name
3. **LAST NAME**: Customer last name
4. **LEAD STATUS**: Current quote status (`quote_status`)
5. **LEAD SOURCE**: Source of the lead (E-commerce, IMCRM, etc.) (`source`)
6. **PLAN NAME**: Selected insurance plan name (`insurance_provider_plan.text`)
7. **COVERAGE UP TO**: Maximum coverage amount (`coverage_up_to`)
8. **TOTAL PRICE**: Total premium amount (`premium`)
9. **POLICY NUMBER**: Policy number (if booked) (`policy_number`)
10. **ADVISOR**: Assigned advisor name (`advisor`)
11. **CREATED DATE**: Lead creation date (`created_at`) - Sortable
12. **PAYMENT AUTHORIZED DATE**: Payment authorization date (`authorized_at`)
13. **LAST MODIFIED DATE**: Last update date (`updated_at`) - Sortable
14. **POLICY EXPIRY DATE**: Policy expiry date (`previous_policy_expiry_date`) - Sortable

**Code Reference**: `resources/js/inertia/Pages/CyberQuote/Index.vue:86-116`

## Filters Available

**Code Reference**: `resources/js/inertia/Pages/CyberQuote/Index.vue:30-55,427-662`

### Basic Filters

- **Reference ID** (`code`): Search by lead reference ID (text input)
- **First Name** (`first_name`): Filter by customer first name (text input)
- **Last Name** (`last_name`): Filter by customer last name (text input)
- **Email** (`email`): Filter by customer email (text input)
- **Mobile Number** (`mobile_no`): Filter by mobile number (text input)

### Status Filters

- **Lead Status** (`quote_status_id`): Filter by quote status (ComboBox dropdown)
- **Payment Status** (`payment_status_id`): Filter by payment status (select dropdown)
- **IM AML Status** (`insurer_aml_status`): Filter by insurer AML status (multi-select dropdown)

### Date Filters

- **Created Date Start** (`created_at_start`): Start date for creation date range (date picker)
- **Created Date End** (`created_at_end`): End date for creation date range (date picker)
- **Policy Start Date** (`policy_expiry_date`): Policy start date filter (date picker)
- **Policy End Date** (`policy_expiry_date_end`): Policy end date filter (date picker)
- **Payment Due Date** (`payment_due_date`): Filter by payment due date (range date picker with multi-calendar)
- **Booking Date** (`booking_date`): Filter by booking date range (range date picker with multi-calendar)
- **Last Modified Date** (`last_modified_date`): Filter by last modification date (range date picker)
- **Transaction Approved Date** (`transaction_approved_dates`): Filter by transaction approval dates (range date picker, max 30 days)

### Other Filters

- **Advisor** (`advisor_id`): Filter by assigned advisor (multi-select ComboBox, hidden for Cyber Advisor role)
- **Plan Name** (`plan_name`): Filter by plan name (multi-select dropdown with select all/clear actions)
- **E-commerce** (`is_ecommerce`): Filter by E-commerce flag (dropdown: All/Yes/No)
- **Coverage Up To** (`coverage_up_to`): Filter by coverage amount (select dropdown from cyber coverages)
- **Insurer Tax Invoice Number** (`insurer_tax_invoice_number`): Filter by insurer tax invoice number (text input)
- **Insurer Commission Tax Invoice Number** (`insurer_commission_tax_invoice_number`): Filter by commission tax invoice number (text input)
- **Policy Number** (`previous_quote_policy_number_text`): Filter by policy number (text input)

**Note**: Date filters are mutually exclusive - selecting one resets others (payment_due_date, booking_date, created_at).

## Data Source

### Query Builder

**Location**: `app/Services/Quotes/CyberQuoteService.php:26-70`

**Base Query**:

- Table: `personal_quotes`
- Quote Type: Cyber (`QuoteTypes::CYBER`)
- Customer Type: Individual only (never Entity)

**Relationships Loaded**:

- `quoteStatus`
- `currentlyInsuredWith`
- `advisor`
- `paymentStatus`
- `payments`
- `quoteDetail`
- `renewalBatchModel`
- `nationality`
- `insuranceProviderPlan`

## Pagination

- **Per Page**: 10 leads
- **Pagination Type**: Simple pagination (`simplePaginate()`)
- **Query String**: Preserved in pagination links

## Related Files

### Backend

- **Controller**: `app/Http/Controllers/V2/CyberQuoteController.php`
- **Service**: `app/Services/Quotes/CyberQuoteService.php`
- **Request**: Filter parameters via query string

### Frontend

- **Component**: `resources/js/inertia/Pages/CyberQuote/Index.vue`
- **Partials**: `LeadAssignment` component for advisor assignment

## Navigation

### From Lead List

- **Create Lead**: Click "Create Lead" button → Navigate to form
- **View Details**: Click on lead row or reference ID → Navigate to lead details
- **Filter**: Use filter panel to narrow down results

## Customer Type

**FR Requirement**: This LOB will always have customer type as **Individual** and never Entity profile.

**Implementation**:

- Enforced in lead creation
- Query filters exclude entity types
- Data display shows individual customer type only
- No entity profile support for Cyber Insurance

## Lead Reference Format

**Format**: `CYB-xxxxxx`

**Prefix**: `CYB-` (from quote type short_code)

**Example**: `CYB-123456`

**Location**: Generated automatically when lead is created via external API

## Related Documentation

- [Lead Form](./../lead-form/README.md) - Creating new leads
- [Lead Details](./../lead-details/README.md) - Viewing lead details
- [Permissions & Roles](./../permissions-roles.md) - Access control

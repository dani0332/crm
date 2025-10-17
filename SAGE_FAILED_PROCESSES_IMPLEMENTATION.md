# Sage Failed Processes Bulk View - Implementation Guide

## Overview

This document provides a comprehensive overview of the implementation of the Sage Failed Processes bulk view functionality. This feature allows users to view and export all failed sage processes along with their related quote/send_update entries and sage API logs.

## Features Implemented

### 1. Service Layer

**File:** `app/Services/SageProcessesService.php`

**Purpose:** Handles the business logic for fetching failed sage processes

**Key Features:**

- Fetches all sage processes with status = 'failed'
- Joins with quote/send_update tables to get related entries
- Filters only entries where quote status = 'Policy Booking Failed' (76) or send_update status = 'UPDATE_BOOKING_FAILED'
- Fetches the latest failed entry from sage_api_logs for each process
- Supports pagination and export modes
- Includes comprehensive filters:
  - Insurance Provider
  - Quote Type (Line of Business)
  - Date Range (from/to)
  - Quote Code
  - User ID

**Key Methods:**

- `getFailedSageProcesses($isExport = false)` - Main method to fetch data
- `buildFailedProcessesQuery()` - Builds the complex query with multiple joins
- `applyFilters($query)` - Applies request filters to the query
- `applyAuthorizationFilters($query)` - Applies user-based authorization (placeholder for future implementation)

**Query Details:**
The service performs complex joins across multiple tables:

- `sage_processes` (main table)
- `insurance_provider`
- `users`
- All quote type tables (PersonalQuote, CarQuoteRequest, HomeQuoteRequest, etc.)
- `send_update_logs`
- `quote_status` tables
- `quote_type`
- `sage_api_logs` (latest failed entry)

### 2. Controller Layer

**File:** `app/Http/Controllers/V2/SageProcessesController.php`

**Purpose:** Handles HTTP requests for the failed sage processes feature

**Key Features:**

- Strict type declarations for Laravel 12 compatibility
- Permission-based middleware using `PermissionsEnum::VIEW_SAGE_API_LOGS`
- Comprehensive error handling with logging
- Constructor dependency injection

**Methods:**

1. `index()` - Display listing page

   - Returns Inertia view with data
   - Provides filter options (insurance providers, quote types, users)
   - Handles errors gracefully

2. `export()` - Export to Excel
   - Fetches data for export
   - Generates export logs
   - Returns downloadable Excel file
   - Handles errors with JSON response

### 3. Export Class

**File:** `app/Exports/SageProcessesExport.php`

**Purpose:** Handles Excel export formatting

**Key Features:**

- Extends `BaseReportsExport`
- Implements `WithTitle` for sheet naming
- Comprehensive column headers
- Smart data mapping with fallbacks

**Export Columns:**

1. Sage Process ID
2. Quote Code
3. Quote Type
4. Quote Status
5. Insurance Provider
6. User Name
7. User Email
8. Sage Request Type
9. Sage Endpoint
10. Sage API Status
11. Sage Response (parsed for readability)
12. Process Message
13. Process Created At
14. Process Updated At
15. Sage API Log Created At

**Data Processing:**

- Parses JSON sage responses to extract error messages
- Formats dates to 'DD-MM-YYYY HH:mm:ss'
- Truncates long responses to 200 characters
- Handles null values with 'N/A' fallback

### 4. Routes

**File:** `routes/web.php`

**Routes Added:**

```php
Route::get('sage-failed-processes', [SageProcessesController::class, 'index'])
    ->name('sage-failed-processes.index');

Route::get('sage-failed-processes-export', [SageProcessesController::class, 'export'])
    ->name('sage-failed-processes.export');
```

**Middleware:**

- `auth` - Requires authentication
- `last_login_check` - Checks last login
- `readonly_db` - Uses read-only database connection
- `permission:VIEW_SAGE_API_LOGS` - Requires sage API logs view permission

### 5. Vue.js Frontend

**File:** `resources/js/inertia/Pages/SageProcesses/Index.vue`

**Purpose:** Provides user interface for viewing and filtering failed sage processes

**Key Features:**

- Modern Vue 3 Composition API with `<script setup>`
- Responsive data table with sortable columns
- Advanced filtering system
- Export functionality
- Pagination support
- Real-time filter count badge

**Components Used:**

- DataTable - Main table display
- Modal - Filter dialog
- Button - Action buttons
- Badge - Status indicators
- Tooltip - Helpful hints
- Input/Select/DatePicker - Filter inputs
- Pagination - Page navigation

**Table Columns:**

1. Sage Process ID (sortable)
2. Quote Code (clickable link to quote detail)
3. Quote Type (badge)
4. Quote Status (danger badge)
5. Insurance Provider
6. User Name
7. Sage Request Type
8. Sage Status (color-coded badge)
9. Process Message (with tooltip for long text)
10. Updated At (formatted date)

**Filter Options:**

1. Quote Code (text input)
2. Insurance Provider (multi-select, searchable)
3. Line of Business (multi-select, searchable)
4. User (multi-select, searchable)
5. Date Range (date from/to pickers)

**User Experience Features:**

- Loading states for table and export
- Empty state with helpful message
- Error notifications
- Filter count badge
- Clickable quote codes that navigate to detail pages
- Truncated text with tooltips for long content
- Color-coded badges for status visualization
- Responsive pagination

## Database Schema Requirements

### Tables Used:

1. **sage_processes**

   - id, user_id, insurance_provider_id
   - model_type, model_id (polymorphic)
   - status, message
   - created_at, updated_at

2. **sage_api_logs**

   - id, user_id
   - model_id, model_type (polymorphic)
   - section_id, section_type (polymorphic)
   - sage_request_type, sage_end_point
   - sage_payload, response, status
   - created_at

3. **Quote Tables** (all variations)

   - personal_quotes
   - car_quote_requests
   - home_quote_requests
   - health_quote_requests
   - life_quote_requests
   - business_quote_requests
   - travel_quote_requests

   Common fields: id, uuid, code, quote_type_id, quote_status_id

4. **send_update_logs**

   - id, uuid, code
   - quote_type_id, status
   - personal_quote_id (parent quote)

5. **Supporting Tables**
   - insurance_provider (id, text, code)
   - users (id, name, email)
   - quote_type (id, code, text)
   - quote_status (id, text)

## Status Enums

### Sage Process Status

**File:** `app/Enums/SageEnum.php`

- `SAGE_PROCESS_FAILED_STATUS = 'failed'`
- `STATUS_FAIL = 'fail'` (for API logs)

### Quote Status

**File:** `app/Enums/QuoteStatusEnum.php`

- `POLICY_BOOKING_FAILED = 76`

### Send Update Status

**File:** `app/Enums/SendUpdateLogStatusEnum.php`

- `UPDATE_BOOKING_FAILED = 'UPDATE_BOOKING_FAILED'`

## Permissions Required

**Permission:** `VIEW_SAGE_API_LOGS`

Users must have this permission to:

- Access the failed sage processes index page
- Export failed sage processes data

This permission is already used in the existing codebase for:

- Viewing sage API logs: `Route::get('sage-api-logs/{sectionId}')`
- Getting latest sage errors: `Route::get('sage-api-logs/{sectionId}/latest-error')`

## How to Access

### URL Routes:

1. **Index Page:** `/sage-failed-processes`
2. **Export:** `/sage-failed-processes-export?[filters]`

### Named Routes:

```php
route('sage-failed-processes.index')
route('sage-failed-processes.export')
```

## Usage Examples

### 1. Viewing Failed Processes

1. Navigate to `/sage-failed-processes`
2. Click "Search Filters" button
3. Apply desired filters
4. Click "Apply Filters"
5. View results in the data table

### 2. Exporting Failed Processes

1. Apply filters to narrow down results
2. Click "Export to Excel" button
3. Excel file will download automatically

### 3. Viewing Quote Details

- Click on any Quote Code in the table
- Redirects to the quote detail page

## Implementation Patterns Followed

### 1. Laravel Best Practices

✅ Strict typing with `declare(strict_types=1)`
✅ Dependency injection in controllers
✅ Service layer for business logic
✅ Repository pattern for data access (via repositories)
✅ Comprehensive error handling and logging
✅ PSR-12 coding standards

### 2. Security Implementation

✅ Permission-based middleware
✅ Input validation through request filters
✅ SQL injection prevention via query builder
✅ Authorization filters placeholder
✅ CSRF protection (automatic)

### 3. Vue.js Component Patterns

✅ Composition API with `<script setup>`
✅ Permission checks using `useCan()`
✅ Reactive data with `ref()` and `reactive()`
✅ Computed properties for derived data
✅ Consistent prop validation
✅ Comprehensive error handling
✅ User-friendly feedback with toast notifications

### 4. Code Documentation

✅ PHPDoc blocks for all methods
✅ Inline comments for complex logic
✅ Type hints and return types
✅ Parameter descriptions
✅ Class-level documentation

## Testing Checklist

### Functional Testing

- [ ] Can access the page with correct permissions
- [ ] Cannot access without `VIEW_SAGE_API_LOGS` permission
- [ ] Data loads correctly on page load
- [ ] Filters work as expected
- [ ] Pagination works correctly
- [ ] Sorting works on sortable columns
- [ ] Quote code links navigate to correct pages
- [ ] Export generates correct Excel file
- [ ] Export includes all filtered data
- [ ] Empty state displays when no data

### Data Integrity Testing

- [ ] Only failed sage processes are displayed
- [ ] Only entries with policy booking failed status are shown
- [ ] Latest failed sage API log is correctly fetched
- [ ] All quote types are handled correctly
- [ ] Send update logs are included
- [ ] Dates are formatted correctly

### Performance Testing

- [ ] Page loads in reasonable time
- [ ] Export completes for large datasets
- [ ] Pagination doesn't cause performance issues
- [ ] Query optimization is effective

## Future Enhancements

### Potential Improvements:

1. **Advanced Filters**

   - Sage request type filter
   - Insurance provider category filter
   - Advanced date filters (last 7 days, last 30 days, etc.)

2. **Bulk Actions**

   - Retry failed processes in bulk
   - Mark as resolved
   - Assign to user for investigation

3. **Analytics Dashboard**

   - Failed processes count by insurance provider
   - Failed processes trend over time
   - Most common error types

4. **Notifications**

   - Real-time alerts for new failed processes
   - Email digest of failed processes

5. **Auto-Retry Mechanism**

   - Automatic retry for certain error types
   - Configurable retry rules

6. **Detailed View**
   - Modal or side panel with complete sage API log details
   - Request/response viewer with JSON formatting
   - Timeline of process attempts

## Troubleshooting

### Common Issues:

1. **No data displayed**

   - Verify sage_processes table has failed entries
   - Check if quotes have POLICY_BOOKING_FAILED status
   - Ensure sage_api_logs has failed entries

2. **Permission denied**

   - Verify user has `VIEW_SAGE_API_LOGS` permission
   - Check role assignments

3. **Export not working**

   - Check write permissions for storage
   - Verify Excel export dependencies are installed
   - Check export logs for errors

4. **Query performance issues**
   - Add database indexes on frequently filtered columns
   - Consider caching for filter options
   - Optimize joins if needed

## Related Files

### Backend:

- Service: `app/Services/SageProcessesService.php`
- Controller: `app/Http/Controllers/V2/SageProcessesController.php`
- Export: `app/Exports/SageProcessesExport.php`
- Routes: `routes/web.php` (lines 91, 847-848)

### Frontend:

- Index Page: `resources/js/inertia/Pages/SageProcesses/Index.vue`

### Models Referenced:

- `app/Models/SageProcess.php`
- `app/Models/SageApiLog.php`
- `app/Models/PersonalQuote.php`
- `app/Models/SendUpdateLog.php`
- Various quote type models

### Enums:

- `app/Enums/SageEnum.php`
- `app/Enums/QuoteStatusEnum.php`
- `app/Enums/SendUpdateLogStatusEnum.php`
- `app/Enums/PermissionsEnum.php`

## Maintenance Notes

### When to Update This Feature:

1. **New Quote Type Added**

   - Add join in `SageFailedProcessService::buildFailedProcessesQuery()`
   - Update CASE statements for quote_code, quote_uuid, quote_type_id, quote_status

2. **New Status Values**

   - Update filter conditions if new failed statuses are added
   - Update enum references

3. **Performance Optimization**

   - Add database indexes if query becomes slow
   - Consider materialized views for complex joins
   - Implement caching for filter options

4. **UI/UX Improvements**
   - Update table columns based on user feedback
   - Add new filters as requested
   - Enhance export format

## Contact & Support

For questions or issues related to this implementation:

- Review this documentation
- Check Laravel logs: `storage/logs/laravel.log`
- Review browser console for frontend errors
- Check database query logs for performance issues

---

**Implementation Date:** October 6, 2025
**Laravel Version:** 12.x
**Vue.js Version:** 3.5.x
**PHP Version:** 8.2+

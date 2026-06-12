# Cyber Insurance Permissions & Roles

## Overview

This document describes the roles and permissions system for Cyber Insurance LOB, including role definitions, permission mappings, and access control rules.

## Roles

### Cyber Manager

**Role Constant**: `CYBER_MANAGER`

**Role Name**: `CYBER_MANAGER`

**Location**: `app/Enums/RolesEnum.php:110`

**Description**:

- Allows user to view, create, and edit all Cyber leads in the system
- Full access to Cyber Insurance module
- Can add batch numbers to leads
- Equivalent to Car Manager role for Cyber LOB

**Permissions**:

- All Cyber quote permissions (list, create, edit, show)
- Additional manager-level permissions
- Can view and edit all leads (not just assigned)

**Special Capabilities**:

- Add batch numbers (`canAddBatchNumber` permission check)
- View all leads regardless of assignment
- Full CRUD operations on Cyber leads

### Cyber Advisor

**Role Constant**: `CYBER_ADVISOR`

**Role Name**: `CYBER_ADVISOR`

**Location**: `app/Enums/RolesEnum.php:109`

**Description**:

- Allows user to view Cyber leads in the system
- Can view assigned leads
- Can create new leads
- Can edit leads (with restrictions)
- Equivalent to Car Advisor role for Cyber LOB

**Permissions**:

- Cyber quote list, create, edit, show permissions
- Limited to assigned leads (unless has `VIEW_ALL_LEADS`)

**Restrictions**:

- Cannot add batch numbers
- May have limited access to certain administrative functions

## Permissions

### Core Cyber Permissions

#### CYBER_QUOTES_LIST

**Permission**: `cyber-quotes-list`

**Location**: `app/Enums/PermissionsEnum.php:458`

**Description**: Allows user to view the Cyber quotes list page

**Usage**:

- Required for accessing `/personal-quotes/cyber` (index page, route name: `cyber-quotes-list`)
- Controller middleware: `CyberQuoteController@index`

**Equivalent**: `car-quotes-list` for Car LOB

#### CYBER_QUOTES_CREATE

**Permission**: `cyber-quotes-create`

**Location**: `app/Enums/PermissionsEnum.php:459`

**Description**: Allows user to create new Cyber leads

**Usage**:

- Required for accessing `/personal-quotes/cyber/create` (create form, route name: `cyber-quotes-create`)
- Required for `POST /personal-quotes/cyber` (store method, route name: `cyber-quotes-store`)
- Controller middleware: `CyberQuoteController@create`, `store`

**Equivalent**: `car-quotes-create` for Car LOB

#### CYBER_QUOTES_EDIT

**Permission**: `cyber-quotes-edit`

**Location**: `app/Enums/PermissionsEnum.php:460`

**Description**: Allows user to edit existing Cyber leads

**Usage**:

- Required for accessing `/personal-quotes/cyber/{uuid}/edit` (edit form, route name: `cyber-quotes-edit`)
- Required for `PUT /personal-quotes/cyber/{uuid}` (update method, route name: `cyber-quotes-update`)
- Controller middleware: `CyberQuoteController@edit`, `update`

**Restrictions**:

- Editing blocked when lead status = "Policy Booked"
- Email and mobile fields immutable after creation

**Equivalent**: `car-quotes-edit` for Car LOB

#### CYBER_QUOTES_SHOW

**Permission**: `cyber-quotes-show`

**Location**: `app/Enums/PermissionsEnum.php:461`

**Description**: Allows user to view Cyber lead details

**Usage**:

- Required for accessing `/personal-quotes/cyber/{uuid}` (show page, route name: `cyber-quotes-show`)
- Controller middleware: `CyberQuoteController@show`

**Equivalent**: `car-quotes-show` for Car LOB

### Additional Cyber Permissions

#### CYBER_COMPREHENSIVE_DASHBOARD

**Permission**: `cyber-comprehensive-dashboard`

**Location**: `app/Enums/PermissionsEnum.php:462`

**Description**: Access to comprehensive dashboard for Cyber Insurance

#### CYBER_CONVERSION_REPORT

**Permission**: `cyber-conversion-report`

**Location**: `app/Enums/PermissionsEnum.php:463`

**Description**: Access to conversion reports for Cyber Insurance

#### CYBER_DISTRIBUTION_REPORT

**Permission**: `cyber-distribution-report`

**Location**: `app/Enums/PermissionsEnum.php:464`

**Description**: Access to distribution reports for Cyber Insurance

## Permission Mapping

### Car to Cyber Mapping

**Pattern**: All Car permissions have Cyber equivalents

**Examples**:

- `car-quotes-list` → `cyber-quotes-list`
- `car-quotes-create` → `cyber-quotes-create`
- `car-quotes-edit` → `cyber-quotes-edit`
- `car-quotes-show` → `cyber-quotes-show`

**Exception**: Car-specific permissions (e.g., vehicle-related) do not have Cyber equivalents

### Permission Seeding

**Location**: `database/seeders/CyberQuoteDataSeeder.php`

**Method**: `seedCyberPermissions()`

**Process**:

1. Creates Cyber permissions based on Car permissions
2. Maps Car permissions to Cyber equivalents
3. Assigns permissions to Cyber roles

## Access Control Rules

### Controller Middleware

**Location**: `app/Http/Controllers/V2/CyberQuoteController.php:19-26`

**Rules**:

```php
// Index: List or VIEW_ALL_LEADS
'permission:'.PermissionsEnum::CYBER_QUOTES_LIST.'|'.PermissionsEnum::VIEW_ALL_LEADS

// Create/Store: Create permission only
'permission:'.PermissionsEnum::CYBER_QUOTES_CREATE

// Edit/Update: Edit or VIEW_ALL_LEADS
'permission:'.PermissionsEnum::CYBER_QUOTES_EDIT.'|'.PermissionsEnum::VIEW_ALL_LEADS

// Show: Show or VIEW_ALL_LEADS
'permission:'.PermissionsEnum::CYBER_QUOTES_SHOW.'|'.PermissionsEnum::VIEW_ALL_LEADS
```

### Form Request Authorization

**Location**: `app/Http/Requests/Cyber/CyberQuoteRequest.php:16-21`

**Rules**:

```php
public function authorize(): bool
{
    return $this->user()->can(PermissionsEnum::CYBER_QUOTES_CREATE)
        || $this->user()->can(PermissionsEnum::CYBER_QUOTES_EDIT)
        || $this->user()->can(PermissionsEnum::VIEW_ALL_LEADS);
}
```

### Service Layer Checks

**Location**: `app/Services/Quotes/CyberQuoteService.php:138`

**Example**:

```php
$data['permissions']['canEditQuote'] = (
    $this->can(Auth::user(), PermissionsEnum::CYBER_QUOTES_EDIT)
    || (userHasProduct(quoteTypeCode::CYBER)
        && $this->can(Auth::user(), PermissionsEnum::VIEW_ALL_LEADS))
);
```

**Special Check**:

```php
'canAddBatchNumber' => $this->hasRole(Auth::user(), RolesEnum::CyberManager)
```

## Team & Product Setup

### Team Creation

**Location**: `database/seeders/CyberQuoteDataSeeder.php:109-127`

**Teams Created**:

1. **Product Team**: "Cyber Insurance"
   - Type: `PRODUCT`
   - Parent: None
2. **Team**: "Cyber Insurance - Team"
   - Type: `TEAM`
   - Parent: "Cyber Insurance" (product team)

**Purpose**:

- Organize Cyber Insurance advisors
- Support team-based allocation
- Product assignment during lead allocation

### Product Assignment

**Requirement**: Product and team to be assigned to advisor during allocation

**Implementation**:

- Handled in `BaseAllocationPipe::assignLeadToUserAndGetQuote()`
- Product: "Cyber Insurance"
- Team: Assigned based on advisor's team membership

## Role Assignment

### Advisor Mapping

**Requirement**: Advisors should be mapped to Cyber Insurance LOB

**Process**:

1. User assigned `CYBER_ADVISOR` or `CYBER_MANAGER` role
2. User assigned to "Cyber Insurance" product
3. User assigned to "Cyber Insurance - Team"
4. Lead allocation record created for Cyber quote type

**Location**: User management interface

**Note**: Same user can handle multiple LOBs if roles and products are mapped

## Permission Inheritance

### VIEW_ALL_LEADS Override

**Permission**: `VIEW_ALL_LEADS`

**Effect**:

- Overrides all LOB-specific permissions
- Allows viewing/editing all leads across all LOBs
- Typically assigned to Admin users

**Usage**:

- Used as fallback in permission checks
- Allows admins full access without LOB-specific permissions

## Seeder Information

### CyberQuoteDataSeeder

**Location**: `database/seeders/CyberQuoteDataSeeder.php`

**Responsibilities**:

1. Creates quote type (Cyber Insurance)
2. Maps quote statuses
3. Seeds roles (CyberAdvisor, CyberManager)
4. Creates product and team
5. Seeds document types
6. Seeds Cyber permissions

**Run Command**:

```bash
php artisan db:seed --class=CyberQuoteDataSeeder
```

## Related Files

### Enums

- **Permissions**: `app/Enums/PermissionsEnum.php`
- **Roles**: `app/Enums/RolesEnum.php`
- **Quote Types**: `app/Enums/QuoteTypes.php`

### Controllers

- **CyberQuoteController**: `app/Http/Controllers/V2/CyberQuoteController.php`

### Requests

- **CyberQuoteRequest**: `app/Http/Requests/Cyber/CyberQuoteRequest.php`

### Seeders

- **CyberQuoteDataSeeder**: `database/seeders/CyberQuoteDataSeeder.php`

## Permission Summary Table

| Permission | Constant              | Description         | Controller Methods |
| ---------- | --------------------- | ------------------- | ------------------ |
| List       | `CYBER_QUOTES_LIST`   | View lead list      | `index`            |
| Create     | `CYBER_QUOTES_CREATE` | Create new leads    | `create`, `store`  |
| Edit       | `CYBER_QUOTES_EDIT`   | Edit existing leads | `edit`, `update`   |
| Show       | `CYBER_QUOTES_SHOW`   | View lead details   | `show`             |

## Role Summary Table

| Role          | Constant        | Permissions              | Special Capabilities              |
| ------------- | --------------- | ------------------------ | --------------------------------- |
| Cyber Manager | `CYBER_MANAGER` | All Cyber permissions    | Add batch numbers, view all leads |
| Cyber Advisor | `CYBER_ADVISOR` | List, Create, Edit, Show | View assigned leads               |

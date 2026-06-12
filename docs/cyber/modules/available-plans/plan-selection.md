# Plan Selection Documentation

## Overview

This document explains how Cyber Insurance plans are selected and assigned to quotes, including the selection process, plan updates, and quote refresh logic.

## Plan Selection Process

### Selection Flow

```
User Views Available Plans
    ↓
User Clicks "Select Plan" Button
    ↓
handlePlanSelected(plan) Called
    ↓
Update selectedProviderPlan State
    ↓
Reload Quote Data (preserve state)
    ↓
Refresh Available Plans Table
    ↓
Plan Assigned to Quote
```

## Selection Handler

### Method

**Location**: `resources/js/inertia/Pages/CyberQuote/Show.vue:356-367`

**Code**:

```javascript
const handlePlanSelected = plan => {
  selectedProviderPlan.value.id = plan.id;
  selectedProviderPlan.value.planName = plan.planName;
  selectedProviderPlan.value.providerName = plan.providerName;
  selectedProviderPlan.value.premium = plan.premium;
  router.reload({
    preserveState: true,
    preserveScroll: true,
    only: ['payments', 'quoteRequest', 'quote', 'bookPolicyDetails'],
  });
  onLoadAvailablePlansData();
};
```

### Selected Plan State

**Location**: `resources/js/inertia/Pages/CyberQuote/Show.vue:349-354`

**Structure**:

```javascript
const selectedProviderPlan = ref({
  id: page.props?.quote?.plan_id,
  planName: page.props?.quote?.plans?.name,
  providerName: page.props?.quote?.plans?.providerName,
  premium: page.props?.quote?.plans?.premium,
});
```

**Properties**:

- `id`: Selected plan ID
- `planName`: Plan name
- `providerName`: Provider name
- `premium`: Plan premium

## Plan Update Process

### Router Reload

**Purpose**: Refresh quote data after plan selection

**Parameters**:

- `preserveState: true` - Maintains component state
- `preserveScroll: true` - Maintains scroll position
- `only: ['payments', 'quoteRequest', 'quote', 'bookPolicyDetails']` - Only reloads specific props

**Effect**:

- Quote data refreshed from server
- Selected plan information updated
- Related data (payments, booking details) refreshed

### Plans Refresh

**Method**: `onLoadAvailablePlansData()`

**Purpose**: Reload available plans table after selection

**Effect**:

- Fetches latest plans from API
- Updates table with current data
- Reflects any plan changes

## Plan Assignment

### Backend Assignment

Plan selection triggers backend update via:

- Quote reload fetches updated quote data
- `plan_id` field updated in database
- Plan details stored in quote record

### Database Fields

**Table**: `personal_quotes`  
**Field**: `plan_id` - References selected plan

**Related Tables**:

- `insurance_provider_plans` - Plan master data
- `personal_quote_details` - Quote detail records

## Selection UI

### Select Button

**Location**: Action column in plans table

**Component**: Button in DataTable action slot

**Action**: Calls `handlePlanSelected(plan)` with plan object

### Selected Plan Display

**Location**: Quote show page header/summary section

**Displays**:

- Selected plan name
- Provider name
- Premium amount

## Plan Data Refresh

### After Selection

**Process**:

1. Plan selected via `handlePlanSelected()`
2. Router reloads quote data
3. `onLoadAvailablePlansData()` refreshes plans table
4. UI updates with selected plan information

### Refresh Method

**Location**: `resources/js/inertia/Pages/CyberQuote/Show.vue:369-420`

**Method**: `onLoadAvailablePlansData()`

**Triggers**:

- Initial page load
- After plan selection
- Manual refresh

## Plan Selection Validation

### Pre-selection Checks

- Plan must exist in available plans
- Plan must not be disabled (`isDisabled: false`)
- Quote must be in valid status

### Selection State

**Current Selection**:

- Stored in `selectedProviderPlan` ref
- Initialized from `page.props.quote.plan_id`
- Updated on selection

## Related Files

- **Selection Handler**: `resources/js/inertia/Pages/CyberQuote/Show.vue:356-367`
- **Plan State**: `resources/js/inertia/Pages/CyberQuote/Show.vue:349-354`
- **Data Refresh**: `resources/js/inertia/Pages/CyberQuote/Show.vue:369-420`
- **Frontend Component**: `resources/js/inertia/Pages/CyberQuote/Show.vue`

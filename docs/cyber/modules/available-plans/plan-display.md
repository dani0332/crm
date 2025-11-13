# Plan Display Documentation

## Overview

This document explains how Cyber Insurance plans are displayed in the frontend, including the table structure, plan details modal, and user interactions.

## Frontend Component

### Location

**File**: `resources/js/inertia/Pages/CyberQuote/Show.vue`  
**Section**: Available Plans table (lines 299-1479)

### Component Structure

```vue
<template>
  <!-- Available Plans Section -->
  <DataTable
    :headers="availablePlansTable.columns"
    :items="availablePlansTable.data"
    :isLoading="availablePlansTable.isLoading"
  >
    <!-- Table columns -->
  </DataTable>

  <!-- Plan Details Modal -->
  <x-modal v-model="modals.planDetails">
    <TabGroup>
      <!-- Plan Details Tab -->
      <!-- Included Benefits Tab -->
      <!-- Policy Wordings Tab -->
    </TabGroup>
  </x-modal>
</template>
```

## Table Configuration

### Table Columns

**Location**: `resources/js/inertia/Pages/CyberQuote/Show.vue:299-336`

**Columns Defined**:

```javascript
const availablePlansTable = reactive({
  data: [],
  isLoading: false,
  columns: [
    { text: 'Provider Name', value: 'providerName' },
    { text: 'Plans', value: 'name' },
    { text: 'Coverage Up to', value: 'coverageUpTo' },
    { text: 'Quote Number', value: 'quoteNumber' },
    { text: 'Price without VAT', value: 'priceWithoutVat' },
    { text: 'VAT', value: 'vat' },
    { text: 'Price (with VAT)', value: 'priceWithVat' },
    { text: 'Action', value: 'action' },
  ],
});
```

### Column Details

1. **Provider Name** (`providerName`)

   - Insurance provider name
   - Displayed with diamond icon indicator

2. **Plans** (`name`)

   - Plan name/title

3. **Coverage Up to** (`coverageUpTo`)

   - Maximum coverage amount
   - Formatted from `coverage` field

4. **Quote Number** (`quoteNumber`)

   - Insurer quote reference number
   - Mapped from `insurerQuoteNo`

5. **Price without VAT** (`priceWithoutVat`)

   - Base premium before VAT
   - Mapped from `discountPremium`

6. **VAT** (`vat`)

   - VAT amount (5%)
   - Displayed as separate column

7. **Price (with VAT)** (`priceWithVat`)

   - Total price including VAT
   - Calculated: `discountPremium + vat`

8. **Action** (`action`)
   - View plan details button
   - Opens plan details modal

## Plan Data Structure

### Plan Object Properties

**Source**: API response from KEN API

**Properties**:

```javascript
{
  id: 123,                              // Plan ID
  name: "Cyber Shield Plan",            // Plan name
  providerName: "ABC Insurance",         // Provider name
  coverage: "1000000",                  // Coverage amount (raw)
  coverageUpTo: "1,000,000",            // Formatted coverage
  insurerQuoteNo: "QUOTE-12345",        // Quote number
  quoteNumber: "QUOTE-12345",           // Mapped quote number
  discountPremium: "500.00",            // Base premium
  priceWithoutVat: "500.00",            // Mapped base premium
  vat: "25.00",                         // VAT amount
  priceWithVat: "525.00",               // Calculated total
  isManualUpdate: false,                // Manual update flag
  isDisabled: false,                   // Disabled flag
  benefits: {                           // Benefits object
    INCLUSION: [
      { code: "BEN001", text: "Data Breach Coverage" }
    ]
  },
  policyWordings: [                     // Policy documents
    { id: 1, text: "Policy Document.pdf" }
  ]
}
```

## Data Loading

### Loading State

**Location**: `resources/js/inertia/Pages/CyberQuote/Show.vue:369-420`

**Process**:

1. Set `isLoading = true`
2. Show loading spinner
3. Make API call
4. Process response
5. Set `isLoading = false`

**Loading Indicator**:

```vue
<div v-if="availablePlansTable.isLoading" class="flex justify-center my-8">
  <x-spinner size="lg" />
</div>
```

### Data Processing

**Response Handling**:

```javascript
if (typeof res.data === 'string') {
  // Error message from API
  availablePlansTable.data = res.data;
} else if (res.data?.quotes?.plans && Array.isArray(res.data.quotes.plans)) {
  // Success: Plans array in quotes.plans
  availablePlansTable.data = res.data.quotes.plans.map(plan => ({
    ...plan,
    coverageUpTo: plan.coverage ?? '-',
    quoteNumber: plan.insurerQuoteNo ?? '-',
    priceWithoutVat: plan.discountPremium ?? '0',
    vat: plan.vat ?? '0',
    priceWithVat: (
      parseFloat(plan.discountPremium ?? 0) + parseFloat(plan.vat ?? 0)
    ).toFixed(2),
  }));
} else if (Array.isArray(res.data) && res.data.length > 0) {
  // Success: Direct plans array
  availablePlansTable.data = res.data.map(plan => ({
    // Same mapping
  }));
} else {
  // No plans available
  availablePlansTable.data = [];
}
```

## Plan Details Modal

### Modal Structure

**Location**: `resources/js/inertia/Pages/CyberQuote/Show.vue:1318-1479`

**Tabs**:

1. **Plan Details** (index 0)
2. **Included Benefits** (index 1)
3. **Policy Wordings** (index 2)

### Plan Details Tab

**Displays**:

- Plan Name
- Provider Name
- Premium
- Coverage Up to

**Code Reference**: `resources/js/inertia/Pages/CyberQuote/Show.vue:1351-1396`

### Included Benefits Tab

**Displays**: List of included benefits from `benefits.INCLUSION`

**Structure**:

```javascript
planDetails.benefits.INCLUSION = [
  { code: 'BEN001', text: 'Data Breach Coverage' },
  { code: 'BEN002', text: 'Ransomware Protection' },
];
```

**Display**: Each benefit shown with checkmark icon

**Code Reference**: `resources/js/inertia/Pages/CyberQuote/Show.vue:1398-1439`

### Policy Wordings Tab

**Displays**: List of policy documents from `policyWordings`

**Structure**:

```javascript
planDetails.policyWordings = [{ id: 1, text: 'Policy Document.pdf' }];
```

**Display**: Document links with PDF icon

**Code Reference**: `resources/js/inertia/Pages/CyberQuote/Show.vue:1441-1479`

## Plan Details Retrieval

### Method

**Location**: `resources/js/inertia/Pages/CyberQuote/Show.vue:422-457`

**Logic**:

```javascript
const getPlanDetails = id => {
  viewButtonLoading.value = true;
  try {
    // Try to find plan in current table data
    const foundPlan = availablePlansTable.data.find(plan => plan.id === id);
    if (foundPlan) {
      planDetails.value = foundPlan;
      modals.planDetails = true;
      viewButtonLoading.value = false;
    } else {
      // Fetch from API if not in table
      // NOTE: This route does NOT exist in routes/web.php - This is a bug in the code
      // Cyber plan_details route is missing. Other LOBs have routes like:
      // - car/{quoteId}/plan_details/{planId}
      // - health/{quoteId}/plan_details/{planId}
      // - travel/{quoteId}/plan_details/{planId}
      // But Cyber does not have this route defined.
      axios
        .get(`/cyber/${page.props.quote.uuid}/plan_details/${id}`)
        .then(res => {
          planDetails.value = res.data;
          modals.planDetails = true;
          viewButtonLoading.value = false;
        })
        .catch(err => {
          notification.error({
            title: 'Error',
            message: 'Plan Details Not Found',
          });
          viewButtonLoading.value = false;
        });
    }
  } catch (err) {
    notification.error({
      title: 'Error',
      message: 'Something went wrong',
    });
    viewButtonLoading.value = false;
  }
};
```

## Price Calculation

### VAT Calculation

**VAT Rate**: 5% (standard UAE VAT)

**Calculation**:

```javascript
priceWithVat = (
  parseFloat(discountPremium ?? 0) + parseFloat(vat ?? 0)
).toFixed(2);
```

**Example**:

- Base Premium: 500.00
- VAT: 25.00
- Total: 525.00

### Formatting

**Coverage Formatting**:

```javascript
coverageUpTo: plan.coverage ?? '-';
// Displays: "1,000,000" or "-" if not available
```

**Price Formatting**:

```javascript
priceWithoutVat: plan.discountPremium ?? '0';
vat: plan.vat ?? '0';
priceWithVat: (
  parseFloat(plan.discountPremium ?? 0) + parseFloat(plan.vat ?? 0)
).toFixed(2);
```

## Table Features

### Pagination

- **Rows per page**: 15
- **Hide footer**: When less than 15 plans
- **Component**: `DataTable` from `vue3-easy-data-table`

### Selection

- **Selected Plans**: `selectedPlans` ref (currently empty array)
- **Selection Mode**: Multiple selection supported

### Loading States

- **Table Loading**: `availablePlansTable.isLoading`
- **Button Loading**: `viewButtonLoading` for plan details button

## Error Handling

### API Errors

**Error Display**:

- If API returns error message string → Displayed in table
- If API fails → Empty array, error logged to console

**User Feedback**:

- Loading spinner during API call
- Error notification if plan details not found
- Empty state if no plans available

## Related Files

- **Frontend Component**: `resources/js/inertia/Pages/CyberQuote/Show.vue`
- **Data Loading**: `resources/js/inertia/Pages/CyberQuote/Show.vue:369-420`
- **Plan Details**: `resources/js/inertia/Pages/CyberQuote/Show.vue:422-457`
- **Table Configuration**: `resources/js/inertia/Pages/CyberQuote/Show.vue:299-336`

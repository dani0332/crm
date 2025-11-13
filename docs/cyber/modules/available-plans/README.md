# Available Plans Module

## Overview

The Available Plans module displays and manages Cyber Insurance plans retrieved from the KEN API. It provides advisors with a comprehensive view of available insurance plans, their pricing, coverage details, and allows plan selection for quotes.

## Purpose

- **Plan Display**: Show all available Cyber Insurance plans for a quote
- **Plan Details**: Display detailed information including benefits and policy wordings
- **Plan Selection**: Allow advisors to select plans for quotes
- **Price Calculation**: Display pricing with and without VAT
- **Coverage Information**: Show coverage limits and details

## Key Features

- **Real-time Plan Fetching**: Fetches plans from KEN API endpoint
- **Plan Details Modal**: View plan details, benefits, and policy wordings
- **Price Display**: Shows price without VAT, VAT amount, and total price
- **Plan Selection**: Select plans for quotes
- **Loading States**: Shows loading indicators during API calls
- **Error Handling**: Graceful error handling for API failures

## Components

### Frontend Component

- **File**: `resources/js/inertia/Pages/CyberQuote/Show.vue`
- **Section**: Available Plans table and modal
- **Framework**: Vue.js 3.5 with Composition API

### Backend Service

- **File**: `app/Services/Quotes/CyberQuoteService.php`
- **Methods**: `getAvailablePlans()`, `listQuotePlans()`, `getQuotePlans()`

### API Endpoint

- **Controller**: `app/Http/Controllers/V2/CentralController.php`
- **Method**: `loadAvailablePlans()`
- **Route**: `POST /quotes/cyber/available-plans/{uuid}`
- **Route Definition**: `POST /{quoteType}/available-plans/{id}` (where quoteType = 'cyber')
- **Code Reference**: `routes/web.php:625`

## Plan Data Structure

### Plan Object

```javascript
{
  id: 123,
  name: "Cyber Shield Plan",
  providerName: "ABC Insurance",
  coverage: "1000000",
  coverageUpTo: "1,000,000",
  quoteNumber: "QUOTE-12345",
  discountPremium: "500.00",
  priceWithoutVat: "500.00",
  vat: "25.00",
  priceWithVat: "525.00",
  isManualUpdate: false,
  isDisabled: false,
  benefits: {
    INCLUSION: [
      { code: "BEN001", text: "Data Breach Coverage" }
    ]
  },
  policyWordings: [
    { id: 1, text: "Policy Document.pdf" }
  ]
}
```

## Plan Display

### Table Columns

1. **Provider Name**: Insurance provider name
2. **Plans**: Plan name
3. **Coverage Up to**: Maximum coverage amount
4. **Quote Number**: Insurer quote reference number
5. **Price without VAT**: Base premium
6. **VAT**: VAT amount (5%)
7. **Price (with VAT)**: Total price including VAT
8. **Action**: View plan details button

### Plan Details Modal

Three tabs:

1. **Plan Details**: Basic plan information
2. **Included Benefits**: List of covered benefits
3. **Policy Wordings**: Policy documents

## Related Files

### Backend

- `app/Services/Quotes/CyberQuoteService.php` - Plan fetching logic
- `app/Http/Controllers/V2/CentralController.php` - API endpoint
- `app/Services/CentralService.php` - Central service routing

### Frontend

- `resources/js/inertia/Pages/CyberQuote/Show.vue` - Plan display component

### API Integration

- KEN API endpoint: `/cyber/get-quote-plans`
- Configuration: `config/constants.php`

## Next Steps

For detailed information:

- [Plan Fetching](./plan-fetching.md) - How plans are retrieved from API
- [Plan Display](./plan-display.md) - Frontend display logic
- [Plan Selection](./plan-selection.md) - Plan selection process
- [API Integration](./api-integration.md) - KEN API integration details

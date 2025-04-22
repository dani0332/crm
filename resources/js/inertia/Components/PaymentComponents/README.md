# Payment Components

This directory contains a collection of Vue components used for managing payments in the application. These components are modular parts of what used to be one large component (`PaymentTableNew.vue`) and have been split for better maintainability and code organization.

## Components Overview

### PaymentTable.vue
The main container component that orchestrates all other payment components. It handles the data flow and main functionality for displaying and managing payments.

### PaymentHeader.vue
Displays the header section of the payment management interface with action buttons:
- **Add Manual Payment** - Button to add a new manual payment (available when permissions allow)
- **Download Proforma Payment Request** - Button to download proforma payment document
- **Update Total Price** - Component for updating the total price of a payment (visible when specific permissions are present)

### PaymentTableHeader.vue
Renders the table header with column names and tooltips for the payment management table.

### PaymentRow.vue
Dedicated component for rendering a payment row in the payment table. Features:
- Expandable row to show/hide payment splits
- Payment details display
- Action buttons for Edit, Delete, Capture, Approve, and Void operations
- Status indicators with appropriate styling
- Permission-based button display

### PaymentSplitRow.vue
Dedicated component for rendering a payment split row that appears when a payment is expanded. Features:
- Detailed view of individual payment splits
- Action buttons for View, Copy Payment Link, Delete, Retry, and Post operations
- Displays appropriate data for split payments
- Handles specific split payment operations

### DocumentGallery.vue
A modal component for viewing uploaded payment documents with features like:
- Navigation between multiple documents
- Zoom controls for images
- Support for PDF and image display
- Download options

### ConfirmationModal.vue
A reusable modal component for confirming actions such as:
- Voiding payments
- Deleting payments
- Retrying payment processes

## Usage

Import components individually or use the index.js barrel file:

```javascript
// Import individual components
import PaymentTable from './PaymentComponents/PaymentTable.vue';

// Or import using the barrel file
import { PaymentTable, PaymentHeader, PaymentRow, PaymentSplitRow } from './PaymentComponents';
```

The main entry point is the `PaymentTable` component which accepts properties for payments data and event handlers for payment actions.

## Main Actions

1. **Add Manual Payment**: Adds a new manual payment entry
2. **Download Proforma Payment**: Downloads the proforma payment request document
3. **Update Total Price**: Updates the total price of a payment (requires specific permissions)

## PaymentRow and PaymentSplitRow Implementation

The PaymentRow and PaymentSplitRow components separate the display logic for payments and their splits, which previously were combined in a single template:

- **PaymentRow**: Handles the main payment display with expand/collapse functionality
- **PaymentSplitRow**: Shows individual payment splits when a payment is expanded

This separation allows for cleaner code and better organization, as each component focuses on a specific part of the UI.

## Payment Status Management

The components support various payment statuses:
- Pending
- Paid
- Authorized
- Declined
- Void
- Credit Approved
- Partially Paid
- Allocated (to Sage)

## Requirements

These components depend on:
- Vue 3 with Composition API
- TailwindCSS for styling
- Axios for API requests
- Moment.js for date formatting 
# Cyber Insurance - Payments and Documents Implementation Guide

This document outlines the implementation steps required to enable payments and document management for Cyber Insurance quotes.

---

## Documents Setup

### Overview
To ensure the correct document types are displayed and processed for Cyber Insurance payments, the following setup steps are required.

### Implementation Steps

#### Step 1: Azure Directory Setup
- Create a directory in Azure with the name `cyber`
- This directory will be used to store Cyber Insurance-related documents

#### Step 2: Database Seeder Configuration
Create the following document types in the seeder:
- **Receipt** - Required for payment processing
- **Payment Proof** - Required for payment verification
- **Discount Proof** - Required for discount validation
- **Additional document types** - As specified in the FRD (Functional Requirements Document) by Fahad

#### Step 3: Document Type Configuration
After completing steps 1 and 2, configure the following to ensure documents are correctly associated and displayed:
- Adjust **quote type ID** to match Cyber Insurance
- Configure **folder structure** to point to the Azure `cyber` directory
- Update any other required data fields so documents are correctly picked up and displayed based on quote type

---

## Payments Setup

### Overview
The payment functionality for Cyber Insurance follows a similar pattern to Travel Insurance, with some specific configuration requirements.

### Implementation Steps

#### Step 1: Initial Price Source Identification
- Identify the source field for **Initial Price**
- Determine if it should be pulled from:
  - Premium field
  - Other relevant fields in the quote/plan data
- Document the selected field for future reference

#### Step 2: Provider Name and Plan Configuration
- Fix provider name and plan display based on relationship mapping
- Identify the data source for:
  - **Plan name** - Where it can be retrieved from (e.g., plan table, quote relationship)
  - **Insurer provider name** - Where it can be retrieved from (e.g., provider table, insurance company relationship)
- Ensure proper relationships are established to fetch this data correctly

#### Step 3: Approve & Capture Logic Adjustment
- Modify the approve and capture logic to treat Cyber payments similar to Travel Insurance
- **Key requirement**: No AML (Anti-Money Laundering) and KYC (Know Your Customer) checks are necessary for Cyber Insurance payments
- Update validation rules accordingly

#### Step 4: Broker Commission Setup (BA Task)
- Business Analyst (BA) will configure broker commission settings from CMS
- This configuration will enable **Credit Card** payment method availability for Cyber Insurance payments
- No developer action required for this step

#### Step 5: Existing Payment Processes
The following payment processes remain unchanged and will work as-is:
- Add payment
- Payment frequency selection
- Collection type selection
- Payment methods configuration
- Approve payment
- Capture payment
- Void payment

All these processes use existing functionality and require no modifications.

---

## Summary

**To make payments functional for Cyber Insurance:**
- Complete steps 1-4 as outlined above
- Steps 1-3 require developer implementation
- Step 4 is a BA/Configuration task
- Step 5 uses existing processes and requires no changes

**Note:** Additional details will be added if there are any changes in the FRD (Functional Requirements Document).

---

## Additional Notes

- This implementation follows patterns established in Travel Insurance payment flows
- All existing payment infrastructure can be reused
- Only configuration and minor logic adjustments are needed for Cyber Insurance-specific requirements

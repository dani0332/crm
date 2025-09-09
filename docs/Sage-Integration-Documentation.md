# Sage Integration Documentation

## Overview

The Sage Integration Module is a comprehensive financial system integration that connects the insurance platform with Sage 300 ERP system. It handles policy booking, invoice generation, payment processing, and financial data synchronization across multiple lines of business including Car/Motor, Health, Life, and Travel insurance.

## Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [Core Components](#core-components)
3. [Integration Workflow](#integration-workflow)
4. [API Endpoints & Services](#api-endpoints--services)
5. [Database Schema](#database-schema)
6. [Job Queue System](#job-queue-system)
7. [Configuration & Environment](#configuration--environment)
8. [Error Handling & Logging](#error-handling--logging)
9. [Development Guidelines](#development-guidelines)

## Architecture Overview

The Sage Integration follows a layered architecture pattern with asynchronous processing:

```
┌─────────────────────────────────────────┐
│           Frontend Layer                │
│    (Quote Management, Payment UI)       │
└─────────────────┬───────────────────────┘
                  │
┌─────────────────┴───────────────────────┐
│         Controller Layer                │
│      (SageApi, CentralController)       │
└─────────────────┬───────────────────────┘
                  │
┌─────────────────┴───────────────────────┐
│          Service Layer                  │
│   (SageApiService, SageCustomApiService) │
└─────────────────┬───────────────────────┘
                  │
┌─────────────────┴───────────────────────┐
│         Factory Layer                   │
│       (SagePayloadFactory)              │
└─────────────────┬───────────────────────┘
                  │
┌─────────────────┴───────────────────────┐
│          Job Layer                      │
│    (BookPolicyOnSageJob, etc.)          │
└─────────────────┬───────────────────────┘
                  │
┌─────────────────┴───────────────────────┐
│         Model Layer                     │
│  (SageProcess, SageApiLog)              │
└─────────────────┬───────────────────────┘
                  │
┌─────────────────┴───────────────────────┐
│        External Systems                 │
│         (Sage 300 ERP)                  │
└─────────────────────────────────────────┘
```

## Core Components

### 1. Services

#### `SageApiService`

- **Location**: `app/Services/SageApiService.php`
- **Purpose**: Main service orchestrating comprehensive Sage 300 integration
- **Key Features**:
  - Integrated policy booking with complete financial processing
  - Simultaneous AR/AP invoice creation and payment receipt generation
  - Automatic invoice-to-payment mapping during policy booking
  - Embedded product integration within policy booking flow
  - Split payment and installment plan handling
  - Comprehensive error handling and retry logic
  - Multi-step process coordination triggered by single policy booking action

#### `SageCustomApiService`

- **Location**: `app/Services/SageCustomApiService.php`
- **Purpose**: Custom Sage API operations and authentication
- **Key Features**:
  - Token-based authentication
  - Payment schedule management
  - Custom endpoint handling
  - Redis caching for tokens

#### `SageApiEmbeddedProductService`

- **Location**: `app/Services/SageApiEmbeddedProductService.php`
- **Purpose**: Handles embedded product integration with Sage
- **Key Features**:
  - Embedded product booking
  - Product-specific payload generation
  - Transaction status management

### 2. Models

#### `SageProcess`

- **Location**: `app/Models/SageProcess.php`
- **Purpose**: Tracks Sage integration processes and their status
- **Key Features**:
  - Process status management (pending, processing, completed, failed)
  - Morphic relationship to various models
  - Insurance provider association
  - Request/response logging

#### `SageApiLog`

- **Location**: `app/Models/SageApiLog.php`
- **Purpose**: Comprehensive logging of all Sage API interactions
- **Key Features**:
  - Step-by-step process tracking
  - Request/response payload storage
  - User action tracking
  - Morphic relationships for flexible logging

### 3. Controllers

#### `SageApi`

- **Location**: `app/Http/Controllers/SageApi.php`
- **Purpose**: HTTP controller for Sage API operations
- **Key Features**:
  - Request processing coordination
  - Response handling and formatting
  - API log retrieval
  - Error message processing

### 4. Factory Classes

#### `SagePayloadFactory`

- **Location**: `app/Factories/SagePayloadFactory.php`
- **Purpose**: Generates Sage-compatible payloads for various operations
- **Key Features**:
  - AR Invoice payload generation
  - AP Invoice payload generation
  - Payment receipt payload creation
  - Customer creation payloads
  - Split payment handling
  - Reversal and correction logic

### 5. Enums

#### `SageEnum`

- **Location**: `app/Enums/SageEnum.php`
- **Purpose**: Constants and enums for Sage integration
- **Key Features**:
  - Process status constants
  - Request type definitions
  - API endpoint constants
  - Error code definitions
  - Bank and payment codes

### 6. Traits

#### `SageLoggable`

- **Location**: `app/Traits/SageLoggable.php`
- **Purpose**: Provides logging functionality for Sage operations
- **Key Features**:
  - Standardized API call logging
  - Request/response tracking
  - User action logging
  - Error handling

## Integration Workflow

### 1. Policy Booking & Financial Integration Workflow

```
Policy Ready → Book Policy Button Click → Comprehensive Sage Integration → Status Update & Confirmation
```

**Process Steps**:

1. **Policy Preparation**: User completes policy details and payment information
2. **Book Policy Action**: User clicks "Book and send policy/book policy" button
3. **Integrated Processing**: System processes in sequence:
   - Policy booking in Sage 300
   - Payment receipt generation (for paid payments only)
   - Invoice creation (AR Premium & Commission invoices)
   - Invoice-to-payment receipt mapping
   - Embedded product booking (if applicable)
   - All financial transactions synchronization
4. **Queue Processing**: `BookPolicyOnSageJob` orchestrates all Sage operations
5. **Status Updates**: System updates policy status and financial records
6. **Confirmation**: User receives confirmation of complete booking

### 2. Comprehensive Financial Processing (Triggered by Policy Booking)

```
Book Policy Click → Payment Receipt Generation → Invoice Creation → Receipt-to-Invoice Mapping → Financial Synchronization
```

**Integrated Process Steps**:

1. **Payment Receipt Processing** (First Step):
   - Payment receipts created ONLY for paid payments
   - Split payment receipts for installment plans (paid installments only)
   - Prepayment receipts for advance payments that are already processed
   - No receipts generated for pending or unpaid amounts
2. **Invoice Creation** (Second Step):
   - AR Premium invoices generated after receipt creation
   - AR Commission invoices created for insurers
   - AP invoices for vendor payments (if applicable)
3. **Financial Mapping** (Third Step):
   - Automatic mapping of existing payment receipts to newly created invoices
   - Payment application to outstanding balances
   - Discount application and adjustment processing
4. **Sage Synchronization**:
   - All financial data synchronized with Sage 300
   - Batch processing for efficient data transfer
   - Real-time status updates throughout process

### 3. Embedded Product Workflow (Part of Policy Booking)

```
Product Selection → Policy Booking Click → Integrated EP Processing → Status Update
```

**Process Steps**:

1. **Product Selection**: User selects embedded products during policy creation
2. **Integrated Booking**: When "Book Policy" is clicked, embedded products are processed alongside policy
3. **EP Processing**: `BookEmbeddedProductOnSageJob` handles:
   - Embedded product booking in Sage 300
   - EP-specific invoice generation
   - Payment allocation for embedded products
   - Integration with main policy booking process
4. **Status Synchronization**: Embedded product status updated with policy booking status

## API Endpoints & Services

### Primary Sage 300 Endpoints

#### AR (Accounts Receivable) Endpoints

- **`AR/ARCustomers`** - Customer management
- **`AR/ARInvoiceBatches`** - Invoice batch processing
- **`AR/ARReceiptAndAdjustmentBatches`** - Receipt and adjustment processing
- **`AR/ARPostInvoices`** - Invoice posting
- **`AR/ARPostReceiptsAndAdjustments`** - Receipt posting

#### AP (Accounts Payable) Endpoints

- **`AP/APInvoiceBatches`** - Vendor invoice processing
- **`AP/APPaymentAndAdjustmentBatches`** - Payment batch processing
- **`AP/APPostInvoices`** - Invoice posting
- **`AP/APPostPaymentsAndAdjustments`** - Payment posting

### Custom API Endpoints

#### Authentication & Management

- **`/api/User/Login`** - Authentication token generation
- **`/api/APBatch/GetInvoiceBatchWise/`** - Payment schedule retrieval
- **`/api/APBatch/`** - Payment schedule updates

## Database Schema

### Primary Tables

#### `sage_processes` Table

- **Purpose**: Tracks Sage integration processes
- **Key Fields**:
  - `user_id` - User initiating the process
  - `insurance_provider_id` - Associated insurance provider
  - `model_type` - Polymorphic model type
  - `model_id` - Polymorphic model ID
  - `request` - Request type (book_policy, send_update, etc.)
  - `message` - Process message/result
  - `status` - Process status (pending, processing, completed, failed)

#### `sage_api_logs` Table

- **Purpose**: Comprehensive API interaction logging
- **Key Fields**:
  - `user_id` - User performing the action
  - `section_id` - Section identifier (polymorphic)
  - `section_type` - Section type (polymorphic)
  - `model_id` - Model identifier (polymorphic)
  - `model_type` - Model type (polymorphic)
  - `step` - Current step in process
  - `total_steps` - Total steps in process
  - `sage_request_type` - Type of Sage request
  - `sage_end_point` - Sage API endpoint used
  - `sage_payload` - Request payload (JSON)
  - `response` - API response (JSON)
  - `status` - Request status (success, fail)
  - `entry_type` - Entry type (straight, reversal, correction)

### Relationships

#### Process Relationships

- `SageProcess` → `morphTo()` relationship with various models
- `SageProcess` → `belongsTo(InsuranceProvider::class)`
- `SageApiLog` → `morphTo()` relationships for section and model
- `SageApiLog` → `belongsTo(User::class)`

## Job Queue System

### Background Jobs

#### `BookPolicyOnSageJob`

- **Purpose**: Orchestrates complete policy booking and financial integration with Sage 300
- **Features**:
  - Comprehensive processing triggered by single "Book Policy" action
  - Simultaneous policy booking, invoice creation, and payment processing
  - Automatic invoice-to-payment mapping and financial synchronization
  - Embedded product booking integration
  - Asynchronous processing with 80-second timeout
  - Retry logic with 1 attempt and overlapping prevention
  - Multi-step error handling and recovery

#### `BookEmbeddedProductOnSageJob`

- **Purpose**: Books embedded products as part of integrated policy booking process
- **Features**:
  - Triggered during main policy booking flow
  - Product-specific invoice and payment processing
  - Integration with main policy financial transactions
  - Status synchronization with policy booking status

#### `PostPrepaymentToSageJob`

- **Purpose**: Processes prepayments as part of comprehensive booking flow
- **Features**:
  - Integrated with policy booking process
  - Automatic prepayment receipt generation
  - Invoice mapping and application
  - Financial synchronization with Sage 300

#### `SendUpdateSageJob`

- **Purpose**: Handles policy endorsements and updates with complete financial processing
- **Features**:
  - Endorsement processing with automatic invoice adjustments
  - Correction and reversal handling
  - Complete financial transaction updates
  - Change tracking and audit trail

### Queue Configuration

- **Queue Name**: Default Laravel queue
- **Retry Logic**: Configurable per job type
- **Timeout**: Job-specific timeouts (80s for policy booking)
- **Overlapping Prevention**: WithoutOverlapping middleware

## Configuration & Environment

### Environment Variables

#### Sage 300 Configuration

```env
SAGE_300_BASE_URL=https://sage-api.company.com
SAGE_300_API_DATE_FORMAT=Y-m-d
SAGE_300_CUSTOM_API_USERNAME=api_user
SAGE_300_CUSTOM_API_USER_PASSWORD=api_password
SAGE_300_CUSTOM_API_VERSION=v1
SAGE_300_CUSTOM_API_DB_NAME=company_db
```

#### Integration Settings

```env
SAGE_ENABLED=true
SAGE_PROCESS_TIMEOUT=300
SAGE_RETRY_ATTEMPTS=3
```

### Configuration Files

- **Sage Settings**: Managed through application storage
- **Payment Gateway Config**: Insurance provider specific
- **API Timeouts**: Configurable per operation type

## Error Handling & Logging

### Error Handling Strategy

#### API Error Handling

- **Connection Errors**: Retry with exponential backoff
- **Authentication Errors**: Token refresh and retry
- **Validation Errors**: Log and notify user
- **Timeout Errors**: Queue for retry with increased timeout

#### Business Logic Errors

- **Duplicate Records**: Handle with appropriate messaging
- **Invalid Data**: Comprehensive validation before API calls
- **Status Conflicts**: Check current status before processing

### Logging Implementation

#### Comprehensive Logging

- **Request/Response Logging**: All API interactions logged
- **Step-by-Step Tracking**: Multi-step processes tracked individually
- **User Action Logging**: All user-initiated actions logged
- **Error Context**: Detailed error context and stack traces

#### Log Categories

- **API Calls**: `SageApiLog` model for structured logging
- **Process Tracking**: `SageProcess` model for workflow status
- **Application Logs**: Laravel log files for system events
- **Error Logs**: Dedicated error logging with context

### Monitoring & Alerts

#### Process Monitoring

- **Queue Health**: Monitor job queue performance
- **API Availability**: Check Sage 300 system availability
- **Error Rates**: Track error rates and patterns
- **Performance Metrics**: Monitor API response times

## Development Guidelines

### Integration Best Practices

#### Comprehensive Policy Booking Integration

1. **Single Action Triggers All Processing** - Policy booking button initiates complete financial integration
2. **Atomic Transaction Handling** - All invoices, receipts, and mappings processed together
3. **Always use payload factory** for request generation across all financial transactions
4. **Implement comprehensive error handling** - Handle failures across all integrated processes
5. **Use coordinated asynchronous processing** - Orchestrate multiple Sage operations efficiently
6. **Log complete transaction flows** - Track all steps from policy booking through financial completion
7. **Validate entire financial picture** - Ensure all payments, invoices, and mappings are consistent
8. **Handle complex timeout scenarios** - Manage timeouts across multiple integrated operations

#### Data Management

1. **Use polymorphic relationships** for flexible model associations
2. **Implement proper status tracking** throughout the process
3. **Store original payloads** for debugging and reprocessing
4. **Use enum constants** for consistent status and type management
5. **Maintain data integrity** across all operations

#### Security Considerations

1. **Secure API credentials** using environment variables
2. **Implement proper authentication** with token management
3. **Validate all inputs** before processing
4. **Use HTTPS** for all API communications
5. **Log security events** for audit purposes

### Policy Booking Operations

#### Standard Policy Booking

**Trigger**: "Book Policy" button click
**Process Flow**:

1. **Policy Validation**: Validate policy details and customer information
2. **Payment Status Assessment**: Identify which payments have been completed/paid
3. **Receipt Creation** (First Financial Step): Generate payment receipts ONLY for paid payments
   - Create receipts for completed credit card transactions
   - Generate receipts for received bank transfers
   - Create receipts for cash payments already collected
   - Skip receipt generation for pending or unpaid amounts
4. **Invoice Generation** (Second Financial Step):
   - Create AR Premium invoices for customer billing
   - Generate AR Commission invoices for insurer payments
   - Create AP invoices for vendor payments (if applicable)
5. **Financial Mapping** (Third Financial Step): Map existing payment receipts to newly created invoices
6. **Sage Integration**: Synchronize all financial data with Sage 300 system
7. **Status Updates**: Update policy status to "Policy Booked"
8. **Confirmation**: Send confirmation to user and relevant parties

#### Send Update Booking (Endorsements)

**Trigger**: "Send Update" or "Book Endorsement" button click
**Process Flow**:

1. **Change Analysis**: Identify policy changes and calculate financial impact
2. **Endorsement Processing**:
   - Create endorsement records with change details
   - Calculate premium adjustments (increases/decreases)
   - Determine commission adjustments
3. **Financial Transaction Handling**:
   - Generate adjustment invoices (Credit/Debit notes)
   - Create correction entries for original transactions
   - Process additional payments or refunds as needed
4. **Sage Synchronization**:
   - Send reversal entries for original transactions (if required)
   - Create new corrected transactions
   - Update customer and vendor accounts
5. **Status Management**: Update policy and endorsement status
6. **Documentation**: Generate endorsement certificates and updated policy documents

#### Booking Process Variations

##### Upfront Payment Booking

- **Scenario**: Customer pays full premium upfront
- **Process**: Payment receipt created first (for paid amount), then premium invoice generated, then mapped
- **Sage Operations**: Receipt creation → AR invoice creation → immediate payment application

##### Split Payment Booking

- **Scenario**: Customer chooses installment payment plan
- **Process**: Receipts created only for paid installments, then invoices generated with payment schedules, then mapping
- **Sage Operations**: Paid installment receipts → AR invoices with full payment schedules → progressive receipt mapping

##### Insurer Direct Payment Booking

- **Scenario**: Insurer pays premium directly
- **Process**: AP prepayment processing with customer invoice generation
- **Sage Operations**: AP payment receipts and AR invoice generation with automatic mapping

##### Embedded Product Booking

- **Scenario**: Policy includes embedded products (extended warranties, etc.)
- **Process**: Additional product invoicing and payment allocation
- **Sage Operations**: Separate EP invoices integrated with main policy booking

### Troubleshooting Guide

#### Common Issues

#### API Connection Issues

- **Symptom**: Connection timeout or refused
- **Solution**: Check network connectivity and API endpoint configuration
- **Debug**: Review API logs and network configuration

#### Authentication Failures

- **Symptom**: Invalid token errors
- **Solution**: Refresh authentication token and retry
- **Debug**: Check credentials and token expiration

#### Data Validation Errors

- **Symptom**: Sage returns validation errors
- **Solution**: Review payload structure and required fields
- **Debug**: Compare payload with Sage documentation

#### Queue Processing Issues

- **Symptom**: Jobs stuck in queue or failing
- **Solution**: Check queue worker status and job configuration
- **Debug**: Review job logs and error messages

### Update Protocol

#### System Updates

1. **Review Integration Points** - Check all Sage API endpoints
2. **Update Documentation** - Keep API documentation current
3. **Test Thoroughly** - Test all integration scenarios
4. **Monitor Performance** - Check system performance after updates
5. **Validate Data Flow** - Ensure data integrity across systems
6. **Update Configuration** - Adjust settings as needed
7. **Train Users** - Update user documentation and training
8. **Monitor Errors** - Watch for new error patterns
9. **Update Logging** - Enhance logging as needed
10. **Review Security** - Ensure security measures are current

### Quick Reference

#### Key Files

- **Main Service**: `app/Services/SageApiService.php`
- **Custom API**: `app/Services/SageCustomApiService.php`
- **Payload Factory**: `app/Factories/SagePayloadFactory.php`
- **Process Model**: `app/Models/SageProcess.php`
- **API Log Model**: `app/Models/SageApiLog.php`
- **Enums**: `app/Enums/SageEnum.php`
- **Jobs**: `app/Jobs/BookPolicyOnSageJob.php`, etc.
- **Command**: `app/Console/Commands/SageProcessesCommand.php`

#### Key Concepts

- **Asynchronous Processing**: All Sage operations use job queues
- **Comprehensive Logging**: Every API interaction is logged
- **Status Tracking**: Process status tracked throughout workflow
- **Error Recovery**: Robust error handling and retry logic
- **Data Integrity**: Maintains consistency across systems
- **Security**: Secure authentication and data transmission

# Digital Broker on Record (BOR) - Technical Documentation

## Table of Contents
1. [Overview](#overview)
2. [Architecture](#architecture)
3. [Database Schema](#database-schema)
4. [API Endpoints](#api-endpoints)
5. [Frontend Components](#frontend-components)
6. [Email Workflows](#email-workflows)
7. [Document Management](#document-management)
8. [Permissions & Security](#permissions--security)
9. [Configuration](#configuration)
10. [Deployment Notes](#deployment-notes)

## Overview

The Digital Broker on Record (BOR) feature enables insurance brokers to digitally initiate, track, and manage broker appointment requests across all Lines of Business (LOBs). The system supports both Individual and Entity customer types with different workflows for document signing and upload.

### Key Features
- **BOR Request Creation**: Dynamic forms based on customer type and LOB
- **Digital Signatures**: Individual customers can sign documents online
- **Document Upload**: Entity customers can upload signed documents
- **Email Automation**: Automated notifications via Bird service
- **Insurer Notifications**: Automatic notifications to insurance providers
- **Status Tracking**: Comprehensive status management throughout the process
- **Permission-based Access**: Role-based access control for document uploads

## Architecture

### Core Components

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Frontend      │    │   Backend       │    │   External      │
│   (Vue.js)      │◄──►│   (Laravel)     │◄──►│   Services      │
└─────────────────┘    └─────────────────┘    └─────────────────┘
│                     │                     │
├─ BorLogsSection     ├─ BorController     ├─ Bird Service
├─ BorRequestForm     ├─ BorService        ├─ Azure Storage
├─ BorLogsList        ├─ BorEmailService   ├─ PDF Generation
└─ Modals             └─ BorPdfService     └─ Email Templates
```

### Technology Stack
- **Backend**: Laravel 10+ with PHP 8.2+
- **Frontend**: Vue.js 3.5+ with Inertia.js
- **Database**: MySQL with polymorphic relationships
- **Storage**: Azure Blob Storage for documents
- **Email**: Bird service for automated workflows
- **PDF**: DomPDF for document generation

## Database Schema

### Primary Tables

#### `bor_logs`
```sql
CREATE TABLE bor_logs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    personal_quote_id BIGINT NOT NULL,
    customer_type ENUM('Individual', 'Entity') NOT NULL,
    policy_number VARCHAR(255) NULL,
    insurer_name VARCHAR(255) NOT NULL,
    company_name VARCHAR(255) NULL,
    insurance_provider_id INT NULL,
    insurance_contact_id INT NULL,
    chassis_number VARCHAR(255) NULL,
    date_created TIMESTAMP NOT NULL,
    status VARCHAR(50) DEFAULT 'SIGNATURE_REQUESTED',
    bor_reference VARCHAR(255) UNIQUE NOT NULL,
    document_id VARCHAR(255) NULL,
    document_path VARCHAR(500) NULL,
    signature_path VARCHAR(500) NULL,
    email_sent BOOLEAN DEFAULT FALSE,
    date_uploaded TIMESTAMP NULL,
    date_signed TIMESTAMP NULL,
    customer_signature_name VARCHAR(255) NULL,
    user_agent TEXT NULL,
    quote_document_id BIGINT NULL,
    cancellation_reason VARCHAR(100) NULL,
    additional_notes TEXT NULL,
    download_clicked BOOLEAN DEFAULT FALSE,
    policy_expiry DATE NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (personal_quote_id) REFERENCES personal_quotes(id),
    FOREIGN KEY (insurance_provider_id) REFERENCES insurance_provider(id),
    FOREIGN KEY (insurance_contact_id) REFERENCES insurance_provider_contacts(id),
    FOREIGN KEY (quote_document_id) REFERENCES quote_documents(id)
);
```

#### `insurance_provider_contacts`
```sql
CREATE TABLE insurance_provider_contacts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    insurance_provider_id INT NOT NULL,
    quote_type_id INT NOT NULL,
    department VARCHAR(255) NULL,
    emails MEDIUMTEXT NOT NULL,
    
    FOREIGN KEY (insurance_provider_id) REFERENCES insurance_provider(id),
    FOREIGN KEY (quote_type_id) REFERENCES quote_type(id)
);
```

### Relationships
- **BorLog** belongs to **PersonalQuote** (polymorphic)
- **BorLog** belongs to **InsuranceProvider**
- **BorLog** belongs to **InsuranceProviderContact**
- **BorLog** belongs to **QuoteDocument** (polymorphic)

## API Endpoints

### V2 Controller (IMCRM Integration)

#### `GET /bor/requests`
**Purpose**: Fetch BOR logs for a specific lead
```php
// Parameters
- leadId: int (required)
- lob: string (required)
- page: int (optional, default: 1)

// Response
{
    "success": true,
    "data": {
        "data": [...], // Paginated BOR logs
        "current_page": 1,
        "last_page": 1,
        "next_page_url": null,
        "prev_page_url": null
    },
    "total": 5,
    "bor_status_enum": {...}
}
```

#### `POST /bor/requests`
**Purpose**: Create new BOR request
```php
// Request Body
{
    "personal_quote_id": 123,
    "lob": "car",
    "customer_type": "Individual",
    "insurer_name": "John Doe",
    "company_name": null,
    "insurance_provider_id": 5,
    "insurance_contact_id": 10,
    "policy_number": "POL123456",
    "policy_expiry": "2024-12-31",
    "chassis_number": "ABC123456789"
}

// Response
{
    "success": "BOR request created successfully and email sent to customer.",
    "newBorLog": {...}
}
```

#### `PUT /bor/requests/{id}`
**Purpose**: Update existing BOR request
```php
// Same request body as POST
// Response includes updatedBorLog
```

#### `POST /bor/requests/{id}/upload`
**Purpose**: Upload BOR document (requires BOR_DOCUMENT_UPLOAD permission)
```php
// Request (multipart/form-data)
- file: File (required, max 10MB)
- document_type_code: string (optional)

// Response
{
    "success": true,
    "message": "BOR document uploaded successfully",
    "borLog": {...},
    "document": {...}
}
```

#### `GET /bor/requests/{id}/generate-link`
**Purpose**: Generate customer-facing BOR link
```php
// Response
{
    "success": true,
    "data": "https://portal.domain.com/car-insurance/quote/uuid/bor/IM-BOR-123456"
}
```

### API V1 Controller (Customer Portal)

#### `GET /api/v1/bor/{borRefId}`
**Purpose**: Get BOR log with documents for customer portal
```php
// Response
{
    "data": {
        "uploaded_documents": [...],
        "signed_pdf": [...]
    }
}
```

#### `POST /api/v1/bor/sign-document`
**Purpose**: Sign BOR document (customer action)
```php
// Request Body
{
    "bor_ref_id": "IM-BOR-123456",
    "file": "base64_encoded_file_or_file_upload",
    "is_base_64": true,
    "document_type_code": "BAL",
    "download_clicked": false,
    "insurer_name": "John Doe",
    "policy_number": "POL123456"
}
```

#### `POST /api/v1/bor/upload-document`
**Purpose**: Upload BOR document (customer action)
```php
// Request Body
{
    "bor_ref_id": "IM-BOR-123456",
    "file": "file_upload",
    "quote_type": "car",
    "quote_uuid": "uuid",
    "document_type_code": "BAL"
}
```

#### `POST /api/v1/bor/completion-email/{borRefId}`
**Purpose**: Trigger completion email workflow
```php
// Response
{
    "message": "success",
    "result": {...}
}
```

## Frontend Components

### Core Components Structure

```
BorLogsSection.vue (Main Container)
├── BorLogsList.vue (Data Table)
│   ├── BorCancelModal.vue
│   ├── BorDoneModal.vue
│   └── BorViewDocumentModal.vue
├── BorRequestForm.vue (Create/Edit Form)
└── BorUploadDocument.vue (Upload Modal)
```

### Key Component Features

#### BorLogsSection.vue
- **Auto-collapse**: Collapses when lead status is "Policy Issued"
- **Pagination**: Laravel-style pagination with 15 items per page
- **Real-time Updates**: Refreshes data after actions
- **Error Handling**: Comprehensive error states and retry mechanisms

#### BorRequestForm.vue
- **Dynamic Forms**: Different fields based on customer type and LOB
- **Validation**: Client-side validation with server-side backup
- **Edit Mode**: Supports editing existing BOR requests
- **Document Preview**: Shows uploaded/signed documents in edit mode

#### BorLogsList.vue
- **Action Buttons**: Context-sensitive actions based on BOR status
- **Status Badges**: Visual status indicators with color coding
- **Permission Checks**: Respects BOR_DOCUMENT_UPLOAD permission
- **Copy Link**: Generates customer-facing BOR links

### Status Management

```javascript
const statusConfig = {
    SIGNATURE_REQUESTED: {
        class: 'bg-yellow-100 text-yellow-800',
        text: 'Signature Requested'
    },
    DOCUMENT_SIGNED: {
        class: 'bg-indigo-100 text-indigo-800',
        text: 'Document Signed'
    },
    DOCUMENT_UPLOADED: {
        class: 'bg-purple-100 text-purple-800',
        text: 'Document Uploaded'
    },
    CANCELLED: {
        class: 'bg-gray-100 text-gray-800',
        text: 'Cancelled'
    },
    COMPLETED: {
        class: 'bg-green-100 text-green-800',
        text: 'Completed'
    }
};
```

## Email Workflows

### Email Types

#### 1. BOR Request Email (BorRequestMail)
**Trigger**: When BOR request is created
**Recipients**: Customer's primary email
**Template**: Dynamic based on customer type
- **Individual**: Sign and Accept workflow
- **Entity**: Upload BOR letter workflow

**Bird Integration**:
```php
// Workflow URL stored in ApplicationStorage
ApplicationStorageEnums::BIRD_BOR_WORKFLOW_URL

// Email Data Structure
{
    "uuid": "quote_uuid",
    "ref_id": "quote_code",
    "quote_type": "car-insurance",
    "workflow_type": "BOR_REQUEST",
    "quote_link": "https://portal.domain.com/car-insurance/quote/uuid/bor/IM-BOR-123456",
    "customer_name": "John Doe",
    "subject_line": "BOR Request - Car Insurance",
    "insurance": {
        "insurance_name": "ABC Insurance",
        "insurance_representative": "rep@email.com"
    },
    "customer": {
        "email": "customer@email.com",
        "first_name": "John",
        "last_name": "Doe",
        "company_name": "",
        "mobile": "+971501234567"
    },
    "bor_data": {
        "policy_number": "POL123456",
        "insurer_name": "ABC Insurance",
        "customer_type": "individual",
        "bor_ref_id": "IM-BOR-123456",
        "document_id": "DOC123",
        "date_created": "2024-01-15 10:30:00"
    },
    "attachPdf": "base64_encoded_pdf_for_entities",
    "advisor": {...}
}
```

#### 2. BOR Completion Email (BorCompletionMail)
**Trigger**: When document is signed/uploaded
**Recipients**: Customer's primary email
**Purpose**: Confirmation of successful BOR completion

#### 3. Insurer Notification Email (BorInsurerNotificationMail)
**Trigger**: When BOR document is uploaded/signed
**Recipients**: Insurance provider contacts
**Special Handling**: 
- Car LOB with Sukoon: Subject includes chassis number
- Only sent if insurance provider is configured
- Sent only once per BOR request

### Email Configuration

#### Bird Service Integration
```php
// Service Configuration
BirdService::triggerWebHookRequest($workflowUrl, $emailData)

// Workflow URLs stored in ApplicationStorage
- BIRD_BOR_WORKFLOW_URL: BOR request emails
- BIRD_BOR_COMPLETION_WORKFLOW_URL: Completion emails  
- BIRD_BOR_INSURER_WORKFLOW_URL: Insurer notifications
```

## Document Management

### Document Types by LOB

```php
$lobToDocumentType = [
    'Car' => DocumentTypeCode::BAL,
    'Bike' => DocumentTypeCode::BAL_BIKE,
    'Travel' => DocumentTypeCode::BAL_TRVL,
    'Home' => DocumentTypeCode::BAL_HOME,
    'Pet' => DocumentTypeCode::BAL_PET,
    'Health' => DocumentTypeCode::BAL_HLTH,
    'Life' => DocumentTypeCode::BAL_LIFE,
    'Cycle' => DocumentTypeCode::BAL_CYCLE,
    'Yacht' => DocumentTypeCode::BAL_YCHT,
    'Business' => [DocumentTypeCode::BUS_BAL, DocumentTypeCode::BAL_BS],
    'Group Medical' => DocumentTypeCode::GM_BOL,
];
```

### Document Storage

#### Azure Blob Storage
- **Container**: Configured via `azureIM` disk
- **Path Structure**: `/bor/{bor_reference}/{document_type}/{filename}`
- **File Types**: PDF, DOC, DOCX, JPG, JPEG, PNG
- **Size Limit**: 10MB per file

#### Document Processing
```php
// Upload Process
1. Validate file type and size
2. Generate unique filename
3. Store in Azure Blob Storage
4. Create QuoteDocument record
5. Update BorLog with document reference
6. Trigger completion emails
```

### PDF Generation

#### BorPdfService Features
- **Dynamic Templates**: Different layouts for Individual vs Entity
- **Signature Integration**: Embeds digital signatures
- **LOB-specific Data**: Includes policy numbers, chassis numbers
- **Base64 Encoding**: For email attachments and previews

#### PDF Template (bor-document.blade.php)
```php
// Template Variables
- customer_type: 'Individual' | 'Entity'
- customer_name: Customer full name
- company_name: Company name (Entity only)
- insurance_company: Insurance provider name
- policy_number: Policy number (Individual only)
- chassis_number: Chassis number (Motor LOBs)
- current_date: Current date
- signature_path: Digital signature image
- include_signature: Boolean flag
- date_signed: Signature date
- date_signed_time: Signature time
- document_id: Unique document hash
- user_agent: Browser information
```

## Permissions & Security

### Permission System

#### BOR_DOCUMENT_UPLOAD Permission
```php
// Middleware Protection
$this->middleware('permission:' . PermissionsEnum::BOR_DOCUMENT_UPLOAD, 
    ['only' => ['uploadDocument']]);

// Frontend Check
const canUpload = can(permissionsEnum.BOR_DOCUMENT_UPLOAD);
```

#### Role-Based Access
- **Advisor**: Can create, edit, cancel BOR requests
- **Admin**: Full access including document upload
- **Customer**: Can only sign/upload via secure links

### Security Measures

#### BOR Reference Generation
```php
// Format: IM-BOR-{ddmmyyHis}-{count}
// Example: IM-BOR-150124103045-1
public static function generateBorId(int $leadId): string
{
    // Thread-safe generation with database locks
    // Prevents duplicate references
    // Includes timestamp and sequence number
}
```

#### Secure Links
- **Customer Portal**: Uses BOR reference for access
- **No Authentication**: Links are self-contained
- **Time-based**: Links expire after reasonable time
- **Audit Trail**: All actions logged with IP and user agent

## Configuration

### Application Storage Settings

#### Required CMS Configuration
```php
// Bird Workflow URLs
ApplicationStorageEnums::BIRD_BOR_WORKFLOW_URL
ApplicationStorageEnums::BIRD_BOR_COMPLETION_WORKFLOW_URL  
ApplicationStorageEnums::BIRD_BOR_INSURER_WORKFLOW_URL

// Portal Configuration
config('constants.AFIA_WEBSITE_DOMAIN')
```

#### Database Seeders

##### ApplicationStorageSeeder
```php
// BIRD_BOR_WORKFLOW_URL Configuration
ApplicationStorage::create([
    'key_name' => ApplicationStorageEnums::BIRD_BOR_WORKFLOW_URL,
    'value' => 'https://bird-service.com/workflows/bor-request',
    'description' => 'Bird workflow URL for BOR request emails'
]);
```

##### RolePermissionSeeder
```php
// BOR Document Upload Permission
Permission::create([
    'name' => PermissionsEnum::BOR_DOCUMENT_UPLOAD,
    'guard_name' => 'web',
    'description' => 'Permission to upload BOR documents'
]);
```

##### DocumentTypesSeeder
```php
// BOR Signature Document Type
[
    'code' => DocumentTypeCode::BOR_SIGN,
    'text' => 'Bor Signature',
    'quote_type_id' => null, // Available for all LOBs
    'is_active' => 1,
    'folder_path' => 'bor',
    'accepted_files' => '.pdf,.png,.jpeg,.jpg',
    'max_files' => 5,
    'max_size' => 25,
    'is_required' => 0,
    'send_to_customer' => 0,
    'sort_order' => null,
    'receive_from_customer' => 1,
    'category' => DocumentTypeCode::QUOTE,
    'is_required_for_send_policy' => 0,
    'business_type_of_insurance_id' => null,
    'business_type_of_customer' => null,
],

// BOR Document Types by LOB (Broker Appointment Letter)
[
    'code' => 'BAL',
    'text' => 'Broker Appointment letter',
    'description' => '',
    'is_active' => 1,
    'quote_type_id' => QuoteTypeId::CompanyCar,
    'folder_path' => 'car',
    'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',
    'max_files' => 10,
    'max_size' => 25,
    'is_required' => 0,
    'send_to_customer' => 0,
    'sort_order' => 11,
    'receive_from_customer' => 1,
    'category' => DocumentTypeCode::QUOTE,
    'is_required_for_send_policy' => 0,
    'business_type_of_insurance_id' => null,
    'business_type_of_customer' => null,
],
```

##### BorDocumentSeeder
```php
// Updates existing BOR document types to maintain consistency
$documentTypes = [
    DocumentTypeCode::BAL_BIKE,    // Bike BOR
    DocumentTypeCode::BAL,          // Car BOR
    DocumentTypeCode::BAL_HOME,     // Home BOR
    DocumentTypeCode::BAL_LIFE,     // Life BOR
    DocumentTypeCode::BAL_TRVL,     // Travel BOR
    DocumentTypeCode::BAL_HLTH,     // Health BOR
    DocumentTypeCode::BAL_YCHT,     // Yacht BOR
    DocumentTypeCode::BAL_CYCLE,    // Cycle BOR
    DocumentTypeCode::BAL_PET,      // Pet BOR
    DocumentTypeCode::BAL_BS,       // Business BOR
    DocumentTypeCode::BUS_BAL,      // Business BOR (Alternative)
];

foreach ($documentTypes as $documentType) {
    $existingDocumentType = ModelsDocumentType::where('code', $documentType)
        ->where('category', 'QUOTE')
        ->get();
    
    foreach ($existingDocumentType as $documentTypeObj) {
        if ($documentTypeObj) {
            $documentTypeObj->update([
                'code' => $documentType,
                'text' => 'Broker on Record Letter',
                'description' => 'Please upload the BOR letter with the signature and stamp on your official company letterhead.',
                'is_active' => 1,
            ]);
        }
    }
}
```

### Environment Variables

#### Required Configuration
```env
# Azure Storage
AZURE_STORAGE_CONNECTION_STRING=
AZURE_STORAGE_CONTAINER=

# Bird Service
BIRD_SERVICE_URL=
BIRD_API_KEY=

# Portal Domain
AFIA_WEBSITE_DOMAIN=https://portal.domain.com
```

## Deployment Notes

### Database Migrations

#### Required Migrations
1. `create_bor_logs_table.php`
2. `create_insurance_provider_contacts_table.php`
3. `add_bor_document_upload_permission.php`

#### Migration Order
```bash
php artisan migrate --path=database/migrations/create_insurance_provider_contacts_table.php
php artisan migrate --path=database/migrations/create_bor_logs_table.php
php artisan db:seed --class=RolePermissionSeeder
php artisan db:seed --class=ApplicationStorageSeeder
```

### Frontend Build

#### Component Registration
```javascript
// Auto-imported components (unplugin-vue-components)
- BorLogsSection
- BorLogsList  
- BorRequestForm
- BorUploadDocument
- BorCancelModal
- BorDoneModal
- BorViewDocumentModal
```

#### Route Configuration
```php
// Web Routes (IMCRM)
Route::prefix('bor')->name('bor.')->group(function () {
    Route::get('/requests', [BorController::class, 'index'])->name('requests.index');
    Route::post('/requests', [BorController::class, 'store'])->name('requests.store');
    Route::put('/requests/{id}', [BorController::class, 'update'])->name('requests.update');
    Route::post('/requests/{id}/upload', [BorController::class, 'uploadDocument'])->name('requests.upload');
    Route::get('/requests/{id}/generate-link', [BorController::class, 'generateLink'])->name('requests.generate-link');
    Route::get('/logs/{borLogId}/download', [BorController::class, 'downloadDocument'])->name('logs.download');
    Route::get('/logs/{borLogId}/view-signed-pdf', [BorController::class, 'viewSignedPdf'])->name('logs.view-signed-pdf');
    Route::put('/logs/{id}/cancel', [BorController::class, 'cancelBor'])->name('logs.cancel');
    Route::put('/logs/{id}/mark-done', [BorController::class, 'markDone'])->name('logs.mark-done');
    Route::get('/get-representor', [BorController::class, 'getRepresentor'])->name('get-representor');
});

// API Routes (Customer Portal)
Route::prefix('api/v1/bor')->group(function () {
    Route::get('/{borRefId}', [BorController::class, 'getBorLog']);
    Route::get('/{borRefId}/sse', [BorController::class, 'getBorLogSSE']);
    Route::post('/sign-document', [BorController::class, 'signDocument']);
    Route::post('/upload-document', [BorController::class, 'uploadDocument']);
    Route::delete('/delete-document', [BorController::class, 'deleteDocument']);
    Route::post('/completion-email/{borRefId}', [BorController::class, 'borCompletionEmailTrigger']);
    Route::post('/generate-pdf', [BorController::class, 'generatePdf']);
    Route::get('/document-types', [BorController::class, 'getDocumentTypes']);
});
```

### Testing Checklist

#### Functional Testing
- [ ] BOR request creation for all LOBs
- [ ] Customer type validation (Individual/Entity)
- [ ] LOB-specific field requirements
- [ ] Email delivery via Bird service
- [ ] Document upload with permissions
- [ ] Digital signature workflow
- [ ] Insurer notification emails
- [ ] Status transitions
- [ ] Permission-based access control

#### Integration Testing
- [ ] Bird service connectivity
- [ ] Azure storage integration
- [ ] PDF generation
- [ ] Email template rendering
- [ ] Database transaction integrity
- [ ] Frontend-backend communication

#### Performance Testing
- [ ] Large file upload handling
- [ ] Concurrent BOR requests
- [ ] Database query optimization
- [ ] Memory usage during PDF generation
- [ ] Email delivery performance

### Monitoring & Logging

#### Key Metrics
- BOR request creation rate
- Email delivery success rate
- Document upload success rate
- Average processing time
- Error rates by component

#### Logging Points
```php
// Critical Logging Events
LoggerService::info('BOR Request Created', [...]);
LoggerService::error('BOR Email Failed', [...]);
LoggerService::warning('Document Upload Failed', [...]);
LoggerService::info('Insurer Notification Sent', [...]);
```

### Troubleshooting

#### Common Issues

1. **Email Delivery Failures**
   - Check Bird service connectivity
   - Verify workflow URLs in ApplicationStorage
   - Check customer email validity

2. **Document Upload Issues**
   - Verify Azure storage configuration
   - Check file size limits (10MB)
   - Verify BOR_DOCUMENT_UPLOAD permission

3. **PDF Generation Problems**
   - Check DomPDF configuration
   - Verify template file paths
   - Check memory limits for large documents

4. **Status Update Issues**
   - Verify database transaction integrity
   - Check BorStatusEnum values
   - Verify frontend-backend status synchronization

---

**Document Version**: 1.0  
**Last Updated**: October 2025  
**Maintained By**: Development Team

# CSV Export Job Refactoring Guide

## Overview

The `ExportCsvAndSendEmailJob` has been refactored to provide a cleaner, more maintainable structure using proper abstractions and dependency injection.

## Problems with the Old Structure

1. **Dynamic Class Instantiation**: Used string class names and `app()` for instantiation
2. **Tight Coupling**: Job was tightly coupled to the `ExcelExportable` trait
3. **Mixed Responsibilities**: Single job handled both CSV generation and email sending
4. **Poor Error Context**: Difficult to debug which export class caused failures
5. **Inflexible Structure**: Hard to customize behavior for different export types

## New Structure

### 1. Contract-Based Interface

```php
// app/Contracts/CsvExportableInterface.php
interface CsvExportableInterface
{
    public function collection(array $requestParams = []): Collection;
    public function headings(): array;
    public function map($record): array;
    public function getQuery(array $requestParams = []): ?Builder;
    public function getExportMetadata(array $requestParams = []): array;
}
```

### 2. Dedicated Services

- **CsvExportService**: Handles CSV file generation with memory-efficient chunking
- **EmailExportService**: Manages email sending with proper configuration

### 3. Clean Job Implementation

```php
// app/Jobs/CsvExportEmailJob.php
class CsvExportEmailJob implements ShouldQueue
{
    public function __construct(
        private CsvExportableInterface $exporter,
        private string $recipientEmail,
        private string $subject,
        private array $requestParams = [],
        private array $ccRecipients = []
    ) {
        $this->onQueue('renewals');
    }
}
```

### 4. Modern Trait

```php
// app/Traits/ModernCsvExportable.php
trait ModernCsvExportable
{
    public function emailCSV(string $fileName, array $requestParams = []): JsonResponse
    {
        CsvExportEmailJob::dispatch(
            $this,
            $requestParams['recipientEmail'],
            $requestParams['subject'],
            $requestParams,
            $requestParams['ccRecipients'] ?? []
        );

        return response()->json([
            'message' => 'Your export is being processed. You will receive an email with the CSV file shortly.',
        ]);
    }
}
```

## Migration Steps

### 1. Update Export Classes

**Before (using ExcelExportable trait):**

```php
class CarQuoteExport
{
    use ExcelExportable;

    public function collection($requestParams = [])
    {
        return app(CarQuoteService::class)->getGridData(requestParams: $requestParams)->get();
    }

    public function headings(): array { /* ... */ }
    public function map($quote): array { /* ... */ }
}
```

**After (implementing CsvExportableInterface):**

```php
class ModernCarQuoteExport implements CsvExportableInterface
{
    use ModernCsvExportable;

    public function __construct(
        private CarQuoteService $carQuoteService
    ) {}

    public function collection(array $requestParams = []): Collection
    {
        return $this->carQuoteService->getGridData(requestParams: $requestParams)->get();
    }

    public function getQuery(array $requestParams = []): ?Builder
    {
        return $this->carQuoteService->getGridData(requestParams: $requestParams);
    }

    public function headings(): array { /* ... */ }
    public function map($record): array { /* ... */ }

    public function getExportMetadata(array $requestParams = []): array
    {
        return [
            'exportClass' => static::class,
            'timestamp' => now()->toISOString(),
            'parameters' => $requestParams,
            'exportType' => 'car_quotes',
        ];
    }
}
```

### 2. Update Controllers

**Before:**

```php
if ($request['exportType'] == 'email') {
    return app(CarQuoteExport::class)->emailCSV('Car-List', $request->all());
}
```

**After:**

```php
if ($request['exportType'] == 'email') {
    return app(ModernCarQuoteExport::class)->emailCSV('Car-List', $request->all());
}
```

### 3. Register Services (if needed)

In `app/Providers/AppServiceProvider.php`:

```php
public function register()
{
    $this->app->singleton(CsvExportService::class);
    $this->app->singleton(EmailExportService::class);
}
```

## Benefits of New Structure

### 1. **Type Safety**

- Proper interfaces and dependency injection
- No more string-based class instantiation
- Better IDE support and autocompletion

### 2. **Separation of Concerns**

- CSV generation logic separated from email logic
- Job focuses only on orchestration
- Services handle specific responsibilities

### 3. **Better Testing**

- Easy to mock interfaces
- Individual services can be tested separately
- Clear dependencies

### 4. **Memory Efficiency**

- Improved chunking implementation
- Better garbage collection
- Configurable chunk sizes

### 5. **Better Error Handling**

- Structured logging with context
- Cleaner error messages
- Proper exception handling

### 6. **Flexibility**

- Easy to add new export types
- Customizable behavior per export class
- Extensible metadata system

## Key Improvements

1. **Memory Management**: Better chunking and garbage collection
2. **Error Context**: Structured logging with export class information
3. **Type Safety**: Proper interfaces instead of dynamic instantiation
4. **Testability**: Clean dependencies and separation of concerns
5. **Maintainability**: Clear structure and single responsibility principle

## Migration Status

The following export classes have been migrated to the new structure:

1. **CarQuoteExport** ✅ Migrated
2. **HealthQuotesExport** ✅ Migrated
3. **TravelQuoteExport** ✅ Migrated
4. **LifeQuotesExport** ✅ Migrated
5. **BusinessQuoteExport** ✅ Migrated
6. **KycLogsExport** ✅ Migrated
7. **HomeQuoteExport** ✅ Migrated
8. **PersonalQuotesExport** ✅ Migrated

## Remaining Export Classes to Migrate

The following export classes still use the old `ExcelExportable` trait and need migration:

1. **TmLeadsExport**
2. **RMQuotesExport**
3. **AmtQuoteExport**
4. **CarQuoteExportWithEmailMobile**
5. **EmbeddedProductReport**
6. **CarQuoteExportWithPlans**
7. **RenewalQuotesExport**
8. **RetentionReportExport**
9. **CarQuoteExportWithMakeModelTrims**
10. **PersonalQuotesExport**

## Cleanup Plan

### Phase 1: Complete ✅

- Created new interface and services
- Migrated 6 export classes to new structure
- Cleaned up example/demo files

### Phase 2: Remaining Migration

⚠️ **Current Priority** - Must complete before Phase 3:

- Migrate the remaining 9 export classes (listed in Phase 3)
- Update their controllers to use new classes
- Ensure all functionality is preserved
- Test each migration thoroughly

### Phase 3: Final Cleanup (After Phase 2)

⚠️ **Cannot be completed yet** - The following components are still in use:

- **ExportCsvAndSendEmailJob** - Used by 9 remaining export classes via ExcelExportable trait
- **ExcelExportable trait** - Used by 9 export classes that haven't been migrated yet

**Remaining classes using old system:**

- TmLeadsExport
- RMQuotesExport
- CarQuoteExportWithEmailMobile
- AmtQuoteExport
- EmbeddedProductReport
- CarQuoteExportWithPlans
- RenewalQuotesExport
- RetentionReportExport
- CarQuoteExportWithMakeModelTrims

**After migrating all remaining classes:**

- Remove old `ExportCsvAndSendEmailJob`
- Remove old `ExcelExportable` trait
- Clean up any remaining references

## Backward Compatibility

The old `ExportCsvAndSendEmailJob` and `ExcelExportable` trait remain functional but should be migrated gradually to the new structure. Both can coexist during the migration period.

## Testing the New Structure

```php
// Test CSV generation service
$service = app(CsvExportService::class);
$exporter = app(ModernCarQuoteExport::class);
$filePath = $service->generateCsvFile($exporter, ['fileName' => 'test']);

// Test email service
$emailService = app(EmailExportService::class);
$emailService->sendCsvByEmail(
    $exporter,
    'test@example.com',
    'Test Export',
    ['fileName' => 'test']
);

// Test job dispatch
CsvExportEmailJob::dispatch(
    $exporter,
    'test@example.com',
    'Test Export',
    ['fileName' => 'test']
);
```

This refactoring provides a much cleaner, more maintainable codebase while preserving all existing functionality.

## Export-to-Email Pattern

### **New Standardized Approach**

A reusable helper method has been added to the base `Controller` class to standardize export-to-email functionality across all controllers:

```php
// In any controller extending Controller
public function exportToEmail(Request $request)
{
    $exportClassMap = [
        'report_type_1' => ExportClass1::class,
        'report_type_2' => ExportClass2::class,
    ];

    return $this->handleExportToEmail($request, $exportClassMap, 'Custom Subject Prefix');
}
```

### **Features:**

1. **Automatic Detection**: Detects if export class uses new `CsvExportableInterface` or old `ExcelExportable` trait
2. **Graceful Fallback**: Falls back to old system for unmigrated export classes
3. **Validation**: Built-in request validation for email, subject, and CC recipients
4. **Error Handling**: Proper error responses for unsupported report types
5. **Standardized Response**: Consistent JSON response format across all controllers

### **Usage Examples:**

**Request Format:**

```json
{
  "report": "car_quotes",
  "recipientEmail": "user@example.com",
  "subject": "Car Quotes Export",
  "ccRecipients": ["manager@example.com"]
}
```

**Response (Success):**

```json
{
  "message": "Your export is being processed. You will receive an email with the CSV file shortly.",
  "report_type": "car_quotes",
  "recipient": "user@example.com",
  "subject": "Quote Export: car_quotes"
}
```

**Response (Unsupported):**

```json
{
  "error": "Email export is not yet supported for this report type.",
  "report_type": "some_report",
  "suggestion": "Please use the download option instead..."
}
```

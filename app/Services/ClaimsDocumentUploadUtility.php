<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BusinessTypeOfInsurance;
use App\Models\Claim;
use App\Models\GenericDocument;
use App\Models\InsuranceProvider;
use App\Models\QuoteType;
use App\Services\Logger\LoggerService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Utility class to upload documents from claims folder to Azure Storage
 * and insert records into generic_documents table.
 *
 * This is a one-time use utility class.
 *
 * Folder structure expected:
 * For BUSINESS LOB:
 * claims/
 *   BUSINESS/
 *     {business_type_of_insurance_id}/
 *       {insurance_provider_id}/
 *         {filename}.pdf
 *
 * For other LOBs (Travel, Yacht, etc.):
 * claims/
 *   {LOB}/
 *     {insurance_provider_id}/
 *       {filename}.pdf
 */
class ClaimsDocumentUploadUtility
{
    private const CLAIMS_FOLDER = 'claims';
    private const AZURE_PATH_PREFIX = 'documents/claims';
    private const CLAIM_ID = 1; // documentable_id
    private const CREATED_BY_ID = 1030;

    /**
     * Process and upload all documents from claims folder
     *
     * @return array Statistics about the upload process
     */
    public function processAllDocuments(): array
    {
        // Feature logging is already started in the controller
        LoggerService::info('Starting processAllDocuments');

        $stats = [
            'processed' => 0,
            'uploaded' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        $claimsPath = base_path(self::CLAIMS_FOLDER);

        if (! File::exists($claimsPath)) {
            $error = "Claims folder not found at: {$claimsPath}";
            $stats['errors'][] = $error;
            LoggerService::error('Claims folder not found', extra: [
                'claims_path' => $claimsPath,
            ]);

            return $stats;
        }

        // Get all LOB folders (Travel, Yacht, etc.)
        $lobFolders = File::directories($claimsPath);

        foreach ($lobFolders as $lobFolder) {
            $lobName = basename($lobFolder);

            // Get quote type ID from LOB folder name
            $quoteType = $this->getQuoteTypeByName(lobName: $lobName);

            if (! $quoteType) {
                $error = "Quote type not found for LOB: {$lobName}";
                $stats['errors'][] = $error;
                LoggerService::warning('Quote type not found', extra: [
                    'lob_name' => $lobName,
                    'lob_folder' => $lobFolder,
                ]);
                continue;
            }

            // Check if this is BUSINESS LOB (has extra folder level for business_type_of_insurance_id)
            $isBusiness = strtoupper($lobName) === 'BUSINESS';

            if ($isBusiness) {
                // For BUSINESS: claims/BUSINESS/{business_type_of_insurance_id}/{insurance_provider_id}/{filename}.pdf
                $businessTypeFolders = File::directories($lobFolder);

                foreach ($businessTypeFolders as $businessTypeFolder) {
                    $businessTypeId = (int) basename($businessTypeFolder);

                    if (! is_numeric(basename($businessTypeFolder))) {
                        $error = "Invalid business type of insurance ID folder: {$businessTypeFolder}";
                        $stats['errors'][] = $error;
                        LoggerService::warning('Invalid business type of insurance ID folder', extra: [
                            'business_type_folder' => $businessTypeFolder,
                            'lob_name' => $lobName,
                        ]);
                        continue;
                    }

                    // If folder name is 0, set to null
                    if ($businessTypeId === 0) {
                        $businessTypeId = null;
                    }

                    // Get all insurance provider folders inside business type folder
                    $providerFolders = File::directories($businessTypeFolder);

                        foreach ($providerFolders as $providerFolder) {
                        $providerFolderName = basename($providerFolder);
                        $providerId = $this->extractIntegerFromFolderName($providerFolderName);

                        if ($providerId === null) {
                            $error = "Invalid insurance provider ID folder: {$providerFolder}";
                            $stats['errors'][] = $error;
                            LoggerService::warning('Invalid insurance provider ID folder', extra: [
                                'provider_folder' => $providerFolder,
                                'business_type_id' => $businessTypeId,
                                'lob_name' => $lobName,
                            ]);
                            continue;
                        }

                        // Validate insurance provider exists
                        if (! InsuranceProvider::where('id', $providerId)->exists()) {
                            $error = "Insurance provider ID {$providerId} does not exist in database";
                            $stats['errors'][] = $error;
                            LoggerService::warning('Insurance provider ID not found - skipping provider folder', extra: [
                                'lob' => $lobName,
                                'business_type_of_insurance_id' => $businessTypeId,
                                'insurance_provider_id' => $providerId,
                                'provider_folder' => $providerFolder,
                            ]);
                            continue;
                        }

                        // Validate business type of insurance exists (if not null)
                        if ($businessTypeId !== null && ! BusinessTypeOfInsurance::where('id', $businessTypeId)->exists()) {
                            $error = "Business type of insurance ID {$businessTypeId} does not exist in database";
                            $stats['errors'][] = $error;
                            LoggerService::warning('Business type of insurance ID not found - skipping provider folder', extra: [
                                'lob' => $lobName,
                                'business_type_of_insurance_id' => $businessTypeId,
                                'insurance_provider_id' => $providerId,
                                'provider_folder' => $providerFolder,
                            ]);
                            continue;
                        }

                        // Process files in this provider folder
                        $this->processFilesInFolder(
                            $providerFolder,
                            $quoteType->id,
                            $providerId,
                            $businessTypeId,
                            $lobName,
                            $stats
                        );
                    }
                }
            } else {
                // For other LOBs: claims/{LOB}/{insurance_provider_id}/{filename}.pdf
                $providerFolders = File::directories($lobFolder);

                foreach ($providerFolders as $providerFolder) {
                    $providerFolderName = basename($providerFolder);
                    $providerId = $this->extractIntegerFromFolderName($providerFolderName);

                    if ($providerId === null) {
                        $error = "Invalid insurance provider ID folder: {$providerFolder}";
                        $stats['errors'][] = $error;
                        LoggerService::warning('Invalid insurance provider ID folder', extra: [
                            'provider_folder' => $providerFolder,
                            'lob_name' => $lobName,
                        ]);
                        continue;
                    }

                    // Validate insurance provider exists
                    if (! InsuranceProvider::where('id', $providerId)->exists()) {
                        $error = "Insurance provider ID {$providerId} does not exist in database";
                        $stats['errors'][] = $error;
                        LoggerService::warning('Insurance provider ID not found - skipping provider folder', extra: [
                            'lob' => $lobName,
                            'business_type_of_insurance_id' => null,
                            'insurance_provider_id' => $providerId,
                            'provider_folder' => $providerFolder,
                        ]);
                        continue;
                    }

                    // Process files in this provider folder (business_type_of_insurance_id is null for non-BUSINESS)
                    $this->processFilesInFolder(
                        $providerFolder,
                        $quoteType->id,
                        $providerId,
                        null,
                        $lobName,
                        $stats
                    );
                }
            }
        }

        return $stats;
    }

    /**
     * Process all files in a provider folder
     *
     * @param string $providerFolder Path to provider folder
     * @param int $quoteTypeId Quote type ID
     * @param int $insuranceProviderId Insurance provider ID
     * @param int|null $businessTypeOfInsuranceId Business type of insurance ID (null for non-BUSINESS LOBs)
     * @param string $lobName LOB name (e.g., "Business", "Travel")
     * @param array $stats Statistics array (passed by reference)
     * @return void
     */
    private function processFilesInFolder(
        string $providerFolder,
        int $quoteTypeId,
        int $insuranceProviderId,
        ?int $businessTypeOfInsuranceId,
        string $lobName,
        array &$stats
    ): void {
        // Get all files in the provider folder - only PDF files
        $files = File::files(directory: $providerFolder);

        foreach ($files as $file) {
            // Only process PDF files
            $extension = strtolower(File::extension($file->getPathname()));
            if ($extension !== 'pdf') {
                $error = "Skipping non-PDF file: {$file->getFilename()}";
                $stats['errors'][] = $error;
                LoggerService::debug('Skipping non-PDF file', extra: [
                    'lob' => $lobName,
                    'business_type_of_insurance_id' => $businessTypeOfInsuranceId,
                    'insurance_provider_id' => $insuranceProviderId,
                    'filename' => $file->getFilename(),
                    'extension' => $extension,
                ]);
                continue;
            }

            $stats['processed']++;

            try {
                $result = $this->processDocument(
                    $file->getPathname(),
                    $file->getFilename(),
                    $quoteTypeId,
                    $insuranceProviderId,
                    $businessTypeOfInsuranceId,
                    $lobName
                );

                if ($result['success']) {
                    $stats['uploaded']++;
                } else {
                    $stats['failed']++;
                    $errorMessage = $result['error'] ?? "Failed to process: {$file->getFilename()}";
                    $stats['errors'][] = $errorMessage;
                    LoggerService::error('Failed to process document', extra: [
                        'lob' => $lobName,
                        'business_type_of_insurance_id' => $businessTypeOfInsuranceId,
                        'insurance_provider_id' => $insuranceProviderId,
                        'filename' => $file->getFilename(),
                        'error' => $errorMessage,
                    ]);
                }
            } catch (\Exception $e) {
                $stats['failed']++;
                $errorMessage = "Error processing {$file->getFilename()}: {$e->getMessage()}";
                $stats['errors'][] = $errorMessage;
                LoggerService::error('Exception processing document', extra: [
                    'lob' => $lobName,
                    'business_type_of_insurance_id' => $businessTypeOfInsuranceId,
                    'insurance_provider_id' => $insuranceProviderId,
                    'filename' => $file->getFilename(),
                    'error' => $e->getMessage(),
                ], exception: $e);
            }
        }
    }

    /**
     * Process a single document: upload to Azure and insert into database
     *
     * @param string $filePath Full path to the file
     * @param string $originalFileName Original filename
     * @param int $quoteTypeId Quote type ID
     * @param int $insuranceProviderId Insurance provider ID
     * @param int|null $businessTypeOfInsuranceId Business type of insurance ID (null for non-BUSINESS LOBs)
     * @param string $lobName LOB name (e.g., "Business", "Travel")
     * @return array Result with success status
     */
    private function processDocument(
        string $filePath,
        string $originalFileName,
        int $quoteTypeId,
        int $insuranceProviderId,
        ?int $businessTypeOfInsuranceId = null,
        string $lobName = ''
    ): array {
        // Initialize azurePath to track uploaded file for cleanup on failure
        $azurePath = null;

        try {
            // Read file content
            $fileContent = File::get($filePath);

            if (! $fileContent) {
                $error = "Could not read file: {$filePath}";
                LoggerService::error('Failed to read file', extra: [
                    'lob' => $lobName,
                    'business_type_of_insurance_id' => $businessTypeOfInsuranceId,
                    'insurance_provider_id' => $insuranceProviderId,
                    'filename' => $originalFileName,
                    'file_path' => $filePath,
                ]);

                return [
                    'success' => false,
                    'error' => $error,
                ];
            }

            // Get file extension and validate it's PDF
            $extension = strtolower(File::extension($filePath));
            if ($extension !== 'pdf') {
                $error = "Only PDF files are allowed. File: {$originalFileName}";
                LoggerService::warning('Non-PDF file skipped', extra: [
                    'lob' => $lobName,
                    'business_type_of_insurance_id' => $businessTypeOfInsuranceId,
                    'insurance_provider_id' => $insuranceProviderId,
                    'filename' => $originalFileName,
                    'extension' => $extension,
                ]);

                return [
                    'success' => false,
                    'error' => $error,
                ];
            }

            $mimeType = 'application/pdf';

            // Sanitize filename: replace spaces, dashes, and special characters with underscores
            $sanitizedName = $this->sanitizeFileName($originalFileName);

            // Generate unique filename for Azure: {timestamp}_{uuid}.{ext}
            $timestamp = time();
            $uuid = $this->generateUuid();
            $azureFileName = "{$timestamp}_{$uuid}.{$extension}";
            $azurePath = self::AZURE_PATH_PREFIX.'/'.$azureFileName;

            // Upload to Azure Storage (following QuoteDocumentService pattern)
            $uploaded = Storage::disk('azureIM')->put($azurePath, $fileContent);

            if (! $uploaded) {
                $error = "Failed to upload to Azure: {$azurePath}";
                LoggerService::error('Azure upload failed', extra: [
                    'lob' => $lobName,
                    'business_type_of_insurance_id' => $businessTypeOfInsuranceId,
                    'insurance_provider_id' => $insuranceProviderId,
                    'filename' => $originalFileName,
                    'azure_path' => $azurePath,
                ]);

                return [
                    'success' => false,
                    'error' => $error,
                ];
            }

            // Verify the file exists in Azure Storage (similar to EpBookingService pattern)
            if (! Storage::disk('azureIM')->exists($azurePath)) {
                $error = "File uploaded but verification failed: {$azurePath}";
                LoggerService::error('Azure verification failed', extra: [
                    'lob' => $lobName,
                    'business_type_of_insurance_id' => $businessTypeOfInsuranceId,
                    'insurance_provider_id' => $insuranceProviderId,
                    'filename' => $originalFileName,
                    'azure_path' => $azurePath,
                ]);

                return [
                    'success' => false,
                    'error' => $error,
                ];
            }

            // Insert into database
            GenericDocument::create([
                'uuid' => $uuid,
                'documentable_type' => Claim::class,
                'documentable_id' => self::CLAIM_ID,
                'quote_type_id' => $quoteTypeId,
                'name' => $sanitizedName,
                'path' => $azurePath,
                'mime_type' => $mimeType,
                'created_by_id' => self::CREATED_BY_ID,
                'insurance_provider_id' => $insuranceProviderId,
                'business_type_of_insurance_id' => $businessTypeOfInsuranceId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            LoggerService::info('Document uploaded successfully', extra: [
                'lob' => $lobName,
                'business_type_of_insurance_id' => $businessTypeOfInsuranceId,
                'insurance_provider_id' => $insuranceProviderId,
                'filename' => $originalFileName,
                'sanitized_name' => $sanitizedName,
                'azure_path' => $azurePath,
                'quote_type_id' => $quoteTypeId,
            ]);

            return [
                'success' => true,
                'path' => $azurePath,
                'name' => $sanitizedName,
            ];
        } catch (\Exception $e) {
            // If file was uploaded to Azure but database insert failed, delete the orphaned file
            if ($azurePath !== null) {
                try {
                    if (Storage::disk('azureIM')->exists($azurePath)) {
                        Storage::disk('azureIM')->delete($azurePath);
                        LoggerService::info('Deleted orphaned file from Azure Storage after database insert failure', extra: [
                            'lob' => $lobName,
                            'business_type_of_insurance_id' => $businessTypeOfInsuranceId,
                            'insurance_provider_id' => $insuranceProviderId,
                            'filename' => $originalFileName,
                            'azure_path' => $azurePath,
                        ]);
                    }
                } catch (\Exception $deleteException) {
                    // Log deletion failure but don't fail the entire operation
                    LoggerService::error('Failed to delete orphaned file from Azure Storage', extra: [
                        'lob' => $lobName,
                        'business_type_of_insurance_id' => $businessTypeOfInsuranceId,
                        'insurance_provider_id' => $insuranceProviderId,
                        'filename' => $originalFileName,
                        'azure_path' => $azurePath,
                        'delete_error' => $deleteException->getMessage(),
                    ], exception: $deleteException);
                }
            }

            $error = $e->getMessage();
            LoggerService::error('Exception during document processing', extra: [
                'lob' => $lobName,
                'business_type_of_insurance_id' => $businessTypeOfInsuranceId,
                'insurance_provider_id' => $insuranceProviderId,
                'filename' => $originalFileName,
                'error' => $error,
            ], exception: $e);

            return [
                'success' => false,
                'error' => $error,
            ];
        }
    }

    /**
     * Get QuoteType by name (case-insensitive)
     *
     * @param string $lobName LOB folder name (e.g., "Travel", "Yacht")
     * @return QuoteType|null
     */
    private function getQuoteTypeByName(string $lobName): ?QuoteType
    {
        // Normalize the name: capitalize first letter, rest lowercase
        $normalizedName = ucfirst(strtolower($lobName));

        return QuoteType::where('code', $normalizedName)->first();
    }

    /**
     * Sanitize filename: replace spaces, dashes, and special characters with underscores
     *
     * @param string $fileName Original filename
     * @return string Sanitized filename with extension
     */
    private function sanitizeFileName(string $fileName): string
    {
        // Get extension first
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        $nameWithoutExt = pathinfo($fileName, PATHINFO_FILENAME);

        // Replace spaces, dashes, and ALL special characters with underscores
        // Keep only alphanumeric characters and underscores
        $sanitized = preg_replace('/[^a-zA-Z0-9]/', '_', $nameWithoutExt);
        $sanitized = preg_replace('/_+/', '_', $sanitized); // Replace multiple underscores with single
        $sanitized = trim($sanitized, '_'); // Remove leading/trailing underscores

        // If sanitized name is empty, use a default
        if (empty($sanitized)) {
            $sanitized = 'document';
        }

        // Add extension back
        return $sanitized.($extension ? '.'.$extension : '');
    }

    /**
     * Extract integer from folder name (e.g., "TOKIO MARINE 5" -> 5)
     *
     * @param string $folderName Folder name that may contain text and numbers
     * @return int|null Extracted integer or null if no integer found
     */
    private function extractIntegerFromFolderName(string $folderName): ?int
    {
        // Extract all digits from the folder name
        if (preg_match('/\d+/', $folderName, $matches)) {
            return (int) $matches[0];
        }

        return null;
    }

    /**
     * Generate a unique UUID string
     * Format: f669c058f922425c8a8e (similar to the example)
     * Ensures uniqueness by checking against existing GenericDocument records
     *
     * @return string
     */
    private function generateUuid(): string
    {
        // Generate a unique string similar to the example format
        // Combine uniqid with random string to get ~20 character unique identifier
        $prefix = uniqid('', true); // e.g., "67890abc123def.12345678"
        $uuid = str_replace('.', '', $prefix); // Remove dot
        $uuid = substr($uuid, 0, 20); // Limit to ~20 characters like example

        // Check for collisions and regenerate until unique (following EmbeddedProductRepository pattern)
        while (GenericDocument::where('uuid', $uuid)->first()) {
            $prefix = uniqid('', true).rand(1, 100);
            $uuid = str_replace('.', '', $prefix);
            $uuid = substr($uuid, 0, 20);
        }

        return $uuid;
    }
}


<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Claim;
use App\Models\GenericDocument;
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
 * claims/
 *   {LOB}/           (e.g., Travel, Yacht)
 *     {insurance_provider_id}/  (e.g., 40, 5)
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

            // Get all insurance provider folders
            $providerFolders = File::directories($lobFolder);

            foreach ($providerFolders as $providerFolder) {
                $providerId = (int) basename($providerFolder);

                if (! is_numeric(basename($providerFolder))) {
                    $error = "Invalid insurance provider ID folder: {$providerFolder}";
                    $stats['errors'][] = $error;
                    LoggerService::warning('Invalid insurance provider ID folder', extra: [
                        'provider_folder' => $providerFolder,
                        'lob_name' => $lobName,
                    ]);
                    continue;
                }

                // Get all files in the provider folder - only PDF files
                $files = File::files(directory: $providerFolder);

                foreach ($files as $file) {
                    // Only process PDF files
                    $extension = strtolower(File::extension($file->getPathname()));
                    if ($extension !== 'pdf') {
                        $error = "Skipping non-PDF file: {$file->getFilename()}";
                        $stats['errors'][] = $error;
                        LoggerService::debug('Skipping non-PDF file', extra: [
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
                            $quoteType->id,
                            $providerId
                        );

                        if ($result['success']) {
                            $stats['uploaded']++;
                        } else {
                            $stats['failed']++;
                            $stats['errors'][] = $result['error'] ?? "Failed to process: {$file->getFilename()}";
                        }
                    } catch (\Exception $e) {
                        $stats['failed']++;
                        $stats['errors'][] = "Error processing {$file->getFilename()}: {$e->getMessage()}";
                    }
                }
            }
        }

        return $stats;
    }

    /**
     * Process a single document: upload to Azure and insert into database
     *
     * @param string $filePath Full path to the file
     * @param string $originalFileName Original filename
     * @param int $quoteTypeId Quote type ID
     * @param int $insuranceProviderId Insurance provider ID
     * @return array Result with success status
     */
    private function processDocument(
        string $filePath,
        string $originalFileName,
        int $quoteTypeId,
        int $insuranceProviderId
    ): array {
        try {
            // Read file content
            $fileContent = File::get($filePath);

            if (! $fileContent) {
                $error = "Could not read file: {$filePath}";
                LoggerService::error('Failed to read file', extra: [
                    'file_path' => $filePath,
                    'original_filename' => $originalFileName,
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
                    'file_path' => $filePath,
                    'original_filename' => $originalFileName,
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
                    'file_path' => $filePath,
                    'original_filename' => $originalFileName,
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
                    'file_path' => $filePath,
                    'original_filename' => $originalFileName,
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
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            LoggerService::info('Document uploaded successfully', extra: [
                'original_filename' => $originalFileName,
                'sanitized_name' => $sanitizedName,
                'azure_path' => $azurePath,
                'quote_type_id' => $quoteTypeId,
                'insurance_provider_id' => $insuranceProviderId,
            ]);

            return [
                'success' => true,
                'path' => $azurePath,
                'name' => $sanitizedName,
            ];
        } catch (\Exception $e) {
            $error = $e->getMessage();
            LoggerService::error('Exception during document processing', extra: [
                'file_path' => $filePath,
                'original_filename' => $originalFileName,
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
     * Generate a unique UUID string
     * Format: f669c058f922425c8a8e (similar to the example)
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

        return $uuid;
    }
}


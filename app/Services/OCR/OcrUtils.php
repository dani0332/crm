<?php

declare(strict_types=1);

namespace App\Services\OCR;

use App\Enums\OCRDocumentTypeEnum;
use App\Enums\QuoteTypes;
use App\Models\DocumentType;
use App\Models\SendUpdateLog;
use App\Services\AccuracyMatrixCacheService;
use App\Services\Logger\LoggerService;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Model;

class OcrUtils
{
    public static function getCleanData(array $data): array
    {
        return array_filter($data, function ($value) {
            return $value !== null && $value !== '';
        });
    }

    public static function getFieldsToUpdate(array $fieldsToUpdate): array
    {
        $dataToUpdate = [];
        foreach ($fieldsToUpdate as $field => $value) {
            if ($value !== null && $value !== '') {
                $dataToUpdate[$field] = $value;
            }
        }

        return $dataToUpdate;
    }

    public static function formatDate(?string $date): ?string
    {
        if (empty($date)) {
            return null;
        }

        try {
            return Carbon::parse($date)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function extractFirstName(?string $fullName): ?string
    {
        if (empty($fullName)) {
            return null;
        }

        $nameParts = explode(' ', trim($fullName));

        return $nameParts[0] ?? null;
    }

    public static function extractLastName(?string $fullName): ?string
    {
        if (empty($fullName)) {
            return null;
        }

        $nameParts = explode(' ', trim($fullName));
        if (count($nameParts) > 1) {
            // Join all parts except the first as last name
            return implode(' ', array_slice($nameParts, 1));
        }

        return null;
    }

    public static function formatGender(?string $gender): ?string
    {
        if (empty($gender)) {
            return null;
        }

        return match (strtoupper(trim($gender))) {
            'M', 'MALE' => 'Male',
            'F', 'FEMALE' => 'Female',
            default => $gender
        };
    }

    public static function ensureArray($data): array
    {
        if (is_object($data)) {
            return (array) $data;
        }

        return is_array($data) ? $data : [];
    }

    public static function resolveProp($object, $prop)
    {
        if (is_object($object) && property_exists($object, $prop)) {
            return $object->$prop;
        }

        return null;
    }

    public static function parseDate($date, $default = null, $format = 'Y-m-d')
    {
        try {
            return $date ? Carbon::parse($date)->format($format) : $default;
        } catch (Exception $e) {
            LoggerService::error(self::class.' - Exception occurred during date parsing: ', exception: $e);

            return $default;
        }
    }

    public static function getProvider(Model $quote)
    {
        if ($quote instanceof SendUpdateLog) {
            return $quote->insuranceProvider?->code ?? null;
        }

        if ($quote->payment?->insuranceProvider) {
            return $quote->payment?->insuranceProvider?->code ?? null;
        }

        // Fallback case for regular quotes
        if ($quote->payments && $quote->payments->isNotEmpty()) {
            $firstPayment = $quote->payments->first();
            if ($firstPayment?->insuranceProvider) {
                return $firstPayment->insuranceProvider->code;
            }
        }

        return null;
    }

    /**
     * Update Accuracy Matrix cache after successful OCR processing
     */
    public static function updateAccuracyMatrix(
        QuoteTypes $quoteType,
        Model $quote,
        OCRDocumentTypeEnum $docType,
        object $data,
        DocumentType $documentType
    ): void {
        $accuracyMatrixService = app(AccuracyMatrixCacheService::class);

        if (!$accuracyMatrixService->isEligibleQuote($quote, $quoteType)) {
            return;
        }

        if (!self::requiresOcrNotifications($docType)) {
            return;
        }

        $policyNumber = $accuracyMatrixService->extractPolicyNumberFromOcrData($data, $docType);
        $docId = self::generateDocumentId($quote, $documentType);

        $accuracyMatrixService->updateDocumentData(
            $quote->id,
            $quoteType->value,
            $docType,
            $docId,
            $policyNumber,
            true
        );
    }


    /**
     * Check if the document type requires OCR notifications.
     * Returns true for any document type that is enabled for OCR processing.
     */
    public function requiresOcrNotifications(OCRDocumentTypeEnum $docType): bool
    {
        // Get all enabled types across all quote types
        $allEnabledTypes = [];

        foreach (QuoteTypes::cases() as $quoteType) {
            $enabledTypes = OCRDocumentTypeEnum::getEnabledTypes($quoteType);
            // Convert enum objects to their string values for comparison
            $enabledTypeValues = array_map(fn($type) => $type->value, $enabledTypes);
            $allEnabledTypes = array_merge($allEnabledTypes, $enabledTypeValues);
        }

        // Remove duplicates and check if the document type's value is in the enabled list
        $allEnabledTypes = array_unique($allEnabledTypes);

        return in_array($docType->value, $allEnabledTypes);
    }

    private static function generateDocumentId(Model $quote, DocumentType $documentType): string
    {
        return "{$documentType->code}_{$quote->uuid}_" . uniqid();
    }

    public static function checkIfQuoteTypeIsGroupMedical(QuoteTypes $quoteType): QuoteTypes
    {
        if ($quoteType == QuoteTypes::GROUP_MEDICAL) {
            return QuoteTypes::BUSINESS; 
        }

        return $quoteType;
    }

    public static function isSendUpdateEligibleForOCR($quote, $isSendUpdate)
    {
        // Home & Group Medical only for Send Update, not allowed for other LOBs
        $allowedLOBs = [
            QuoteTypes::getId(QuoteTypes::HOME),
            QuoteTypes::getId(QuoteTypes::GROUP_MEDICAL)
        ];
        
        // Check if this is a Business quote (ID 5) that's actually Group Medical
        $isGroupMedicalBusiness = app(OCRService::class)->isGroupMedicalBusiness($quote);
        
        $isEligible = $isSendUpdate && $quote instanceof \App\Models\SendUpdateLog && 
                     (in_array($quote->quote_type_id, $allowedLOBs) || $isGroupMedicalBusiness);
        
        LoggerService::info('isSendUpdateEligibleForOCR - Eligibility Check', [
            'is_send_update' => $isSendUpdate,
            'is_send_update_log_instance' => $quote instanceof \App\Models\SendUpdateLog,
            'quote_type_id' => $quote->quote_type_id ?? 'N/A',
            'quote_uuid' => $quote->uuid ?? 'N/A',
            'quote_code' => $quote->code ?? 'N/A',
            'allowed_lob_ids' => $allowedLOBs,
            'is_lob_allowed' => $quote instanceof \App\Models\SendUpdateLog ? in_array($quote->quote_type_id, $allowedLOBs) : false,
            'is_group_medical_business' => $isGroupMedicalBusiness,
            'final_eligibility' => $isEligible,
            'eligibility_reason' => $isEligible ? 'Eligible for OCR' : self::getIneligibilityReason($quote, $isSendUpdate, $allowedLOBs),
        ]);
        
        return $isEligible;
    }
    
    private static function getIneligibilityReason($quote, $isSendUpdate, $allowedLOBs)
    {
        if (!$isSendUpdate) {
            return 'Not a Send Update';
        }
        
        if (!($quote instanceof SendUpdateLog)) {
            return 'Quote is not a SendUpdateLog instance';
        }
        
        if (!in_array($quote->quote_type_id, $allowedLOBs)) {
            return 'LOB not allowed for Send Update OCR (only HOME and GROUP_MEDICAL allowed)';
        }
        
        return 'Unknown reason';
    }
}

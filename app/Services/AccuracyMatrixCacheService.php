<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\InsurerProviderEnum;
use App\Enums\OCRDocumentTypeEnum;
use App\Enums\QuoteTypes;
use App\Services\Logger\LoggerService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AccuracyMatrixCacheService
{
    private const CACHE_PREFIX = 'accuracy_matrix';
    private const TTL_HOURS = 24;
    private const MANDATORY_DOC_TYPES = [
        'tax_invoice' => OCRDocumentTypeEnum::TAX_INVOICE,
        'tax_invoice_buyer' => OCRDocumentTypeEnum::TAX_INVOICE_RAISED_BY_BUYER,
        'policy_schedule' => OCRDocumentTypeEnum::MOTOR_INSURANCE_POLICY_SCHEDULE,
    ];
    private const ELIGIBLE_QUOTE_TYPES = [
        QuoteTypes::HOME,
        QuoteTypes::BUSINESS,
    ];
    private const ELIGIBLE_PROVIDERS = [
        InsurerProviderEnum::GIG_INSURANCE,
        InsurerProviderEnum::SUKOON_OMAN_INSURANCE,
        InsurerProviderEnum::TAKAFUL_EMARAT_INSURANCE,
        InsurerProviderEnum::ORIENT_INSURANCE,
        InsurerProviderEnum::NATIONAL_GENERAL_INSURANCE,
        InsurerProviderEnum::METLIFE_INSURANCE,
        InsurerProviderEnum::DUBAI_NATIONAL_INSURANCE,
        InsurerProviderEnum::DUBAI_INSURANCE_COMPANY,
        InsurerProviderEnum::CIGNA_INSURANCE,
        InsurerProviderEnum::SALAMA_INSURANCE,
    ];

    private function getCacheKey(int $quoteId, string $quoteType): string
    {
        $cacheKey = self::CACHE_PREFIX.":{$quoteId}:{$quoteType}";
        $cacheKey = str_replace(' ', '_', $cacheKey);
        LoggerService::info('AccuracyMatrixCacheService::getCacheKey', [
            'quote_id' => $quoteId,
            'quote_type' => $quoteType,
            'cache_key' => $cacheKey
        ]);
        return $cacheKey;
    }

    public function isEligibleQuote(Model $quote, QuoteTypes $quoteType): bool
    {
        // For Group Medical quotes, we need to check eligibility as if they were Business quotes
        $checkQuoteType = $quoteType;
        
        // If this is a Group Medical quote, check eligibility as a Business quote
        if ($quoteType === QuoteTypes::GROUP_MEDICAL) {
            $checkQuoteType = QuoteTypes::BUSINESS;
            LoggerService::info('AccuracyMatrixCacheService::isEligibleQuote - Group Medical detected, checking as Business', [
                'quote_id' => $quote->id,
                'original_quote_type' => $quoteType->value,
                'check_quote_type' => $checkQuoteType->value
            ]);
        }
        
        if (! in_array($checkQuoteType, self::ELIGIBLE_QUOTE_TYPES)) {
            LoggerService::info('AccuracyMatrixCacheService::isEligibleQuote - Quote type not eligible', [
                'quote_id' => $quote->id,
                'quote_type' => $quoteType->value,
                'check_quote_type' => $checkQuoteType->value,
                'eligible_types' => array_map(fn($type) => $type->value, self::ELIGIBLE_QUOTE_TYPES),
            ]);
            return false;
        }

        // Only check for Group Medical if it's a Business quote
        if ($checkQuoteType === QuoteTypes::BUSINESS) {
            // If it's already a Group Medical quote, then it passes this check
            if ($quoteType === QuoteTypes::GROUP_MEDICAL) {
                $isGroupMedical = true;
            } else {
                $isGroupMedical = $checkQuoteType->isGroupMedical($quote);
            }

            if (! $isGroupMedical) {
                LoggerService::info('AccuracyMatrixCacheService::isEligibleQuote - Not a Group Medical quote', [
                    'quote_id' => $quote->id,
                    'quote_type' => $quoteType->value,
                ]);
                return false;
            }
        }

        $providerCode = $this->getProviderCode($quote);
        $isEligible = $providerCode && in_array($providerCode, self::ELIGIBLE_PROVIDERS);

        LoggerService::info('AccuracyMatrixCacheService::isEligibleQuote - Provider eligibility check', [
            'quote_id' => $quote->id,
            'quote_type' => $quoteType->value,
            'provider_code' => $providerCode,
            'is_eligible' => $isEligible,
        ]);

        return $isEligible;
    }

    private function getProviderCode(Model $quote): ?string
    {
        LoggerService::info('AccuracyMatrixCacheService::getProviderCode - Start', [
            'quote_id' => $quote->id,
            'has_payments' => isset($quote->payments) && $quote->payments !== null,
            'payments_count' => isset($quote->payments) ? $quote->payments->count() : 0
        ]);

        if ($quote->payments && $quote->payments->isNotEmpty()) {
            $latestPayment = $quote->payments->first();
            
            LoggerService::info('AccuracyMatrixCacheService::getProviderCode - Payment found', [
                'payment_id' => $latestPayment->id ?? null,
                'has_insurance_provider' => isset($latestPayment->insuranceProvider) && $latestPayment->insuranceProvider !== null
            ]);
            
            if ($latestPayment && $latestPayment->insuranceProvider) {
                $providerCode = $latestPayment->insuranceProvider->code;
                
                LoggerService::info('AccuracyMatrixCacheService::getProviderCode - Provider found', [
                    'provider_code' => $providerCode,
                    'is_eligible' => in_array($providerCode, self::ELIGIBLE_PROVIDERS)
                ]);
                
                return $providerCode;
            }
        }

        LoggerService::info('AccuracyMatrixCacheService::getProviderCode - No provider found', []);
        return null;
    }

    public function updateDocumentData(
        int $quoteId,
        string $quoteType,
        OCRDocumentTypeEnum $documentType,
        string $docId,
        ?string $policyNumber,
        bool $ocrCompleted = false
    ): void {
        // Debug log to check if this method is being called
        LoggerService::info('AccuracyMatrixCacheService::updateDocumentData called', [
            'quote_id' => $quoteId,
            'quote_type' => $quoteType,
            'doc_type' => $documentType->value,
            'policy_number' => $policyNumber,
            'ocr_completed' => $ocrCompleted,
        ]);
        
        // Validate inputs
        if ($quoteId <= 0 || empty($quoteType) || empty($docId)) {
            LoggerService::info('AccuracyMatrixCacheService::updateDocumentData - Invalid inputs', [
                'quote_id' => $quoteId,
                'quote_type' => $quoteType,
                'doc_id' => $docId,
            ]);
            return;
        }
        
        // If this is a Group Medical quote, store it as Business for accuracy matrix
        $cacheQuoteType = $quoteType;
        if ($quoteType === QuoteTypes::GROUP_MEDICAL->value) {
            $cacheQuoteType = QuoteTypes::BUSINESS->value;
            LoggerService::info('AccuracyMatrixCacheService::updateDocumentData - Group Medical detected, storing as Business', [
                'quote_id' => $quoteId,
                'original_quote_type' => $quoteType,
                'cache_quote_type' => $cacheQuoteType
            ]);
        }

        $cacheKey = $this->getCacheKey($quoteId, $cacheQuoteType);
        $data = Cache::get($cacheKey, []);

        $docTypeKey = $this->getDocumentTypeKey($documentType);
        if (! $docTypeKey) {
            return;
        }

        if (! isset($data['quote_id'])) {
            $data = $this->initializeCacheStructure($quoteId, $quoteType);
        }

        // Clean and validate policy number
        $cleanPolicyNumber = ! empty($policyNumber) ? trim($policyNumber) : null;

        $data['mandatory_docs'][$docTypeKey] = [
            'doc_id' => $docId,
            'policy_number' => $cleanPolicyNumber,
            'ocr_completed' => $ocrCompleted,
            'uploaded_at' => now()->toISOString(),
        ];

        $data = $this->validateAccuracyMatrix($data);

        Cache::put($cacheKey, $data, now()->addHours(self::TTL_HOURS));
    }

    public function removeDocument(
        int $quoteId,
        string $quoteType,
        OCRDocumentTypeEnum $documentType
    ): void {
        // If this is a Group Medical quote, store it as Business for accuracy matrix
        $cacheQuoteType = $quoteType;
        if ($quoteType === QuoteTypes::GROUP_MEDICAL->value) {
            $cacheQuoteType = QuoteTypes::BUSINESS->value;
            LoggerService::info('AccuracyMatrixCacheService::removeDocument - Group Medical detected, using Business cache', [
                'quote_id' => $quoteId,
                'original_quote_type' => $quoteType,
                'cache_quote_type' => $cacheQuoteType
            ]);
        }
        
        $cacheKey = $this->getCacheKey($quoteId, $cacheQuoteType);
        $data = Cache::get($cacheKey, []);

        if (empty($data)) {
            return;
        }

        $docTypeKey = $this->getDocumentTypeKey($documentType);
        if (! $docTypeKey) {
            return;
        }

        unset($data['mandatory_docs'][$docTypeKey]);

        $data = $this->validateAccuracyMatrix($data);

        if (empty($data['mandatory_docs'])) {
            Cache::forget($cacheKey);
        } else {
            Cache::put($cacheKey, $data, now()->addHours(self::TTL_HOURS));
        }
    }

    public function getMatrixStatus(int $quoteId, string $quoteType): ?array
    {
        LoggerService::info('AccuracyMatrixCacheService::getMatrixStatus - Start', [
            'quote_id' => $quoteId,
            'quote_type' => $quoteType
        ]);
        
        // If this is a Group Medical quote, check the Business cache
        $cacheQuoteType = $quoteType;
        if ($quoteType === QuoteTypes::GROUP_MEDICAL->value) {
            $cacheQuoteType = QuoteTypes::BUSINESS->value;
            LoggerService::info('AccuracyMatrixCacheService::getMatrixStatus - Group Medical detected, checking Business cache', [
                'quote_id' => $quoteId,
                'original_quote_type' => $quoteType,
                'cache_quote_type' => $cacheQuoteType
            ]);
        }
        
        $cacheKey = $this->getCacheKey($quoteId, $cacheQuoteType);
        $data = Cache::get($cacheKey);

        if (! $data) {
            LoggerService::info('AccuracyMatrixCacheService::getMatrixStatus - No data in cache', [
                'cache_key' => $cacheKey
            ]);
            return null;
        }
        
        if (! isset($data['matrix_status'])) {
            LoggerService::info('AccuracyMatrixCacheService::getMatrixStatus - No matrix status in data', [
                'cache_key' => $cacheKey,
                'data_keys' => array_keys($data)
            ]);
            return null;
        }

        LoggerService::info('AccuracyMatrixCacheService::getMatrixStatus - Matrix status found', [
            'cache_key' => $cacheKey,
            'matrix_status' => $data['matrix_status']
        ]);
        
        return $data['matrix_status'];
    }

    public function clearValidation(int $quoteId, string $quoteType): void
    {
        // If this is a Group Medical quote, clear the Business cache
        $cacheQuoteType = $quoteType;
        if ($quoteType === QuoteTypes::GROUP_MEDICAL->value) {
            $cacheQuoteType = QuoteTypes::BUSINESS->value;
            LoggerService::info('AccuracyMatrixCacheService::clearValidation - Group Medical detected, clearing Business cache', [
                'quote_id' => $quoteId,
                'original_quote_type' => $quoteType,
                'cache_quote_type' => $cacheQuoteType
            ]);
        }
        
        $cacheKey = $this->getCacheKey($quoteId, $cacheQuoteType);
        Cache::forget($cacheKey);
    }

    private function initializeCacheStructure(int $quoteId, string $quoteType): array
    {
        return [
            'quote_id' => $quoteId,
            'quote_type' => $quoteType,
            'mandatory_docs' => [],
            'matrix_status' => [
                'show_matrix' => false,
                'status' => 'hidden',
                'is_valid' => false,
                'tooltip' => '',
                'last_validated' => null,
            ],
        ];
    }

    private function validateAccuracyMatrix(array $data): array
    {
        $quoteId = $data['quote_id'] ?? null;
        $quoteType = $data['quote_type'] ?? null;
        
        LoggerService::info('AccuracyMatrixCacheService::validateAccuracyMatrix - Start', [
            'quote_id' => $quoteId,
            'quote_type' => $quoteType
        ]);
        
        $mandatoryDocs = $data['mandatory_docs'] ?? [];
        $allDocsPresent = count($mandatoryDocs) === count(self::MANDATORY_DOC_TYPES);
        $allOcrCompleted = true;
        $policyNumbers = [];

        LoggerService::info('AccuracyMatrixCacheService::validateAccuracyMatrix - Document count check', [
            'mandatory_docs_count' => count($mandatoryDocs),
            'required_docs_count' => count(self::MANDATORY_DOC_TYPES),
            'all_docs_present' => $allDocsPresent,
            'available_docs' => array_keys($mandatoryDocs),
            'required_docs' => array_keys(self::MANDATORY_DOC_TYPES)
        ]);

        foreach ($mandatoryDocs as $docKey => $docData) {
            LoggerService::info('AccuracyMatrixCacheService::validateAccuracyMatrix - Checking document', [
                'doc_key' => $docKey,
                'doc_id' => $docData['doc_id'] ?? null,
                'ocr_completed' => $docData['ocr_completed'] ?? false,
                'policy_number' => $docData['policy_number'] ?? null
            ]);
            
            if (! $docData['ocr_completed']) {
                $allOcrCompleted = false;
                LoggerService::info('AccuracyMatrixCacheService::validateAccuracyMatrix - OCR not completed for document', [
                    'doc_key' => $docKey
                ]);
                break;
            }

            $policyNumber = trim($docData['policy_number'] ?? '');
            if (empty($policyNumber)) {
                $allOcrCompleted = false;
                LoggerService::info('AccuracyMatrixCacheService::validateAccuracyMatrix - Policy number missing', [
                    'doc_key' => $docKey
                ]);
                break;
            }

            $policyNumbers[] = $policyNumber;
        }

        if (! $allDocsPresent || ! $allOcrCompleted) {
            $data['matrix_status'] = [
                'show_matrix' => false,
                'status' => 'hidden',
                'is_valid' => false,
                'tooltip' => '',
                'last_validated' => now()->toISOString(),
            ];
            
            LoggerService::info('AccuracyMatrixCacheService::validateAccuracyMatrix - Matrix hidden', [
                'all_docs_present' => $allDocsPresent,
                'all_ocr_completed' => $allOcrCompleted,
                'reason' => !$allDocsPresent ? 'Missing documents' : 'OCR incomplete or policy number missing'
            ]);
        } else {
            $uniquePolicyNumbers = array_unique($policyNumbers);
            $policyNumbersMatch = count($uniquePolicyNumbers) === 1;

            $data['matrix_status'] = [
                'show_matrix' => true,
                'status' => $policyNumbersMatch ? 'green' : 'red',
                'is_valid' => $policyNumbersMatch,
                'tooltip' => $policyNumbersMatch
                    ? 'Policy Number matched on the Uploaded Documents'
                    : 'Policy Number not matched on the Uploaded Documents',
                'last_validated' => now()->toISOString(),
            ];
            
            LoggerService::info('AccuracyMatrixCacheService::validateAccuracyMatrix - Matrix visible', [
                'policy_numbers' => $policyNumbers,
                'unique_policy_numbers' => $uniquePolicyNumbers,
                'policy_numbers_match' => $policyNumbersMatch,
                'status' => $policyNumbersMatch ? 'green' : 'red'
            ]);
        }

        return $data;
    }

    private function getDocumentTypeKey(OCRDocumentTypeEnum $documentType): ?string
    {
        $key = match ($documentType) {
            OCRDocumentTypeEnum::TAX_INVOICE => 'tax_invoice',
            OCRDocumentTypeEnum::TAX_INVOICE_RAISED_BY_BUYER => 'tax_invoice_buyer',
            OCRDocumentTypeEnum::MOTOR_INSURANCE_POLICY_SCHEDULE => 'policy_schedule',
            OCRDocumentTypeEnum::POLICY_SCHEDULE => 'policy_schedule',
            default => null,
        };
        
        LoggerService::info('AccuracyMatrixCacheService::getDocumentTypeKey', [
            'document_type' => $documentType->value,
            'document_type_key' => $key,
            'is_mandatory' => $key !== null
        ]);
        
        return $key;
    }

    public function extractPolicyNumberFromOcrData(object $data, OCRDocumentTypeEnum $documentType): ?string
    {
        LoggerService::info('AccuracyMatrixCacheService::extractPolicyNumberFromOcrData - Start', [
            'document_type' => $documentType->value,
            'data_properties' => get_object_vars($data)
        ]);
        
        $policyNumber = match ($documentType) {
            OCRDocumentTypeEnum::TAX_INVOICE => $this->resolveProp($data, 'policyNumber')
                ?? $this->resolveProp($data, 'taxInvoiceNumber')
                ?? $this->resolveProp($data, 'insurancePolicyNumber'),
            OCRDocumentTypeEnum::TAX_INVOICE_RAISED_BY_BUYER => $this->resolveProp($data, 'policyNumber')
                ?? $this->resolveProp($data, 'taxInvoiceNumber')
                ?? $this->resolveProp($data, 'insurancePolicyNumber'),
            OCRDocumentTypeEnum::MOTOR_INSURANCE_POLICY_SCHEDULE => $this->resolveProp($data, 'policyNumber')
                ?? $this->resolveProp($data, 'policy_number')
                ?? $this->resolveProp($data, 'insurancePolicyNumber'),
            OCRDocumentTypeEnum::POLICY_SCHEDULE => $this->resolveProp($data, 'policyNumber')
                ?? $this->resolveProp($data, 'policy_number')
                ?? $this->resolveProp($data, 'insurancePolicyNumber'),
            default => null,
        };
        
        LoggerService::info('AccuracyMatrixCacheService::extractPolicyNumberFromOcrData - Result', [
            'document_type' => $documentType->value,
            'extracted_policy_number' => $policyNumber
        ]);
        
        return $policyNumber;
    }

    private function resolveProp(object $data, string $property): mixed
    {
        $value = $data->{$property} ?? null;
        
        LoggerService::info('AccuracyMatrixCacheService::resolveProp', [
            'property' => $property,
            'value_exists' => $value !== null,
            'value_type' => $value !== null ? gettype($value) : null,
            'value' => $value
        ]);
        
        return $value;
    }
}

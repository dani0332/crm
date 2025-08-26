<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\InsurerProviderEnum;
use App\Enums\OCRDocumentTypeEnum;
use App\Enums\QuoteTypes;
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
        return self::CACHE_PREFIX.":{$quoteId}:{$quoteType}";
    }

    public function isEligibleQuote(Model $quote, QuoteTypes $quoteType): bool
    {
        if (! in_array($quoteType, self::ELIGIBLE_QUOTE_TYPES)) {
            return false;
        }

        // Only check for Group Medical if it's a Business quote
        if ($quoteType === QuoteTypes::BUSINESS) {
            $isGroupMedical = $quoteType->isGroupMedical($quote);

            if (! $isGroupMedical) {
                return false;
            }
        }

        $providerCode = $this->getProviderCode($quote);
        $isEligible = $providerCode && in_array($providerCode, self::ELIGIBLE_PROVIDERS);

        return $isEligible;
    }

    private function getProviderCode(Model $quote): ?string
    {
        if ($quote->payments && $quote->payments->isNotEmpty()) {
            $latestPayment = $quote->payments->first();
            if ($latestPayment && $latestPayment->insuranceProvider) {
                return $latestPayment->insuranceProvider->code;
            }
        }

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
        // Validate inputs
        if ($quoteId <= 0 || empty($quoteType) || empty($docId)) {
            return;
        }

        $cacheKey = $this->getCacheKey($quoteId, $quoteType);
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
        $cacheKey = $this->getCacheKey($quoteId, $quoteType);
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
        $cacheKey = $this->getCacheKey($quoteId, $quoteType);
        $data = Cache::get($cacheKey);

        if (! $data || ! isset($data['matrix_status'])) {
            return null;
        }

        return $data['matrix_status'];
    }

    public function clearValidation(int $quoteId, string $quoteType): void
    {
        $cacheKey = $this->getCacheKey($quoteId, $quoteType);
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
        $mandatoryDocs = $data['mandatory_docs'] ?? [];
        $allDocsPresent = count($mandatoryDocs) === count(self::MANDATORY_DOC_TYPES);
        $allOcrCompleted = true;
        $policyNumbers = [];

        foreach ($mandatoryDocs as $docKey => $docData) {
            if (! $docData['ocr_completed']) {
                $allOcrCompleted = false;
                break;
            }

            $policyNumber = trim($docData['policy_number'] ?? '');
            if (empty($policyNumber)) {
                $allOcrCompleted = false;
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
        }

        return $data;
    }

    private function getDocumentTypeKey(OCRDocumentTypeEnum $documentType): ?string
    {
        return match ($documentType) {
            OCRDocumentTypeEnum::TAX_INVOICE => 'tax_invoice',
            OCRDocumentTypeEnum::TAX_INVOICE_RAISED_BY_BUYER => 'tax_invoice_buyer',
            OCRDocumentTypeEnum::MOTOR_INSURANCE_POLICY_SCHEDULE => 'policy_schedule',
            default => null,
        };
    }

    public function extractPolicyNumberFromOcrData(object $data, OCRDocumentTypeEnum $documentType): ?string
    {
        return match ($documentType) {
            OCRDocumentTypeEnum::TAX_INVOICE => $this->resolveProp($data, 'policyNumber')
                ?? $this->resolveProp($data, 'taxInvoiceNumber')
                ?? $this->resolveProp($data, 'insurancePolicyNumber'),
            OCRDocumentTypeEnum::TAX_INVOICE_RAISED_BY_BUYER => $this->resolveProp($data, 'policyNumber')
                ?? $this->resolveProp($data, 'taxInvoiceNumber')
                ?? $this->resolveProp($data, 'insurancePolicyNumber'),
            OCRDocumentTypeEnum::MOTOR_INSURANCE_POLICY_SCHEDULE => $this->resolveProp($data, 'policyNumber')
                ?? $this->resolveProp($data, 'policy_number')
                ?? $this->resolveProp($data, 'insurancePolicyNumber'),
            default => null,
        };
    }

    private function resolveProp(object $data, string $property): mixed
    {
        return $data->{$property} ?? null;
    }
}

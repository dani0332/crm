<?php

declare(strict_types=1);

namespace App\Services\OCR;

use App\Models\DocumentType;
use App\Models\OcrLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class OcrLogService
{
    public function logActivity(
        Model $quote,
        DocumentType $documentType,
        string $status,
        ?array $requestData = null,
        ?array $responseData = null,
        ?float $executionTimeMs = null,
        ?string $errorMessage = null,
        int $userId = 0
    ): void {
        try {
            $providerId = $this->extractProviderId($quote);
            $uploadedThrough = $this->determineUploadSource($userId);
            
            $documentTypeCode = $documentType->code ?? 'UNKNOWN';
            $documentTypeName = $documentType->text ?? 'Unknown Document Type';

            OcrLog::create([
                'ocr_loggable_type' => get_class($quote),
                'ocr_loggable_id' => $quote->id,
                'document_type_code' => $documentTypeCode,
                'document_type_name' => $documentTypeName,
                'status' => $status,
                'request_data' => $requestData,
                'response_data' => $responseData,
                'execution_time_ms' => $executionTimeMs,
                'error_message' => $errorMessage,
                'provider_id' => $providerId,
                'user_id' => $userId,
                'uploaded_through' => $uploadedThrough,
            ]);

            $this->logToSystemLog($quote, $documentType, $status, $userId, $uploadedThrough);
        } catch (\Exception $e) {
            $this->logError($quote, $e);
        }
    }

    private function extractProviderId(Model $quote): ?int
    {
        if ($quote->payments && $quote->payments->isNotEmpty()) {
            $latestPayment = $quote->payments->first();
            if ($latestPayment && $latestPayment->insuranceProvider) {
                return $latestPayment->insuranceProvider->id;
            }
        }
        
        return null;
    }

    private function determineUploadSource(int $userId): string
    {
        return $userId ? 'IMCRM' : 'Other than IMCRM';
    }

    private function logToSystemLog(
        Model $quote, 
        DocumentType $documentType, 
        string $status, 
        int $userId, 
        string $uploadedThrough
    ): void {
        Log::info('OCR activity logged - Quote UUID: ' . $quote->uuid, [
            'quote_id' => $quote->id,
            'document_type' => $documentType->code,
            'status' => $status,
            'user_id' => $userId,
            'uploaded_through' => $uploadedThrough,
        ]);
    }

    private function logError(Model $quote, \Exception $e): void
    {
        Log::error('Failed to log OCR activity - Quote UUID: ' . $quote->uuid, [
            'quote_id' => $quote->id,
            'error' => $e->getMessage(),
        ]);
    }
}
<?php

namespace App\Services\OCR\Passport;

use App\Models\PassportVisaDetail;
use App\Services\Logger\LoggerService;
use App\Services\OCR\OcrUtils;
use App\Services\OCR\Validators\OCRDocumentValidator;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PassportDataProcessor
{
    use OcrUtils;

    private PassportExtractor $passportExtractor;
    private array $extractedData = [];

    public function __construct(
        private Model $quote,
        private object $data,
        private string $documentTypeCode,
        private int $memberDetailId
    ) {
        $this->passportExtractor = new PassportExtractor($this->data);
    }

    public function processPassportData(): bool
    {
        try {
            DB::beginTransaction();

            $this->extractedData = $this->passportExtractor->extractPassportData()->getExtractedData();

            LoggerService::info('Passport data processor started');

            $this->addOrUpdatePassportNumber();

            LoggerService::info('Passport data processor completed', [
                'extracted_data' => $this->extractedData,
                'document_type_code' => $this->documentTypeCode,
            ]);

            $ocrDocumentValidator = app()->make(OCRDocumentValidator::class, [
                'quoteId' => $this->quote->id,
                'quoteableType' => get_class($this->quote),
            ]);

            $isOCRSuccess = $ocrDocumentValidator->validatePassportFields($this->documentTypeCode, $this->memberDetailId);
            LoggerService::info('Passport data validation result for document type: '.$this->documentTypeCode.' is: '.($isOCRSuccess ? 'true' : 'false'), json_encode($this->extractedData));

            DB::commit();

            return true;
        } catch (Exception $e) {
            DB::rollBack();

            LoggerService::error('Passport data processing failed', exception: $e);

            return false;
        }
    }

    private function addOrUpdatePassportNumber(): void
    {
        PassportVisaDetail::updateOrCreate(
            [
                'quoteable_type' => get_class($this->quote),
                'quoteable_id' => $this->quote->id,
                'customer_member_id' => $this->memberDetailId > 0 ? $this->memberDetailId : null,
            ],
            [
                'passport_number' => $this->extractedData['passport_number'] ?? null,
                'passport_country' => $this->extractedData['passport_country'] ?? null,
                'passport_expiry_date' => $this->extractedData['passport_expiry_date'] ?? null,
            ]
        );
    }
}

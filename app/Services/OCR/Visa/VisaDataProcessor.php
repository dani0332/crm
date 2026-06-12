<?php

namespace App\Services\OCR\Visa;

use App\Models\PassportVisaDetail;
use App\Services\Logger\LoggerService;
use App\Services\OCR\OcrUtils;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class VisaDataProcessor
{
    use OcrUtils;

    private VisaExtractor $visaExtractor;
    private array $extractedData = [];

    public function __construct(
        private Model $quote,
        private object $data,
        private string $documentTypeCode,
        private int $memberDetailId
    ) {
        $this->visaExtractor = new VisaExtractor($this->data);
    }

    public function processVisaData(): bool
    {
        try {
            DB::beginTransaction();

            $this->extractedData = $this->visaExtractor->extractVisaData()->getExtractedData();

            LoggerService::info('Visa data processor started');

            $this->addOrUpdateVisaDetails();

            LoggerService::info('Visa data processor completed', [
                'extracted_data' => $this->extractedData,
                'document_type_code' => $this->documentTypeCode,
            ]);

            DB::commit();

            return true;

        } catch (Exception $e) {
            DB::rollBack();
            LoggerService::error('Visa data processing failed', exception: $e);

            return false;
        }
    }

    private function addOrUpdateVisaDetails(): void
    {
        PassportVisaDetail::updateOrCreate([
            'quoteable_type' => get_class($this->quote),
            'quoteable_id' => $this->quote->id,
            'customer_member_id' => $this->memberDetailId,
        ], [
            'name' => $this->extractedData['name'] ?? null,
            'visa_number' => $this->extractedData['visa_number'] ?? null,
            'visa_file_number' => $this->extractedData['visa_file_number'] ?? null,
            'visa_type' => $this->extractedData['visa_type'] ?? null,
            'visa_issue_date' => $this->extractedData['visa_issue_date'] ?? null,
            'visa_expiry_date' => $this->extractedData['visa_expiry_date'] ?? null,
            'visa_issuance_authority' => $this->extractedData['visa_issuance_authority'] ?? null,
            'profession' => $this->extractedData['profession'] ?? null,
            'sponsor' => $this->extractedData['sponsor'] ?? null,
        ]);
    }
}

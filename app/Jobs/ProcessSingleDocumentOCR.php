<?php

namespace App\Jobs;

use App\Enums\ApplicationStorageEnums;
use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\OCRDocumentTypeEnum;
use App\Enums\OCRSourceEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Models\CarQuote;
use App\Models\DocumentType;
use App\Models\Nationality;
use App\Models\OCRResponseData;
use App\Models\QuoteDocument;
use App\Services\ApplicationStorageService;
use App\Services\Logger\LoggerService;
use App\Services\LookupService;
use App\Services\QuoteDocumentService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ProcessSingleDocumentOCR implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const OCR_UTIL_FEAT = 'OCR UTIL FEATURE';

    public $timeout = 120;
    public $tries = 3;
    public $backoff = [10, 30, 60];

    public function __construct(
        private int $quoteId,
        private int $documentId,
        private string $documentTypeCode
    ) {
        $this->onQueue('ocr_dedicated');
    }

    public function handle(): void
    {
        if (getAppStorageValueByKey(ApplicationStorageEnums::OCR_UTIL_ENABLED) != '1') {
            LoggerService::warning(self::OCR_UTIL_FEAT.' - '.self::class.' - OCR util processing is disabled, skipping document processing', extra: [
                'quote_id' => $this->quoteId,
                'document_id' => $this->documentId,
                'message' => 'OCR util processing has been disabled via application_storages flag',
            ]);

            return;
        }

        $document = QuoteDocument::find($this->documentId);

        if (! $document) {
            LoggerService::info(self::OCR_UTIL_FEAT.' - '.self::class.' - Document not found, skipping', [
                'document_id' => $this->documentId,
            ]);

            return;
        }

        $quote = CarQuote::with([
            'payments' => function ($q) {
                $q->latest('created_at')
                    ->take(1)
                    ->select('id', 'paymentable_id', 'paymentable_type', 'insurance_provider_id', 'created_at')
                    ->with(['insuranceProvider:id,code']);
            },
            'insuranceProvider:id,code',
            'personalQuote:id,uuid,quote_id,quote_type_id,policy_number,policy_start_date,policy_expiry_date,policy_issuance_date',
        ])->find($this->quoteId);

        if (! $quote) {
            LoggerService::info(self::OCR_UTIL_FEAT.' - '.self::class.' - Quote not found, skipping', [
                'quote_id' => $this->quoteId,
            ]);

            return;
        }

        LoggerService::startQuoteLogging($quote, LoggerFeatureEnum::LEAD_OCR_DATA_COMPARISON);

        $documentType = DocumentType::where('code', $document->document_type_code)
            ->where('quote_type_id', QuoteTypes::CAR->id())
            ->first();

        if (! $documentType) {
            LoggerService::info(self::OCR_UTIL_FEAT.' - '.self::class.' - DocumentType not found', [
                'quote_id' => $quote->id,
                'document_type_code' => $document->document_type_code,
            ]);

            return;
        }

        $isDocOCREnabled = OCRDocumentTypeEnum::isOCREnabled($documentType, QuoteTypes::CAR);

        if (! $isDocOCREnabled) {
            LoggerService::info(self::OCR_UTIL_FEAT.' - '.self::class.' - Document type not OCR enabled', [
                'quote_id' => $quote->id,
                'document_type_code' => $document->document_type_code,
            ]);

            return;
        }

        $ocrDocType = OCRDocumentTypeEnum::getDocumentType($documentType);

        LoggerService::info(self::OCR_UTIL_FEAT.' - '.self::class.' - Starting document processing', [
            'quote_id' => $quote->id,
            'quote_uuid' => $quote->uuid,
            'document_id' => $document->id,
            'doc_type' => $ocrDocType->value,
        ]);

        $leadDataStructure = $this->getLeadDataStructure($ocrDocType->value, $quote);
        $ocrDataStructure = [];
        $ocrResponseData = null;

        $ocrRecord = OCRResponseData::where('quoteable_id', $quote->id)
            ->where('quoteable_type', QuoteTypes::CAR->modelClass())
            ->first();

        if ($ocrRecord) {
            $ocrResponseJson = json_decode($ocrRecord->ocr_response, true);
            $ocrDataJson = json_decode($ocrRecord->ocr_data, true);

            if (isset($ocrResponseJson[$ocrDocType->value]) && isset($ocrDataJson[$ocrDocType->value])) {
                $ocrResponseData = $ocrResponseJson[$ocrDocType->value];
                $ocrDataStructure = $ocrDataJson[$ocrDocType->value];

                LoggerService::info(self::OCR_UTIL_FEAT.' - '.self::class.' - Using cached OCR data from database', [
                    'quote_id' => $quote->id,
                    'doc_type' => $ocrDocType->value,
                ]);
            } else {
                $ocrData = $this->callOcrApi($quote, $document, $ocrDocType->value);

                if ($ocrData) {
                    $ocrResponseData = [
                        'status' => 'true',
                        'response' => json_encode($ocrData),
                        'reason' => 'Success',
                    ];
                    $ocrDataStructure = $this->getOcrDataStructure($ocrDocType->value, $ocrData, $quote);
                } else {
                    $ocrResponseData = [
                        'status' => 'false',
                        'response' => json_encode([]),
                        'reason' => 'OCR API failed or document URL unavailable',
                    ];
                    $ocrDataStructure = [];
                }
            }
        } else {
            $ocrData = $this->callOcrApi($quote, $document, $ocrDocType->value);

            if ($ocrData) {
                $ocrResponseData = [
                    'status' => 'true',
                    'response' => json_encode($ocrData),
                    'reason' => 'Success',
                ];
                $ocrDataStructure = $this->getOcrDataStructure($ocrDocType->value, $ocrData, $quote);
            } else {
                $ocrResponseData = [
                    'status' => 'false',
                    'response' => json_encode([]),
                    'reason' => 'OCR API failed or document URL unavailable',
                ];
                $ocrDataStructure = [];
            }
        }

        DB::table('temp_ocr_document_results')->updateOrInsert(
            [
                'quote_id' => $this->quoteId,
                'document_id' => $this->documentId,
            ],
            [
                'doc_type' => $ocrDocType->value,
                'lead_data' => json_encode($leadDataStructure),
                'ocr_data' => json_encode($ocrDataStructure),
                'ocr_response' => json_encode($ocrResponseData),
                'processed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        if (! empty($ocrDataStructure)) {
            LoggerService::info(self::OCR_UTIL_FEAT.' - '.self::class.' - Document processing completed', [
                'quote_id' => $quote->id,
                'document_id' => $document->id,
                'doc_type' => $ocrDocType->value,
            ]);
        } else {
            LoggerService::warning(self::OCR_UTIL_FEAT.' - '.self::class.' - Document processing completed with no OCR data (failed or skipped)', [
                'quote_id' => $quote->id,
                'document_id' => $document->id,
                'doc_type' => $ocrDocType->value,
                'ocr_response_status' => $ocrResponseData['status'] ?? 'unknown',
            ]);
        }

        $this->checkAndTriggerAggregation($quote);
    }

    private function checkAndTriggerAggregation(CarQuote $quote): void
    {
        $totalDocuments = $quote->documents()->whereIn('document_type_code', $this->getOcrDocumentCodes())->count();

        $lock = Cache::lock('ocr_aggregation_dispatch_'.$this->quoteId, 10);

        if ($lock->get()) {
            try {
                $processedDocuments = DB::table('temp_ocr_document_results')
                    ->where('quote_id', $this->quoteId)
                    ->count();

                LoggerService::info(self::OCR_UTIL_FEAT.' - '.self::class.' - Checking aggregation trigger', [
                    'quote_id' => $this->quoteId,
                    'total_documents' => $totalDocuments,
                    'processed_documents' => $processedDocuments,
                ]);

                if ($processedDocuments >= $totalDocuments && $processedDocuments > 0) {
                    $alreadyDispatched = Cache::get('ocr_aggregation_dispatched_'.$this->quoteId);

                    if ($alreadyDispatched) {
                        LoggerService::info(self::OCR_UTIL_FEAT.' - '.self::class.' - Aggregation already dispatched, skipping', [
                            'quote_id' => $this->quoteId,
                        ]);

                        return;
                    }

                    Cache::put('ocr_aggregation_dispatched_'.$this->quoteId, true, now()->addMinutes(10));

                    LoggerService::info(self::OCR_UTIL_FEAT.' - '.self::class.' - All documents processed, triggering aggregation', [
                        'quote_id' => $this->quoteId,
                    ]);

                    AggregateQuoteOCRComparison::dispatch($this->quoteId)
                        ->onQueue('lead_ocr_data_comparison')
                        ->delay(now()->addSeconds(5));
                }
            } finally {
                $lock->release();
            }
        } else {
            LoggerService::info(self::OCR_UTIL_FEAT.' - '.self::class.' - Could not acquire lock for aggregation check, another worker is handling it', [
                'quote_id' => $this->quoteId,
            ]);
        }
    }

    private function callOcrApi(CarQuote $quote, QuoteDocument $document, string $docType): ?object
    {
        $providerCode = $this->extractProviderCode($quote);
        $refId = $quote->code;
        $isEcom = false;

        $docUrl = app(QuoteDocumentService::class)->getDocumentUrl($document->doc_url);

        if (! $docUrl) {
            LoggerService::warning(self::OCR_UTIL_FEAT.' - '.self::class.' - Failed to get document URL', [
                'quote_uuid' => $quote->uuid,
                'document_id' => $document->id,
                'doc_url' => $document->doc_url,
            ]);

            return null;
        }

        $requestData = [
            'ref_id' => $refId,
            'uuid' => $quote->uuid,
            'quote_type_id' => QuoteTypes::CAR->id(),
            'doc_url' => $docUrl,
            'doc_type' => $docType,
            'provider_code' => $providerCode,
            'image' => false,
        ];

        LoggerService::info(self::OCR_UTIL_FEAT.' - '.self::class.' - Calling OCR API', [
            'quote_uuid' => $quote->uuid,
            'doc_type' => $docType,
            'provider_code' => $providerCode,
        ]);

        try {
            $response = Http::baseUrl(config('constants.OCR_API_ENDPOINT'))
                ->withHeader('Referer', trim(config('constants.APP_URL'), '/'))
                ->withHeader('x-api-key', config('constants.OCR_API_KEY'))
                ->withHeader('source', $isEcom ? OCRSourceEnum::ECOM->value : OCRSourceEnum::IMCRM->value)
                ->timeout(config('constants.OCR_API_TIMEOUT'))
                ->post('/process-document', $requestData);

            $responseData = $response->json();

            if ($response->successful()) {
                LoggerService::info(self::OCR_UTIL_FEAT.' - '.self::class.' - OCR API call successful', [
                    'quote_uuid' => $quote->uuid,
                    'document_id' => $document->id,
                    'doc_type' => $docType,
                ]);

                return json_decode(json_encode($responseData));
            }

            LoggerService::warning(self::OCR_UTIL_FEAT.' - '.self::class.' - OCR API call failed', [
                'quote_uuid' => $quote->uuid,
                'document_id' => $document->id,
                'response_status' => $response->status(),
            ]);

            return null;
        } catch (\Exception $e) {
            LoggerService::warning(self::OCR_UTIL_FEAT.' - '.self::class.' - OCR API call exception', [
                'quote_uuid' => $quote->uuid,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function extractProviderCode(CarQuote $quote): ?string
    {
        if ($quote->payments && $quote->payments->isNotEmpty()) {
            $latestPayment = $quote->payments->first();
            if ($latestPayment && $latestPayment->insuranceProvider) {
                return $latestPayment->insuranceProvider->code;
            }
        }

        return $quote->insuranceProvider?->code;
    }

    private function getOcrDocumentCodes(): array
    {
        return DocumentType::query()
            ->active()
            ->byQuoteTypeId(QuoteTypes::CAR->id())
            ->get()
            ->filter(fn (DocumentType $documentType) => OCRDocumentTypeEnum::getDocumentType($documentType) !== null)
            ->pluck('code')
            ->values()
            ->all();
    }

    private function getLeadDataStructure(string $ocrDocType, $quote)
    {
        switch ($ocrDocType) {
            case OCRDocumentTypeEnum::ID_CARD->value:
                return $this->getEmiratesIdLeadDataStructure($quote);
            case OCRDocumentTypeEnum::DRIVING_LICENSE->value:
                return $this->getDrivingLicenseLeadDataStructure($quote);
            case OCRDocumentTypeEnum::REGISTRATION_CERTIFICATE->value:
                return $this->getVehicleRegistrationCertificateLeadDataStructure($quote);
            case OCRDocumentTypeEnum::TAX_INVOICE->value:
                return $this->getTaxInvoiceLeadDataStructure($quote);
            case OCRDocumentTypeEnum::TAX_INVOICE_RAISED_BY_BUYER->value:
                return $this->getTaxInvoiceRaisedByBuyerLeadDataStructure($quote);
            case OCRDocumentTypeEnum::MOTOR_INSURANCE_POLICY_SCHEDULE->value:
                return $this->getMotorInsurancePolicyScheduleLeadDataStructure($quote);
            case OCRDocumentTypeEnum::CERTIFICATE_OF_ISSUANCE->value:
                return $this->getCertificateOfIssuanceLeadDataStructure($quote);
            default:
                return [];
        }
    }

    private function getOcrDataStructure(string $ocrDocType, object $ocrData, $quote)
    {
        switch ($ocrDocType) {
            case OCRDocumentTypeEnum::ID_CARD->value:
                return $this->getEmiratesIdOCRDataStructure($ocrData);
            case OCRDocumentTypeEnum::DRIVING_LICENSE->value:
                return $this->getDrivingLicenseOCRDataStructure($ocrData);
            case OCRDocumentTypeEnum::REGISTRATION_CERTIFICATE->value:
                return $this->getVehicleRegistrationCertificateOCRDataStructure($ocrData, $quote->insurance_provider_id ?? 0);
            case OCRDocumentTypeEnum::TAX_INVOICE->value:
                return $this->getTaxInvoiceOCRDataStructure($ocrData, $quote);
            case OCRDocumentTypeEnum::TAX_INVOICE_RAISED_BY_BUYER->value:
                return $this->getTaxInvoiceRaisedByBuyerOCRDataStructure($ocrData, $quote);
            case OCRDocumentTypeEnum::MOTOR_INSURANCE_POLICY_SCHEDULE->value:
                return $this->getMotorInsurancePolicyScheduleOCRDataStructure($ocrData);
            case OCRDocumentTypeEnum::CERTIFICATE_OF_ISSUANCE->value:
                return $this->getCertificateOfIssuanceOCRDataStructure($ocrData);
            default:
                return [];
        }
    }

    private function getEmiratesIdLeadDataStructure($quote): array
    {
        $result = [];
        $insured = $quote->latestInsured;
        $insuredKyc = $insured?->insuredKyc;

        if ($insured) {
            $result = [
                'eid_number' => $insured->id_number,
                'first_name' => $insured->first_name,
                'last_name' => $insured->last_name,
                'date_of_birth' => $insured->dob,
                'nationality_id' => $insured->nationality_id,
                'gender' => $insured->gender,
            ];
        }

        if ($insuredKyc) {
            $result = array_merge($result, [
                'id_issuance_date' => $insuredKyc->id_issuance_date,
                'id_expiry_date' => $insuredKyc->id_expiry_date,
                'place_of_birth' => $insuredKyc->place_of_birth,
                'country_of_residence' => $insuredKyc->country_of_residence,
                'residential_address' => $insuredKyc->residential_address,
                'employer_company_name' => $insuredKyc->employer_company_name,
                'job_title' => $insuredKyc->job_title,
                'issuing_place' => $insuredKyc->issuance_place,
            ]);
        }

        return $result;
    }

    private function getDrivingLicenseLeadDataStructure($quote): array
    {
        $result = [];
        $vehicleDriverDetail = $quote->vehicleDriverDetail;

        if ($vehicleDriverDetail) {
            $result = [
                'driver_license_number' => $vehicleDriverDetail->driver_license_number,
                'driver_gender' => $vehicleDriverDetail->driver_gender,
                'driver_license_issue_date' => $vehicleDriverDetail->driver_license_issue_date,
                'driver_license_expiry_date' => $vehicleDriverDetail->driver_license_expiry_date,
                'driver_license_issue_place' => $vehicleDriverDetail->driver_license_issue_place,
                'traffic_code_number' => $vehicleDriverDetail->traffic_code_number,
                'driver_first_name' => $vehicleDriverDetail->driver_first_name,
                'driver_last_name' => $vehicleDriverDetail->driver_last_name,
                'driver_dob' => $vehicleDriverDetail->driver_dob,
                'nationality_id' => $vehicleDriverDetail->nationality_id,
            ];
        }

        return $result;
    }

    private function getVehicleRegistrationCertificateLeadDataStructure($quote): array
    {
        $result = [];
        $vehicleDriverDetail = $quote->vehicleDriverDetail;
        $registrationCertificate = $quote->registrationCertificate;

        if ($vehicleDriverDetail) {
            $result = [
                'vehicle_plate_number' => $vehicleDriverDetail->vehicle_plate_number,
                'first_registration_date' => $vehicleDriverDetail->first_registration_date,
                'vehicle_color' => $vehicleDriverDetail->vehicle_color,
                'vehicle_engine_number' => $vehicleDriverDetail->vehicle_engine_number,
                'traffic_code_number' => $vehicleDriverDetail->traffic_code_number,
            ];
        }

        if ($registrationCertificate) {
            $result = array_merge($result, [
                'place_of_issue' => $registrationCertificate->place_of_issue,
                'expiry_date' => $registrationCertificate->expiry_date?->format('Y-m-d'),
                'owner' => $registrationCertificate->owner,
                'nationality_id' => $registrationCertificate->nationality_id,
                'mortgage_by' => $registrationCertificate->mortgage_by,
                'model' => $registrationCertificate->model,
                'vehicle_type' => $registrationCertificate->vehicle_type,
                'origin' => $registrationCertificate->origin,
            ]);
        }

        return $result;
    }

    private function getTaxInvoiceLeadDataStructure($quote): array
    {
        $result = [
            'price_with_vat' => $this->formatNumber($quote->price_with_vat),
            'price_vat_applicable' => $this->formatNumber($quote->price_vat_applicable),
            'vat' => $this->formatNumber($quote->vat),
            'policy_issuance_date' => $quote->policy_issuance_date ? Carbon::parse($quote->policy_issuance_date)->format('Y-m-d') : null,
        ];

        $payment = $quote->payment;

        if ($payment) {
            $result = array_merge($result, [
                'insurer_invoice_date' => $payment->insurer_invoice_date ? Carbon::parse($payment->insurer_invoice_date)->format('Y-m-d') : null,
                'insurer_tax_number' => $payment->insurer_tax_number ?? null,
                'tax_invoice_number' => $payment->tax_invoice_number ?? null,
            ]);
        }

        return $result;
    }

    private function getTaxInvoiceRaisedByBuyerLeadDataStructure($quote): array
    {
        $result = [];
        $payment = $quote->payment;

        if ($payment) {
            $result = [
                'commission_vat' => $this->formatNumber($payment->commission_vat),
                'commission' => $this->formatNumber($payment->commission),
                'commmission_percentage' => $payment->commmission_percentage,
                'insurer_commmission_invoice_number' => $payment->insurer_commmission_invoice_number,
                'commission_vat_applicable' => $this->formatNumber($payment->commission_vat_applicable),
            ];
        }

        return $result;
    }

    private function getMotorInsurancePolicyScheduleLeadDataStructure($quote): array
    {
        return [
            'policy_number' => $quote->personalQuote?->policy_number,
        ];
    }

    private function getCertificateOfIssuanceLeadDataStructure($quote): array
    {
        return [
            'policy_number' => $quote->personalQuote?->policy_number,
            'policy_start_date' => ($quote->personalQuote?->policy_start_date) ? Carbon::parse($quote->personalQuote->policy_start_date)->format('Y-m-d') : null,
            'policy_expiry_date' => ($quote->personalQuote?->policy_expiry_date) ? Carbon::parse($quote->personalQuote->policy_expiry_date)->format('Y-m-d') : null,
            'policy_issuance_date' => ($quote->personalQuote?->policy_issuance_date) ? Carbon::parse($quote->personalQuote->policy_issuance_date)->format('Y-m-d') : null,
        ];
    }

    private function getEmiratesIdOCRDataStructure(object $ocrData): array
    {
        return [
            'eid_number' => $ocrData->idNumber ?? null,
            'first_name' => $this->extractFirstName($ocrData->name ?? ''),
            'last_name' => $this->extractLastName($ocrData->name ?? ''),
            'date_of_birth' => $ocrData->dateOfBirth ?? null,
            'nationality_id' => $this->getNationalityId($ocrData->nationality ?? null),
            'gender' => $this->formatGender($ocrData->sex ?? null),
            'id_issuance_date' => $this->formatDate($ocrData->issuingDate ?? null),
            'id_expiry_date' => $this->formatDate($ocrData->expiryDate ?? null),
            'place_of_birth' => $this->getNationalityId($ocrData->nationality ?? null),
            'country_of_residence' => $this->getNationalityId($ocrData->country ?? null),
            'residential_address' => ! empty($ocrData->issuingPlace) ? $ocrData->issuingPlace.', UAE' : null,
            'employer_company_name' => $ocrData->sponsor ?? null,
            'job_title' => $ocrData->occupation ?? null,
            'issuing_place' => $ocrData->issuingPlace ?? null,
        ];
    }

    private function getDrivingLicenseOCRDataStructure(object $ocrData): array
    {
        $personalInfo = $ocrData->personalInformation ?? null;

        return [
            'driver_license_number' => $ocrData->licenseNumber ?? null,
            'driver_gender' => $this->formatGender($personalInfo?->sex ?? null),
            'driver_license_issue_date' => $this->formatDate($ocrData->issueDate ?? null),
            'driver_license_expiry_date' => $this->formatDate($ocrData->expiryDate ?? null),
            'driver_license_issue_place' => $this->getIssuancePlaceCode($ocrData->placeOfIssue ?? null),
            'traffic_code_number' => $ocrData->trafficCodeNumber ?? null,
            'driver_first_name' => $this->extractFirstName($personalInfo?->fullName ?? ''),
            'driver_last_name' => $this->extractLastName($personalInfo?->fullName ?? ''),
            'driver_dob' => $this->formatDate($personalInfo?->dateOfBirth ?? null),
            'nationality_id' => $this->getNationalityId($personalInfo?->nationality ?? null),
        ];
    }

    private function getVehicleRegistrationCertificateOCRDataStructure(object $ocrData, int $providerId): array
    {
        $plateInfo = $this->extractPlateCodeNumber($ocrData->trafficPlateNumber ?? null);

        return [
            'vehicle_plate_number' => $plateInfo['plate_number'] ?? null,
            'first_registration_date' => $this->formatDate($ocrData->registrationDate ?? null),
            'vehicle_color' => $this->getVehicleColorCode($ocrData->vehicalColor ?? null, QuoteTypeId::Car, $providerId),
            'vehicle_engine_number' => $ocrData->engineNumber ?? null,
            'traffic_code_number' => $ocrData->trafficCodeNumber ?? null,
            'place_of_issue' => $ocrData->placeOfIssue ?? null,
            'expiry_date' => $this->formatDate($ocrData->expiryDate ?? null),
            'owner' => $ocrData->owner ?? null,
            'nationality_id' => $this->getNationalityId($ocrData->nationality ?? null),
            'mortgage_by' => $ocrData->mortageBy ?? null,
            'model' => $ocrData->vehicalModel ?? null,
            'vehicle_type' => $ocrData->vehicalType ?? null,
            'origin' => $ocrData->origin ?? null,
        ];
    }

    private function getTaxInvoiceOCRDataStructure(object $ocrData, $quote): array
    {
        $priceVatApplicable = $ocrData->price?->baseAmount ?? null;
        $priceWithVat = $ocrData->price?->totalAmount ?? null;

        $vatPercentage = app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::VAT_VALUE);
        $vatAmount = $priceVatApplicable * $vatPercentage / 100;

        return [
            'price_with_vat' => $this->formatNumber($priceWithVat),
            'price_vat_applicable' => $this->formatNumber($priceVatApplicable),
            'vat' => $this->formatNumber($vatAmount),
            'policy_issuance_date' => ($ocrData->issuanceDate ?? null) ? Carbon::parse($ocrData->issuanceDate)->format('Y-m-d') : null,
            'insurer_invoice_date' => ($ocrData->invoiceDate ?? null) ? Carbon::parse($ocrData->invoiceDate)->format('Y-m-d') : null,
            'tax_invoice_number' => $ocrData->taxInvoiceNumber ?? null,
            'insurer_tax_number' => $ocrData->taxInvoiceNumber ?? null,
        ];
    }

    private function getTaxInvoiceRaisedByBuyerOCRDataStructure(object $ocrData, $quote): array
    {
        $payment = $quote->payment;
        $commissionVat = $ocrData->commission?->VAT ?? null;
        $commissionTotal = $ocrData->commission?->totalAmount ?? null;
        $commissionVatApplicable = $ocrData->commission?->baseAmount ?? null;
        $commissionPercentageDivisor = 1 + ($commissionVat > 0 ? .05 : 0);
        $commissionWithoutVat = $commissionTotal - $commissionVat;
        $premiumWithoutVat = $payment?->total_price / $commissionPercentageDivisor;
        $commissionPercentage = roundNumber((($commissionWithoutVat / $premiumWithoutVat) * 100));

        return [
            'commission_vat' => $this->formatNumber($commissionVat),
            'commission' => $this->formatNumber($commissionTotal),
            'commmission_percentage' => $commissionPercentage,
            'insurer_commmission_invoice_number' => $ocrData->taxInvoiceNumber ?? null,
            'commission_vat_applicable' => $this->formatNumber($commissionVatApplicable),
        ];
    }

    private function getMotorInsurancePolicyScheduleOCRDataStructure(object $ocrData): array
    {
        return [
            'policy_number' => $ocrData->policyNumber ?? null,
        ];
    }

    private function getCertificateOfIssuanceOCRDataStructure(object $ocrData): array
    {
        return [
            'policy_number' => $ocrData->policyNumber ?? null,
            'policy_start_date' => $ocrData->policyStartDate ?? null,
            'policy_expiry_date' => $ocrData->policyExpiryDate ?? null,
            'policy_issuance_date' => $ocrData->policyIssuanceDate ?? null,
        ];
    }

    private function getNationalityId(?string $nationality): ?int
    {
        if (empty($nationality)) {
            return null;
        }

        $nationalityRecord = Nationality::where('text', $nationality)
            ->orWhere('code', $nationality)
            ->orWhere('country_name', $nationality)
            ->first();

        return $nationalityRecord?->id;
    }

    private function extractFirstName(?string $fullName): string
    {
        if (empty($fullName)) {
            return '';
        }

        $nameParts = explode(' ', trim($fullName));

        return $nameParts[0] ?? '';
    }

    private function extractLastName(?string $fullName): string
    {
        if (empty($fullName)) {
            return '';
        }

        $nameParts = explode(' ', trim($fullName));
        if (count($nameParts) > 1) {
            array_shift($nameParts);

            return implode(' ', $nameParts);
        }

        return '';
    }

    private function formatGender(?string $gender): ?string
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

    private function formatDate(?string $date): ?string
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

    private function getIssuancePlaceCode(?string $issuancePlace): ?string
    {
        if (empty($issuancePlace)) {
            return null;
        }

        $issuancePlaces = app(LookupService::class)->getIssuancePlaces();

        return $issuancePlaces->first(function ($place) use ($issuancePlace) {
            return strtolower($place->text) === strtolower($issuancePlace);
        })?->code ?? null;
    }

    private function getVehicleColorCode(?string $vehicleColor, int $quoteTypeId, ?int $providerId): ?string
    {
        if (empty($vehicleColor) || ! $providerId) {
            return null;
        }

        $vehicleColors = app(LookupService::class)->getVehicleColors($quoteTypeId, $providerId);

        $matchedColor = $vehicleColors->first(function ($color) use ($vehicleColor) {
            return strtolower($color->text) === strtolower($vehicleColor);
        });

        return $matchedColor?->code ?? null;
    }

    private function extractPlateCodeNumber(?string $plateNumber): ?array
    {
        if (empty($plateNumber)) {
            return null;
        }

        $cleaned = trim($plateNumber);

        if (preg_match('/^([A-Z0-9]+)[\/:\-\s\']*(\d+)$/i', $cleaned, $matches)) {
            return [
                'plate_code' => $matches[1],
                'plate_number' => $matches[2],
            ];
        }

        return [
            'plate_code' => null,
            'plate_number' => null,
        ];
    }

    private function formatNumber($number): ?string
    {
        return $number !== null ? number_format($number, 2) : null;
    }

    public function failed(\Throwable $exception): void
    {
        LoggerService::error(self::OCR_UTIL_FEAT.' - '.self::class.' - Job failed after all retries', extra: [
            'quote_id' => $this->quoteId,
            'document_id' => $this->documentId,
            'attempts' => $this->attempts(),
            'exception' => $exception->getMessage(),
            'exception_trace' => $exception->getTraceAsString(),
            'exception_code' => $exception->getCode(),
            'exception_file' => $exception->getFile(),
            'exception_line' => $exception->getLine(),
        ]);
    }
}

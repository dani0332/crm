<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\LeadOcrDataComparison;
use Carbon\Carbon;
use App\Enums\QuoteTypes;
use App\Enums\QuoteStatusEnum;
use App\Models\DocumentType;
use App\Enums\OCRDocumentTypeEnum;
use App\Enums\QuoteTypeId;
use App\Models\CarQuote;
use App\Models\QuoteDocument;
use App\Services\Logger\LoggerService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use App\Enums\OCRSourceEnum;
use App\Models\Nationality;
use App\Services\LookupService;
use App\Services\QuoteDocumentService;

class ProcessLeadOCRDataComparison implements ShouldQueue
{
    use Queueable;
    protected  $startDate;
    protected  $endDate;
    protected  $uuid; 

    public function __construct($uuid = null,  $startDate, $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->uuid = $uuid;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->getCarDocuments();
    }

    public function getCarDocuments()
    {
        $uuid = request('uuid');
        //$startDate = Carbon::parse('2025-03-01')->startOfMonth();
        //$endDate = Carbon::parse('2025-03-31')->endOfMonth();

        $documentTypeCodes = DocumentType::query()
            ->active()
            ->byQuoteTypeId(QuoteTypes::CAR->id())
            ->get()
            ->filter(fn (DocumentType $documentType) => OCRDocumentTypeEnum::getDocumentType($documentType) !== null)
            ->pluck('code')
            ->values()
            ->all();

        $carQuotes = CarQuote::query()
            ->select([
                'id',
                'uuid',
                'code',
                'quote_status_id',
                'policy_booking_date',
                'insurance_provider_id',
                'price_with_vat',
                'price_vat_applicable',
                'vat',
                'policy_issuance_date',
            ])
        ->where('quote_status_id', QuoteStatusEnum::PolicyBooked)
            ->when($this->uuid, function ($q) {
                $q->where('uuid', $this->uuid);
            })
            ->when($this->startDate && $this->endDate, function ($q) {
                $q->whereBetween(
                    'transaction_approved_at',
                    [$this->startDate, $this->endDate]
                );
            })
            ->whereHas('documents', function ($q) use ($documentTypeCodes) {
                $q->whereIn('document_type_code', $documentTypeCodes);
            })
            ->with([
                'documents' => function ($q) use ($documentTypeCodes) {
                    $q->whereIn('document_type_code', $documentTypeCodes)
                        ->select('id', 'quote_documentable_id', 'doc_name', 'doc_url', 'doc_mime_type', 'document_type_code', 'is_ocr_processed');
                },
                'payments' => function ($q) {
                    $q->latest('created_at')
                        ->take(1)
                        ->select('id', 'paymentable_id', 'paymentable_type', 'insurance_provider_id', 'created_at')
                        ->with(['insuranceProvider:id,code']);
                },
                'insuranceProvider:id,code',
            ]);

            $carQuotes = $carQuotes->get();
           
        $carQuoteIds = $carQuotes->filter(fn ($quote) => $quote->documents->isNotEmpty())->pluck('id');

        LoggerService::info('getCarDocuments - Car quotes with OCR documents fetched', extra: [
            'uuid_filter' => $uuid,
            'document_type_codes' => $documentTypeCodes,
            'start_date' => $this->startDate ? $this->startDate->toDateString() : null,
            'end_date' => $this->endDate ? $this->endDate->toDateString() : null,
            'total_car_quotes' => $carQuotes->count(),
            'car_quotes_with_documents' => $carQuoteIds->count(),
            'car_quote_ids' => $carQuoteIds->toArray(),
        ]);

        $this->processOcrDocumentsForLeads($carQuotes, $documentTypeCodes);

        return apiResponse(null, Response::HTTP_OK, 'OCR documents reprocessing job has been completed');
    }

    private function processOcrDocumentsForLeads($carQuotes, array $documentTypeCodes): void
    {
        LoggerService::info(self::class.'::processOcrDocumentsForLeads - Starting to process OCR documents', extra: [
            'total_quotes' => $carQuotes->count(),
            'document_type_codes' => $documentTypeCodes,
        ]);

        foreach ($carQuotes as $quote) {
            LoggerService::info(self::class.'::processOcrDocumentsForLeads - Processing quote', extra: [
                'quote_id' => $quote->id,
                'quote_uuid' => $quote->uuid,
                'quote_code' => $quote->code,
                'documents_count' => $quote->documents->count(),
            ]);

            $emiratesIdDocumentsFound = 0;
            $emiratesIdDocumentsProcessed = 0;
            $emiratesIdDocumentsFailed = 0;
            $leadDataStructure = [];
            $ocrDataStructure = [];
            $comparisonStructure = [];

            foreach ($quote->documents as $document) {
                LoggerService::info(self::class.'::processOcrDocumentsForLeads - Checking document', extra: [
                    'quote_id' => $quote->id,
                    'document_id' => $document->id,
                    'document_type_code' => $document->document_type_code,
                    'doc_name' => $document->doc_name,
                    'has_doc_url' => (bool) $document->doc_url,
                    'has_doc_mime_type' => (bool) $document->doc_mime_type,
                ]);

                $documentType = DocumentType::where('code', $document->document_type_code)
                    ->where('quote_type_id', QuoteTypes::CAR->id())
                    ->first();

                if ($documentType) {
                    // Check if the document type is enabled for OCR
                    $isDocOCREnabled = OCRDocumentTypeEnum::isOCREnabled($documentType, QuoteTypes::CAR);

                    // Skip if nto enabled for OCR
                    if (! $isDocOCREnabled) {
                        LoggerService::info(self::class.'::processOcrDocumentsForLeads - Document type is not enabled for OCR', extra: [
                            'quote_id' => $quote->id,
                            'quote_uuid' => $quote->uuid,
                            'document_id' => $document->id,
                            'document_type_code' => $document->document_type_code,
                            'document_type_name' => $documentType->name,
                        ]);

                        continue;
                    }

                    // Get the OCR document type
                    $ocrDocType = OCRDocumentTypeEnum::getDocumentType($documentType);

                    LoggerService::info(self::class."::processOcrDocumentsForLeads - Found {$documentType->name} document", extra: [
                        'quote_id' => $quote->id,
                        'quote_uuid' => $quote->uuid,
                        'document_id' => $document->id,
                        'document_type_code' => $document->document_type_code,
                        'total_emirates_id_found' => $emiratesIdDocumentsFound,
                    ]);

                    $this->processOcrDocument($quote, $document, $ocrDocType->value, $leadDataStructure, $ocrDataStructure);

                    // Caculate accuracy of the OCR data
                    //if ($ocrDocType->value === OCRDocumentTypeEnum::ID_CARD->value) {
                        //print_r($leadDataStructure[$ocrDocType->value]);
                        //print_r($ocrDataStructure[$ocrDocType->value]);
                        $count = count($leadDataStructure[$ocrDocType->value]);
                        $matchCount = 0;

                        foreach ($leadDataStructure[$ocrDocType->value] as $key => $value) {
                            if (! isset($ocrDataStructure[$ocrDocType->value][$key])) {
                                continue;
                            }

                            if ($leadDataStructure[$ocrDocType->value][$key] === $ocrDataStructure[$ocrDocType->value][$key]) {
                                $matchCount++;
                            }
                        }

                       //print_r($matchCount.' - '.$count); exit;

                        $comparisonStructure[$ocrDocType->value] = [
                            'count' => $count,
                            'match_count' => $matchCount,
                            'accuracy' => $count > 0 ? number_format($matchCount / $count * 100, 2) : 0,
                        ];
                   //}

                } else {
                    LoggerService::warning(self::class.'::processOcrDocumentsForLeads - DocumentType not found', extra: [
                        'quote_id' => $quote->id,
                        'document_id' => $document->id,
                        'document_type_code' => $document->document_type_code,
                    ]);
                }
            }

           //print_r($leadDataStructure); exit;
            //print_r($ocrDataStructure); exit;
            // Save data in database
            $this->saveleadOCRComparisonData($quote->id, $quote->uuid, $leadDataStructure, $ocrDataStructure, $comparisonStructure);

            LoggerService::info(self::class.'::processOcrDocumentsForLeads - Quote processing summary', extra: [
                'quote_id' => $quote->id,
                'quote_uuid' => $quote->uuid,
                'total_documents' => $quote->documents->count(),
                'emirates_id_documents_found' => $emiratesIdDocumentsFound,
                'emirates_id_documents_processed' => $emiratesIdDocumentsProcessed,
                'emirates_id_documents_failed' => $emiratesIdDocumentsFailed,
            ]);
        }

        LoggerService::info(self::class.'::processOcrDocumentsForLeads - Completed processing all documents', extra: [
            'total_quotes_processed' => $carQuotes->count(),
        ]);
    }

    private function saveleadOCRComparisonData($quoteId, $quoteUuid, $leadDataStructure, $ocrDataStructure, $comparisonStructure): void
    {
        // Calculate total accuracy
        $totalAccuracy = 0;
        $totalCount = count($comparisonStructure) * 100;
        foreach ($comparisonStructure as $key => $value) {
            $totalAccuracy += $value['accuracy'];
        }
        $totalAccuracy = number_format(($totalAccuracy / $totalCount)* 100, 2);

        // Save data in database
        LeadOcrDataComparison::updateOrCreate([
            'quoteable_id' => $quoteId,
            'quoteable_type' => QuoteTypes::CAR->modelClass(),
            'uuid' => $quoteUuid,
        ], [
            'lead_data' => json_encode($leadDataStructure),
            'ocr_data' => json_encode($ocrDataStructure),
            'ocr_responses' => json_encode($this->ocrReponseStructure),
            'compairson_data' => json_encode($comparisonStructure),
            'comparison_score' => $totalAccuracy,
        ]);
    }

    private function processOcrDocument(CarQuote $quote, QuoteDocument $document, string $ocrDocType,
        array &$leadDataStructure, array &$ocrDataStructure): bool
    {
        // Get lead data structure for the document type
        $leadDataStructure[$ocrDocType] = $this->getLeadDataStructure($ocrDocType, $quote);

        LoggerService::info(self::class.'::processOcrDocument - Processing OCR document', extra: [
            'quote_id' => $quote->id,
            'quote_uuid' => $quote->uuid,
            'quote_code' => $quote->code,
            'document_id' => $document->id,
            'document_type_code' => $document->document_type_code,
            'ocr_doc_type' => $ocrDocType,
            'doc_name' => $document->doc_name,
            'doc_url' => $document->doc_url,
        ]);

        if (! $document->doc_url || ! $document->doc_mime_type) {
            LoggerService::warning(self::class.'::processOcrDocument - Missing document payload', extra: [
                'quote_id' => $quote->id,
                'document_id' => $document->id,
                'has_doc_url' => (bool) $document->doc_url,
                'has_doc_mime_type' => (bool) $document->doc_mime_type,
            ]);

            return false;
        }

        $ocrData = $this->callOcrApi($quote, $document, $ocrDocType);

        if ($ocrData) {
            LoggerService::info(self::class.'::processOcrDocument - OCR API call successful', extra: [
                'quote_id' => $quote->id,
                'document_id' => $document->id,
                'has_data' => ! empty($ocrData),
            ]);

            // Add ocr data structure
            $ocrDataStructure[$ocrDocType] = $this->getOcrDataStructure($ocrDocType, $ocrData, $quote);

            return true;
        }

        LoggerService::warning(self::class.'::processOcrDocument - OCR API call failed', extra: [
            'quote_id' => $quote->id,
            'document_id' => $document->id,
        ]);

        return false;
    }

    // Lead data structures
    private function getLeadDataStructure(string $ocrDocType, $quote)
    {
        switch ($ocrDocType) {
            case OCRDocumentTypeEnum::ID_CARD->value:
                return $this->getEmiratesIdLeadDataStructure($quote);
                break;
            case OCRDocumentTypeEnum::DRIVING_LICENSE->value:
                return $this->getDrivingLicenseLeadDataStructure($quote);
                break;
            case OCRDocumentTypeEnum::REGISTRATION_CERTIFICATE->value:
                return $this->getVehicleRegistrationCertificateLeadDataStructure($quote);
                break;
            case OCRDocumentTypeEnum::TAX_INVOICE->value:
                return $this->getTaxInvoiceLeadDataStructure($quote);
                break;
            case OCRDocumentTypeEnum::TAX_INVOICE_RAISED_BY_BUYER->value:
                return $this->getTaxInvoiceRaisedByBuyerLeadDataStructure($quote);
                break;
            case OCRDocumentTypeEnum::MOTOR_INSURANCE_POLICY_SCHEDULE->value:
                return $this->getMotorInsurancePolicyScheduleLeadDataStructure($quote);
                break;
            case OCRDocumentTypeEnum::CERTIFICATE_OF_ISSUANCE->value:
                return $this->getCertificateOfIssuanceLeadDataStructure($quote);
                break;
            default:
                break;
        }
    }

    // --------- OCR data structures ---------
    private function getOcrDataStructure(string $ocrDocType, object $ocrData, $quote)
    {
        switch ($ocrDocType) {
            case OCRDocumentTypeEnum::ID_CARD->value:
                return $this->getEmiratesIdOCRDataStructure($ocrData);
                break;
            case OCRDocumentTypeEnum::DRIVING_LICENSE->value:
                return $this->getDrivingLicenseOCRDataStructure($ocrData);
                break;
            case OCRDocumentTypeEnum::REGISTRATION_CERTIFICATE->value:
                return $this->getVehicleRegistrationCertificateOCRDataStructure($ocrData, $quote->insurance_provider_id);
                break;
            case OCRDocumentTypeEnum::TAX_INVOICE->value:
                return $this->getTaxInvoiceOCRDataStructure($ocrData, $quote);
                break;
            case OCRDocumentTypeEnum::TAX_INVOICE_RAISED_BY_BUYER->value:
                return $this->getTaxInvoiceRaisedByBuyerOCRDataStructure($ocrData, $quote);
                break;
            case OCRDocumentTypeEnum::MOTOR_INSURANCE_POLICY_SCHEDULE->value:
                return $this->getMotorInsurancePolicyScheduleOCRDataStructure($ocrData);
                break;
            case OCRDocumentTypeEnum::CERTIFICATE_OF_ISSUANCE->value:
                return $this->getCertificateOfIssuanceOCRDataStructure($ocrData);
                break;
            default:
                break;
        }
    }

    private function getEmiratesIdOCRDataStructure(object $ocrData): array
    {
        return [
            'eid_number' => $ocrData->idNumber,
            'first_name' => $this->extractFirstName($ocrData->name),
            'last_name' => $this->extractLastName($ocrData->name),
            'date_of_birth' => $ocrData->dateOfBirth,
            'nationality_id' => $this->getNationalityId($ocrData->nationality),
            'gender' => $this->formatGender($ocrData->sex),
            'id_issuance_date' => $this->formatDate($ocrData->issuingDate),
            'id_expiry_date' => $this->formatDate($ocrData->expiryDate),
            'place_of_birth' => $this->getNationalityId($ocrData->nationality),
            'country_of_residence' => $this->getNationalityId($ocrData->country),
            'residential_address' => $ocrData->issuingPlace.', UAE',
            'employer_company_name' => $ocrData->sponsor,
            'job_title' => $ocrData->occupation,
            'issuing_place' => $ocrData->issuingPlace,
        ];
    }

    private function getDrivingLicenseOCRDataStructure(object $ocrData): array
    {
        return [
            'driver_license_number' => $ocrData->licenseNumber,
            'driver_gender' => $this->formatGender($ocrData->personalInformation['sex']),
            'driver_license_issue_date' => $this->formatDate($ocrData->issueDate),
            'driver_license_expiry_date' => $this->formatDate($ocrData->expiryDate),
            'driver_license_issue_place' => $this->getIssuancePlaceCode($ocrData->placeOfIssue),
            'traffic_code_number' => $ocrData->trafficCodeNumber,
            'driver_first_name' => $this->extractFirstName($ocrData->personalInformation['fullName']),
            'driver_last_name' => $this->extractLastName($ocrData->personalInformation['fullName']),
            'driver_dob' => $this->formatDate($ocrData->personalInformation['dateOfBirth']),
            'nationality_id' => $this->getNationalityId($ocrData->personalInformation['nationality']),
        ];
    }

    private function getVehicleRegistrationCertificateOCRDataStructure(object $ocrData, int $providerId): array
    {
        $plateInfo = $this->extractPlateCodeNumber($ocrData->trafficPlateNumber ?? null);
        $bankName = $this->getBankCode($ocrData->mortageBy ?? null, QuoteTypeId::Car, $providerId);

        return [

            'vehicle_plate_number' => $plateInfo['plate_number'] ?? null,
            'first_registration_date' => $this->formatDate($ocrData->registrationDate ?? null),
            'vehicle_color' => $this->getVehicleColorCode($ocrData->vehicalColor ?? null, QuoteTypeId::Car, $providerId),
            'vehicle_engine_number' => $ocrData->engineNumber ?? null,
            'traffic_code_number' => $ocrData->trafficCodeNumber ?? null,
            'place_of_issue' => $ocrData->placeOfIssue,
            'expiry_date' => $this->formatDate($ocrData->expiryDate),
            'owner' => $ocrData->owner ?? null,
            'nationality_id' => $this->getNationalityId($ocrData->nationality),
            'mortgage_by' => $ocrData->mortageBy,
            'model' => $ocrData->vehicalModel,
            'vehicle_type' => $ocrData->vehicalType,
            'origin' => $ocrData->origin,

        ];
    }

    private function getTaxInvoiceOCRDataStructure(object $ocrData, $quote): array
    {
        $payment = $quote->payment;
        $taxInvoiceNumber = $ocrData->taxInvoiceNumber ?? $payment?->insurer_tax_number;
        $priceVatApplicable = $ocrData->price?->baseAmount ?? $quote->price_vat_applicable;
        $priceWithVat = $ocrData->price?->totalAmount ?? $quote->price_with_vat;

        $vatPercentage = app(\App\Services\ApplicationStorageService::class)->getValueByKey(\App\Enums\ApplicationStorageEnums::VAT_VALUE);
        $vatAmount = $priceVatApplicable * $vatPercentage / 100;

        return [
            'price_with_vat' => $priceWithVat,
            'price_vat_applicable' => $priceVatApplicable,
            'vat' => $vatAmount,
            'policy_issuance_date' => Carbon::parse($ocrData->issuanceDate ?? $quote->policy_issuance_date)->format('Y-m-d'),
            'insurer_invoice_date' => Carbon::parse($ocrData->invoiceDate)->format('Y-m-d'),
            'tax_invoice_number' => $taxInvoiceNumber,
            'insurer_tax_number' => $taxInvoiceNumber,
        ];
    }

    private function getTaxInvoiceRaisedByBuyerOCRDataStructure(object $ocrData, $quote): array
    {
        $commissionVat = $ocrData->commission['VAT'] ?? ($quote->payment?->comission_vat ?: 0);
        $commissionTotal = $ocrData->commission['totalAmount'] ?? $quote->payment?->comission;

        $commissionVatApplicable = $ocrData->commission['baseAmount'] ?? $quote->payment?->commission_vat_applicable;
        $commissionPercentageDivisor = 1 + ($commissionVat > 0 ? .05 : 0);
        $commissionWithoutVat = $commissionTotal - $commissionVat;
        $premiumWithoutVat = $quote->payment->total_price / $commissionPercentageDivisor;
        $commissionPercentage = roundNumber((($commissionWithoutVat / $premiumWithoutVat) * 100)) ?? $this->quote->payment?->comission_percentage;

        return [
            'commission_vat' => $commissionVat,
            'commission' => $commissionTotal,
            'commmission_percentage' => $commissionPercentage,
            'insurer_commmission_invoice_number' => $ocrData->taxInvoiceNumber ?? $quote->payment?->insurer_commmission_invoice_number,
            'commission_vat_applicable' => $commissionVatApplicable,
        ];
    }

    private function getMotorInsurancePolicyScheduleOCRDataStructure(object $ocrData): array
    {
        return [
            'policy_number' => $ocrData->policyNumber,
            //'policy_start_date' => isset($ocrData->policyStartDate) ? Carbon::parse($ocrData->policyStartDate)->format('Y-m-d') : null,
            //'policy_expiry_date' => isset($ocrData->policyExpiryDate) ? Carbon::parse($ocrData->policyExpiryDate)->format('Y-m-d') : null,
        ];
    }

    private function getCertificateOfIssuanceOCRDataStructure(object $ocrData): array
    {
        return [
            'policy_number' => $ocrData->policyNumber,
            'policy_start_date' => $ocrData->policyStartDate ?? null,
            'policy_expiry_date' => $ocrData->policyExpiryDate ?? null,
            'policy_issuance_date' => $ocrData->policyIssuanceDate ?? null,
        ];
    }

    // --------- Lead data structures ---------
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
                'traffic_code_number' => $registrationCertificate->traffic_code_number,
            ]);
        }

        return $result;
    }

    private function getTaxInvoiceLeadDataStructure($quote): array
    {
        $result = [];

        $result = [
            'price_with_vat' => $quote->price_with_vat,
            'price_vat_applicable' => $quote->price_vat_applicable,
            'vat' => $quote->vat,
            'policy_issuance_date' => Carbon::parse($quote->policy_issuance_date)->format('Y-m-d'),
        ];

        $payment = $quote->payment;

        if ($payment) {
            $result = array_merge($result, [
                'insurer_invoice_date' => Carbon::parse($payment->insurer_invoice_date)->format('Y-m-d'),
                'insurer_tax_number' => $payment->insurer_tax_number,
                'tax_invoice_number' => $payment->tax_invoice_number,
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
                'commission_vat' => $payment->commission_vat,
                'commission' => $payment->commission,
                'commmission_percentage' => $payment->commmission_percentage,
                'insurer_commmission_invoice_number' => $payment->insurer_commmission_invoice_number,
                'commission_vat_applicable' => $payment->commission_vat_applicable,
            ];
        }

        return $result;
    }

    private function getMotorInsurancePolicyScheduleLeadDataStructure($quote): array
    {
        return [
            'policy_number' => $quote->personalQuote->policy_number,
            //'policy_start_date' => Carbon::parse($quote->personalQuote->policy_start_date)->format('Y-m-d'),
            //'policy_expiry_date' => Carbon::parse($quote->personalQuote->policy_expiry_date)->format('Y-m-d'),
        ];
    }

    private function getCertificateOfIssuanceLeadDataStructure($quote): array
    {
        return [
            'policy_number' => $quote->personalQuote->policy_number,
            'policy_start_date' => Carbon::parse($quote->personalQuote->policy_start_date)->format('Y-m-d'),
            'policy_expiry_date' => Carbon::parse($quote->personalQuote->policy_expiry_date)->format('Y-m-d'),
            'policy_issuance_date' => Carbon::parse($quote->personalQuote->policy_issuance_date)->format('Y-m-d'),
        ];
    }

    private function callOcrApi(CarQuote $quote, QuoteDocument $document, string $docType): ?object
    {
        $providerCode = $this->extractProviderCode($quote);
        $refId = $this->getRefId($quote);
        $isEcom = false;

         $docUrl = app(QuoteDocumentService::class)->getDocumentUrl($document->doc_url);   
        // $docUrl = "https://azstorinsurancemarketstg.blob.core.windows.net/imcrmdev/{$document->doc_url}";


        if (! $docUrl) {
            LoggerService::warning(self::class.'::callOcrApi - Failed to get document URL', extra: [
                'quote_uuid' => $quote->uuid,
                'document_id' => $document->id,
                'doc_url' => $document->doc_url,
            ]);

            // Add to ocr response structure
            $this->ocrReponseStructure[$docType] = [
                'status' => 'false',
                'doc_url' => $document->doc_url,
                'doc_type' => $docType,
                'reason' => 'Failed to get document URL',
            ];

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

        LoggerService::info(self::class.'::callOcrApi - OCR API Request Details', extra: [
            'request_data' => $requestData,
        ]);

        try {
            $response = Http::baseUrl(config('constants.OCR_API_ENDPOINT'))
                ->withHeader('Referer', trim(config('constants.APP_URL'), '/'))
                ->withHeader('x-api-key', config('constants.OCR_API_KEY'))
                ->withHeader('source', $isEcom ? OCRSourceEnum::ECOM->value : OCRSourceEnum::IMCRM->value)
                ->timeout(config('constants.OCR_API_TIMEOUT'))
                ->post('/process-document', $requestData);

            $responseData = $response->json();
            $responseBody = $response->body();

            if ($response->successful()) {
                LoggerService::info(self::class.'::callOcrApi - OCR API Response Success', extra: [
                    'quote_uuid' => $quote->uuid,
                    'document_id' => $document->id,
                    'has_data' => ! empty($responseData),
                    'response_body' => $responseBody,
                    'response_data' => $responseData,
                ]);

                // Add to ocr response structure
                $this->ocrReponseStructure[$docType] = [
                    'status' => 'true',
                    'response' => $responseBody,
                    'reason' => 'Success',
                ];

                return (object) $responseData;
            }

            LoggerService::warning(self::class.'::callOcrApi - OCR API Response Failed', extra: [
                'quote_uuid' => $quote->uuid,
                'document_id' => $document->id,
                'response_status' => $response->status(),
                'response_headers' => $response->headers(),
                'response_body' => $responseBody,
                'response_data' => $responseData,
                'response_message' => $responseData['message'] ?? ($responseData['error'] ?? 'Unknown error'),
            ]);

            // Add to ocr response structure
            $this->ocrReponseStructure[$docType] = [
                'status' => 'false',
                'response' => $responseData,
                'reason' => 'Failed',
            ];

            return null;
        } catch (\Exception $e) {
            LoggerService::error(self::class.'::callOcrApi - Exception occurred during API call', exception: $e);

            return null;
        }

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

    private function getNationalityName(?int $nationalityId): ?string
    {
        if (empty($nationalityId)) {
            return null;
        }

        $nationalityRecord = Nationality::find($nationalityId);

        // Return country_name if available, otherwise fall back to text
        return $nationalityRecord?->country_name ?? $nationalityRecord?->text ?? null;
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
            array_shift($nameParts); // Remove first name

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

    public function formatDate(?string $date): ?string
    {
        if (empty($date)) {
            return null;
        }

        try {
            return Carbon::parse($date)->format('Y-m-d');
        } catch (\Exception $e) {
            LoggerService::error('Failed to format date', exception: $e);

            return null;
        }
    }

    public function getIssuancePlaceCode(?string $issuancePlace): ?string
    {
        if (empty($issuancePlace)) {
            return null;
        }

        $issuancePlaces = app(LookupService::class)->getIssuancePlaces();

        return $issuancePlaces->first(function ($place) use ($issuancePlace) {
            return strtolower($place->text) === strtolower($issuancePlace);
        })?->code ?? null;
    }

    public function getVehicleColorCode(?string $vehicleColor, int $quoteTypeId, ?int $providerId): ?string
    {
        LoggerService::info('OCR Utils - getVehicleColorCode called', extra: [
            'vehicleColor' => $vehicleColor,
            'quoteTypeId' => $quoteTypeId,
            'providerId' => $providerId,
        ]);

        if (empty($vehicleColor)) {
            LoggerService::info('OCR Utils - Vehicle color is empty, returning null');

            return null;
        }

        if (! $providerId) {
            LoggerService::warning('OCR Utils - No valid provider id for vehicle color code', extra: [
                'vehicleColor' => $vehicleColor,
                'quoteTypeId' => $quoteTypeId,
                'providerId' => $providerId,
            ]);

            return null;
        }

        $vehicleColors = app(LookupService::class)->getVehicleColors($quoteTypeId, $providerId);

        $matchedColor = $vehicleColors->first(function ($color) use ($vehicleColor) {
            return strtolower($color->text) === strtolower($vehicleColor);
        });

        LoggerService::info('OCR Utils - Vehicle color lookup result', extra: [
            'vehicleColor' => $vehicleColor,
            'providerId' => $providerId,
            'availableColors' => $vehicleColors->pluck('text')->toArray(),
            'matchedColor' => $matchedColor?->code,
            'matchedColorText' => $matchedColor?->text,
        ]);

        return $matchedColor?->code ?? null;
    }

    public function getBankCode(?string $bankName, int $quoteTypeId, ?int $providerId): ?string
    {
        if (empty($bankName)) {
            return null;
        }

        if (! $providerId) {
            LoggerService::warning('OCR Utils - No valid provider id for bank code');

            return null;
        }

        $banks = app(LookupService::class)->getBankNames($quoteTypeId, $providerId);

        return $banks->first(function ($bank) use ($bankName) {
            return strtolower($bank->text) === strtolower($bankName);
        })?->code ?? null;
    }

    public function extractPlateCodeNumber(?string $plateNumber): ?array
    {
        if (empty($plateNumber)) {
            return null;
        }

        $cleaned = trim($plateNumber);

        // $parts = explode('/', $cleaned, 2);
        if (preg_match('/^([A-Z0-9]+)[\/:\-\s\']*(\d+)$/i', $cleaned, $matches)) {
            LoggerService::info('OCR Utils - extractPlateCodeNumber - Plate code and number extracted', extra: [
                'plate_number' => $plateNumber,
                'matches' => $matches,
            ]);

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

    private function extractProviderCode(CarQuote $quote): ?string
    {
        if ($quote->payments && $quote->payments->isNotEmpty()) {
            $latestPayment = $quote->payments->first();
            if ($latestPayment && $latestPayment->insuranceProvider) {
                return $latestPayment->insuranceProvider->code;
            }
        }

        if ($quote->insuranceProvider) {
            return $quote->insuranceProvider->code;
        }

        return null;
    }

    private function getRefId(CarQuote $quote): string
    {
        return $quote->code;
    }

}

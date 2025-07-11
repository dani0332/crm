<?php

namespace App\Services\PolicyIssuanceAutomation\Car;

use App\Enums\ApplicationStorageEnums;
use App\Enums\DocumentTypeCode;
use App\Enums\InsuranceProvidersEnum;
use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Enums\PolicyIssuanceStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\TravelQuoteEnum;
use App\Interfaces\PolicyIssuanceInterface;
use App\Models\Payment;
use App\Models\PolicyIssuanceLog;
use App\Repositories\PaymentRepository;
use App\Repositories\PersonalQuoteRepository;
use App\Services\ApplicationStorageService;
use App\Services\Logger\LoggerService;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

class LivaInsuranceService implements PolicyIssuanceInterface
{
    private $className = 'livaInsuranceService';
    private mixed $baseUrl;
    private $headers = [];

    public const INSURER_CODE = InsuranceProvidersEnum::ALNC;
    public const TYPE = quoteTypeCode::Car;
    public const TYPE_ID = QuoteTypeId::Car;

    public mixed $vat = null;
    public $policyIssuance = null;
    public $currentInsurerApiStatus = null;

    public const UPLOAD_DOCUMENTS = 'UploadDocuments';
    public const ISSUE_POLICY = 'IssuePolicy';
    public const GET_POLICY_DOCUMENTS = 'GetPolicyDocument';
    public const BOOK_POLICY = 'BookPolicy';

    public function __construct()
    {
        $this->baseUrl = config('constants.LIVA_API_BASE_URL');
        $this->headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic '.config('constants.LIVA_BASIC_AUTH'),
            'PartnerId' => config('constants.LIVA_PARENT_ID'),
            'location' => config('constants.LIVA_LOCATION'),
            'Authentication' => 'Bearer '.config('constants.LIVA_AUTHENTICATION'),
            'SubscriptionKey' => config('constants.LIVA_SUBSCRIPTION_KEY'),
        ];
    }

    private function getAPISteps(): array
    {
        return [
            self::UPLOAD_DOCUMENTS,
            self::ISSUE_POLICY,
            self::GET_POLICY_DOCUMENTS,
            self::BOOK_POLICY,
        ];
    }

    public function isPolicyIssuanceAutomationEnabled(): bool
    {
        return (bool) app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::ENABLE_LIVA_CAR_POLICY_ISSUANCE);
    }

    public function isPolicyIssuanceAutomationRetryEnabledForTimeout(): bool
    {
        return (bool) app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::ENABLE_RETRY_TIMEOUT_LIVA_CAR_POLICY_ISSUANCE);
    }

    public function createPolicyIssuanceSchedule($quote, $insurer)
    {
        LoggerService::startQuoteLogging($quote);
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' started');

        if ($this->isPolicyIssuanceAutomationEnabled()) {
            $this->policyIssuance = (new PolicyIssuanceService)->schedulePolicyIssuance($quote, $insurer, self::TYPE, $this->className);
        } else {
            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' -  LIVA Car Automation is disabled');
        }

        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' ended');

        return $this->policyIssuance;
    }

    public function executeSteps($process): array
    {
        $response = ['status' => false, 'error' => null, 'message' => null];

        $this->policyIssuance = $process;
        $quote = $process->model;

        LoggerService::startQuoteLogging($quote, LoggerFeatureEnum::POLICY_AUTOMATION);
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - PID : '.$process->id.' - Plan ID : '.$quote->plan_id.' started');

        try {
            if (! $this->isPolicyIssuanceAutomationEnabled()) {
                LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - LIVA Car Automation is disabled');
                $response['error'] = 'LIVA Car Automation is disabled';
                $response['message'] = 'LIVA Car Automation is disabled';

                return $response;
            }

            $lastCompletedStep = $process->completed_step;
            $nextStepToBeExecuted = $lastCompletedStep ? $this->getNextStep($lastCompletedStep) : self::UPLOAD_DOCUMENTS;
            $executeStepSequence = $this->executeStepSequence($quote, $process, $nextStepToBeExecuted);

            $response['status'] = $executeStepSequence['status'];
            $response['message'] = $executeStepSequence['message'];
        } catch (Exception $e) {
            $response['error'] = $e->getMessage();
            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Exception : '.$e->getMessage());

            return $response;
        }

        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - PID : '.$process->id.' ended');

        return $response;
    }

    private function executeStepSequence($quote, $process, $nextStepToBeExecuted): void
    {
        /* if ($nextStepToBeExecuted === PolicyIssuanceEnum::GIG_CAR_AUTO_CAPTURE) {
            $this->executeAutoCaptureStep($quote, $process);
            $nextStepToBeExecuted = $this->getNextStep($process->completed_step);
        }

        if ($nextStepToBeExecuted === PolicyIssuanceEnum::GIG_CAR_UPLOAD_DOCUMENTS) {
            $this->executeUploadDocumentsStep($quote, $process);
            $nextStepToBeExecuted = $this->getNextStep($process->completed_step);
        }

        if ($nextStepToBeExecuted === PolicyIssuanceEnum::GIG_CAR_ISSUE_POLICY) {
            $this->executeIssuePolicyStep($quote, $process);
            $nextStepToBeExecuted = $this->getNextStep($process->completed_step);
        }

        if ($nextStepToBeExecuted === PolicyIssuanceEnum::GIG_CAR_GET_POLICY_DOCUMENTS) {
            $this->executeGetPolicyDocumentsStep($quote, $process);
            $nextStepToBeExecuted = $this->getNextStep($process->completed_step);
        }

        if ($nextStepToBeExecuted === PolicyIssuanceEnum::GIG_CAR_BOOK_POLICY) {
            $this->executeBookPolicyStep($quote, $process);
            $nextStepToBeExecuted = $this->getNextStep($process->completed_step);
        } */
    }

    public function getNextStep($completedStep = null): ?string
    {
        $allSteps = PolicyIssuanceEnum::getPolicyIssuanceSteps(self::INSURER_CODE, self::TYPE);

        if (! $completedStep) {
            return $allSteps[0];
        }

        $completedStepIndex = array_search($completedStep, $allSteps);
        if ($completedStepIndex === false || $completedStepIndex === count($allSteps) - 1) {
            return null;
        }

        return $allSteps[$completedStepIndex + 1];
    }

    private function livaHttpCall($endPoint, $payload)
    {
        $url = $this->baseUrl.$endPoint;

        return Http::timeout(20)->withHeaders($this->headers)->post($url, $payload);
    }

    private function livaHasError($response): bool
    {
        if ($response->failed()) {
            return true;
        }

        $responseObject = $response->object();

        if (isset($responseObject->DocumentInfo->ErrorInfo)) {
            if (is_array($responseObject->DocumentInfo->ErrorInfo) && ! empty($responseObject->DocumentInfo->ErrorInfo)) {
                return true;
            }
            if (is_string($responseObject->DocumentInfo->ErrorInfo) && ! empty($responseObject->DocumentInfo->ErrorInfo)) {
                return true;
            }
        }

        return false;
    }

    private function extractLivaErrorMessage($response): string
    {
        if ($response->failed()) {
            return $response->body() ?? 'HTTP request failed';
        }

        $responseObject = $response->object();

        if (isset($responseObject->DocumentInfo->ErrorInfo)) {
            if (is_array($responseObject->DocumentInfo->ErrorInfo)) {
                $errorMessages = [];
                foreach ($responseObject->DocumentInfo->ErrorInfo as $error) {
                    if (isset($error->ErrorCode) && isset($error->ErrorMsg)) {
                        $errorMessages[] = "Error {$error->ErrorCode}: {$error->ErrorMsg}";
                    } elseif (is_string($error)) {
                        $errorMessages[] = $error;
                    }
                }

                return implode('; ', $errorMessages);
            }

            if (is_string($responseObject->DocumentInfo->ErrorInfo)) {
                return $responseObject->DocumentInfo->ErrorInfo;
            }
        }

        return 'Unknown error occurred';
    }

    public function updateQuoteRequest($quote)
    {
        LoggerService::startQuoteLogging($quote->code, LoggerFeatureEnum::POLICY_AUTOMATION);

        $livaMapping = app(LivaInsurancePayloadMapping::class);

        $nationalityId = $livaMapping->nationalityList($quote->latestInsured->nationality->text);
        if (! $nationalityId) {
            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Nationality not found on LIVA', extra: [
                'nationality' => $quote->latestInsured->nationality->text,
            ]);
        }

        $homeCountryLicenseIssuance = $livaMapping->nationalityList($quote?->carQuoteRequestDetail?->home_country_license_issuance);
        if (! $homeCountryLicenseIssuance) {
            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Home Country License Issuance nationality not found on LIVA', extra: [
                'nationality' => $quote?->carQuoteRequestDetail?->home_country_license_issuance,
            ]);
        }

        $vehicleMakeId = $livaMapping->vehicleMakeList(strtoupper($quote?->carMake?->text));
        if (! $vehicleMakeId) {
            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Vehicle Make not found on LIVA', extra: [
                'vehicleMake' => strtoupper($quote?->carMake?->text),
            ]);
        }

        $vehicleModelList = $this->getVehicleModelId($vehicleMakeId);

        if (empty($vehicleModelList) || ! isset($vehicleModelList[strtoupper($quote?->carModel?->text)])) {
            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Vehicle Model not found on LIVA', extra: [
                'vehicleModel' => $quote?->carModel?->text,
                'vehicleMake' => $quote?->carMake?->text,
            ]);
        }

        $vehicleModelId = $vehicleModelList[strtoupper($quote?->carModel?->text)];

        $vehicleVariants = $this->getVehicleVariants($quote->latestInsured->dob, $quote->mobile_no, $vehicleMakeId, $vehicleModelId, $quote->year_of_manufacture, $quote->code);

        $closestVariant = $this->matchClosestVehicleVariant($vehicleVariants, [
            'cc' => $quote?->carModelDetail?->cubic_capacity,
            'noOfDoors' => $quote?->carModelDetail?->no_of_doors,
            'noOfCyl' => $quote?->cylinder,
            'bodyType' => $quote?->vehicleType->text,
            'trim' => $quote?->carQuoteRequestDetail?->insurer_trim,
        ]);

        $endPoint = 'quote/update/v2';
        $payload = [
            'QuotationRequest' => [
                'CustomerDetails' => [
                    'Gender' => 'M',
                    'DOB' => $quote->latestInsured->dob.' 00:00:00',
                    'Nationality' => $nationalityId,
                    'MobileNo' => $quote->mobile_no,
                    'EmailId' => $quote->email,
                    'FirstName' => $quote->latestInsured->first_name,
                    'LastName' => $quote->latestInsured->last_name,
                    'NationalId' => $quote->latestInsured->id_number,
                    'CustomerCategory' => 1,
                ],
                'VehicleDetails' => [
                    'CC' => $quote?->carModelDetail?->cubic_capacity,
                    'PlaceOfRegn' => '1', // TODO: need to ask ecom side
                    'NcbYears' => 99,
                    'DateOfRegn' => $quote?->carQuoteRequestDetail?->first_registration_date ? ($quote?->carQuoteRequestDetail?->first_registration_date.' 00:00:00') : '',
                    'YearOfManf' => $quote?->year_of_manufacture,
                    'InsuredValue' => $quote?->car_value,
                    'VehicleDescCode' => $closestVariant->VehicleDescCode,
                    'VehicleDesc' => $quote?->carQuoteRequestDetail?->insurer_trim,
                    'Seats' => $quote?->seat_capacity,
                    'UseCode' => '2',
                    'BodyType' => $closestVariant->BodyTypeCode,
                    'MakeCode' => $vehicleMakeId,
                    'ModelCode' => $vehicleModelId,
                    'NoOfCyl' => $quote?->cylinder,
                    'EstimatedAnnualMileage' => $quote?->carQuoteRequestDetail?->annual_mileage_estimate,
                    'VehicleSpecification' => $quote?->is_gcc_standard,
                    'vehHP' => $closestVariant->HP,
                    'NoOfDoors' => $quote?->carModelDetail?->no_of_doors,
                    'DrivenWheel' => $closestVariant->DrivenWheel,
                    'modelSpecification' => $closestVariant->ModelSpecification,
                    'ColorCode' => $quote?->carQuoteRequestDetail?->vehicle_color,
                    'RegistrationType' => '1', // TODO: rta transaction based
                    'RtaTransactionType' => (string) $quote?->carQuoteRequestDetail?->rta_transaction_type,
                    'RegnNoText' => $quote->carQuoteRequestDetail?->plate_code,
                    'RegnNoNumber' => $quote->carQuoteRequestDetail?->plate_number,
                    'ChassisNo' => $quote->carQuoteRequestDetail?->chassis_number,
                    'EngineNo' => $quote->carQuoteRequestDetail?->engine_number,
                    'TcfNo' => $quote->carQuoteRequestDetail?->traffic_code_number,
                ],
                'TransactionDetails' => [
                    'PolicyTypeCode' => '1', // TODO: need to ask api team
                    'EffectiveDate' => '2025-01-06 21:09:00', // TODO: need to ask api team
                    'SchemeCode' => '13',
                    'TariffCode' => '17', // TODO: need to ask api team
                    'PartnerTrnReferenceNumber' => $quote->uuid ?? 'Q1aw2bvcvT', // TODO: need to ask
                ],
                'OptionalCovers' => [
                    [
                        'CoverIncluded' => true,
                        'CoverMappingCode' => '2-1-0', // TODO: need to ask
                    ],
                ],
                'DriverDetails' => [
                    [
                        'DriverName' => $quote?->latestInsured->first_name.' '.$quote?->latestInsured->last_name,
                        'MainDriverInd' => $quote?->carQuoteRequestDetail?->is_insured_and_driver_same ? 'Y' : 'N',
                        'DriverDOB' => $quote?->carQuoteRequestDetail?->driver_dob.' 00:00:00',
                        'DriverGender' => str_starts_with(strtoupper($quote?->carQuoteRequestDetail?->driver_gender ?? ''), 'M') ? 'M' : 'F',
                        'FirstDrvLicCountry' => $homeCountryLicenseIssuance,
                        'LocalLicense' => $quote?->carQuoteRequestDetail?->driver_uae_driving_experience,
                        'OtherLicense' => $quote?->carQuoteRequestDetail?->home_country_driving_experience,
                        'LicenseNo' => $quote?->carQuoteRequestDetail?->driver_license_number,
                    ],
                ],
                'QuotationNo' => $quote?->carQuotePlanDetail?->insurer_quote_no,
                'PolicyId' => $quote?->carQuotePlanDetail?->insurer_quote_no,
                'EndtId' => '0',
                'ProposalForm' => false,
                'UserComments' => 'Update Quote Request',
            ],
        ];

        $response = $this->livaHttpCall($endPoint, $payload);
        dd($response->object());

        return $response;
    }

    public function retrieveQuoteRequest()
    {
        $endPoint = 'transactions/retrieve/v2';
        $payload['RetrieveRequest'] = [
            'RetrieveType' => '2', // Retrive Type 2 = New Business Quote Retrival
            'TransactionNumber' => '7860255', // Quote number
            'EmailId' => 'john.doe@example.com',
            'PartnerTrnReferenceNumber' => 'Q1aw2bvcvT',
            'ProposalForm' => true,
        ];

        $response = $this->livaHttpCall($endPoint, $payload);

        return $response;
    }

    private function executeCreatePolicyStep($quote, $process): void
    {
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Step Executing : '.PolicyIssuanceEnum::LIVA_CAR_ISSUE_POLICY);
        $policyIssuanceResponse = $this->createPolicy($quote);

        if (! $policyIssuanceResponse['status']) {
            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Policy issuance failed', extra: ['response' => $policyIssuanceResponse]);
            throw new Exception($policyIssuanceResponse['error']);
        }

        $process->update(['completed_step' => $policyIssuanceResponse['completed_step']]);
    }

    public function createPolicy($quote)
    {
        LoggerService::startQuoteLogging($quote);
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' started', extra: [
            'time' => now()->format('Y-m-d H:i:s'),
        ]);

        $myFile = fopen(__DIR__ . '/response.txt', 'r');
        $txt = fread($myFile, filesize(__DIR__ . '/response.txt'));
        fclose($myFile);

        LoggerService::info('document decode work started', extra: [
            'time' => now()->format('Y-m-d H:i:s'),
        ]);
        
        $file = (base64_decode(base64_decode($txt)));

        // Save the decoded PDF data to a file
        $pdfFileName = 'policy_document_' . $quote->code . '_' . date('Y-m-d_H-i-s') . '.pdf';
        
        // Create temp directory if it doesn't exist
        $tempDir = storage_path('temp');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        
        $pdfFilePath = $tempDir . '/' . $pdfFileName;
        
        $bytesWritten = file_put_contents($pdfFilePath, base64_decode($file));
        LoggerService::info('document decode work done', extra: [
            'time' => now()->format('Y-m-d H:i:s'),
        ]);
        
        if ($bytesWritten === false) {
            LoggerService::error('automation:'.$this->className.' fn:'.__FUNCTION__.' Failed to save PDF file at: ' . $pdfFilePath);
            throw new Exception('Failed to save PDF document');
        }

        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' PDF saved successfully', extra: [
            'file_path' => $pdfFilePath,
            'file_size' => $bytesWritten . ' bytes'
        ]);

        // separate the pages, one page should tax invoice, 2nd should be policy schedule and 3rd should be policy document
        $this->separatePdfPages($pdfFilePath, $quote);
        LoggerService::info('document upload work done', extra: [
            'time' => now()->format('Y-m-d H:i:s'),
        ]);

        dd('done');

        // $documents = $issuePolicyResult?->Documents?->PolicyReportsPdf;

        LoggerService::startQuoteLogging($quote);
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' started');

        $response = ['status' => false, 'completed_step' => PolicyIssuanceEnum::LIVA_CAR_ISSUE_POLICY, 'error' => null, 'message' => null];
        $endPoint = 'policy/create/v2';

        $payload = [
            'PolicyRequest' => [
                'QuotationNo' => $quote?->carQuotePlanDetail?->insurer_quote_no ?? 7872837,
                'PremiumPayable' => 840,
                'PartnerTrnReferenceNumber' => $quote->code,
                'Documents' => [
                    'DocsInResponse' => true,
                    'DocsDetails' => [
                        'PolicySchedule' => true,
                        'HirePurchaseLetter' => false,
                        'ProposalForm' => false,
                        'LetterToBank' => false,
                        'MotorArabicCertificate' => true,
                        'Receipt' => true,
                    ]
                ],
                'PolicyConfirmationSMS' => false,
                'PolicyConfirmationEmail' => false,
            ],
        ];

        $issuePolicy = $this->livaHttpCall($endPoint, $payload);
        $issuePolicyResponse = $issuePolicy->object();
        $this->storePolicyIssuanceLog($quote, $payload, $issuePolicyResponse, $this->baseUrl.$endPoint, $response['completed_step'], $this->livaHasError($issuePolicy) ? PolicyIssuanceEnum::FAILED_STATUS : PolicyIssuanceEnum::SUCCESS_STATUS);

        if ($this->livaHasError($issuePolicy)) {
            $errorMessage = $this->extractLivaErrorMessage($issuePolicy);
            $response['error'] = $errorMessage ?? 'Policy issuance failed';

            return $response;
        }

        $issuePolicyResult = $issuePolicyResponse?->PolicyResponse;

        $quote->update([
            'policy_number' => $issuePolicyResult?->PolicyNumber,
            'policy_issuance_date' => $issuePolicyResult?->PolicyCreationDate,
            'policy_start_date' => $issuePolicyResult?->PolicyEffectiveDate,
            'policy_expiry_date' => $issuePolicyResult?->PolicyExpiryDate,
            'price_vat_applicable' => $issuePolicyResult?->PremiumWithoutVAT,
            'vat' => $issuePolicyResult?->VatAmount,
            'vat' => $issuePolicyResult?->Commission,
        ]);

        Payment::where('code', $quote->code)->update([
            'commission_vat_applicable' => $issuePolicyResult?->Commission,
            'commission' => $issuePolicyResult?->Commissionincldvat,
        ]);

        // move it to IMCRM.
        $documents = $issuePolicyResult?->Documents?->PolicyReportsPdf;

        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Policy issued successfully and Quote updated');

        $response['status'] = true;
        $response['message'] = 'Policy issued successfully and Quote updated';

        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' ended');

        return $response;
    }

    private function storePolicyIssuanceLog($quote, $payload, $response, $endPoint, $step, $status = 'success'): void
    {
        $log = PolicyIssuanceLog::create([
            'policy_issuance_id' => $this->policyIssuance->id,
            'model_type' => $quote->getMorphClass(),
            'model_id' => $quote->id,
            'step' => $step,
            'endPoint' => $endPoint,
            'payload' => json_encode($payload),
            'response' => json_encode($response),
            'status' => $status,
        ]);

        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' Policy Issuance ID : '.$this->policyIssuance?->id.' Log ID : '.$log->id);
    }

    private function executeUploadDocumentsStep($quote, $process): void
    {
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Step Executing : '.PolicyIssuanceEnum::LIVA_CAR_UPLOAD_DOCUMENTS);
        $uploadDocumentsResponse = $this->UploadDocuments($quote);

        if (! $uploadDocumentsResponse['status']) {
            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Document upload failed', extra: ['response' => $uploadDocumentsResponse]);
            throw new Exception($uploadDocumentsResponse['error']);
        }

        $process->update(['completed_step' => $uploadDocumentsResponse['completed_step']]);
    }

    public function uploadDocuments($quote)
    {
        LoggerService::startQuoteLogging($quote);
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' started');
        $endPoint = 'documents/upload/v2';
        
        $documents = $quote->documents;
        if ($documents->isEmpty()) {
            return ['status' => false, 'message' => 'No documents found for quote'];
        }

        $livaMapping = app(LivaInsurancePayloadMapping::class);

        $requiredDocuments = array_filter($documents->toArray(), function ($document) {
            return in_array($document['document_type_code'], [DocumentTypeCode::DRIVING_LICENSE, DocumentTypeCode::EMIRATES_ID, DocumentTypeCode::REGISTRATION_CARD_MULKIYA]);
        });

        $attachments = [];

        foreach ($requiredDocuments as $document) {
            try {
                // Get the file path (assuming documents are stored in storage)
                $filePath = config('constants.AZURE_IM_STORAGE_URL').config('constants.AZURE_IM_STORAGE_CONTAINER').'/'.$document['doc_url']; // Adjust path as needed

                // Read file content and convert to base64
                $fileContent = file_get_contents($filePath);
                $base64Content = base64_encode($fileContent);

                // Get file extension
                $extension = pathinfo($filePath, PATHINFO_EXTENSION);

                // Map document type based on your business logic
                $documentType = $livaMapping->getDocumentType($document['document_type_code'] ?? 'other');

                $attachments[] = [
                    'DocumentType' => $documentType,
                    'Content' => $base64Content,
                    'Extension' => $extension,
                ];

                LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Document processed: '.$document['document_type_text']);

            } catch (\Exception $ex) {
                LoggerService::error('automation:'.$this->className.' fn:'.__FUNCTION__.' Error processing document', exception: $ex);

                continue;
            }
        }

        if (empty($attachments)) {
            return ['status' => false, 'message' => 'No valid documents could be processed'];
        }

        $payload['UploadDocumentsRequest'] = [
            'TransactionType' => '5',
            'TransactionNumber' => /* $quote?->carQuotePlanDetail?->insurer_quote_no ?? */ 7872837, // TODO: Use actual quote number
            'Attachments' => $attachments,
        ];

        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Payload created with '.count($attachments).' attachments');

        $response = $this->livaHttpCall($endPoint, $payload);

        $responseStatus = [];
        $allUploadsSuccessful = true;

        foreach ($response->object()->UploadDocumentsResponse->Attachments as $value) {
            $uploadStatus = $value->UploadStatus ?? false;
            $responseStatus[] = [
                'DocumentType' => $value->DocumentType ?? 'Unknown',
                'UploadStatus' => $uploadStatus,
            ];
            
            if (! $uploadStatus) {
                $allUploadsSuccessful = false;
            }
            
            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Document upload status', extra: [
                'DocumentType' => $value->DocumentType,
                'UploadStatus' => $uploadStatus,
            ]);
        }

        if ($allUploadsSuccessful) {
            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' All documents uploaded successfully', extra: [
                'details' => $responseStatus,
            ]);
            $status = true;
        } else {
            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Some documents failed to upload', extra: [
                'details' => $responseStatus,
            ]);
            $status = false;
        }

        return $status;
    }

    public function getVehicleModelId($makeCode)
    {
        $payload = [
            'MDRequest' => [
                'dropdowns' => [
                    'dropdown-name' => 'getVehicleModelListFromMakePartner',
                    'dropdown-criteria' => [
                        [
                            'criteria-name' => 'makeCode',
                            'criteria-value' => $makeCode,
                        ],
                        [
                            'criteria-name' => 'partnerID',
                            'criteria-value' => config('constants.LIVA_PARENT_ID'),
                        ],
                        [
                            'criteria-name' => 'schemeCode',
                            'criteria-value' => '13',
                        ],
                    ],
                ],
            ],
        ];

        $livaMapping = $this->livaHttpCall('v2/masterservices', $payload);

        return collect($livaMapping->object()->getVehicleModelListFromMake->items)
            ->pluck('value', 'name')
            ->mapWithKeys(fn ($value, $key) => [strtoupper(trim($key)) => $value]);
    }

    public function getVehicleVariants($dob, $mobileNo, $makeCode, $modelCode, $modelYear, $refId)
    {
        $payload = [
            'VehicleVariantsRequest' => [
                'DOB' => $dob.' 00:00:00',
                'MobileNo' => $mobileNo,
                'MakeCode' => (int) $makeCode,
                'ModelCode' => (int) $modelCode,
                'ModelYear' => (int) $modelYear,
                'PartnerTrnReferenceNumber' => $refId,
            ],
        ];

        $livaMapping = $this->livaHttpCall('vehicle/variants/v2', $payload);

        return collect($livaMapping->object()->VehicleVariantsResponse->Variants);
    }

    /* public function issuePolicyAndFillPolicyDetails($quote, $selectedPlan, $travelType): array
    {
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' started');

        $response = ['status' => false, 'completed_step' => PolicyIssuanceEnum::ALLIANCE_TRAVEL_ISSUE_POLICY, 'error' => null, 'message' => null];

        $titleTraveller = [];
        $firstNameTraveller = [];
        $lastNameTraveller = [];
        $dobTraveller = [];
        $passportTraveller = [];
        $nationalityTraveller = [];

        $customerMembers = $quote->customerMembers;
        $primaryMember = $this->getPrimaryMember($quote, $customerMembers);
        foreach ($customerMembers as $member) {

            $titleTraveller[] = $this->getTitle($member->gender);
            $firstNameTraveller[] = $member->first_name;
            $lastNameTraveller[] = $member->last_name ?? ' ';
            $dobTraveller[] = $member->dob ? Carbon::parse($member->dob)->format('Y-m-d') : null;
            $passportTraveller[] = $member->passport;
            $nationalityTraveller[] = $member->nationality->alliance_nationality_id;
        }

        $endPoint = '/v1/quote/'.$travelType.'/finalise';
        $payload = [
            'quote_id' => $selectedPlan?->insurer_quote_id,
            'scheme_id' => $selectedPlan?->alliance_scheme_id,
            'title_customer' => $this->getTitle($primaryMember->gender),
            'first_name_customer' => $primaryMember->first_name,
            'last_name_customer' => $primaryMember->last_name,
            'title_traveller' => $titleTraveller,
            'first_name_traveller' => $firstNameTraveller,
            'last_name_traveller' => $lastNameTraveller,
            'dob' => $dobTraveller,
            'passport_number' => $passportTraveller,
            'nationality_traveller' => $nationalityTraveller,
            'email' => 'happiness@support.insurancemarket.ae', // will be static, as we dont share customer contact details outside organization
            'mobile' => '971502245943', // will be static, as we dont share customer contact details outside organization
            'agency_reference' => 'asc',
        ];
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - PayLoad : '.json_encode($payload));

        $issuePolicy = $this->livaHttpCall($endPoint, $payload);
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Response : '.$issuePolicy);

        $issuePolicyResponse = $issuePolicy->object();
        $this->storePolicyIssuanceLog($quote, $payload, $issuePolicyResponse, $this->baseUrl.$endPoint, $response['completed_step'], $issuePolicy->failed() ? PolicyIssuanceEnum::FAILED_STATUS : PolicyIssuanceEnum::SUCCESS_STATUS);

        if ($issuePolicy->failed()) {
            $response['error'] = $issuePolicyResponse?->errors;

            return $response;
        }
        $issuePolicyResult = $issuePolicyResponse?->result;
        $insurerPolicyId = $issuePolicyResult?->policy_id;
        $premium = $issuePolicyResult?->premium;
        $priceVatApplicable = $premium / (1 + ((float) $this->vat / 100));
        $policyIssuanceDate = Carbon::now();
        $coverDays = $this->calculateCoverDaysForExpiryDate($quote, $travelType);
        $policyExpiryDate = Carbon::parse($quote->policy_start_date)->addDays($coverDays)->subDay();

        $quote->update([
            'insurer_policy_id' => $insurerPolicyId,
            'price_with_vat' => $premium,
            'vat' => $premium - $priceVatApplicable,
            'price_vat_applicable' => $priceVatApplicable,
            'policy_issuance_date' => $policyIssuanceDate,
            'policy_expiry_date' => $policyExpiryDate,
        ]);
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Policy Issued Api called successfully and Quote is updated');

        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' ended');

        $response['status'] = true;
        $response['message'] = 'Policy Issued Api called successfully and Quote is updated.';

        return $response;
    } */

    private function matchClosestVehicleVariant($vehicleVariants, $searchCriteria)
    {
        if (empty($vehicleVariants)) {
            return null;
        }

        $closestVariant = null;
        $highestScore = 0;

        foreach ($vehicleVariants as $variant) {
            $score = $this->calculateVariantMatchScore($variant, $searchCriteria);

            if ($score > $highestScore) {
                $highestScore = $score;
                $closestVariant = $variant;
            }
        }

        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' - Best match found with score: '.$highestScore, extra: [
            'variant' => $closestVariant,
            'criteria' => $searchCriteria,
        ]);

        return $closestVariant;
    }

    private function calculateVariantMatchScore($variant, $searchCriteria): float
    {
        $score = 0;
        $maxScore = 0;

        // CC (Engine Capacity) - Weight: 30%
        if (isset($searchCriteria['cc']) && $searchCriteria['cc'] !== null) {
            $ccScore = $this->calculateNumericScore($variant->CC ?? 0, $searchCriteria['cc']);
            $score += $ccScore * 0.3;
        }
        $maxScore += 0.3;

        // Number of Cylinders - Weight: 20%
        if (isset($searchCriteria['noOfCyl']) && $searchCriteria['noOfCyl'] !== null) {
            $cylScore = $this->calculateNumericScore($variant->NoOfCyl ?? 0, $searchCriteria['noOfCyl']);
            $score += $cylScore * 0.2;
        }
        $maxScore += 0.2;

        // Number of Doors - Weight: 20%
        if (isset($searchCriteria['noOfDoors']) && $searchCriteria['noOfDoors'] !== null) {
            $doorScore = $this->calculateNumericScore($variant->NoOfDoors ?? 0, $searchCriteria['noOfDoors']);
            $score += $doorScore * 0.2;
        }
        $maxScore += 0.2;

        // Body Type - Weight: 20%
        if (isset($searchCriteria['bodyType']) && $searchCriteria['bodyType'] !== null) {
            $bodyTypeScore = $this->calculateStringScore($variant->BodyType ?? '', $searchCriteria['bodyType']);
            $score += $bodyTypeScore * 0.2;
        }
        $maxScore += 0.2;

        // Variant Name - Weight: 10%
        if (isset($searchCriteria['trim']) && $searchCriteria['trim'] !== null) {
            $nameScore = $this->calculateStringScore($variant->ModelSpecification ?? '', $searchCriteria['trim']);
            $score += $nameScore * 0.1;
        }
        $maxScore += 0.1;

        // Normalize score to 0-100 range
        return $maxScore > 0 ? ($score / $maxScore) * 100 : 0;
    }

    private function calculateNumericScore($value1, $value2): float
    {
        $val1 = (float) $value1;
        $val2 = (float) $value2;

        if ($val1 == $val2) {
            return 1.0; // Perfect match
        }

        if ($val1 == 0 || $val2 == 0) {
            return 0.0; // No match if one is zero
        }

        $difference = abs($val1 - $val2);
        $average = ($val1 + $val2) / 2;
        $percentageDifference = ($difference / $average) * 100;

        // Return score based on percentage difference
        return match (true) {
            $percentageDifference <= 5 => 0.95,   // Very close
            $percentageDifference <= 10 => 0.85,  // Close
            $percentageDifference <= 20 => 0.70,  // Moderate
            $percentageDifference <= 30 => 0.50,  // Fair
            $percentageDifference <= 50 => 0.30,  // Poor
            default => 0.10                       // Very poor
        };
    }

    private function calculateStringScore($string1, $string2): float
    {
        $str1 = strtolower(trim($string1));
        $str2 = strtolower(trim($string2));

        if ($str1 === $str2) {
            return 1.0; // Perfect match
        }

        if (empty($str1) || empty($str2)) {
            return 0.0; // No match if either is empty
        }

        // Check if one string contains the other
        if (str_contains($str1, $str2) || str_contains($str2, $str1)) {
            return 0.8; // High score for partial match
        }

        // Calculate similarity percentage
        $similarity = 0;
        similar_text($str1, $str2, $similarity);

        return $similarity / 100;
    }

    private function separatePdfPages($pdfFilePath, $quote): void
    {
        try {
            // Initialize FPDI
            $pdf = new \setasign\Fpdi\Fpdi();
            
            // Get the number of pages
            $pageCount = $pdf->setSourceFile($pdfFilePath);
            
            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Total pages found: ' . $pageCount);
            
            // Define page mappings
            $pageMap = [
                1 => 'tax_invoice',
                2 => 'policy_schedule', 
                3 => 'policy_document'
            ];
            
            // Extract each page
            for ($pageNum = 1; $pageNum <= $pageCount; $pageNum++) {
                if (isset($pageMap[$pageNum])) {
                    $this->extractSinglePage($pdfFilePath, $pageNum, $pageMap[$pageNum], $quote);
                } else {
                    LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Skipping page ' . $pageNum . ' (not mapped)');
                }
            }
            
            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' PDF pages separated successfully');
            
        } catch (\Exception $e) {
            LoggerService::error('automation:'.$this->className.' fn:'.__FUNCTION__.' Error separating PDF pages', exception: $e);
            throw new Exception('Failed to separate PDF pages: ' . $e->getMessage());
        }
    }

    private function extractSinglePage($sourcePdfPath, $pageNumber, $documentType, $quote): void
    {
        try {
            // Create new PDF instance
            $pdf = new \setasign\Fpdi\Fpdi();
            
            // Set source file
            $pdf->setSourceFile($sourcePdfPath);
            
            // Import the specific page
            $templateId = $pdf->importPage($pageNumber);
            
            // Get page size
            $size = $pdf->getTemplateSize($templateId);
            
            // Add page with same orientation and size
            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            
            // Use the imported page
            $pdf->useTemplate($templateId);
            
            // Generate filename
            $filename = $documentType . '_' . $quote->code . '_' . date('Y-m-d_H-i-s') . '.pdf';
            
            // Create temp directory if it doesn't exist
            $tempDir = storage_path('temp');
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }
            
            $outputPath = $tempDir . '/' . $filename;
            
            // Save the single page PDF
            $pdf->Output($outputPath, 'F');

            // Create UploadedFile object from generated PDF
            $file = new UploadedFile(
                $outputPath,
                $filename,
                'application/pdf',
                null,
                true // test mode - don't validate file was uploaded via HTTP
            );
            
            // Map document types to document type codes
            $documentTypeCode = $this->getDocumentTypeCode($documentType);
            
            // Prepare data array for fetchUploadDocument
            $data = [
                'document_type_code' => $documentTypeCode,
                'quote_uuid' => $quote->uuid,
            ];
            
            // Set request parameters needed by fetchUploadDocument
            request()->merge([
                'quote_type_id' => $quote->quote_type_id ?? null,
                'quote_type' => $this->getQuoteType($quote),
                'is_send_update' => false, // Set to true if this is for send update
                // 'send_update_id' => $sendUpdateId, // Only if is_send_update is true
            ]);
            
            // Get file size before uploading
            $fileSize = filesize($outputPath);
            
            // Call fetchUploadDocument using PersonalQuoteRepository
            $result = PersonalQuoteRepository::uploadDocument($quote->id, $file, $data);
            
            // Clean up temporary file
            if (file_exists($outputPath)) {
                unlink($outputPath);
            }
            
            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Page ' . $pageNumber . ' extracted and uploaded successfully', extra: [
                'document_type' => $documentType,
                'document_type_code' => $documentTypeCode,
                'upload_result' => $result,
                'file_size' => $fileSize . ' bytes'
            ]);
            
        } catch (\Exception $e) {
            LoggerService::error('automation:'.$this->className.' fn:'.__FUNCTION__.' Error extracting page ' . $pageNumber, exception: $e);
            throw new Exception('Failed to extract page ' . $pageNumber . ': ' . $e->getMessage());
        }
    }

    /**
     * Map document types to document type codes
     *
     * @param string $documentType
     * @return string
     */
    private function getDocumentTypeCode(string $documentType): string
    {
        return match($documentType) {
            'tax_invoice' => 'TI',
            'policy_schedule' => 'CPS',
            'policy_document' => 'CPC',
            default => 'UNKNOWN_DOCUMENT'
        };
    }

    /**
     * Get quote type from quote object
     *
     * @param object $quote
     * @return string
     */
    private function getQuoteType(object $quote): string
    {
        // You can adjust this logic based on your quote object structure
        return $quote->quote_type ?? 'Car';
    }
}

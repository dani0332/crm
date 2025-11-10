<?php

namespace App\Services\PolicyIssuanceAutomation;

use App\Enums\CarRegistrationType;
use App\Enums\DocumentTypeCode;
use App\Enums\InsuranceProviderEnum;
use App\Enums\InsuranceProvidersEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\TeamNameEnum;
use App\Enums\UserNameEnum;
use App\Enums\WorkflowTypeEnum;
use App\Jobs\AutomationFailedJob;
use App\Jobs\PolicyIssuanceJob;
use App\Jobs\SendBookPolicyDocumentsJob;
use App\Models\PolicyIssuance;
use App\Models\PolicyIssuanceLog;
use App\Models\QuoteDocument;
use App\Services\Logger\LoggerService;
use App\Services\PolicyIssuanceAutomation\Car\GIGInsuranceService;
use App\Services\PolicyIssuanceAutomation\Car\LivaInsuranceService;
use App\Services\PolicyIssuanceAutomation\Travel\AllianceInsuranceService;
use App\Traits\GenericQueriesAllLobs;
use Carbon\Carbon;

class PolicyIssuanceService
{
    use GenericQueriesAllLobs;

    private string $className = 'policyIssuanceService';

    public function __construct() {}

    public function init($quoteType, $insurerCode)
    {
        return match (ucfirst($quoteType)) {
            QuoteTypes::TRAVEL->value => match ($insurerCode) {
                InsuranceProviderEnum::ALNC->value => new AllianceInsuranceService,
                default => null,
            },
            QuoteTypes::CAR->value => match ($insurerCode) {
                InsuranceProvidersEnum::RSA => new LivaInsuranceService,
                InsuranceProvidersEnum::AXA => new GIGInsuranceService,

                default => null,
            },
            default => null,
        };
    }

    public function checkAllowedAutomations($quoteType, $quote)
    {
        $allowedQuoteTypes = [QuoteTypes::CAR->value];
        $allowedInsuranceProviders = [InsuranceProvidersEnum::AXA, InsuranceProvidersEnum::RSA];

        $payment = $quote->payments()->mainLeadPayment()->first();
        $insuranceProvider = getInsuranceProvider($payment, $quoteType);

        if (! $insuranceProvider) {
            return false;
        }

        if (! in_array(ucfirst($quoteType), $allowedQuoteTypes)) {
            return false;
        }

        if (! in_array($insuranceProvider?->code, $allowedInsuranceProviders)) {
            return false;
        }

        if (
            $insuranceProvider?->code === InsuranceProvidersEnum::RSA &&
            $quote?->registration_type !== CarRegistrationType::PERSONAL
        ) {
            return false;
        }

        return true;
    }

    public function schedulePolicyIssuance($quote, $insurer, $quoteType, $logFor)
    {
        $policyIssuance = $quote->policyIssuance;

        if ($policyIssuance) {
            LoggerService::info('automation:'.$logFor.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' -  Policy Issuance Schedule already exists PID : '.$policyIssuance->id);
        } else {
            $policyIssuance = PolicyIssuance::create([
                'insurance_provider_id' => $insurer->id, 'model_type' => $quote->getMorphClass(), 'model_id' => $quote->id, 'quote_type' => $quoteType, 'status' => PolicyIssuanceEnum::PENDING_STATUS,
            ]);
            LoggerService::info('automation:'.$logFor.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' -  Policy Issuance Schedule created PID : '.$policyIssuance->id);
        }
    }
    public function executePolicyIssuanceAutomationSteps()
    {
        info('cmd:'.$this->className.' fn:'.__FUNCTION__);

        /* Get Unique Insurer per lob to get the statuses for which automation is enabled */
        $uniqueInsurerListByLob = PolicyIssuance::with(['insuranceProvider:id,code,text'])
            ->whereIn('status', [PolicyIssuanceEnum::PENDING_STATUS, PolicyIssuanceEnum::BOOKING_PENDING_STATUS, PolicyIssuanceEnum::TIMEOUT_STATUS])
            ->select(['quote_type', 'insurance_provider_id'])
            ->distinct()->get();

        if (count($uniqueInsurerListByLob) > 0) {
            info('cmd:'.$this->className.' fn:'.__FUNCTION__.' - Total Unique Insurer List By LOB Count : '.count($uniqueInsurerListByLob));
            /* Get the statuses for which automation is enabled for insurers against each LOB */
            $policyIssuanceInsurerAutomationStatuses = $this->getInsurerAutomationStatus($uniqueInsurerListByLob);
            foreach ($policyIssuanceInsurerAutomationStatuses as $policyIssuanceInsurerAutomationStatus) {
                info('cmd:'.$this->className.' fn:'.__FUNCTION__.' - Process Automation for Quote Type : '.$policyIssuanceInsurerAutomationStatus->quote_type.' - Insurer : '.$policyIssuanceInsurerAutomationStatus?->insuranceProvider?->code);
                /* process policy issuance automation for each insurer against each LOB */
                $this->processPolicyIssuanceRecords($policyIssuanceInsurerAutomationStatus);
            }
        } else {
            info('cmd:'.$this->className.' fn:'.__FUNCTION__.' - No Unique Insurer found');
        }
    }

    public function getInsurerAutomationStatus($policyIssuanceProcesses)
    {
        info('cmd:'.$this->className.' fn:'.__FUNCTION__);
        foreach ($policyIssuanceProcesses as $policyIssuanceProcess) {
            $statuses = [];
            $quoteType = $policyIssuanceProcess?->quote_type;
            $insuranceProvider = $policyIssuanceProcess?->insuranceProvider;
            if ($this->init($quoteType, $insuranceProvider?->code)?->isPolicyIssuanceAutomationEnabled()) {
                $statuses[] = PolicyIssuanceEnum::PENDING_STATUS;
                $statuses[] = PolicyIssuanceEnum::BOOKING_PENDING_STATUS;
            }
            if ($this->init($quoteType, $insuranceProvider?->code)?->isPolicyIssuanceAutomationRetryEnabledForTimeout()) {
                $statuses[] = PolicyIssuanceEnum::TIMEOUT_STATUS;
            }
            $policyIssuanceProcess->statuses = $statuses;
        }

        return $policyIssuanceProcesses;
    }

    public function getPolicyIssuanceStepsStatus($quote, $quoteType, $throughAutomation = false): array
    {
        $response = [
            'isPolicyAutomationEnabled' => true,
        ];
        $payment = $quote->payments()->mainLeadPayment()->first();
        $insuranceProvider = getInsuranceProvider($payment, $quoteType);
        $insuranceProviderAutomation = $this->init($quoteType, $insuranceProvider?->code);
        if (! $insuranceProviderAutomation || ! $insuranceProviderAutomation?->isPolicyIssuanceAutomationEnabled()) {
            $response['isPolicyAutomationEnabled'] = false;

            return $response;
        }

        return array_merge($response, $insuranceProviderAutomation->getStepsLockingStatus($quote, $throughAutomation));
    }

    private function processPolicyIssuanceRecords($policyIssuanceAutomationStatus)
    {
        $quoteType = $policyIssuanceAutomationStatus?->quote_type;
        $insuranceProvider = $policyIssuanceAutomationStatus?->insuranceProvider;
        $statuses = $policyIssuanceAutomationStatus?->statuses;

        /* Fetch Policy Issuance Records against statuses by each LOB and Insurer */
        $policyIssuanceQuery = PolicyIssuance::where(['quote_type' => $quoteType, 'insurance_provider_id' => $insuranceProvider->id])
            ->whereIn('status', [PolicyIssuanceEnum::PENDING_STATUS, PolicyIssuanceEnum::BOOKING_PENDING_STATUS]);
        $policyIssuanceCount = $policyIssuanceQuery->count();
        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Process Records for Quote Type: '.$quoteType.', Insurer : '.$insuranceProvider?->code.' - Count : '.$policyIssuanceCount.' - Statuses : '.json_encode($statuses));
        if ($policyIssuanceCount > 0) {
            $policyIssuanceQuery->chunk(100, function ($policyIssuanceProcesses) {
                foreach ($policyIssuanceProcesses as $policyIssuanceProcess) {
                    info('automation:'.$this->className.' fn:'.__FUNCTION__.' PID: '.$policyIssuanceProcess->id.' dispatch automation job');
                    PolicyIssuanceJob::dispatch($policyIssuanceProcess->id)->onQueue('policy-issuance-automation');
                    info('automation:'.$this->className.' fn:'.__FUNCTION__.' PID: '.$policyIssuanceProcess->id.' automation job dispatched');
                }
            });
        } else {
            info('automation:'.$this->className.' fn:'.__FUNCTION__.' No Records found for Quote Type: '.$quoteType.', Insurer : '.$insuranceProvider?->code.' - Statuses : '.json_encode($statuses));
        }

    }

    /**
     * Process policy issuance entries that are stuck in processing status
     */
    public function processStuckPolicyIssuances()
    {
        $fifteenMinutesAgo = Carbon::now()->subMinutes(15);

        // Find all policy issuance entries stuck in processing status for more than 15 minutes
        $stuckPolicyIssuanceAutomations = PolicyIssuance::where('status', PolicyIssuanceEnum::PROCESSING_STATUS)->where('updated_at', '<', $fifteenMinutesAgo);

        $count = $stuckPolicyIssuanceAutomations->count();
        LoggerService::info('cmd:'.$this->className.' fn:'.__FUNCTION__.' Found '.$count.' stuck policy issuance processes');

        $stuckPolicyIssuanceAutomations->chunk(1000, function ($policyIssuanceAutomations) {
            foreach ($policyIssuanceAutomations as $policyIssuance) {
                try {
                    LoggerService::info('cmd:'.$this->className.' fn:'.__FUNCTION__.' trigger for policy issuance ID: '.$policyIssuance->id);

                    $this->markPolicyIssuanceFailed($policyIssuance);

                    LoggerService::info('cmd:'.$this->className.' fn:'.__FUNCTION__.' triggered for policy issuance ID: '.$policyIssuance->id);
                } catch (\Exception $e) {
                    LoggerService::info('cmd:'.$this->className.' fn:'.__FUNCTION__.' Exception occurred while processing policy issuance ID: '.$policyIssuance->id.'. Error: '.$e->getMessage());
                }
            }
        });

    }

    /**
     * Mark a policy issuance as failed and handle the related processes
     */
    private function markPolicyIssuanceFailed(PolicyIssuance $policyIssuance)
    {
        info('cmd:'.$this->className.' fn:'.__FUNCTION__.' Policy Issuance ID : '.$policyIssuance->id.' Started');

        $quote = $policyIssuance->model;
        $quoteType = $policyIssuance?->quote_type;
        $insuranceProvider = $policyIssuance?->insuranceProvider;

        if (! $insuranceProvider) {
            info('job:'.$this->className.' fn:'.__FUNCTION__.' Quote :  '.$quote->code.' - Insurance Provider not found');

            return;
        }
        $insurerPolicyAutomation = (new PolicyIssuanceService)->init($quoteType, $insuranceProvider->code);

        $updatePolicyIssuanceData['status'] = PolicyIssuanceEnum::FAILED_STATUS;

        if (! $quote) {
            info('cmd:'.$this->className.' fn:'.__FUNCTION__.' Quote not found for policy issuance ID: '.$policyIssuance?->id);
            if (! $policyIssuance->message) {
                $updatePolicyIssuanceData['message'] = json_encode(['error' => 'Quote not found']);
            }
            $policyIssuance->update($updatePolicyIssuanceData);

            return;
        }

        info('cmd:'.$this->className.' fn:'.__FUNCTION__.' Processing Quote: '.$quote->code.' - Policy Issuance ID: '.$policyIssuance?->id);

        // Update policy issuance status to failed
        if (! $policyIssuance->message) {
            $updatePolicyIssuanceData['message'] = json_encode(['error' => 'Policy issuance process stuck for more than 15 minutes']);
        }
        $policyIssuance->update($updatePolicyIssuanceData);

        info('cmd:'.$this->className.' fn:'.__FUNCTION__.' Policy issuance marked as failed for Quote: '.$quote->code.' and Policy Issuance ID : '.$policyIssuance?->id);

        $insurerApiStatus = $insurerPolicyAutomation->getInsurerAPIStatusByStep($policyIssuance);

        if ($quoteType === QuoteTypes::CAR->value && in_array($insuranceProvider->code, [InsuranceProvidersEnum::RSA, InsuranceProvidersEnum::AXA])) {
            $this->updateAPIIssuanceAndInsurerStatus($quote, $quoteType, $insurerApiStatus, PolicyIssuanceEnum::POLICY_ISSUANCE_API_STATUS_NO_ID);
        } else {
            // TODO:: This should be updated with the new function in PolicyIssuanceService
            $insurerPolicyAutomation?->updateQuoteApiIssuanceStatusAndAllocate($quote, $insurerApiStatus, PolicyIssuanceEnum::POLICY_ISSUANCE_API_STATUS_NO_ID);
        }

        info('cmd:'.$this->className.' fn:'.__FUNCTION__.' Completed processing for Quote: '.$quote->code.' and Policy Issuance ID : '.$policyIssuance?->id);
    }

    public function documentUploadPreChecks($quoteTypeId, $quote, $requiredDocs)
    {
        $quoteDocuments = QuoteDocument::where('quote_documentable_type', get_class($quote))
            ->where('quote_documentable_id', $quote->id)
            ->whereHas('documentType', function ($query) use ($quoteTypeId) {
                $query->where([
                    'category' => DocumentTypeCode::QUOTE,
                    'quote_type_id' => $quoteTypeId,
                    'is_active' => 1,
                ]);
            })
            ->pluck('document_type_code')
            ->toArray();

        $isRequiredDocsUploaded = count(array_diff($requiredDocs, $quoteDocuments)) === 0;

        if (! $isRequiredDocsUploaded) {
            return ['status' => false, 'message' => 'Required documents not uploaded for policy issuance automation'];
        }

        return ['status' => true, 'message' => 'Required documents uploaded for policy issuance automation'];
    }

    public function storePolicyIssuanceLog($quote, $payload, $response, $endPoint, $step, $status, $policyIssuance): void
    {
        $log = PolicyIssuanceLog::create([
            'policy_issuance_id' => $policyIssuance->id,
            'model_type' => $quote->getMorphClass(),
            'model_id' => $quote->id,
            'step' => $step,
            'endPoint' => $endPoint,
            'payload' => json_encode($payload),
            'response' => json_encode($response),
            'status' => $status,
        ]);

        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' PID : '.$policyIssuance?->id.' Policy Issuance Log ID : '.$log->id);
    }

    public function updateAPIIssuanceAndInsurerStatus($quote, $quoteType, $newInsurerApiStatus = null, $newApiIssuanceStatus = null, $processInvolved = null)
    {
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' Started');
        $policyIssuanceAutomation = $quote->policyIssuance;
        $isPolicyBooked = $quote->quote_status_id === QuoteStatusEnum::PolicyBooked;
        $isPolicyBookingFailed = $quote->quote_status_id === QuoteStatusEnum::POLICY_BOOKING_FAILED;

        $isPolicyAutomationStatusCompleted = $policyIssuanceAutomation?->status == PolicyIssuanceEnum::COMPLETED_STATUS;
        $insurerApiStatus = $quote?->insurer_api_status;
        $apiIssuanceStatus = $quote?->api_issuance_status;

        $isInsurerApiStatusAlreadyFailed = $quote->isBookingFailed() || $quote->isPolicyIssuanceFailed();
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' Existing Insurer API Status : '.$isInsurerApiStatusAlreadyFailed);

        // /* If Quote API issuance status is not already set, than set insurer api and api issuance status */
        if (! $apiIssuanceStatus) {
            if ($isPolicyAutomationStatusCompleted && $isPolicyBooked && ! $insurerApiStatus) {
                $newApiIssuanceStatus = PolicyIssuanceEnum::POLICY_ISSUANCE_API_STATUS_YES_ID;

            } elseif ($isPolicyAutomationStatusCompleted && $isPolicyBookingFailed) {
                if (! $insurerApiStatus) {
                    $newInsurerApiStatus = PolicyIssuanceEnum::PIA_BOOK_POLICY_API_FAILED_STATUS_ID;
                }
                $newApiIssuanceStatus = PolicyIssuanceEnum::POLICY_ISSUANCE_API_STATUS_NO_ID;
            }
        }
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code, extra: [
            'insurerApiStatus' => $insurerApiStatus, 'apiIssuanceStatus' => $apiIssuanceStatus,
            'isPolicyAutomationStatusCompleted' => $isPolicyAutomationStatusCompleted,
            'isPolicyBooked' => $isPolicyBooked,
            'isPolicyBookingFailed' => $isPolicyBookingFailed,
            'newInsurerApiStatus' => $newInsurerApiStatus,
            'newApiIssuanceStatus' => $newApiIssuanceStatus,
        ]);

        $statusAPIFailed = null;
        if ($quoteType === QuoteTypes::CAR->value) {
            $statusAPIFailed = $this->getInsurerAPIStatuses($newInsurerApiStatus);
        }
        $this->updateQuoteInsurerApiStatus($quote, $newInsurerApiStatus);
        $this->updateQuoteApiIssuanceStatus($quote, $newApiIssuanceStatus);
        $this->allocateLead($quoteType, $quote, $isInsurerApiStatusAlreadyFailed, $statusAPIFailed, $processInvolved);

        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' ended');
    }

    private function updateQuoteInsurerApiStatus($quote, $newInsurerApiStatus)
    {
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' update Quote Insurer API  Status : '.$newInsurerApiStatus);
        if ($newInsurerApiStatus) {
            $quote->update(['insurer_api_status_id' => $newInsurerApiStatus]);
        }
    }

    private function updateQuoteApiIssuanceStatus($quote, $newApiIssuanceStatus)
    {
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' update Quote API Issuance Status : '.$newApiIssuanceStatus);
        if ($newApiIssuanceStatus) {
            $quote->update(['api_issuance_status_id' => $newApiIssuanceStatus]);
        }
    }

    public function allocateLead($quoteType, $quote, $isInsurerApiStatusAlreadyFailed, $statusAPIFailed = null, $processInvolved = null)
    {
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' allocation of failed lead executed');
        $uuid = $quote->uuid;
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Going to allocate failed lead');

        $advisorId = $quote?->advisor_id;
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' - Quote Code : '.$quote->code.' -  check if advisor id already assigned ', extra: [
            'advisorId' => $advisorId,
        ]);

        /* $unassistedTeamId = getTeamId(TeamNameEnum::SIC_UNASSISTED);
        if (! $advisorId) {
            $response = QuoteTypes::getName($quoteType)->allocate($uuid, $unassistedTeamId);
            if ($response && $response['advisorId']) {
                $advisorId = $response['advisorId'];
                LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' - Quote Code : '.$quote->code.' -  Assigned Advisor through Allocation', extra: [
                    'advisorId' => $advisorId,
                ]);
            }
        }

        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' - Quote Code : '.$quote->code.' -  Assigned Advisor', extra: [
            'advisorId' => $advisorId,
        ]); */

        if ($quoteType === QuoteTypes::CAR->value) {
            // For other quote types, we need to dispatch respective failure email job
            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' - Going to dispatch AutomationFailedJob', extra: [
                'actionRequired' => 'Please coordinate with the IT Department to address and rectify the issue.',
                'statusAPIFailed' => $statusAPIFailed,
                'processInvolved' => $processInvolved,
            ]);
            AutomationFailedJob::dispatch(
                $quote->id,
                QuoteTypeId::Car,
                'Please coordinate with the IT Department to address and rectify the issue.',
                $statusAPIFailed,
                $processInvolved,
                WorkflowTypeEnum::CAR_AUTOMATION_FAILED,
                UserNameEnum::PA_USER
            )->onQueue('policy-issuance-automation');
            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' - AutomationFailedJob Dispatched');
        }

        if ($advisorId) {
            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' - Quote Code : '.$quote->code.' -  Quote Document Customer Email Checks', extra: [
                'advisorId' => $advisorId,
                'quote status id' => QuoteStatusEnum::PolicyBooked,
            ]);

            if ($quote->quote_status_id === QuoteStatusEnum::PolicyBooked) {
                // Here we need to dispatch document email
                $data = new \stdClass;
                $data->model_type = $quoteType;
                $data->quote_id = $quote->id;
                LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' - Quote Code : '.$quote->code.' - Dispatching SendBookPolicyDocumentsJob advisor id '.$quote->advisor_id);
                SendBookPolicyDocumentsJob::dispatch($data, $quote->code);
            }
        }
    }

    public function getInsurerAPIStatuses($status = null, $onlyKeys = false)
    {
        $statuses = [
            PolicyIssuanceEnum::PIA_UPLOAD_POLICY_DOCUMENTS_API_FAILED_STATUS_ID => PolicyIssuanceEnum::PIA_UPLOAD_POLICY_DOCUMENTS_API_FAILED,
            PolicyIssuanceEnum::PIA_POLICY_ISSUANCE_API_FAILED_STATUS_ID => PolicyIssuanceEnum::PIA_POLICY_ISSUANCE_API_FAILED,
            PolicyIssuanceEnum::PIA_GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM_API_FAILED_STATUS_ID => PolicyIssuanceEnum::PIA_GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM_API_FAILED,
            PolicyIssuanceEnum::PIA_OCR_PROCESSING_API_FAILED_STATUS_ID => PolicyIssuanceEnum::PIA_OCR_PROCESSING_API_FAILED,
            PolicyIssuanceEnum::PIA_BOOK_POLICY_API_FAILED_STATUS_ID => PolicyIssuanceEnum::PIA_BOOK_POLICY_API_FAILED,
            PolicyIssuanceEnum::PIA_PREVIOUS_POLICY_EXPIRED_STATUS_ID => PolicyIssuanceEnum::PIA_PREVIOUS_POLICY_EXPIRED,
        ];

        if ($onlyKeys) {
            return array_keys($statuses);
        }

        return $status !== null ? ($statuses[$status] ?? null) : $statuses;
    }
}

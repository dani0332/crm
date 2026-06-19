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
use App\Jobs\SendTravelQatarFailedAllocationEmailJob;
use App\Models\PolicyIssuance;
use App\Models\PolicyIssuanceLog;
use App\Models\QuoteDocument;
use App\Models\TravelQuote;
use App\Services\HealthEmailService;
use App\Services\Logger\LoggerService;
use App\Services\PolicyIssuanceAutomation\Car\GIGInsuranceService;
use App\Services\PolicyIssuanceAutomation\Car\LivaInsuranceService;
use App\Services\PolicyIssuanceAutomation\Cyber\AwnicInsuranceService;
use App\Services\PolicyIssuanceAutomation\Device\SmartPhone\NationalGeneralInsurance\NgiInsuranceService;
use App\Services\PolicyIssuanceAutomation\Health\Adnic\AdnicInsuranceService;
use App\Services\PolicyIssuanceAutomation\Travel\Dic\DicInsuranceService;
use App\Services\PolicyIssuanceAutomation\Travel\QatarInsuranceService;
use App\Services\Quotes\DeviceQuoteService;
use App\Traits\GenericQueriesAllLobs;
use Carbon\Carbon;
use Illuminate\Http\Client\Response as HttpClientResponse;
use JsonSerializable;

class PolicyIssuanceService
{
    use GenericQueriesAllLobs;

    private string $className = 'policyIssuanceService';

    private const ALLOCATABLE_QUOTE_TYPES = [
        QuoteTypes::DEVICE,
    ];

    public function __construct() {}

    public function init($quoteType, $insurerCode)
    {
        return match (ucfirst($quoteType)) {
            QuoteTypes::TRAVEL->value => match ($insurerCode) {
                InsuranceProviderEnum::DIC->value => app(DicInsuranceService::class),
                InsuranceProviderEnum::QIC->value => new QatarInsuranceService,
                default => null,
            },
            QuoteTypes::CAR->value => match ($insurerCode) {
                InsuranceProvidersEnum::RSA => new LivaInsuranceService,
                InsuranceProvidersEnum::AXA => new GIGInsuranceService,
                default => null,
            },
            QuoteTypes::DEVICE->value => match ($insurerCode) {
                InsuranceProvidersEnum::NGI => app(NgiInsuranceService::class),
                default => null,
            },
            QuoteTypes::HEALTH->value => match ($insurerCode) {
                InsuranceProvidersEnum::ADNIC => app(AdnicInsuranceService::class),
                default => null,
            },
            QuoteTypes::CYBER->value => match ($insurerCode) {
                InsuranceProvidersEnum::AWNI => app(AwnicInsuranceService::class),
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

        LoggerService::info('Policy Issuance Allowed Automations Check', extra: [
            'quote_type' => $quoteType,
            'insurance_provider' => $insuranceProvider?->code ?? 'N/A',
            'registration_type' => $quote?->registration_type ?? 'N/A',
        ]);

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

    /**
     * Returns false incase of automation is enabled and policy issuance is not failed
     * Returns true incase of automation is disabled or policy issuance is failed
     *
     * @param  mixed  $quote
     * @param  mixed  $quoteType
     * @return bool
     */
    public function shouldValidateBranch($quote, $quoteType)
    {
        $payment = $quote?->payments()?->mainLeadPayment()?->first();
        $insuranceProvider = getInsuranceProvider($payment, $quoteType);
        $insuranceProviderAutomation = $this->init($quoteType, $insuranceProvider?->code);
        if ($insuranceProviderAutomation?->isPolicyIssuanceAutomationEnabled() == true) {

            $policyIssuance = $quote?->policyIssuance;
            /* If policy issuance is failed, then we need to check the branch because it will be manually booked */
            if ($policyIssuance?->status === PolicyIssuanceEnum::FAILED_STATUS) {
                return true;
            }

            return false;
        }

        return true;
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
                    PolicyIssuanceJob::dispatch($policyIssuanceProcess->id);
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

        if (! $insurerPolicyAutomation) {
            info('cmd:'.$this->className.' fn:'.__FUNCTION__.' Insurer policy automation not found for Quote: '.$quote->code.' - Quote Type: '.$quoteType.' - Insurer Code: '.$insuranceProvider->code.' - Policy Issuance ID: '.$policyIssuance?->id);

            return;
        }

        $insurerApiStatus = $insurerPolicyAutomation->getInsurerAPIStatusByStep($policyIssuance);
        $shouldUpdateAPIIssuanceAndInsurerStatus = (new PolicyIssuanceService)->shouldUpdateAPIIssuanceAndInsurerStatus($quoteType, $insuranceProvider);

        if (($quoteType === QuoteTypes::CAR->value && in_array($insuranceProvider->code, [InsuranceProvidersEnum::RSA, InsuranceProvidersEnum::AXA])) || $shouldUpdateAPIIssuanceAndInsurerStatus) {
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
        $responsePayload = $this->resolvePolicyIssuanceLogResponse($response);

        $log = PolicyIssuanceLog::create([
            'policy_issuance_id' => $policyIssuance->id,
            'model_type' => $quote->getMorphClass(),
            'model_id' => $quote->id,
            'step' => $step,
            'endPoint' => $endPoint,
            'payload' => json_encode($payload),
            'response' => json_encode($responsePayload),
            'status' => $status,
        ]);

        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' PID : '.$policyIssuance?->id.' Policy Issuance Log ID : '.$log->id);
    }

    private function resolvePolicyIssuanceLogResponse(mixed $response): mixed
    {
        if ($response instanceof HttpClientResponse) {
            $responsePayload = $response->json();

            $resolvedResponse = $responsePayload === null
                ? $response->body()
                : $responsePayload;

            return $this->normalizeLogResponseValue($resolvedResponse);
        }

        return $this->normalizeLogResponseValue($response);
    }

    private function convertObjectToArray(object $object): array
    {
        $array = [];

        foreach ((array) $object as $key => $value) {
            $array[$key] = $this->normalizeLogResponseValue($value);
        }

        return $array;
    }

    private function normalizeLogResponseValue(mixed $value): mixed
    {
        if (is_object($value)) {
            if (method_exists($value, 'toArray')) {
                return $value->toArray();
            }

            if ($value instanceof JsonSerializable) {
                return $value->jsonSerialize();
            }

            return $this->convertObjectToArray($value);
        }

        if (is_array($value)) {
            return array_map([$this, 'normalizeLogResponseValue'], $value);
        }

        return $value;
    }

    public function shouldUpdateAPIIssuanceAndInsurerStatus($quoteType, $insuranceProvider): bool
    {
        if (! $insuranceProvider) {
            return false;
        }

        return match ($quoteType) {
            QuoteTypes::DEVICE->value => $insuranceProvider->code === InsuranceProvidersEnum::NGI,
            QuoteTypes::CYBER->value => $insuranceProvider->code === InsuranceProvidersEnum::AWNI,
            QuoteTypes::CAR->value => in_array($insuranceProvider->code, [InsuranceProvidersEnum::RSA, InsuranceProvidersEnum::AXA]),
            QuoteTypes::HEALTH->value => $insuranceProvider->code === InsuranceProvidersEnum::ADNIC,
            default => false,
        };
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

        if (in_array($quoteType, [QuoteTypes::CAR->value, QuoteTypes::HEALTH->value, QuoteTypes::CYBER->value, QuoteTypes::DEVICE->value]) && $processInvolved) {
            $statusAPIFailed = $this->getInsurerAPIStatuses($newInsurerApiStatus);
        }
        $this->updateQuoteInsurerApiStatus($quote, $newInsurerApiStatus, $quoteType);
        $this->updateQuoteApiIssuanceStatus($quote, $newApiIssuanceStatus, $quoteType);
        $this->allocateLead($quoteType, $quote, $isInsurerApiStatusAlreadyFailed, $statusAPIFailed, $processInvolved);

        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' ended');
    }

    private function updateQuoteInsurerApiStatus($quote, $newInsurerApiStatus, $quoteType)
    {
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' update Quote Insurer API  Status : '.$newInsurerApiStatus);
        if ($newInsurerApiStatus) {
            $quote->update(['insurer_api_status_id' => $newInsurerApiStatus]);
        }
    }

    private function updateQuoteApiIssuanceStatus($quote, $newApiIssuanceStatus, $quoteType)
    {
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' update Quote API Issuance Status : '.$newApiIssuanceStatus);
        if ($newApiIssuanceStatus) {
            $quote->update(['api_issuance_status_id' => $newApiIssuanceStatus]);
        }
    }

    /**
     * This function is used to allocate a lead to an advisor for failed and passed cases both no just for the failure case
     *
     * @param [type] $quoteType
     * @param [type] $quote
     * @param [type] $isInsurerApiStatusAlreadyFailed
     * @param  string  $statusAPIFailed
     * @param  string  $processInvolved
     * @return void
     */
    public function allocateLead($quoteType, $quote, $isInsurerApiStatusAlreadyFailed, $statusAPIFailed = '', $processInvolved = '')
    {
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' allocation of failed lead executed');
        $uuid = $quote->uuid;
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Going to allocate failed lead');

        $advisorId = $quote?->advisor_id;
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' - Quote Code : '.$quote->code.' -  check if advisor id already assigned ', extra: [
            'advisorId' => $advisorId,
        ]);

        $isPolicyBooked = $quote->quote_status_id === QuoteStatusEnum::PolicyBooked;

        // Assign advisor to lead for cyber policy issuance automation if not assigned and policy is booked only for cyber
        if (! $advisorId && $isPolicyBooked && $quoteType == QuoteTypes::CYBER->value) {
            $this->triggerAdvisorAllocation($quoteType, $quote, $advisorId);
        } elseif (! $advisorId && $isPolicyBooked) {
            $allocationResult = $this->attemptAdvisorAllocation($quoteType, $uuid);
            $advisorId = $allocationResult['advisorId'] ?? null;

            if ($allocationResult['allocationAttempted'] ?? false) {
                LoggerService::info('Quote Code : '.$quote->code.' -  Assigned Advisor through Allocation', extra: [
                    'advisorId' => $advisorId,
                    'allocation_response' => $allocationResult['response'] ?? null,
                ]);
            }
        }

        $hasFailureContext = ! empty($statusAPIFailed) && ! empty($processInvolved);
        $isCarOrCyber = in_array($quoteType, [QuoteTypes::CAR->value, QuoteTypes::CYBER->value, QuoteTypes::DEVICE->value], true);
        // Other LOBs (e.g. Home policy issuance): add `$quoteType === QuoteTypes::HOME->value` and extend `resolveAutomationFailureRouting`.
        // Travel failures use insurer-specific paths (e.g. {@see applyTravelDicAutomationResult}, Alliance allocateLead); they do not populate `$statusAPIFailed` here.

        if ($hasFailureContext && $isCarOrCyber && ! $isPolicyBooked) {
            $this->dispatchAutomationFailedJob(
                $quote->id,
                $quoteType,
                $statusAPIFailed,
                $processInvolved,
                __FUNCTION__,
            );
        } elseif ($quoteType === QuoteTypes::HEALTH->value && ! empty($statusAPIFailed) && ! empty($processInvolved)) {
            app(HealthEmailService::class)->sendSTPAdvisorNotification($quote, true, $processInvolved);
        }

        if ($advisorId) {
            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' - Quote Code : '.$quote->code.' -  Quote Document Customer Email Checks', extra: [
                'advisorId' => $advisorId,
                'quote status id' => QuoteStatusEnum::PolicyBooked,
            ]);

            if ($isPolicyBooked) {
                // Here we need to dispatch document email
                $data = new \stdClass;
                $data->advisorId = $advisorId;
                $data->model_type = $quoteType;
                $data->quote_id = $quote->id;
                LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' - Quote Code : '.$quote->code.' - Dispatching SendBookPolicyDocumentsJob advisor id '.$quote->advisor_id);
                SendBookPolicyDocumentsJob::dispatch($data, $quote->code);
            }
        }
    }

    /**
     * Shared AutomationFailedJob dispatch (only from {@see allocateLead}).
     */
    private function dispatchAutomationFailedJob(
        int $quoteId,
        string $quoteType,
        string $statusAPIFailed,
        string $processInvolved,
        string $logContextFunction,
    ): void {
        $actionRequired = 'Please coordinate with the IT Department to address and rectify the issue.';
        [$jobQuoteTypeId, $workflowType, $recipientUser] = $this->resolveAutomationFailureRouting($quoteType, $processInvolved);

        LoggerService::info('automation:'.$this->className.' fn:'.$logContextFunction.' - Going to dispatch AutomationFailedJob', extra: [
            'actionRequired' => $actionRequired,
            'statusAPIFailed' => $statusAPIFailed,
            'processInvolved' => $processInvolved,
            'jobQuoteTypeId' => $jobQuoteTypeId,
            'workflowType' => $workflowType,
            'recipientUser' => $recipientUser,
        ]);

        AutomationFailedJob::dispatch(
            $quoteId,
            $jobQuoteTypeId,
            $actionRequired,
            $statusAPIFailed,
            $processInvolved,
            $workflowType,
            $recipientUser
        )->onQueue('policy-issuance-automation');

        LoggerService::info('automation:'.$this->className.' fn:'.$logContextFunction.' - AutomationFailedJob Dispatched');
    }

    private function resolveAutomationFailureRouting(string $quoteType, string $processInvolved): array
    {
        $paUser = UserNameEnum::PA_USER;
        $isBookPolicy = $processInvolved === PolicyIssuanceEnum::PROCESS_INVOLVED_BOOK_POLICY;

        // Non-booking failures for Device/Cyber notify the assigned SIC advisor directly (recipientUser null).
        return match ($quoteType) {
            QuoteTypes::DEVICE->value => [
                QuoteTypeId::Device,
                WorkflowTypeEnum::DEVICE_AUTOMATION_FAILED,
                $isBookPolicy ? $paUser : null,
            ],
            QuoteTypes::CYBER->value => [
                QuoteTypeId::Cyber,
                WorkflowTypeEnum::CYBER_AUTOMATION_FAILED,
                $isBookPolicy ? $paUser : null,
            ],
            default => [
                QuoteTypeId::Car,
                WorkflowTypeEnum::CAR_AUTOMATION_FAILED,
                $paUser,
            ],
        };
    }

    private function attemptAdvisorAllocation(string $quoteType, string $uuid): array
    {
        $quoteTypeEnum = QuoteTypes::tryFrom($quoteType);

        $allocationAttempted = $quoteTypeEnum && $this->isAllocationSupported($quoteTypeEnum);

        if (! $allocationAttempted) {
            return [
                'advisorId' => null,
                'response' => null,
                'quoteType' => $quoteTypeEnum?->value,
                'allocationAttempted' => false,
            ];
        }

        $teamId = $this->getAllocationTeamId($quoteTypeEnum);
        $response = $quoteTypeEnum->allocate($uuid, $teamId);

        return [
            'advisorId' => is_array($response) ? ($response['advisorId'] ?? null) : null,
            'response' => $response,
            'quoteType' => $quoteTypeEnum->value,
            'allocationAttempted' => true,
        ];
    }

    private function isAllocationSupported(QuoteTypes $quoteTypeEnum): bool
    {
        return in_array($quoteTypeEnum, self::ALLOCATABLE_QUOTE_TYPES, true);
    }

    private function getAllocationTeamId(QuoteTypes $quoteTypeEnum): int|false
    {
        return match ($quoteTypeEnum) {
            QuoteTypes::DEVICE => getTeamId(TeamNameEnum::SIC_UNASSISTED),
            default => false,
        };
    }

    public function getInsurerAPIStatuses($status = null, $onlyKeys = false)
    {
        $statuses = [
            PolicyIssuanceEnum::PIA_AUTO_CAPTURE_FAILED_STATUS_ID => PolicyIssuanceEnum::PIA_AUTO_CAPTURE_FAILED,
            PolicyIssuanceEnum::PIA_UPLOAD_POLICY_DOCUMENTS_API_FAILED_STATUS_ID => PolicyIssuanceEnum::PIA_UPLOAD_POLICY_DOCUMENTS_API_FAILED,
            PolicyIssuanceEnum::PIA_POLICY_ISSUANCE_API_FAILED_STATUS_ID => PolicyIssuanceEnum::PIA_POLICY_ISSUANCE_API_FAILED,
            PolicyIssuanceEnum::PIA_GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM_API_FAILED_STATUS_ID => PolicyIssuanceEnum::PIA_GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM_API_FAILED,
            PolicyIssuanceEnum::PIA_OCR_PROCESSING_API_FAILED_STATUS_ID => PolicyIssuanceEnum::PIA_OCR_PROCESSING_API_FAILED,
            PolicyIssuanceEnum::PIA_BOOK_POLICY_API_FAILED_STATUS_ID => PolicyIssuanceEnum::PIA_BOOK_POLICY_API_FAILED,
            PolicyIssuanceEnum::PIA_PREVIOUS_POLICY_EXPIRED_STATUS_ID => PolicyIssuanceEnum::PIA_PREVIOUS_POLICY_EXPIRED,
            PolicyIssuanceEnum::PIA_AUTO_CAPTURE_FAILED_STATUS_ID => PolicyIssuanceEnum::PIA_AUTO_CAPTURE_FAILED,
            PolicyIssuanceEnum::PIA_LEGACY_NON_API_STATUS_ID => PolicyIssuanceEnum::PIA_LEGACY_NON_API,
        ];

        if ($onlyKeys) {
            return array_keys($statuses);
        }

        return $status !== null ? ($statuses[$status] ?? null) : $statuses;
    }

    /**
     * Toggle policy issuance automation for a quote
     *
     * @param  object  $requestData  The quote object
     * @param  int  $quoteTypeId  The quote type ID
     * @param  bool  $enabled  Whether to enable or disable automation
     * @return array Response array with success status, message, and data
     */
    public function togglePolicyIssuanceAutomation($requestData, int $quoteTypeId, bool $enabled): array
    {
        $response = ['success' => false, 'message' => 'Unable to toggle policy issuance automation, Please try again later.', 'status_code' => 500];
        $quoteType = QuoteTypes::getName($requestData->quote_type_id)->value;
        $quote = $this->getQuoteObjectBy($quoteType, $requestData->quote_uuid, 'uuid');

        if (! $quote) {
            $response['message'] = 'Quote not found';
            $response['status_code'] = 404;

            return $response;
        }

        LoggerService::info('Toggle Policy Issuance Automation for Lead', extra: [
            'quote_uuid' => $quote->uuid,
            'quote_type_id' => $quoteTypeId,
            'enabled' => $enabled,
        ]);

        $insuranceProvider = $quote?->plan?->insuranceProvider;
        if (! $insuranceProvider) {
            $response['message'] = 'Insurance provider not found';
            $response['status_code'] = 404;

            return $response;
        }

        $isPolicyAutomationEnabled = false;
        if ($quoteTypeId == QuoteTypeId::Car && $insuranceProvider) {
            $policyIssuanceService = $this->init($quoteType, $insuranceProvider->code);
            $isPolicyAutomationEnabled = $policyIssuanceService?->isPolicyIssuanceAutomationEnabled();
        }

        if (! $isPolicyAutomationEnabled) {
            $response['message'] = 'Policy automation is not enabled for this insurer';
            $response['status_code'] = 400;

            return $response;
        }

        $insurerApiStatus = PolicyIssuanceEnum::PIA_LEGACY_NON_API_STATUS_ID;
        $isExistingApiStatusLegacyNon = $quote->insurer_api_status_id == PolicyIssuanceEnum::PIA_LEGACY_NON_API_STATUS_ID;
        if ($enabled && $isExistingApiStatusLegacyNon) {
            $insurerApiStatus = null;
        }

        $quoteData = [
            'policy_issuance_automation_enabled' => $enabled,
        ];

        if (empty($quote->insurer_api_status_id) || $isExistingApiStatusLegacyNon) {
            $quoteData['insurer_api_status_id'] = $insurerApiStatus;
        }

        // Update the policy_issuance_automation_enabled field
        $quote->update($quoteData);

        LoggerService::info('Policy issuance automation toggled successfully for quote', extra: [
            'quote_uuid' => $quote->uuid,
            'quote_code' => $quote->code,
            'enabled' => $enabled,
            'user_id' => auth()->id(),
        ]);

        return [
            'success' => true,
            'message' => $enabled
                ? 'Policy issuance automation enabled successfully'
                : 'Policy issuance automation disabled successfully',
            'data' => [
                'policy_issuance_automation_enabled' => $quote->policy_issuance_automation_enabled,
            ],
            'status_code' => 200,
        ];
    }

    /**
     * This function is used to assign an advisor to a lead for  policy issuance automation if not assigned and policy is booked for the given quote type
     *
     * @param  string  $quoteType
     * @param  object  $quote
     * @param  int  $advisorId
     * @return void
     */
    private function triggerAdvisorAllocation($quoteType, $quote, &$advisorId)
    {
        $response = QuoteTypes::from($quoteType)?->allocate($quote->uuid);
        if ($response && $response['advisorId']) {
            $advisorId = $response['advisorId'];
        }
        LoggerService::info('fn:allocateLead - Quote Code : '.$quote->code.' -  Assigned Advisor through Allocation when advisor id is not assigned during policy issuance automation', extra: [
            'advisorId' => $advisorId,
            'allocation_response' => $response,
        ]);
    }

    /**
     * Travel DIC: apply automation result on the quote. On failure, allocates the lead and
     * notifies via {@see SendTravelQatarFailedAllocationEmailJob} (Bird {@see WorkflowTypeEnum::TRAVEL_QATAR_FAILED_ALLOCATION}),
     * matching {@see QatarInsuranceService::allocateLead} instead of {@see AutomationFailedJob}.
     */
    public function applyTravelDicAutomationResult(
        TravelQuote $quote,
        bool $success,
        ?int $insurerApiStatusId = null,
        ?string $processInvolved = null,
        ?int $apiIssuanceStatusId = null,
    ): void {
        $isInsurerApiStatusAlreadyFailed = $quote->isBookingFailed() || $quote->isPolicyIssuanceFailed();

        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' - Travel DIC result handling', extra: [
            'quote_code' => $quote->code,
            'success' => $success,
            'insurer_api_status_id' => $insurerApiStatusId,
            'process_involved' => $processInvolved,
        ]);

        $apiIssuanceStatusId ??= $success
            ? PolicyIssuanceEnum::POLICY_ISSUANCE_API_STATUS_YES_ID
            : PolicyIssuanceEnum::POLICY_ISSUANCE_API_STATUS_NO_ID;

        $updateData = ['api_issuance_status_id' => $apiIssuanceStatusId];
        if (! $success && $insurerApiStatusId !== null) {
            $updateData['insurer_api_status_id'] = $insurerApiStatusId;
        } elseif ($success) {
            // Clear any prior failure status so a future failure correctly triggers the Bird notification email.
            $updateData['insurer_api_status_id'] = null;
        }

        $quote->update($updateData);

        if (! $success) {
            $quote->refresh();
            $this->allocateTravelDicFailedLeadForBirdNotification($quote, $isInsurerApiStatusAlreadyFailed);
        }
    }

    /**
     * Post–DIC failure: same allocation + Bird email path as {@see AllianceInsuranceService::allocateLead},
     * without {@see AutomationFailedJob}. Called by {@see applyTravelDicAutomationResult} on failure.
     */
    private function allocateTravelDicFailedLeadForBirdNotification(TravelQuote $quote, bool $isInsurerApiStatusAlreadyFailed): void
    {
        $uuid = $quote->uuid;
        LoggerService::info('automation:'.$this->className.' fn:allocateTravelDicFailedLeadForBirdNotification - Going to allocate lead (DIC) ................ Ref-ID: '.$uuid);

        $advisorId = $quote->advisor_id;

        if (! $advisorId) {
            $unassistedTeamId = getTeamId(TeamNameEnum::SIC_UNASSISTED);
            $response = QuoteTypes::TRAVEL->allocate($uuid, $unassistedTeamId);
            if ($response && $response['advisorId']) {
                $advisorId = $response['advisorId'];
            }
            LoggerService::info('automation:'.$this->className.' fn:allocateTravelDicFailedLeadForBirdNotification - Quote Code : '.$quote->code.' -  Assigned Advisor through Allocation', extra: [
                'advisorId' => $advisorId,
                'allocation_response' => $response ?? null,
            ]);
            $quote->refresh();
        }

        LoggerService::info('automation:'.$this->className.' fn:allocateTravelDicFailedLeadForBirdNotification - Quote Code : '.$quote->code.' -  Assigned Advisor', extra: [
            'advisorId' => $advisorId,
        ]);

        if (! $advisorId) {
            return;
        }

        LoggerService::info('automation:'.$this->className.' fn:allocateTravelDicFailedLeadForBirdNotification - Going to dispatch SendTravelQatarFailedAllocationEmailJob ................ Ref-ID: '.$uuid);
        if (! $isInsurerApiStatusAlreadyFailed && $quote->insurer_api_status != null) {
            SendTravelQatarFailedAllocationEmailJob::dispatch($uuid)->delay(now()->addSeconds(30));
        }
    }

    /**
     * IMCRM allowlist: statuses where manual re-trigger of policy automation may be offered.
     */
    public function isReTriggerPolicyAutomationStatusAllowed(?string $status): bool
    {
        $allowed = [
            PolicyIssuanceEnum::TIMEOUT_STATUS,
            PolicyIssuanceEnum::FAILED_STATUS,
        ];

        return in_array($status, $allowed, true);
    }

    /**
     * Whether IMCRM should show "Re Trigger Policy Automation" for this issuance (LOB-specific rules apply).
     */
    public function shouldOfferReTriggerPolicyAutomation(PolicyIssuance $policyIssuance): bool
    {
        $policyIssuance->loadMissing('insuranceProvider');
        $insuranceProvider = $policyIssuance->insuranceProvider;
        $automation = $this->init($policyIssuance->quote_type, $insuranceProvider?->code);

        if (! $automation?->isPolicyIssuanceAutomationEnabled()) {
            return false;
        }

        return match (ucfirst((string) $policyIssuance->quote_type)) {
            QuoteTypes::DEVICE->value => $this->isReTriggerPolicyAutomationStatusAllowed($policyIssuance->status),
            default => false,
        };
    }

    /**
     * Re-run LOB-specific recovery (e.g. immediate NGI document job). Caller must authorize and validate request context.
     *
     * @throws \InvalidArgumentException When automation is disabled, LOB unsupported, or eligibility fails
     */
    public function reTriggerPolicyAutomation(PolicyIssuance $policyIssuance): void
    {
        if (! $this->isReTriggerPolicyAutomationStatusAllowed($policyIssuance->status)) {
            throw new \InvalidArgumentException('Policy issuance automation is not allowed for this status ('.$policyIssuance->status.').');
        }

        $policyIssuance->loadMissing('insuranceProvider');
        $insuranceProvider = $policyIssuance->insuranceProvider;
        $automation = $this->init($policyIssuance->quote_type, $insuranceProvider?->code);

        if (! $automation?->isPolicyIssuanceAutomationEnabled()) {
            throw new \InvalidArgumentException('Policy issuance automation is not enabled for this quote.');
        }

        match (ucfirst((string) $policyIssuance->quote_type)) {
            QuoteTypes::DEVICE->value => app(DeviceQuoteService::class)->identifyAutomationStepToReTrigger($policyIssuance),
            default => throw new \InvalidArgumentException('Re-trigger is not supported for this line of business.'),
        };
    }
}

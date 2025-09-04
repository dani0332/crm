<?php

namespace App\Services\PolicyIssuanceAutomation;

use App\Enums\InsuranceProviderEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Enums\QuoteTypes;
use App\Jobs\PolicyIssuanceJob;
use App\Models\PolicyIssuance;
use App\Services\Logger\LoggerService;
use App\Services\PolicyIssuanceAutomation\Travel\AllianceInsuranceService;
use Carbon\Carbon;

class PolicyIssuanceService
{
    private string $className = 'policyIssuanceService';
    public function __construct() {}

    public function init($quoteType, $insurerCode)
    {
        return match (ucfirst($quoteType)) {
            QuoteTypes::TRAVEL->value => match ($insurerCode) {
                InsuranceProviderEnum::ALNC->value => new AllianceInsuranceService,
                default => null,
            },
            default => null,
        };
    }

    public function schedulePolicyIssuance($quote, $insurer, $quoteType, $logFor)
    {
        $policyIssuance = $quote->policyIssuance;

        if ($policyIssuance) {
            info('automation:'.$logFor.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' -  Policy Issuance Schedule already exists PID : '.$policyIssuance->id);
        } else {
            $policyIssuance = PolicyIssuance::create([
                'insurance_provider_id' => $insurer->id, 'model_type' => $quote->getMorphClass(), 'model_id' => $quote->id, 'quote_type' => $quoteType, 'status' => PolicyIssuanceEnum::PENDING_STATUS,
            ]);
            info('automation:'.$logFor.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' -  Policy Issuance Schedule created PID : '.$policyIssuance->id);
        }
    }
    public function executePolicyIssuanceAutomationSteps()
    {
        info('cmd:'.$this->className.' fn:'.__FUNCTION__);

        /* Get Unique Insurer per lob to get the statuses for which automation is enabled */
        $uniqueInsurerListByLob = PolicyIssuance::with(['insuranceProvider:id,code,text'])
            ->whereIn('status', [PolicyIssuanceEnum::PENDING_STATUS, PolicyIssuanceEnum::TIMEOUT_STATUS])
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
            }
            if ($this->init($quoteType, $insuranceProvider?->code)?->isPolicyIssuanceAutomationRetryEnabledForTimeout()) {
                $statuses[] = PolicyIssuanceEnum::TIMEOUT_STATUS;
            }
            $policyIssuanceProcess->statuses = $statuses;
        }

        return $policyIssuanceProcesses;
    }

    public function getPolicyIssuanceStepsStatus($quote, $quoteType): array
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

        return array_merge($response, $insuranceProviderAutomation->getStepsLockingStatus($quote));

    }

    private function processPolicyIssuanceRecords($policyIssuanceAutomationStatus)
    {
        $quoteType = $policyIssuanceAutomationStatus?->quote_type;
        $insuranceProvider = $policyIssuanceAutomationStatus?->insuranceProvider;
        $statuses = $policyIssuanceAutomationStatus?->statuses;

        /* Fetch Policy Issuance Records against statuses by each LOB and Insurer */
        $policyIssuanceQuery = PolicyIssuance::where(['quote_type' => $quoteType, 'insurance_provider_id' => $insuranceProvider->id])->whereIn('status', $statuses);
        $policyIssuanceCount = $policyIssuanceQuery->count();
        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Process Records for Quote Type: '.$quoteType.', Insurer : '.$insuranceProvider?->code.' - Count : '.$policyIssuanceCount.' - Statuses : '.json_encode($statuses));
        if ($policyIssuanceCount > 0) {
            $policyIssuanceQuery->chunk(100, function ($policyIssuanceProcesses) {
                foreach ($policyIssuanceProcesses as $policyIssuanceProcess) {
                    info('automation:'.$this->className.' fn:'.__FUNCTION__.' PID: '.$policyIssuanceProcess->id.' dispatch automation job');
                    PolicyIssuanceJob::dispatch($policyIssuanceProcess)->onQueue('policy-issuance-automation');
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

        $insurerPolicyAutomation?->updateQuoteApiIssuanceStatusAndAllocate($quote, $insurerApiStatus, PolicyIssuanceEnum::POLICY_ISSUANCE_API_STATUS_NO_ID);

        info('cmd:'.$this->className.' fn:'.__FUNCTION__.' Completed processing for Quote: '.$quote->code.' and Policy Issuance ID : '.$policyIssuance?->id);
    }

}

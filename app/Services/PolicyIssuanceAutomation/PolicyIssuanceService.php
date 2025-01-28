<?php

namespace App\Services\PolicyIssuanceAutomation;

use App\Enums\InsuranceProvidersEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Enums\QuoteTypes;
use App\Jobs\PolicyIssuanceJob;
use App\Models\PolicyIssuance;
use App\Services\PolicyIssuanceAutomation\Travel\AllianceInsuranceService;

class PolicyIssuanceService
{
    private string $className = 'policyIssuanceService';
    public function __construct() {}

    public function init($quoteType, $insurerCode)
    {
        return match (ucfirst($quoteType)) {
            QuoteTypes::TRAVEL->value => match ($insurerCode) {
                InsuranceProvidersEnum::ALNC => new AllianceInsuranceService,
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
                    PolicyIssuanceJob::dispatch($policyIssuanceProcess->id)->onQueue('policy-issuance-automation');
                    info('automation:'.$this->className.' fn:'.__FUNCTION__.' PID: '.$policyIssuanceProcess->id.' automation job dispatched');
                }
            });
        } else {
            info('automation:'.$this->className.' fn:'.__FUNCTION__.' No Records found for Quote Type: '.$quoteType.', Insurer : '.$insuranceProvider?->code.' - Statuses : '.json_encode($statuses));
        }

    }

}

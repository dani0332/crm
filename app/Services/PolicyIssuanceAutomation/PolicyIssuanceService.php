<?php

namespace App\Services\PolicyIssuanceAutomation;

use App\Enums\InsuranceProvidersEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Enums\QuoteTypes;
use App\Jobs\PolicyIssuanceJob;
use App\Models\PolicyIssuance;
use App\Repositories\PolicyIssuanceRepository;
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
    public function executePolicyIssuanceAutomationSteps()
    {
        $policyIssuanceProcesses = $this->policyIssuanceByStatus([PolicyIssuanceEnum::PENDING_STATUS, PolicyIssuanceEnum::TIMEOUT_STATUS]);

        if (count($policyIssuanceProcesses) > 0) {
            info('cmd:'.$this->className.' fn:'.__FUNCTION__.' - Total Policy Issuance Processes Count : '.count($policyIssuanceProcesses));
            $insurerAutomationStatus = $this->getInsurerAutomationStatus($policyIssuanceProcesses);
            $this->processPolicyIssuanceRecords($policyIssuanceProcesses, $insurerAutomationStatus);
        } else {
            info('cmd:'.$this->className.' fn:'.__FUNCTION__.' - No Policy Issuance Process found');
        }
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

    public function getInsurerAutomationStatus($policyIssuanceProcesses)
    {

        $insurerAutomationStatus = [];
        foreach ($policyIssuanceProcesses as $policyIssuanceProcess) {
            $quoteType = $policyIssuanceProcess?->quote_type;
            $insuranceProvider = $policyIssuanceProcess?->insuranceProvider;
            $insurerAutomationStatus[$insuranceProvider->code.'_'.$quoteType] = $this->init($quoteType, $insuranceProvider?->code)?->isPolicyIssuanceAutomationEnabled();
            $insurerAutomationStatus[$insuranceProvider->code.'_'.$quoteType.'_retry'] = $this->init($quoteType, $insuranceProvider?->code)?->isPolicyIssuanceAutomationRetryEnabledForTimeout();
        }

        return $insurerAutomationStatus;
    }

    public function isPolicyIssuanceAutomationEnabled($quoteType, $insurerCode)
    {
        return $this->init($quoteType, $insurerCode)?->isPolicyIssuanceAutomationEnabled();
    }
    public function isPolicyIssuanceAutomationRetryEnabledForTimeout($quoteType, $insurerCode)
    {
        return $this->init($quoteType, $insurerCode)?->isPolicyIssuanceAutomationRetryEnabledForTimeout();
    }

    private function processPolicyIssuanceRecords($policyIssuanceProcesses, $insurerAutomationStatus)
    {
        foreach ($policyIssuanceProcesses as $policyIssuanceProcess) {
            $quoteType = $policyIssuanceProcess?->quote_type;
            $insuranceProvider = $policyIssuanceProcess?->insuranceProvider;
            $isAutomationEnabled = isset($insurerAutomationStatus[$insuranceProvider->code.'_'.$quoteType]) && $insurerAutomationStatus[$insuranceProvider->code.'_'.$quoteType];
            $isAutomationRetryEnabled = isset($insurerAutomationStatus[$insuranceProvider->code.'_'.$quoteType.'_retry']) && $insurerAutomationStatus[$insuranceProvider->code.'_'.$quoteType];
            if ($isAutomationEnabled) {
                info('cmd:'.$this->className.' fn:'.__FUNCTION__.' PID: '.$policyIssuanceProcess->id.' with status '.$policyIssuanceProcess->status.' for Insurer : '.$insuranceProvider?->text);
                $this->dipatchAutomationJob($policyIssuanceProcess, $isAutomationRetryEnabled);
            } else {
                info('cmd:'.$this->className.' fn:'.__FUNCTION__.' - '.$insuranceProvider?->text.' '.$quoteType.' Automation is disabled');
            }
        }
    }

    public function schedulePolicyIssuance($quote, $insurer, $quoteType, $logFor)
    {
        $policyIssuance = $quote->policyIssuance;

        if ($policyIssuance) {
            info('automation:'.$logFor.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' -  Policy Issuance Schedule already exists PID : '.$policyIssuance->id);
        } else {
            $policyIssuance = $this->create([
                'insurance_provider_id' => $insurer->id, 'model_type' => $quote->getMorphClass(), 'model_id' => $quote->id, 'quote_type' => $quoteType, 'status' => PolicyIssuanceEnum::PENDING_STATUS,
            ]);
            info('automation:'.$logFor.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' -  Policy Issuance Schedule created PID : '.$policyIssuance->id);
        }
    }

    public function policyIssuanceByStatus($statuses)
    {
        return PolicyIssuance::with('insurance_provider')->whereIn('status', $statuses)->orderBy('created_at')->get();
    }

    private function dipatchAutomationJob($policyIssuanceProcess, $isAutomationRetryEnabled)
    {

        if ($policyIssuanceProcess->status === PolicyIssuanceEnum::PENDING_STATUS) {
            info('cmd:'.$this->className.' fn:'.__FUNCTION__.' PID: '.$policyIssuanceProcess->id.' dispatch automation job');
            PolicyIssuanceJob::dispatch($policyIssuanceProcess->id)->onQueue('policy-issuance-automation');
            info('cmd:'.$this->className.' fn:'.__FUNCTION__.' PID: '.$policyIssuanceProcess->id.' automation job dispatched');
        } elseif ($policyIssuanceProcess->status === PolicyIssuanceEnum::TIMEOUT_STATUS && $isAutomationRetryEnabled) {
            info('cmd:'.$this->className.' fn:'.__FUNCTION__.' PID: '.$policyIssuanceProcess->id.' Retry is enabled');
            PolicyIssuanceJob::dispatch($policyIssuanceProcess->id)->onQueue('policy-issuance-automation');
            info('cmd:'.$this->className.' fn:'.__FUNCTION__.' PID: '.$policyIssuanceProcess->id.' retry automation job dispatched');

        } elseif ($policyIssuanceProcess->status === PolicyIssuanceEnum::TIMEOUT_STATUS && ! $isAutomationRetryEnabled) {
            info('cmd:'.$this->className.' fn:'.__FUNCTION__.' PID: '.$policyIssuanceProcess->id.' Retry is disabled');
        }

    }

}

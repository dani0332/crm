<?php

namespace App\Services;

use App\Enums\ApplicationStorageEnums;
use App\Enums\HealthFacilityType;
use App\Enums\HealthPlanTypeEnum;
use App\Models\ApplicationStorage;
use App\Models\User;

class HealthEmailService extends BaseService
{
    protected $sendEmailCustomerService;
    protected $birdService;

    public function __construct(SendEmailCustomerService $sendEmailCustomerService, BirdService $birdService)
    {
        $this->sendEmailCustomerService = $sendEmailCustomerService;
        $this->birdService = $birdService;
    }

    public function sendHealthOCBIntroEmail($lead, $previousAdvisorId, $healthQuoteService, $triggerSICWorkFlow = false)
    {
        // Retrieve plans with available ratings for the given lead
        $quote = $healthQuoteService->getQuotePlans($lead->uuid);
        if (! $quote->quote->plans) {
            info('No plans found for lead: '.$lead->uuid.' | time: '.now());
        }

        $advisor = User::where('id', $lead->advisor_id)->first();
        $plans = $this->getQuotePlansByCriteria($quote->quote->healthPlanTypeId, $quote->quote->plans ?? []);
        $emailData = $this->mappingEmailDataForOCBEmail($lead, $advisor, $plans);
        $responseCode = $this->birdService->sendHealthOCBEmail($emailData);
        info('sic sendHealthOCBEmail - Ref ID:'.$lead->uuid.' Time: '.now());
        if ($triggerSICWorkFlow) {
            if (! $lead->sic_flow_enabled) {
                $sicEventName = ApplicationStorage::where('key_name', ApplicationStorageEnums::BIRD_SIC_HEALTH_WORKFLOW)->first();
                info('SIC Health workflow key: '.$sicEventName->value);
                if ($sicEventName) {
                    $apiResponse = $this->birdService->sendSICHealthWorkFlow($emailData);
                    $lead->sic_flow_enabled = true;
                    $lead->save();
                    info('SIC Health workflow event triggered for lead: '.$lead->uuid.' and sic_flow_enabled: '.$lead->sic_flow_enabled);
                    info('SIC Health workflow response: '.$apiResponse);
                } else {
                    info('SIC Health workflow key not found');
                }
            } else {
                info('SIC Health workflow already enabled for lead: '.$lead->uuid);
            }
        }

        return $responseCode;
    }

    public function mappingEmailDataForOCBEmail($lead, $advisor, $plans)
    {
        return (object) [
            'healthQuoteId' => $lead->code,
            'customerEmail' => $lead->email,
            'uuid' => $lead->uuid,
            'customerFullName' => $lead->first_name.' '.$lead->last_name,
            'advisorId' => $advisor->id ?? null,
            'advisorName' => (! empty($advisor->name) ? $advisor->name : ''),
            'advisorEmail' => (! empty($advisor->email) ? $advisor->email : ''),
            'advisorDetails' => $advisor ?? null,
            'quotePlanLink' => config('constants.ECOM_HEALTH_INSURANCE_QUOTE_URL').$lead->uuid,
            'requestAdvisorLink' => config('constants.ECOM_HEALTH_INSURANCE_QUOTE_URL').$lead->uuid.'/?assignAdvisor=true',
            'plans' => $plans,
        ];
    }

    public function getQuotePlansByCriteria($healthPlanTypeId, $plans)
    {
        $entryLevelPlans = ['TE_ECARE_1', 'SROADB_DIC', 'SUKOON_SAFE', 'DIC_N4_NIL', 'NLGIC_PLAN5', 'OI2_RN3_NIL'];
        $goodLevelPlans = ['TE_MN_SILPLUS', 'N2A_DIC', 'SUKOON_HOME', 'NLGIC_PLAN4', 'NT_MN_PEARL', 'VIV_MN_1000SCLASSIC'];
        $bestLevelPlans = ['CIGNA_REGIONAL_COMEXAH_NIL', 'BUPA_PREMIER', 'ALLIANZ_SELECT_PEARL_EXCL', 'APR_ESES_WW', 'ORIENT_NC_HP2', 'TE_MN_PLATINUM'];
        $plansByLevel = [
            HealthPlanTypeEnum::ENTRY_LEVEL->value => $entryLevelPlans,
            HealthPlanTypeEnum::GOOD->value => $goodLevelPlans,
            HealthPlanTypeEnum::BEST->value => $bestLevelPlans,
        ];
        $selectedPlans = collect($plans)->filter(function ($plan) use ($healthPlanTypeId, $plansByLevel) {
            return in_array($plan->planCode, $plansByLevel[$healthPlanTypeId] ?? []);
        });

        return $selectedPlans->map(function ($plan) {
            $lowestRate = collect($plan->ratesPerCopay)->sortBy('discountPremium')->first();
            $filteredSelectedCopay = $lowestRate && ! empty($lowestRate->healthPlanCoPaymentId)
                ? collect($plan->coPayments)->firstWhere('id', $lowestRate->healthPlanCoPaymentId)
                : '';
            $totalValue = ($plan->policyFee ?? 0) + ($plan->basmah ?? 0) + ($lowestRate->discountPremium ?? 0);

            return (object) [
                'name' => $plan->name ?? null,
                'planCode' => $plan->planCode,
                'eligibilityName' => $plan->eligibilityName ?? null,
                'planBenefit' => $this->getBenefitsDetails($plan->benefits, $filteredSelectedCopay) ?? null,
                'total' => $this->formatNumberWithCommas($totalValue),
                'hospital' => (object) [
                    'count' => $plan->healthNetwork->noOfHospitals ?? 0,
                    'text' => $this->getHospitals($plan->healthNetwork->featuredFacilities ?? null) ?? null,
                ],
                'clinic' => (object) [
                    'count' => $plan->healthNetwork->noOfClinics ?? 0,
                    'text' => $this->getClinics($plan->healthNetwork->featuredFacilities ?? null) ?? null,
                ],
            ];
        })->take(6)->toArray();
    }

    public function getHospitals($featuredFacilities)
    {
        if (empty($featuredFacilities)) {
            return null;
        }
        $hospitals = collect($featuredFacilities)
            ->where('type', HealthFacilityType::HOSPITAL->value)
            ->filter(function ($item) {
                return ! empty($item->text);
            })
            ->map(function ($item) {
                return str_replace('Hospital', '', $item->text);
            })->implode(', ');

        return $hospitals ?? '';
    }

    public function getClinics($featuredFacilities)
    {
        if (empty($featuredFacilities)) {
            return null;
        }
        $hospitals = collect($featuredFacilities)
            ->where('type', HealthFacilityType::CLINIC->value)
            ->filter(function ($item) {
                return ! empty($item->text);
            })
            ->map(function ($item) {
                return $item->text;
            })->implode(', ');

        return $hospitals ?? '';
    }

    public function formatNumberWithCommas($number)
    {
        return number_format($number, 2, '.', ',');
    }

    public function getBenefitsDetails($benefitList, $filteredSelectedCopay = null)
    {
        $benefitsTypes = [];
        $getBenefitCode = ['annualLimit', 'regionsCovered'];
        foreach ($benefitList as $key => $covers) {
            foreach ($covers as $cover) {
                if ($key === 'outpatient' && $cover->code === 'medicine') {
                    $benefitsTypes[$cover->code] = ['text' => $cover->value ?? ''];
                } elseif (in_array($cover->code, $getBenefitCode)) {
                    $benefitsTypes[$cover->code] = ['text' => $cover->value ?? ''];
                } elseif ($cover->code === 'outpatient_copay') {
                    $benefitsTypes[$cover->code] = ['text' => $filteredSelectedCopay->text ?? ''];
                }
            }
        }

        return $benefitsTypes;
    }

}

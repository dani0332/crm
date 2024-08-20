<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\User;
use App\Services\BirdService;
use App\Enums\HealthFacilityType;
use App\Enums\HealthPlanTypeEnum;
use App\Models\ApplicationStorage;
use App\Services\HealthQuoteService;
use App\Enums\ApplicationStorageEnums;


class HealthEmailService extends BaseService
{

    protected $birdService;
    protected $healthQuoteService;

    public function __construct()
    {
        $this->birdService =new BirdService();
    }
    public function triggerOCAFollowups($lead)
    {
        // Retrieve plans with available ratings for the given lead

        info('Sending OCA Health followups email for lead: '.$lead->uuid.' | Time: '.now());
            if (! $lead->oca_flow_enabled) {
                $advisor = User::where('id', $lead->advisor_id)->first();
                $emailData = $this->mappingEmailDataForOCAEmail($lead, $advisor);
                $eventName = ApplicationStorage::where('key_name', ApplicationStorageEnums::BIRD_OCA_HEALTH_WORKFLOW)->first();
                info('OCA Health workflow key: '.$eventName->value);
                if ($eventName) {
                    if(!empty($emailData)){
                        info("sendOCAHealthWorkFlow: OCA Health followups email for lead: ".$lead->uuid." | time: ".now());
                        $responseCode = $this->birdService->sendOCAHealthWorkFlow($emailData);
                    }
                    else {
                        $responseCode = null;
                    }
                    $lead->oca_flow_enabled = true;
                    $lead->save();
                    info('OCA Health workflow event triggered for lead: '.$lead->uuid.' and oca_flow_enabled: '.$lead->oca_flow_enabled);
                    info('OCA Health workflow response: '.$responseCode);
                } else {
                    info('OCA Health workflow key not found');
                }
            } else {
                info('OCA Health workflow already enabled for lead: '.$lead->uuid);
            }
        return $responseCode ?? null;
    }
    public function triggerPendingHealthFollowupEmails($lead)
    {
        // Retrieve plans with available ratings for the given lead

        info('triggerPendingHealthFollowupEmails: Sending AppPending Health followups email for lead: '.$lead->uuid.' | time: '.now());
           $lead->pending_flow_enabled  = false;
            if (! $lead->pending_flow_enabled) {
                $advisor = User::where('id', $lead->advisor_id)->first();
                $emailData = $this->mappingEmailDataForOCAEmail($lead, $advisor);
                $eventName = ApplicationStorage::where('key_name', ApplicationStorageEnums::BIRD_APP_PENDING_HEALTH_WORKFLOW)->first();
                info('triggerPendingHealthFollowupEmails Health workflow key: '.$eventName->value);
                if ($eventName) {
                    $responseCode = $this->birdService->sendAppPendingHealthWorkFlow($emailData);
                    $lead->pending_flow_enabled = true;
                    $lead->save();
                    info('triggerPendingHealthFollowupEmails: Health workflow event triggered for lead: '.$lead->uuid.' and oca_flow_enabled: '.$lead->oca_flow_enabled);
                    info('triggerPendingHealthFollowupEmails: Health workflow response: '.$responseCode);
                } else {
                    info('triggerPendingHealthFollowupEmails: Health workflow key not found');
                }
            } else {
                info('triggerPendingHealthFollowupEmails: Health workflow already enabled for lead: '.$lead->uuid);
            }
        return $responseCode ?? null;
    }

    public function mappingEmailDataForOCAEmail($lead, $advisor)
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
            'plans' => [],
        ];
    }

    public function getQuotePlansByCriteria($healthPlanTypeId, $plans)
    {
        $entryLevelPlans = ['TE_ECARE_1', 'SROADB_DIC', 'SUKOON_SAFE', 'DIC_N4_NIL', 'NLGIC_PLAN5', 'OI2_RN3_NIL'];
        $goodLevelPlans = ['TE_MN_SILPLUS', 'N2A_DIC', 'SUKOON_HOME', 'NLGIC_PLAN4', 'NT_MN_PEARL', 'VIV_MN_1000SCLASOCA'];
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

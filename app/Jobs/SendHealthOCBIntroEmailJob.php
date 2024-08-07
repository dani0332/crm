<?php

namespace App\Jobs;

use App\Enums\HealthFacilityType;
use App\Enums\HealthPlanTypeEnum;
use App\Models\HealthQuote;
use App\Services\HealthEmailService;
use App\Services\HealthQuoteService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendHealthOCBIntroEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $quoteUuid;
    protected $healthQuoteService;
    private $previousAdvisor;
    private $triggerSICWorkflow;
    public $tries = 3;
    public $timeout = 60;
    public $backoff = 10;
    /**
     * Create a new job instance.
     */
    public function __construct($quoteUuid, $previousAdvisor, $triggerSICWorkflow = false)
    {
        $this->quoteUuid = $quoteUuid;
        $this->previousAdvisor = $previousAdvisor;
        $this->triggerSICWorkflow = $triggerSICWorkflow;
    }
    /**
     * Execute the job.
     */
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
    public function mapPlans($healthPlanTypeId, $plans)
    {

        $selectedPlans = [];
        $entryLevelPlans = ['TE_ECARE_1', 'SROADB_DIC', 'SUKOON_SAFE', 'DIC_N4_NIL', 'NLGIC_PLAN5', 'OI2_RN3_NIL'];
        $goodLevelPlans = ['TE_MN_SILPLUS', 'N2A_DIC', 'SUKOON_HOME', 'NLGIC_PLAN4', 'NT_MN_PEARL', 'VIV_MN_1000SCLASSIC'];
        $bestLevelPlans = ['CIGNA_REGIONAL_COMEXAH_NIL', 'BUPA_PREMIER', 'ALLIANZ_SELECT_PEARL_EXCL', 'APR_ESES_WW', 'ORIENT_NC_HP2', 'TE_MN_PLATINUM'];
        $selectedPlans = [];
        foreach ($plans as $key => $plan) {
            if (($healthPlanTypeId == HealthPlanTypeEnum::ENTRY_LEVEL->value && in_array($plan->planCode, $entryLevelPlans)) ||
            ($healthPlanTypeId == HealthPlanTypeEnum::GOOD->value && in_array($plan->planCode, $goodLevelPlans)) ||
            ($healthPlanTypeId == HealthPlanTypeEnum::BEST->value && in_array($plan->planCode, $bestLevelPlans))) {
                $selectedPlans[] = $plan;
            }
        }

        return collect($selectedPlans)->map(function ($plan) use ($healthPlanTypeId, $entryLevelPlans, $goodLevelPlans, $bestLevelPlans) {

            if (($healthPlanTypeId == HealthPlanTypeEnum::ENTRY_LEVEL->value && in_array($plan->planCode, $entryLevelPlans)) ||
                ($healthPlanTypeId == HealthPlanTypeEnum::GOOD->value && in_array($plan->planCode, $goodLevelPlans)) ||
                ($healthPlanTypeId == HealthPlanTypeEnum::BEST->value && in_array($plan->planCode, $bestLevelPlans))) {

                $lowestRate = collect($plan->ratesPerCopay)->sortBy('discountPremium')->first();
                if (! empty($lowestRate->healthPlanCoPaymentId)) {
                    $filteredSelectedCopay = collect($plan->coPayments)->firstWhere('id', $lowestRate->healthPlanCoPaymentId);
                } else {
                    $filteredSelectedCopay = '';
                }

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
            }
        })->take(6)->toArray();
    }
    public function getBenefitsDetails($benefitList, $filteredSelectedCopay = null)
    {
        $benefitsTypes = [];
        $getBenefitCode = ['annualLimit', 'regionsCovered'];

        foreach ($benefitList as $key => $covers) {
            foreach ($covers as $cover) {
                if ($key === 'outpatient') {
                    if ($cover->code === 'medicine') {
                        $benefitsTypes[$cover->code] = ['text' => $cover->value ?? ''];
                    }
                } else {
                    if (in_array($cover->code, $getBenefitCode)) {
                        $benefitsTypes[$cover->code] = ['text' => $cover->value ?? ''];
                    }
                    if ($cover->code === 'outpatient_copay') {
                        $benefitsTypes[$cover->code] = ['text' => $filteredSelectedCopay->text ?? ''];
                    }
                }
            }
        }

        return $benefitsTypes;
    }
    public function handle(HealthEmailService $healthEmailService, HealthQuoteService $healthQuoteService): void
    {
        try {
            $lead = HealthQuote::where('uuid', $this->quoteUuid)->first();
            if (! $lead) {
                info('SendHealthOCBIntroEmailJob - Lead not found for uuid: '.$this->quoteUuid);

                return;
            }
            if ($lead->sic_flow_enabled) {
                info('SendHealthOCBIntroEmailJob - SIC work flow is enabled on this lead already : '.$this->quoteUuid);

                return;
            } else {
                info('SendHealthOCBIntroEmailJob - SIC work flow is not enabled on this lead : '.$this->quoteUuid);
                // Retrieve plans with available ratings for the given lead
                $quote = $healthQuoteService->getQuotePlans($lead->uuid);
                $plans = $this->mapPlans($quote->quote->healthPlanTypeId, $quote->quote->plans ?? []);
                $responseCode = $healthEmailService->sendHealthOCBIntroEmail($plans, $lead, $this->previousAdvisor, $healthQuoteService, $this->triggerSICWorkflow);
                if (in_array($responseCode, [200, 201])) {
                    info('SendHealthOCBIntroEmailJob - OCB INTRO Email Sent: '.$responseCode.' Customer Email Address: '.$lead->email.' Quote UuId: '.$this->quoteUuid);
                } else {
                    Log::error('SendHealthOCBIntroEmailJob - OCB INTRO Email Not Sent: '.$responseCode.' Customer EmailAddress:'.$lead->email);
                }
            }

        } catch (Exception $e) {
            info('SendHealthOCBIntroEmailJob - Error: '.$e->getMessage().' with stack trace: '.$e->getTraceAsString());
        }
    }

}

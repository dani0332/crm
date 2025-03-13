<?php

namespace App\Console\Commands;

use App\Enums\ApplicationStorageEnums;
use App\Enums\LeadSourceEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Jobs\Revival\CarRevivalLeadsCreationJob;
use App\Models\CarQuote;
use App\Services\ApplicationStorageService;
use App\Services\LeadAllocationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Sammyjo20\LaravelHaystack\Models\Haystack;

class DttWithDate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'DttWithDate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This cron fetches leads from car quotes for each day in a custom date from ApplicationStorage (dtt_from), replacing Carbon::now() with each day';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $storageService = app(ApplicationStorageService::class);
        $isDttEnabled = $storageService->getValueByKey(ApplicationStorageEnums::DTT_ENABLED);
        if ($isDttEnabled == false || $isDttEnabled == 0) {
            info('DTT is not enabled from CMS');

            return false;
        }

        // Fetch date range from ApplicationStorage
        $date = $storageService->getValueByKey(ApplicationStorageEnums::DTT_FROM);
        if (is_null($date) || $date->is_active == 0) {
            info('DttWithRange: No date range provided in dtt_from_to, skipping execution');

            return false;
        }

        $currentDate = Carbon::parse($date);

        $excludeSources = [
            LeadSourceEnum::AFIA_RENEWAL,
            LeadSourceEnum::AFIA_ENQUIRY,
            LeadSourceEnum::AQEED_LEAD,
            LeadSourceEnum::AQEED_RENEWALS,
            LeadSourceEnum::AQEED_REVIVAL,
            LeadSourceEnum::ARABIC_ADVISORY,
            LeadSourceEnum::ARABIC_CALL_DESK,
            LeadSourceEnum::ARABIC_TELE_MARKETING,
            LeadSourceEnum::ASD,
            LeadSourceEnum::BDM,
            LeadSourceEnum::CALL_DESK,
            LeadSourceEnum::CALL_DESK_WHATSAPP,
            LeadSourceEnum::CAR_FORM,
            LeadSourceEnum::CAR_INSURANCE_AE,
            LeadSourceEnum::CAR_VAULT_AFFINITY_MOTOR,
            LeadSourceEnum::CORPOLINE_NB,
            LeadSourceEnum::CROSS_SELL,
            LeadSourceEnum::DUBAI_NOW,
            LeadSourceEnum::ECOM,
            LeadSourceEnum::ENQUIRY_FROM_RECEPTION,
            LeadSourceEnum::EXISTING_CLIENT_NEW_BUSINESS,
            LeadSourceEnum::HOME_INSURANCEMARKET_AE,
            LeadSourceEnum::IM_PRIO,
            LeadSourceEnum::IMCRM,
            LeadSourceEnum::MEDICAL_LIFE_INSURANCEMARKET_AE,
            LeadSourceEnum::MOBILE,
            LeadSourceEnum::MOTOR_INQUIRY_INSURANCEMARKET_AE,
            LeadSourceEnum::MOTOR_INQUIRY_PROTECTMYCAR,
            LeadSourceEnum::MOTOR_INQUIRY_ZOOM,
            LeadSourceEnum::PERSONAL_CONTACT,
            LeadSourceEnum::POSTMAN,
            LeadSourceEnum::RECYCLED,
            LeadSourceEnum::REFERRAL,
            LeadSourceEnum::REFERRAL_FROM_EXISTING_CLIENT,
            LeadSourceEnum::RENEWAL_UPLOAD,
            LeadSourceEnum::REVIVAL,
            LeadSourceEnum::REVIVED_LEADS_INSURANCEMARKET_AE,
            LeadSourceEnum::TEST,
            LeadSourceEnum::TEST_POSTMAN,
            LeadSourceEnum::TIER_L_FUTUREDATELEADS,
            LeadSourceEnum::TIER_L_QUALFIED,
            LeadSourceEnum::TM_FACEBOOK,
            LeadSourceEnum::TM_OFFSHORE,
            LeadSourceEnum::TM_ORGANIC,
            LeadSourceEnum::TM_RENEWALS,
            LeadSourceEnum::TM_SP_RENEWAL,
            LeadSourceEnum::TM_WHATSAPP,
            LeadSourceEnum::TPL_COMP,
            LeadSourceEnum::TPL_Renewal,
            LeadSourceEnum::TPL_RENEWALS,
            LeadSourceEnum::TRAVEL_INSURANCEMARKET_AE,
            LeadSourceEnum::WALK_IN_CLIENT,
            LeadSourceEnum::WEB,
        ];

        $logPrefix = 'CarRevivalLeadsCreationJob (DttWithRange) -';

        // Replace Carbon::now() with the current date from the range
        $dateOne = $currentDate->copy()->subMonths(11)->toDateString();
        $dateTwo = $currentDate->copy()->subMonths(11)->addDay(1)->toDateString();
        $datethirtyDaysBefore = $currentDate->copy()->subDays(30)->toDateString();

        info($logPrefix.'Processing for current date: '.$currentDate->toDateString()." (dateOne: $dateOne, dateTwo: $dateTwo)");

        $leads = CarQuote::select(
            'id',
            'uuid',
            'first_name',
            'last_name',
            'email',
            'mobile_no',
            'dob',
            'nationality_id',
            'uae_license_held_for_id',
            'back_home_license_held_for_id',
            'year_of_manufacture',
            'emirate_of_registration_id',
            'car_type_insurance_id',
            'claim_history_id',
            'has_ncd_supporting_documents',
            'additional_notes',
            'car_value',
            'car_value_tier',
            'seat_capacity',
            'cylinder',
            'vehicle_type_id',
            'premium',
            'car_make_id',
            'car_model_id',
            'currently_insured_with'
        )
            ->where('is_revived', '=', false)
            ->where('created_at', '>=', $dateOne)
            ->where('created_at', '<', $dateTwo)
            ->whereNotNull(['email'])
            ->where(function ($q) use ($datethirtyDaysBefore) {
                $q->where('source', '!=', LeadSourceEnum::REVIVAL)
                    ->where('created_at', '<=', $datethirtyDaysBefore);
            })
            ->whereNotIn('source', $excludeSources)
            ->whereNull('renewal_batch')
            ->whereNull('previous_quote_policy_number')
            ->whereNotIn('quote_status_id', [
                QuoteStatusEnum::PolicyIssued,
                QuoteStatusEnum::TransactionApproved,
                QuoteStatusEnum::Fake,
                QuoteStatusEnum::Duplicate,
            ])
            ->where('payment_status_id', '!=', PaymentStatusEnum::CAPTURED)
            ->groupBy(['email', 'car_make_id', 'car_model_id', 'year_of_manufacture'])
            ->get();
        info($logPrefix."Count for $dateOne - ".count($leads).' - '.json_encode($leads->pluck('uuid')->toArray()));

        $jobs = [];
        foreach ($leads as $carLead) {
            $isTierR = app(LeadAllocationService::class)->checkIfLeadIsRenewal($carLead);
            if (! $isTierR) {
                $jobs[] = new CarRevivalLeadsCreationJob($carLead);
            }
        }
        if ($jobs && count($jobs)) {
            Haystack::build()
                ->addJobs($jobs)
                ->then(function () use ($logPrefix, $dateOne) {
                    info($logPrefix."All jobs for $dateOne completed successfully");
                })
                ->catch(function () use ($logPrefix, $dateOne) {
                    info($logPrefix."One of batch for $dateOne failed");
                })
                ->finally(function () use ($logPrefix, $dateOne) {
                    info($logPrefix."Everything done for $dateOne");
                })
                ->allowFailures()
                ->withDelay(30)
                ->dispatch();
        } else {
            info($logPrefix."------No leads found for $dateOne------");
        }

        return 0;
    }
}

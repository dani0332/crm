<?php

namespace App\Jobs\Revival;

use App\Enums\ApplicationStorageEnums;
use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Facades\Capi;
use App\Facades\Ken;
use App\Models\ApplicationStorage;
use App\Models\DttRevival;
use App\Models\HealthQuote;
use App\Models\QuoteBatches;
use App\Services\SendEmailCustomerService;
use App\Traits\AddPremiumAllLobs;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;
use Sammyjo20\LaravelHaystack\Concerns\Stackable;
use Sammyjo20\LaravelHaystack\Contracts\StackableJob;
use Throwable;

class HealthRevivalLeadsCreationJob implements ShouldQueue, StackableJob
{
    use AddPremiumAllLobs, Dispatchable, GenericQueriesAllLobs, InteractsWithQueue, Queueable, Stackable;

    public $tries = 3;
    public $timeout = 300;
    public $backoff = 320;
    private $lead = null;

    /**
     * Create a new job instance.
     */
    public function __construct($lead)
    {
        $this->lead = $lead;
        $this->onQueue('renewals');
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $dttEnabled = ApplicationStorage::where('key_name', '=', ApplicationStorageEnums::DTT_HEALTH_ENABLED)->value('value');
        if ($dttEnabled == 0) {
            info('HealthRevivalLeadsCreationJob - DTT_HEALTH is not enabled from cms');

            return false;
        }

        $logPrefix = 'DTTHealth - HealthRevivalLeadsCreationJob - ';

        $this->lead->refresh();
        if ($this->lead->is_revived) {
            info($logPrefix.$this->lead->uuid.' - Lead Already Revived');

            return false;
        }
        try {

            $dataArr = [
                'email' => $this->lead->email,
                // 'email' => 'nouman.hussain@myalfred.com',
                'details' => $this->lead->details,
                'mobileNo' => $this->lead->mobile_no,
                'preference' => $this->lead->preference,
                'source' => LeadSourceEnum::REVIVAL,
                'maritalStatusId' => $this->lead->marital_status_id,
                'premium' => $this->lead->premium,
                'leadTypeId' => $this->lead->lead_type_id,
                'referenceUrl' => config('constants.APP_URL'),
                'price_starting_from' => $this->lead->price_starting_from,
                'is_ebp_renewal' => $this->lead->is_ebp_renewal == 'on' ? true : false,
                'coverForId' => $this->lead->cover_for_id,
                'hasDental' => $this->lead->has_dental == 'on' ? true : false,
                'hasWorldwideCover' => $this->lead->has_worldwide_cover == 'on' ? true : false,
                'hasHome' => $this->lead->has_home == 'on' ? true : false,
                'currentlyInsuredWithId' => $this->lead->currently_insured_with_id,
                'healthTeamType' => $this->lead->health_team_type,
                'healthPlanTypeId' => $this->lead->health_plan_type_id,
            ];
            $dataArr['memberDetails'][] = [
                'firstName' => $this->lead->first_name,
                'lastName' => $this->lead->last_name,
                'dob' => $this->lead->dob,
                'gender' => $this->lead->gender,
                'nationalityId' => $this->lead->nationality_id,
                'emirateOfYourVisaId' => $this->lead->emirate_of_your_visa_id,
                'salaryBandId' => $this->lead->salary_band_id,
                'memberCategoryId' => $this->lead->member_category_id,
            ];

            $capiResponse = Capi::request('/api/v1-save-health-quote', 'post', $dataArr);

            if (! isset($capiResponse->errors) && ! empty($capiResponse->quoteUID)) {
                $healthQuote = $this->getQuoteObject(QuoteTypes::HEALTH->value, $capiResponse->quoteUID);
                // Get the latest quote batch and assign it to the lead.
                $quoteBatch = QuoteBatches::latest()->first();

                if ($capiResponse->isDuplicate) {
                    info($logPrefix.'healthRevivalParentLead -'.$this->lead->uuid.'- childLeadNotCreated - '.$capiResponse->quoteUID.' -isduplicate-'.$capiResponse->isDuplicate);
                    HealthQuote::find($this->lead->id)->update(['is_revived' => true]);

                    $revivalRecord = DttRevival::where([
                        'quote_type_id' => QuoteTypes::HEALTH->id(),
                        'quote_id' => $healthQuote->id,
                        'uuid' => $capiResponse->quoteUID,
                        'revival_quote_batch_id' => $quoteBatch->id,
                        'email_sent' => true,
                        'previous_health_plan_type' => empty($healthQuote->health_plan_type_id) ? false : true,
                    ])->first();

                    if ($revivalRecord) {
                        info($logPrefix.' UUID - '.$capiResponse->quoteUID.' - Revival Record Found');

                        return false;
                    }
                } else {
                    info($logPrefix.'healthRevivalParentLead -'.$this->lead->uuid.'- childLeadCreated - '.$capiResponse->quoteUID);
                }

                $healthRevival = DttRevival::firstOrCreate(
                    [
                        'quote_type_id' => QuoteTypes::HEALTH->id(),
                        'quote_id' => $healthQuote->id,
                        'uuid' => $capiResponse->quoteUID,
                    ],
                    [
                        'revival_quote_batch_id' => $quoteBatch->id,
                        'email_sent' => false,
                        'previous_health_plan_type' => empty($healthQuote->health_plan_type_id) ? false : true,
                    ]
                );

                $customerName = $healthQuote->first_name.' '.$healthQuote->last_name;
                sleep(5);
                if (empty($healthQuote->health_plan_type_id)) {

                    $key = ApplicationStorageEnums::DTT_HEALTH_INITIAL_WITHOUT_HEALTH_TEAM;

                    $emailTemplateId = ApplicationStorage::where('key_name', $key)->value('value');
                    $response = Ken::renewalRequest('/get-health-cheapest-plans', 'post', [
                        'quoteUID' => $capiResponse->quoteUID,
                        'isPlanTypes' => true,
                    ]);
                    if (! isset($response['planTypes'])) {
                        info($logPrefix.'noPlansReturned - UUID -'.$capiResponse->quoteUID.'-'.json_encode($response));

                        return false;
                    }
                    $emailData = new \stdClass;
                    $emailData->planTypes = $response['planTypes'];

                    $emailData->subject = 'Renew your health insurance policy today! '.$healthQuote->code;
                } else {

                    $response = Ken::renewalRequest('/get-health-quote-plans-order-priority', 'post', [
                        'quoteUID' => $healthQuote->uuid,
                        'isModified' => true,
                    ]);

                    if (! isset($response['quote']['plans'])) {
                        info($logPrefix.'noPlansReturned-UUID-'.$capiResponse->quoteUID.'-'.json_encode($response));

                        return false;
                    }
                    $plansArray = [];
                    foreach ($response['quote']['plans'] as $item) {
                        $planObj = new \stdClass;
                        $planObj->id = $item['id'];
                        $planObj->name = $item['name'];
                        $planObj->providerName = $item['providerName'];
                        $planObj->eligibilityName = $item['eligibilityName'];
                        $planObj->planCode = $item['planCode'];
                        $planObj->providerCode = $item['providerCode'];
                        $planObj->total = $item['total'];
                        $planObj->buynowURL = $item['buynowURL'];
                        $planObj->planBenefit = $item['planBenefit'];
                        $plansArray[] = $planObj;
                    }

                    $emailData = new \stdClass;

                    $key = ApplicationStorageEnums::DTT_HEALTH_INITIAL_AND_FOLLOWUP_TEMPLATE;

                    $emailTemplateId = ApplicationStorage::where('key_name', $key)->value('value');

                    $emailData->quotePlanLink = $response['quote']['quotePlanLink'];
                    $emailData->requestAdvisorLink = $response['quote']['requestAdvisorLink'];

                    $emailData->plans = $plansArray;

                    $emailData->subject = 'Act now! Your health insurance renewal is due '.$healthQuote->code;
                }
                $emailData->customerName = $customerName;
                $emailData->customerEmail = $healthQuote->email;
                $emailData->templateId = (int) $emailTemplateId;
                $emailData->lob = QuoteTypes::HEALTH->id();

                $key = ApplicationStorageEnums::DTT_HEALTH_FOLLOWUP_FROM_EMAIL;
                $emailData->fromEmail = ApplicationStorage::where('key_name', $key)->value('value');

                $emailData->tag = 'health-revival-initial-email';
                $emailData->templateType = 'revivalHealthInitial';

                $response = app(SendEmailCustomerService::class)->sendDttEmail($emailData);
                if ($response == 201) {
                    info($logPrefix.'ParentLead - '.$this->lead->uuid.' - childLead - '.$capiResponse->quoteUID.' - emailSent - '.$emailData->customerEmail);

                    $healthRevival->update(['email_sent' => true]);
                    // update child lead
                    HealthQuote::find($healthQuote->id)->update(['quote_status_id' => QuoteStatusEnum::Quoted]);
                    // update parent lead
                    HealthQuote::find($this->lead->id)->update(['is_revived' => true]);
                } else {
                    info($logPrefix.'ParentLead - '.$this->lead->uuid.'- childLead - '.$capiResponse->quoteUID.'emailIsNotSent - '.$emailData->customerEmail);
                }
            } else {
                info($logPrefix.'healthRevivalParentLead - '.$this->lead->uuid.' - capiResponseError - '.json_encode($capiResponse));
            }
        } catch (\Exception $exception) {
            Log::error($logPrefix.'health revival Exception - '.$this->lead->uuid.' - Exception:'.$exception->getMessage());
        }
    }

    public function middleware()
    {
        return [(new WithoutOverlapping($this->lead->uuid))->dontRelease()];
    }

    public function failed(Throwable $exception)
    {
        Log::error('DTTHealth - HealthRevivalLeadsCreationJob - Failed - '.$this->lead->uuid.' Error: '.$exception->getMessage());
    }
}

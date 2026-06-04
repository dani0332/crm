<?php

namespace App\Jobs\Revival;

use App\Enums\ApplicationStorageEnums;
use App\Enums\CustomerTypeEnum;
use App\Enums\HealthCoverForEnum;
use App\Enums\HealthInsureEnum;
use App\Enums\HealthPolicyHolderEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Enums\RelationCodeEnum;
use App\Facades\Capi;
use App\Facades\Ken;
use App\Models\ApplicationStorage;
use App\Models\DttRevival;
use App\Models\HealthQuote;
use App\Models\QuoteBatches;
use App\Services\HealthRevamp\HealthQuoteRevampMigrationMutator;
use App\Services\Logger\LoggerService;
use App\Services\SendEmailCustomerService;
use App\Traits\AddPremiumAllLobs;
use App\Traits\GenericQueriesAllLobs;
use Carbon\Carbon;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;
use Throwable;

class HealthRevivalLeadsCreationJob implements ShouldQueue
{
    use AddPremiumAllLobs, Batchable, Dispatchable, GenericQueriesAllLobs, InteractsWithQueue, Queueable;

    public $tries = 3;
    public $timeout = 300;
    public $backoff = 320;
    private $lead = null;
    private HealthQuoteRevampMigrationMutator $mutator;

    /**
     * Create a new job instance.
     */
    public function __construct($lead)
    {
        $this->lead = $lead;
        $this->mutator = app(HealthQuoteRevampMigrationMutator::class);
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

            if ($this->lead->isMigrated()) {

                $coverForId = $this->lead->cover_for_id;
                $maritalStatusId = $this->lead->marital_status_id;
                $insureCode = $this->lead->insure_code;
                $policyHolderCode = $this->lead->policy_holder_code;
                $policyHolderCategoryCode = $this->lead->policy_holder_category_code;

                // create policyholder member data
                $gender = $this->lead->gender;
                $salaryBandId = $this->lead->salary_band_id;
                $memberCategoryId = $this->lead->member_category_id;
                $visaCategoryId = $this->lead->visa_category_id;

            } else {

                // update following data for non-migrated
                $coverForId = $this->mutator->getCoverForId($this->lead);
                $maritalStatusId = $this->mutator->getMartialStatusId($this->lead);
                $insureCode = $coverForId == HealthCoverForEnum::DOMESTIC_HELPER->value ? null : HealthInsureEnum::MYSELF_AND_MY_FAMILY_MEMBERS->value;
                $policyHolderCode = $coverForId == HealthCoverForEnum::DOMESTIC_HELPER->value ? null : HealthPolicyHolderEnum::ME->value;
                $policyHolderCategoryCode = $this->mutator->getPolicyHolderCategoryCode($this->lead);

                // create policyholder member data for non-migrated
                $gender = $this->mutator->getGender($this->lead);
                $salaryBandId = $this->mutator->getSalaryBandId($this->lead);
                $visaCategoryId = $this->mutator->getVisaCategoryId($this->lead);
                $memberCategoryId = $this->mutator->getMemberCategoryId($this->lead);
            }

            $dataArr = [
                'callSource' => strtolower(LeadSourceEnum::IMCRM),
                'email' => $this->lead->email,
                'details' => $this->lead->details,
                'mobileNo' => $this->lead->mobile_no,
                'preference' => $this->lead->preference,
                'source' => LeadSourceEnum::REVIVAL,
                'premium' => $this->lead->premium,
                'leadTypeId' => $this->lead->lead_type_id,
                'referenceUrl' => config('constants.APP_URL'),
                'isEbpRenewal' => $this->lead->is_ebp_renewal == 'on' ? true : false,
                'hasDental' => $this->lead->has_dental == 'on' ? true : false,
                'hasWorldwideCover' => $this->lead->has_worldwide_cover == 'on' ? true : false,
                'hasHome' => $this->lead->has_home == 'on' ? true : false,
                'currentlyInsuredWithId' => $this->lead->currently_insured_with_id,
                'healthPlanTypeId' => $this->lead->health_plan_type_id,
                'customerType' => CustomerTypeEnum::Individual,
                'price_starting_from' => null,
                'healthTeamType' => $this->lead->health_team_type,
                'subSourceId' => $this->lead->sub_source_id ?? null,
                'subSourceOptionsId' => $this->lead->sub_source_options_id ?? null,
                'additionalNotes' => $this->lead->additional_notes ?? null,
                'userId' => null,
                'policyNumber' => null,
                'policyStartDate' => null,
                'firstName' => $this->lead->first_name,
                'lastName' => $this->lead->last_name,
                'sendOcbEmail' => false,
                'coverForId' => $coverForId,
                'maritalStatusId' => $maritalStatusId,
                'insureCode' => $insureCode, // WHO WOULD THE CUSTOMER LIKE TO INSURE?
                'policyHolderCode' => $policyHolderCode, // WHO WILL BE THE POLICYHOLDER?
                'policyHolderCategoryCode' => $policyHolderCategoryCode,
            ];

            $dob = ! empty($this->lead->dob) ? Carbon::parse($this->lead->dob)->toDateString() : null;
            $dataArr['memberDetails'][] = [
                'firstName' => $this->lead->first_name,
                'lastName' => $this->lead->last_name,
                'dob' => $dob,
                'nationalityId' => $this->lead->nationality_id,
                'emirateOfYourVisaId' => $this->lead->emirate_of_your_visa_id,
                'gender' => $gender,
                'salaryBandId' => $salaryBandId,
                'memberCategoryId' => $memberCategoryId,
                'visaCategoryId' => $visaCategoryId,
                'relationCode' => RelationCodeEnum::SELF->value,
                'maritalStatusId' => $maritalStatusId,
                'isInsured' => $coverForId == HealthCoverForEnum::DOMESTIC_HELPER->value ? false : true,
                'isPolicyHolder' => true,
                'isPrincipal' => $coverForId == HealthCoverForEnum::DOMESTIC_HELPER->value ? false : true,
                'isPecMarked' => $this->lead->pec_marked_at != null,
            ];

            LoggerService::info('Health saveHealthQuote - CAPI API request - Revival', extra: ['request' => $dataArr]);

            $capiResponse = Capi::request('/api/v1-save-health-quote', 'post', $dataArr);

            LoggerService::info('Health saveHealthQuote - CAPI API response - Revival', extra: ['response' => $capiResponse]);

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

                if ($healthQuote->isAUHLead(false) && $healthQuote->isLeadSourceRevivalOrInsuranceWallet()) {
                    // skip email for AUH and Revival/Insurance Wallet
                    LoggerService::info('HealthRevivalLeadsCreationJob - Skipping email for AUH and Revival/Insurance Wallet for uuid: '.$healthQuote->uuid);
                    $response = 201;
                } else {
                    $response = app(SendEmailCustomerService::class)->sendDttEmail($emailData);
                }
                if ($response == 201) {
                    info($logPrefix.'ParentLead - '.$this->lead->uuid.' - childLead - '.$capiResponse->quoteUID.' - emailSent - '.$emailData->customerEmail);

                    $healthRevival->update(['email_sent' => true]);

                    $quoteStatusId = ($healthQuote->isAUHLead(false) && $healthQuote->isLeadSourceRevivalOrInsuranceWallet())
                        ? QuoteStatusEnum::NewLead
                        : QuoteStatusEnum::Quoted;

                    // update child lead
                    HealthQuote::find($healthQuote->id)->update(['quote_status_id' => $quoteStatusId]);

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

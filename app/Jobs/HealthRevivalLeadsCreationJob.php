<?php

namespace App\Jobs;

use App\Enums\ApplicationStorageEnums;
use App\Enums\LeadSourceEnum;
use App\Enums\QuoteTypes;
use App\Facades\Capi;
use App\Facades\Ken;
use App\Models\ApplicationStorage;
use App\Models\DttRevival;
use App\Models\HealthQuote;
use App\Models\QuoteBatches;
use App\Services\CapiRequestService;
use App\Services\SendEmailCustomerService;
use App\Traits\AddPremiumAllLobs;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Sammyjo20\LaravelHaystack\Concerns\Stackable;
use Sammyjo20\LaravelHaystack\Contracts\StackableJob;

class HealthRevivalLeadsCreationJob implements ShouldQueue, StackableJob
{
    use AddPremiumAllLobs, Dispatchable, InteractsWithQueue, Queueable, Stackable;
    use GenericQueriesAllLobs;

    public $tries = 3;
    public $timeout = 90;
    public $backoff = 300;
    private $lead = null;

    /**
     * Create a new job instance.
     */
    public function __construct($lead)
    {
        $this->lead = $lead;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $dttEnabled = ApplicationStorage::where('key_name', '=', ApplicationStorageEnums::DTT_ENABLED)->value('value');
        if ($dttEnabled == 0) {
            info('HealthRevivalLeadsCreationJob - Dtt is not enabled from cms');

            return false;
        }

        // $this->lead->refresh();
        $logPrefix = 'HealthRevivalLeadsCreationJob-';
        try {

            $dataArr = [
                // 'email' =>  $this->lead->email,
                'email' => 'nouman.hussain@myalfred.com',
                'details' => $this->lead->details,
                'mobileNo' => $this->lead->mobile_no,
                'preference' => $this->lead->preference,
                'source' => LeadSourceEnum::REVIVAL,
                'maritalStatusId' => $this->lead->marital_status_id,
                'premium' => $this->lead->premium,
                'leadTypeId' => $this->lead->lead_type_id,
                'referenceUrl' => config('constants.APP_URL'),
                'is_ebp_renewal' => $this->lead->is_ebp_renewal == 'on' ? true : false,
                'coverForId' => $this->lead->cover_for_id,
                'hasDental' => $this->lead->has_dental == 'on' ? true : false,
                'hasWorldwideCover' => $this->lead->has_worldwide_cover == 'on' ? true : false,
                'hasHome' => $this->lead->has_home == 'on' ? true : false,
                'currentlyInsuredWithId' => $this->lead->currently_insured_with_id,
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

            info($logPrefix.'capiPayLoad'.json_encode($dataArr));

            // $capiResponse = CapiRequestService::sendCAPIRequest('/api/v1-save-health-quote', $dataArr, HealthQuote::class);

            $capiResponse = Capi::request('/api/v1-save-health-quote', 'post', $dataArr);

            if (! isset($capiResponse->errors) && ! empty($capiResponse->quoteUID)) {
                info($logPrefix.'healthRevivalParentLead -'.$this->lead->uuid.'- childLeadCreated - '.$capiResponse->quoteUID.' - CAPI Response-'.json_encode($capiResponse));

                $healthQuote = $this->getQuoteObject(QuoteTypes::HEALTH->value, $capiResponse->quoteUID);

                $customerName = $healthQuote->first_name.' '.$healthQuote->last_name;
                if (empty($this->lead->health_team_type)) {

                    $key = ApplicationStorageEnums::DTT_HEALTH_INITIAL_WITHOUT_HEALTH_TEAM;

                    $emailTemplateId = ApplicationStorage::where('key_name', $key)->value('value');
                    $response = Ken::request('/get-health-cheapest-plans', 'post', [
                        'quoteUID' => $capiResponse->quoteUID,
                        'isPlanTypes' => true,
                    ]);

                    $emailData = new \stdClass;
                    $emailData->planTypes = $response['planTypes'];
                } else {

                    $response = Ken::request('/get-health-quote-plans-order-priority', 'post', [
                        'quoteUID' => $healthQuote->uuid,
                    ]);

                    if (empty($response['plans'])) {
                        info($logPrefix.'noPlansReturned-UUID-'.$capiResponse->quoteUID.'-'.json_encode($response));

                        return false;
                    }
                    $plansArray = [];
                    foreach ($response['plans'] as $item) {
                        $planObj = new \stdClass;
                        $planObj->id = $item['id'];
                        $planObj->name = $item['name'];
                        $planObj->providerName = $item['providerName'];
                        $planObj->eligibilityName = $item['eligibilityName'];
                        $planObj->planCode = $item['planCode'];
                        $planObj->providerCode = $item['providerCode'];
                        $planObj->total = $item['premium'];
                        $planObj->buynowURL = $item['planLink'];

                        $lowestRate = collect($item['ratesPerCopay'])->sortBy('discountPremium')->first();

                        $coPaymentsCollection = collect($item['coPayments']);
                        $filteredSelectedCopay = $coPaymentsCollection->where('id', $lowestRate['healthPlanCoPaymentId'])->first();
                        $planObj->planBenefit = $this->getBenefitsDetails($item['benefits'], $filteredSelectedCopay);
                        $plansArray[] = $planObj;
                    }

                    info($logPrefix.'plans'.json_encode($plansArray));

                    $emailData = new \stdClass;

                    $key = ApplicationStorageEnums::DTT_HEALTH_INITIAL_AND_FOLLOWUP_TEMPLATE;

                    $emailTemplateId = ApplicationStorage::where('key_name', $key)->value('value');

                    $emailData->quotePlanLink = config('constants.ECOM_HEALTH_INSURANCE_QUOTE_URL').$healthQuote->uuid;

                    $emailData->plans = $plansArray;

                    info($logPrefix.'emailData -'.json_encode($emailData));
                }
                $emailData->subject = $customerName."'s".' Health Insurance with Alfred '.$healthQuote->code;
                $emailData->customerName = $customerName;
                $emailData->customerEmail = $healthQuote->email;
                $emailData->templateId = (int) $emailTemplateId;

                $emailData->tag = 'health-revival-initial-email';
                $emailData->templateType = 'revivalHealthInitial';

                $response = app(SendEmailCustomerService::class)->sendDttEmail($emailData);
                if ($response == 201) {
                    info($logPrefix.'healthRevivalParentLead -'.$this->lead->uuid.'-childLead - '.$capiResponse->quoteUID.'- emailSent -- '.$emailData->customerEmail);

                    // Get the latest quote batch and assign it to the lead.
                    $quoteBatch = QuoteBatches::latest()->first();

                    DttRevival::create([
                        'quote_type_id' => QuoteTypes::HEALTH->id(),
                        'quote_id' => $healthQuote->id,
                        'uuid' => $capiResponse->quoteUID,
                        'revival_quote_batch_id' => $quoteBatch->id,
                        'email_sent' => true,
                        'previous_health_plan_type' => empty($this->lead->health_team_type) ? false : true,
                    ]);

                    info($logPrefix.'healthRevivalParentLead -'.$this->lead->uuid.'- childLead - '.$capiResponse->quoteUID.'-dttRevivalsInsertedUUID - '.$capiResponse->quoteUID);

                    HealthQuote::find($this->lead->id)->update(['is_revived' => true]);

                    info($logPrefix.'healthRevivalParentLead -'.$this->lead->uuid.'- childLead - '.$capiResponse->quoteUID.'- parentLeadIsRevived - '.$this->lead->id);
                } else {
                    info('HealthRevivalLeadsCreationJob - healthRevivalParentLead -'.$this->lead->uuid.'- childLead - '.$capiResponse->quoteUID.'emailIsNotSent - '.$emailData->customerEmail);
                }
            } else {
                info($logPrefix.'healthRevivalParentLead -'.$this->lead->uuid.'- capiResponseError - '.json_encode($capiResponse));
            }
        } catch (\Exception $exception) {
            Log::error($logPrefix.'health revival Exception - '.$this->lead->id.' - Exception:'.$exception->getMessage());
        }
    }

    private function getBenefitsDetails($benefitList, $filteredSelectedCopay)
    {
        $benefitsTypes = [];
        $getBenefitCode = ['annualLimit', 'regionsCovered'];

        foreach ($benefitList as $key => $covers) {

            if ($key == 'outpatient') {
                foreach ($covers as $cover) {
                    if ($cover['code'] === 'medicine') {
                        $benefitsTypes[$cover['code']] = [
                            'text' => $cover['value'] ?? '',
                        ];
                    }
                }
            } else {
                foreach ($covers as $cover) {
                    if (in_array($cover['code'], $getBenefitCode)) {
                        $benefitsTypes[$cover['code']] = [
                            'text' => $cover['value'] ?? '',
                        ];
                    }
                    if ($cover['code'] == 'outpatientConsultation') {
                        $benefitsTypes[$cover['code']] = [
                            'text' => $filteredSelectedCopay['text'] ?? '',
                        ];
                    }
                }
            }
        }

        return $benefitsTypes;
    }
}

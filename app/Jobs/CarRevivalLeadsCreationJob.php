<?php

namespace App\Jobs;

use App\Enums\ApplicationStorageEnums;
use App\Enums\GenericRequestEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\QuoteTypes;
use App\Enums\TiersEnum;
use App\Facades\Capi;
use App\Facades\Ken;
use App\Models\ApplicationStorage;
use App\Models\CarQuote;
use App\Models\DttRevival;
use App\Models\QuoteBatches;
use App\Models\Tier;
use App\Services\CarQuoteService;
use App\Services\EmailServices\CarEmailService;
use App\Services\SendEmailCustomerService;
use App\Services\UserService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Sammyjo20\LaravelHaystack\Concerns\Stackable;
use Sammyjo20\LaravelHaystack\Contracts\StackableJob;
use Throwable;

class CarRevivalLeadsCreationJob implements ShouldQueue, StackableJob
{
    use Dispatchable, InteractsWithQueue, Queueable, Stackable;
    use GenericQueriesAllLobs;

    public $tries = 3;
    public $timeout = 90;
    public $backoff = 300;
    private $lead = null;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($lead)
    {
        $this->lead = $lead;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        $logPrefix = 'CarRevivalLeadsCreationJob -';

        $dttEnabled = ApplicationStorage::where('key_name', '=', ApplicationStorageEnums::DTT_ENABLED)->value('value');
        if ($dttEnabled == 0) {
            info($logPrefix . 'Dtt is not enabled from cms');

            return false;
        }

        $this->lead->refresh();
        if ($this->lead->is_revived) {
            info($logPrefix . $this->lead->uuid . ' - Lead Already Revived');

            return false;
        }
        try {
            $dataArr = [
                'firstName' => $this->lead->first_name,
                'lastName' => $this->lead->last_name,
                'email' => $this->lead->email,
                'mobileNo' => $this->lead->mobile_no,
                'dob' => $this->lead->dob,
                'nationalityId' => $this->lead->nationality_id,
                'uaeLicenseHeldForId' => $this->lead->uae_license_held_for_id,
                'backHomeLicenseHeldForId' => $this->lead->back_home_license_held_for_id,
                'yearOfManufacture' => $this->lead->year_of_manufacture,
                'emirateOfRegistrationId' => $this->lead->emirate_of_registration_id,
                'carTypeInsuranceId' => $this->lead->car_type_insurance_id,
                'claimHistoryId' => $this->lead->claim_history_id,
                'hasNcdSupportingDocuments' => $this->lead->has_ncd_supporting_documents == GenericRequestEnum::Yes ? true : false,
                'additionalNotes' => $this->lead->additional_notes,
                'carValue' => (int) $this->lead->car_value,
                'carValueTier' => $this->lead->car_value_tier,
                'seatCapacity' => $this->lead->seat_capacity,
                'cylinder' => $this->lead->cylinder,
                'vehicleTypeId' => $this->lead->vehicle_type_id,
                'premium' => $this->lead->premium,
                'carMakeId' => $this->lead->car_make_id,
                'carModelId' => $this->lead->car_model_id,
                'currentlyInsuredWith' => $this->lead->currently_insured_with,
                'source' => LeadSourceEnum::REVIVAL,
                'isEmailSkip' => true,
                'referenceUrl' => config('constants.APP_URL'),
                'sicFlowEnabled' => false,
                'whatsappConsent' => true,
            ];

            $capiResponse = Capi::request('/api/v1-save-car-quote', 'post', $dataArr);

            if (!isset($capiResponse->errors) && !empty($capiResponse->quoteUID)) {
                info($logPrefix . $this->lead->uuid . '- childLeadCreated - ' . $capiResponse->quoteUID);

                $payload = [
                    'quoteUID' => $capiResponse->quoteUID,
                    'callSource' => 'imcrm',
                ];

                $response = Ken::request('/send-ocb-whatsapp-revival', 'post', $payload);

                info($logPrefix . 'CarRevivalLeadsCreationJob-send-ocb-whatsapp-revival -' . json_encode($response));

                $carQuote = $this->getQuoteObject(QuoteTypes::CAR->value, $capiResponse->quoteUID);

                $listQuotePlans = app(CarQuoteService::class)->getPlans($capiResponse->quoteUID, true, true);

                $quotePlansCount = is_countable($listQuotePlans) ? count($listQuotePlans) : 0;

                if ($quotePlansCount == 0) {
                    $key = ApplicationStorageEnums::OCB_NEW_BUSINESS_ZERO_PLAN;
                } elseif ($quotePlansCount == 1) {
                    $key = ApplicationStorageEnums::OCB_NEW_BUSINESS_SINGLE_PLAN;
                } else {
                    $key = ApplicationStorageEnums::OCB_NEW_BUSINESS_MULTIPLE_PLANS;
                }
                $emailTemplateId = ApplicationStorage::where('key_name', $key)->value('value');

                $previousAdvisor = null;
                if (! empty($carQuote->previous_advisor_id)) {
                    $previousAdvisor = app(UserService::class)->getUserById($carQuote->previous_advisor_id);
                }
                $tierR = Tier::where('name', TiersEnum::TIER_R)->where('is_active', 1)->first();

                $listQuotePlans = (is_string($listQuotePlans)) ? [] : $listQuotePlans;

                $emailData = (new CarEmailService(app(SendEmailCustomerService::class)))->buildEmailData($carQuote, $listQuotePlans, $previousAdvisor, $tierR->id);

                $customerName = $carQuote->first_name . ' ' . $carQuote->last_name;

                $emailData->subject = $customerName . "'s" . ' Car Insurance with Alfred ' . $carQuote->code;

                $emailData->templateId = (int) $emailTemplateId;
                $emailData->uuid = $carQuote->uuid;

                $dttAdvisor = ApplicationStorage::where('key_name', '=', ApplicationStorageEnums::DTT_ADVISOR)->value('value');

                $advisor = explode(',', $dttAdvisor);

                $emailData->advisorName = $advisor[0];
                $emailData->advisorEmail = $advisor[1];
                $emailData->tag = 'dtt-initial-email';

                $response = app(SendEmailCustomerService::class)->sendDttEmail($emailData);

                if ($response == 201) {
                    info($logPrefix . 'carRevivalParentLead -' . $this->lead->uuid . '-childLead - ' . $capiResponse->quoteUID . '- emailSent -- ' . $emailData->customerEmail);

                    // Get the latest quote batch and assign it to the lead.
                    $quoteBatch = QuoteBatches::latest()->first();

                    DttRevival::create([
                        'quote_type_id' => QuoteTypes::CAR->id(),
                        'quote_id' => $carQuote->id,
                        'uuid' => $capiResponse->quoteUID,
                        'revival_quote_batch_id' => $quoteBatch->id,
                        'email_sent' => true,
                    ]);

                    CarQuote::find($this->lead->id)->update(['is_revived' => true]);
                } else {
                    info($logPrefix . 'carRevivalParentLead -' . $this->lead->uuid . '- childLead - ' . $capiResponse->quoteUID . 'emailIsNotSent - ' . $emailData->customerEmail);
                }
            } else {
                info($logPrefix . 'carRevivalParentLead -' . $this->lead->uuid . '- capiResponseError - ' . json_encode($capiResponse));
            }
        } catch (\Exception $exception) {
            Log::error($logPrefix . 'DTT Exception - ' . $this->lead->id . ' - Exception:' . $exception->getMessage());
        }
    }

    public function failed(Throwable $exception)
    {
        Log::error('CarRevivalLeadsCreationJob - Failed - ' . $this->lead->id . ' Error: ' . $exception->getMessage());
    }
}

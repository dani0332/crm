<?php

namespace App\Jobs\Revival;

use App\Enums\ApplicationStorageEnums;
use App\Enums\GenericRequestEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\QuoteFlowType;
use App\Enums\QuoteTypes;
use App\Enums\WorkflowTypeEnum;
use App\Facades\Capi;
use App\Models\ApplicationStorage;
use App\Models\CarQuote;
use App\Models\DttRevival;
use App\Models\QuoteBatches;
use App\Services\BirdService;
use App\Services\CarRevivalService;
use App\Services\EmailServices\CarEmailService;
use App\Services\Logger\LoggerService;
use App\Services\SendEmailCustomerService;
use App\Services\UserService;
use App\Traits\GenericQueriesAllLobs;
use Carbon\Carbon;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Throwable;

class CarRevivalLeadsCreationJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable;
    use GenericQueriesAllLobs;

    private const LOG_FLOW = 'car_dtt_revival_creation';

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
        $this->onQueue('renewals');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if ($this->batch()->cancelled()) {
            return false;
        }

        $dttEnabled = ApplicationStorage::where('key_name', '=', ApplicationStorageEnums::DTT_ENABLED)->value('value');
        if ($dttEnabled == 0) {
            LoggerService::info(self::class.': DTT disabled in CMS', [
                'flow' => self::LOG_FLOW,
                'parent_lead_uuid' => $this->lead->uuid,
            ]);

            return false;
        }

        $this->lead->refresh();
        if ($this->lead->is_revived) {
            LoggerService::info(self::class.': Lead already revived', [
                'flow' => self::LOG_FLOW,
                'parent_lead_uuid' => $this->lead->uuid,
            ]);

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
                'vehicleUse' => $this->lead?->vehicle_use,
            ];

            $carQuoteExists = CarQuote::select('uuid')->where([
                'email' => $this->lead->email,
                'mobile_no' => $this->lead->mobile_no,
                'car_make_id' => $this->lead->car_make_id,
                'car_model_id' => $this->lead->car_model_id,
                'vehicle_type_id' => $this->lead->vehicle_type_id,
                'source' => LeadSourceEnum::REVIVAL,
            ])->where('created_at', '>=', Carbon::now()->subMonths(11)->toDateString())->first();

            $revivedLead = null;
            $revivalCarQuoteUUID = null;
            if (! $carQuoteExists) {
                $capiResponse = Capi::request('/api/v1-save-car-quote', 'post', $dataArr);
                if (isset($capiResponse->errors) && empty($capiResponse->quoteUID)) {
                    LoggerService::warning(self::class.': CAPI v1-save-car-quote failed - Error Creating Revival Lead', [
                        'flow' => self::LOG_FLOW,
                        'parent_lead_uuid' => $this->lead->uuid,
                        'parent_lead_id' => $this->lead->id,
                        'capi_path' => '/api/v1-save-car-quote',
                        'capi_errors' => $capiResponse->errors ?? null,
                        'capi_response' => $capiResponse,
                    ]);

                    return false;
                } else {
                    $revivalCarQuoteUUID = $capiResponse->quoteUID;
                    LoggerService::info(self::class.' - '.$this->lead->uuid.' - childLeadCreated - '.$revivalCarQuoteUUID);
                }
            } else {
                $revivalCarQuoteUUID = $carQuoteExists->uuid;
                LoggerService::info(self::class.' - '.$this->lead->uuid.' - childLeadFound - '.$revivalCarQuoteUUID);
                $revivedLead = DttRevival::where([
                    'quote_type_id' => QuoteTypes::CAR->id(),
                    'uuid' => $revivalCarQuoteUUID,
                ])->first();
                if ($revivedLead) {
                    CarQuote::find($this->lead->id)->update(['is_revived' => true]);
                }
            }

            $this->lead->refresh();

            if ($revivalCarQuoteUUID && ! $revivedLead) {

                $carQuote = $this->getQuoteObject(QuoteTypes::CAR->value, $revivalCarQuoteUUID);

                // Allocate the revived car lead using the CarAllocation strategy.
                QuoteTypes::CAR->allocate($carQuote->uuid, false, false, false, false, true);

                $previousAdvisor = null;
                if (! empty($carQuote->previous_advisor_id)) {
                    $previousAdvisor = app(UserService::class)->getUserById($carQuote->previous_advisor_id);
                }

                $emailData = (new CarEmailService(app(SendEmailCustomerService::class)))->buildDttRevivalBirdEmailPayload($carQuote, $previousAdvisor);
                $emailData->workflowType = WorkflowTypeEnum::MOTOR_REVIVAL_OCB;
                // Shifted to Bird Workflow, previous it was using Brevo
                $workflowUrl = ApplicationStorage::where('key_name', ApplicationStorageEnums::MOTOR_REVIVAL_WORKFLOW)->first();

                if (! $workflowUrl || empty($workflowUrl->value)) {
                    LoggerService::warning(self::class.': MOTOR_REVIVAL_WORKFLOW URL missing in CMS (OCB not sent)', [
                        'flow' => self::LOG_FLOW,
                        'parent_lead_uuid' => $this->lead->uuid,
                        'child_quote_uuid' => $revivalCarQuoteUUID,
                    ]);
                    $response = (object) ['status_code' => 0];
                } else {

                    $response = app(BirdService::class)->triggerWebHookRequest($workflowUrl->value, $emailData);
                }

                if (in_array($response->status_code, [201, 200])) {
                    app(CarRevivalService::class)->markRevivalCommsTriggered($revivalCarQuoteUUID);

                    // Get the latest quote batch and assign it to the lead.
                    $quoteBatch = QuoteBatches::latest()->first();

                    $dttRevival = DttRevival::create([
                        'quote_type_id' => QuoteTypes::CAR->id(),
                        'quote_id' => $carQuote->id,
                        'uuid' => $revivalCarQuoteUUID,
                        'revival_quote_batch_id' => $quoteBatch->id,
                        'email_sent' => true,
                    ]);

                    CarQuote::find($this->lead->id)->update(['is_revived' => true]);

                    app(BirdService::class)->createQuoteWorkFlowDetails($carQuote, $response, QuoteFlowType::MOTOR_REVIVAL_OCB->value, (int) QuoteTypes::CAR->id());

                    if (app(BirdService::class)->isFollowupExecuted($revivalCarQuoteUUID, (int) QuoteTypes::CAR->id(), QuoteFlowType::MOTOR_REVIVAL_FOLLOWUP->value)) {
                        LoggerService::info(self::class.': follow-up email already executed', [
                            'flow' => self::LOG_FLOW,
                            'dtt_revival_id' => $dttRevival->id,
                            'parent_lead_uuid' => $this->lead->uuid,
                            'child_quote_uuid' => $revivalCarQuoteUUID,
                        ]);
                    } else {
                        $emailData->workflowType = WorkflowTypeEnum::MOTOR_REVIVAL_FOLLOWUP;

                        CarRevivalFollowUpEmailJob::dispatch($dttRevival->id, $emailData);

                        LoggerService::info(self::class.': revival OCB done — dtt + parent updated + follow-up job queued', [
                            'flow' => self::LOG_FLOW,
                            'dtt_revival_id' => $dttRevival->id,
                            'parent_lead_uuid' => $this->lead->uuid,
                            'child_quote_uuid' => $revivalCarQuoteUUID,
                        ]);
                    }
                } else {
                    LoggerService::warning(self::class.': Bird OCB call did not return success (no dtt / no follow-up)', [
                        'flow' => self::LOG_FLOW,
                        'parent_lead_uuid' => $this->lead->uuid,
                        'child_quote_uuid' => $revivalCarQuoteUUID,
                        'response_code' => $response->status_code,
                    ]);
                }
            }
        } catch (\Exception $exception) {
            LoggerService::warning(self::class.': exception in handle', [
                'flow' => self::LOG_FLOW,
                'parent_lead_id' => $this->lead->id,
                'parent_lead_uuid' => $this->lead->uuid,
                'exception_class' => $exception::class,
                'exception_message' => $exception->getMessage(),
            ]);
        }
    }

    public function failed(Throwable $exception)
    {
        LoggerService::warning(self::class.': job failed after max tries', [
            'flow' => self::LOG_FLOW,
            'parent_lead_id' => $this->lead->id,
            'parent_lead_uuid' => $this->lead->uuid ?? null,
            'exception_class' => $exception::class,
            'exception_message' => $exception->getMessage(),
        ]);
    }

    public function middleware()
    {
        return [(new WithoutOverlapping($this->lead->uuid))->dontRelease()];
    }
}

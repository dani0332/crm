<?php

namespace App\Jobs;

use App\Enums\ApplicationStorageEnums;
use App\Enums\WorkflowTypeEnum;
use App\Models\ApplicationStorage;
use App\Models\CarQuote;
use App\Models\User;
use App\Services\BirdService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NBEventFollowup implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 60;
    public $backoff = 60;
    private $uuid;
    private $templateType;
    /**
     * Create a new job instance.
     */
    public function __construct($uuid, $templateType)
    {
        $this->uuid = $uuid;
        $this->templateType = $templateType;
        //
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {
            $lead = CarQuote::where('uuid', $this->uuid)->first();

            info('Sending NBEventFollowup followups email for lead: '.$lead->uuid.' | Time: '.now());

            $advisor = User::where('id', $lead->advisor_id)->first();
            $emailData = $this->buildNBMotorFollowupEmailData($lead, $advisor);
            $birdMotorEventNB = ApplicationStorage::where('key_name', ApplicationStorageEnums::BIRD_NB_MOTOR_WORKFLOW)->first();
            if ($birdMotorEventNB) {
                $response = app(BirdService::class)->triggerWebHookRequest($birdMotorEventNB->value, $emailData);
                info("NBEventFollowup event triggered for lead  Ref-ID: {$lead->uuid} |Time: ".now());
                info("NBEventFollowup response: {$response->status_code} | Ref-ID: {$lead->uuid} |Time: ".now());

                info("NBEventFollowup lead ref-id: {$lead->uuid}| Quote StatusID: {$lead->quote_status_id} | Time: ".now());
            } else {
                info("NBEventFollowup key not found for lead : Ref-ID: {$lead->uuid} |Time: ".now());
            }

            return $response ?? null;
        } catch (\Throwable $th) {
            $errorMessage = "NBEventFollowup-Error: while sending quote workflow for lead: Ref-ID: {$lead->uuid} | Time: ".now();
            info($errorMessage);
            info("NBEventFollowup-Error: {$th->getMessage()} | Ref-ID: {$lead->uuid} | Time: ".now());
            throw $th;
        }
    }

    public function buildNBMotorFollowupEmailData($lead, $advisor)
    {
        return (object) [
            'quoteUID' => $lead->uuid,
            'customerEmail' => $lead->email,
            'uuid' => $lead->uuid,
            'refID' => $lead->code,
            'customerFullName' => $lead->first_name.' '.$lead->last_name,
            'advisorId' => $advisor->id ?? null,
            'advisorName' => (! empty($advisor->name) ? $advisor->name : ''),
            'advisorEmail' => (! empty($advisor->email) ? $advisor->email : ''),
            'advisorDetails' => $advisor ?? null,
            'quotePlanLink' => config('constants.ECOM_CAR_INSURANCE_QUOTE_URL').$lead->uuid,
            'requestAdvisorLink' => config('constants.ECOM_CAR_INSURANCE_QUOTE_URL').$lead->uuid.'/?assignAdvisor=true',
            'quotePlanApiLink' => config('constants.KEN_API_ENDPOINT').'/get-health-quote-plans-order-priority?'.$lead->uuid.'&lang=en&isModified=true',
            'ApiToken' => config('constants.KEN_API_TOKEN'),
            'basicAuth' => 'Basic '.base64_encode(config('constants.KEN_API_USER').':'.config('constants.KEN_API_PWD')),
            'landLine' => (! empty($advisor->landline_no) ? $advisor->landline_no : ''),
            'mobilePhone' => (! empty($advisor->mobile_no) ? $advisor->mobile_no : ''),
            'whatsAppNumber' => ! empty($advisor->mobile_no) ? formatMobileNo($advisor->mobile_no) : '',
            'mobileNoWithoutSpaces' => (! empty($advisor->mobile_no) ? removeSpaces(formatMobileNoDisplay($advisor->mobile_no)) : ''),
            'workflowType' => WorkflowTypeEnum::NEW_BUSINESS_MOTOR_EVENT_FOLLOWUPS,
            'templateType' => $this->templateType,
            'customerMobile' => (! empty($lead->mobile_no) ? $lead->mobile_no : ''),
            'instantAlfredLink' => config('constants.ECOM_CAR_INSURANCE_QUOTE_URL').$lead->uuid.'/?IA=true',
            'createdAt' => $lead->created_at,
        ];
    }
}

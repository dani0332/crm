<?php

namespace App\Jobs;

use App\Enums\ApplicationStorageEnums;
use App\Enums\AssignmentTypeEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\TiersEnum;
use App\Facades\PostMark;
use App\Models\ApplicationStorage;
use App\Models\InsuranceProvider;
use App\Models\Payment;
use App\Models\PersonalQuote;
use App\Models\Tier;
use App\Models\User;
use App\Services\BikeEmailService;
use App\Services\BikeQuoteService;
use App\Services\HttpRequestService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendOCBIntroEmailForBikeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public $tries = 3;
    public $timeout = 60;
    public $backoff = 300;
    private $quoteUuid;
    private $previousAdvisor;

    /**
     * Create a new job instance.
     */
    public function __construct($quoteUuid, $previousAdvisor)
    {
        $this->quoteUuid = $quoteUuid;
        $this->previousAdvisor = $previousAdvisor;
    }

    /**
     * Execute the job.
     */
    public function handle(HttpRequestService $httpService, BikeEmailService $bikeEmailService, BikeQuoteService $bikeQuoteService): void
    {
        try {
            $lead = PersonalQuote::where('uuid', $this->quoteUuid)->with('bikeQuote')->first();

            if (! $lead) {
                info('SendOCBIntroEmailJobForBike - Lead not found for uuid: '.$this->quoteUuid);

                return;
            } else {
                info('SendOCBIntroEmailJobForBike - Lead found for uuid: '.$this->quoteUuid);

                if (($lead->assignment_type = AssignmentTypeEnum::MANUAL_ASSIGNED || $lead->assignment_type == AssignmentTypeEnum::MANUAL_REASSIGNED) && $lead->source == LeadSourceEnum::DUBAI_NOW) {
                    $this->sendDubaiNowEmail($lead);
                } else {

                    $tierR = Tier::where('name', TiersEnum::TIER_R)->where('is_active', 1)->first();
                    // Retrieve plans with available ratings for the given lead
                    $plans = $httpService->getPlans($lead->uuid, false, false, false, 'Bike');
                    $responseCode = $bikeEmailService->sendBikeOCBIntroEmail($plans, $lead, $tierR, $this->previousAdvisor, $bikeQuoteService);
                    if (in_array($responseCode, [200, 201])) {
                        info('SendOCBIntroEmailJobForBike - OCB INTRO Email Sent: '.$responseCode.' Customer Email Address: '.$lead->email.' Quote UuId: '.$this->quoteUuid);
                    } else {
                        Log::error('SendOCBIntroEmailJobForBike - OCB INTRO Email Not Sent: '.$responseCode.' Customer EmailAddress:'.$lead->email);
                    }
                }
            }
        } catch (Exception $e) {
            info('SendOCBIntroEmailJobForBike - Error: '.$e->getMessage().' with stack trace: '.$e->getTraceAsString());
        }
    }

    public function sendDubaiNowEmail($lead)
    {
        // Retrieve advisor and payment information
        $advisor = User::findOrFail($lead->advisor_id);
        $payment = Payment::where('code', $lead->code)->latest()->first();

        // Initialize variables
        $insuranceProviderName = '';
        $amountPaid = $lead->premium;

        // Check if payment and insurance provider exist
        if ($payment) {
            $insuranceProvider = InsuranceProvider::find($payment->insurance_provider_id);
            $insuranceProviderName = $insuranceProvider ? $insuranceProvider->text : '';
        }

        $dubaiNowCcEmailGroup = ApplicationStorage::where('key_name', ApplicationStorageEnums::DUBAI_NOW_CC_GROUP)->first();

        // Prepare email body
        $body = json_encode([
            'From' => 'no-reply@alert.insurancemarket.email',
            'To' => $advisor->email,
            'Cc' => $dubaiNowCcEmailGroup ? $dubaiNowCcEmailGroup->value : '',
            'TemplateAlias' => 'payment-internal-notification-car-dubai-now',
            'TemplateModel' => [
                'params' => [
                    'customerName' => $lead->first_name.' '.$lead->last_name,
                    'referenceCode' => $lead->code,
                    'providerName' => $insuranceProviderName,
                    'totalPremium' => $amountPaid,
                ],
                'subject' => (config('constants.APP_ENV') != 'production' ? config('constants.APP_ENV').' - ' : '').'DubaiNow || '.$lead->first_name.' has paid for '.$lead->code,
            ],
            'MessageStream' => config('constants.MA_POSTMARK_STREAM'),
        ], JSON_UNESCAPED_SLASHES);

        // Attempt to send email and log response or errors
        try {
            $response = PostMark::sendEmail($body);
            info('SendDubaiNowInternalEmail - Response: '.json_encode($response));
        } catch (Exception $e) {
            Log::error('SendDubaiNowInternalEmail - ERROR:'.$e->getMessage());
        }
    }
}

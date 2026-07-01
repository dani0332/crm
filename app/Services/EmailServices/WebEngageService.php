<?php

namespace App\Services\EmailServices;

use App\Models\QuoteFlowDetails;
use App\Services\Logger\LoggerService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class WebEngageService
{
    private string $baseUrl;
    private string $licenseCode;
    private string $apiToken;

    public function __construct()
    {
        $this->baseUrl = config('constants.WEBENGAGE_BASE_URL', 'https://api.ksa.webengage.com/v1/accounts');
        $this->licenseCode = config('constants.WEBENGAGE_LICENSE_CODE', '');
        $this->apiToken = config('constants.WEBENGAGE_API_TOKEN', '');
    }

    /**
     * Create or update a user in WebEngage via the Users API.
     *
     * @param  string  $userId  Unique identifier for the user (e.g. email or CRM ID)
     * @param  array<string, mixed>  $attributes  User attributes (firstName, lastName, email, phone, etc.)
     */
    public function createUser(string $userId, array $attributes = []): object
    {
        $url = "{$this->baseUrl}/{$this->licenseCode}/users";
        $payload = array_merge(['userId' => $userId], $attributes);

        $logContext = ['userId' => $userId, 'url' => $url];

        try {
            LoggerService::info('WebEngage - createUser initiated', $logContext);

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer '.$this->apiToken,
            ])->post($url, $payload);

            LoggerService::info("WebEngage - createUser response: Status {$response->status()}", $logContext);

            return (object) [
                'status_code' => $response->status(),
                'body' => $response->json(),
                'headers' => $response->headers(),
            ];
        } catch (\Exception $e) {
            LoggerService::error('WebEngage - createUser failed', array_merge($logContext, [
                'error' => $e->getMessage(),
            ]));
            throw $e;
        }
    }

    /**
     * Send a transactional event to WebEngage, which triggers email/notification workflows.
     *
     * @param  string  $userId  The customer's email or unique identifier
     * @param  string  $eventName  The WebEngage event name
     * @param  array<string, mixed>  $eventData  Key-value pairs passed to the event template
     */
    public function sendEvent(string $eventName, array $eventData = []): object
    {
        if (empty($eventData['customerId'])) {
            LoggerService::warning("WebEngage - customer id is empty  sendEvent: {$eventName}");

            return (object) [
                'status_code' => 404,
                'body' => ['message' => 'customer id is empty'],
                'headers' => [],
            ];
        }
        $this->createUser($eventData['customerId'], [
            'firstName' => $eventData['firstName'],
            'lastName' => $eventData['lastName'],
            'email' => $eventData['customerEmail'],
            'phone' => $eventData['customerMobile'],
            'whatsappOptIn' => true,
        ]);
        $url = "{$this->baseUrl}/{$this->licenseCode}/events";
        $payload = (object) [
            'userId' => $eventData['customerId'],
            'eventName' => $eventName,
            'eventData' => (object) $eventData,
        ];

        $logContext = ['userId' => $eventData['customerId'], 'event' => $eventName, 'url' => $url];

        try {
            LoggerService::info('WebEngage - sendEvent initiated', $logContext);

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer '.$this->apiToken,
            ])->post($url, $payload);
            $refId = $eventData['quoteUID'];

            LoggerService::info("WebEngage - sendEvent: {$eventName} | Status: {$response->status()}", $logContext);
            LoggerService::info("WebEngage RefID: {$refId} | Event: {$eventName} | Payload: ".json_encode($payload), $logContext);
            LoggerService::info("WebEngage RefID: {$refId} | Event: {$eventName} | Response: ".json_encode($response->json()), $logContext);

            return (object) [
                'status_code' => $response->status(),
                'body' => $response->json(),
                'headers' => $response->headers(),
            ];
        } catch (\Exception $e) {
            LoggerService::warning('WebEngage - sendEvent failed', array_merge($logContext, [
                'error' => $e->getMessage(),
            ]));

            return (object) [
                'status_code' => 500,
                'body' => [],
                'headers' => [],
            ];
        }
    }

    /**
     * Send a transactional email directly via WebEngage's transactional email API.
     *
     * @param  array<int, string>  $to  Recipient email addresses
     * @param  string  $templateId  The WebEngage email template ID
     * @param  array<string, mixed>  $templateData  Variables to inject into the template
     * @param  array<int, string>  $cc  Optional CC email addresses
     * @param  array<int, string>  $bcc  Optional BCC email addresses
     */
    public function sendTransactionalEmail(
        array $to,
        string $templateId,
        array $templateData = [],
        array $cc = [],
        array $bcc = []
    ): object {
        $url = "{$this->baseUrl}/{$this->licenseCode}/transactional-data";
        $payload = [
            'to' => $to,
            'templateId' => $templateId,
            'templateData' => $templateData,
        ];

        if (! empty($cc)) {
            $payload['cc'] = $cc;
        }

        if (! empty($bcc)) {
            $payload['bcc'] = $bcc;
        }

        $logContext = ['to' => $to, 'templateId' => $templateId, 'url' => $url];

        try {
            LoggerService::info('WebEngage - sendTransactionalEmail initiated', $logContext);

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer '.$this->apiToken,
            ])->post($url, $payload);

            LoggerService::info("WebEngage - sendTransactionalEmail response: Status {$response->status()}", $logContext);

            return (object) [
                'status_code' => $response->status(),
                'body' => $response->json(),
                'headers' => $response->headers(),
            ];
        } catch (\Exception $e) {
            LoggerService::error('WebEngage - sendTransactionalEmail failed', array_merge($logContext, [
                'error' => $e->getMessage(),
            ]));
            throw $e;
        }
    }

    public function buildEmailEventData($data, $advisor)
    {
        return (object) [
            'quoteUID' => $data->uuid,
            'customerEmail' => $data->email,
            'refID' => $data->code,
            'customerFullName' => $data->first_name.' '.$data->last_name,
            'customerMobile' => (! empty($lead->mobile_no) ? formatMobileNo($lead->mobile_no) : ''),
            'advisorId' => $advisor->id ?? null,
            'advisorName' => (! empty($advisor->name) ? $advisor->name : ''),
            'advisorEmail' => (! empty($advisor->email) ? $advisor->email : ''),
            'advisorLandLine' => (! empty($advisor->landline_no) ? $advisor->landline_no : ''),
            'advisorMobilePhone' => (! empty($advisor->mobile_no) ? $advisor->mobile_no : ''),
            'advisorWhatsAppNumber' => ! empty($advisor->mobile_no) ? formatMobileNo($advisor->mobile_no) : '',
            'advisorMobileNoWithoutSpaces' => (! empty($advisor->mobile_no) ? removeSpaces(formatMobileNoDisplay($advisor->mobile_no)) : ''),
        ];
    }

    public function createQuoteWorkFlowDetails($quoteUUID, $flowType = null, $quoteTypeId = null)
    {
        try {

            QuoteFlowDetails::create([
                'quote_uuid' => $quoteUUID,
                'quote_type_id' => $quoteTypeId,
                'flow_type' => $flowType,
                'flow_id' => now(),
                'started_at' => now(),
            ]);
            LoggerService::info('- createQuoteWorkFlowDetails  run id created for quote: '.$quoteUUID);
        } catch (\Throwable $th) {
            LoggerService::error(" - createQuoteWorkFlowDetails-Error: {$th->getMessage()} ");
        }
    }

    public function createQuoteWhatsAppFlowDetails($lead, $flowType = null, $quoteTypeId = null)
    {
        try {
            DB::table('ocb_whatsapp_msg_logs')->insert([
                'uuid' => $lead->uuid,
                'quote_type_id' => $quoteTypeId,
                'mobile_no' => formatMobileNoWithoutPlus($lead->mobile_no),
                'log_message' => Str::camel($flowType),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $th) {
            LoggerService::error(" - createQuoteWhatsAppFlowDetails-Error: {$th->getMessage()}  Line: {$th->getLine()}  Trace: {$th->getTraceAsString()} ");
        }
    }

    public function isFollowupExecuted($quoteUuid, $quoteTypeId, $flowType)
    {
        return QuoteFlowDetails::where('quote_uuid', $quoteUuid)
            ->where('quote_type_id', $quoteTypeId)
            ->where('flow_type', $flowType)
            ->exists();
    }
}

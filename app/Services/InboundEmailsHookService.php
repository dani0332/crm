<?php

namespace App\Services;

use App\Enums\ApplicationStorageEnums;
use App\Enums\LeadSourceEnum;
use App\Enums\ProcessStatusCode;
use App\Enums\QuoteTypes;
use App\Enums\WorkflowTypeEnum;
use App\Jobs\EmailStatusEventJob;
use App\Models\ApplicationStorage;
use App\Models\CarQuote;
use App\Models\DttRevival;
use App\Models\EmailStatus;
use App\Models\HealthQuote;
use App\Models\HomeQuote;
use App\Models\TravelQuote;
use App\Models\User;
use App\Services\Traits\Inboundable;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class InboundEmailsHookService extends BaseService
{
    use Inboundable;

    private function verifyAuthorization()
    {
        $authUser = config('constants.INBOUND_WEBHOOK_BASIC_AUTH_USER_NAME');
        $authPass = config('constants.INBOUND_WEBHOOK_BASIC_AUTH_PASSWORD');

        if (request('basicAuthUsername') === $authUser && request('basicAuthPassword') === $authPass) {
            info(self::class.' - verifyAuthorization: Authorized Access');

            return true;
        }

        return false;
    }

    public function process()
    {
        try {
            info(self::class.' - process: Webhook Received - Verifying Auth...');

            if (! $this->verifyAuthorization()) {
                info(self::class.' - process: Unauthorized Access');

                return apiResponse([], Response::HTTP_UNAUTHORIZED, 'Unauthorized Access');
            }

            $inbound = new \Postmark\Inbound(file_get_contents('php://input'));

            $subject = $inbound->Subject();
            info(self::class." - process: Webhook Received with Subject: {$subject}");

            $data = getQuoteUsingSubject($subject);

            return $this->resolveLead($subject, $data);
        } catch (Exception $e) {
            info(self::class.' - process: Exception occurred', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            return apiResponse([], Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    private function resolveLead($subject, $data)
    {
        if ($data) {
            [$quoteType, $uuid] = $data;

            $lead = $quoteType->model()::where('uuid', $uuid)->first();

            if ($lead) {
                return match ($quoteType) {
                    QuoteTypes::CAR => $this->handleCar($lead),
                    QuoteTypes::TRAVEL => $this->handleTravel($lead),
                    QuoteTypes::HEALTH => $this->handleHealth($lead),
                    default => apiResponse([], Response::HTTP_UNPROCESSABLE_ENTITY, "Unhandled quote type: {$quoteType->value}")
                };
            }

            info(self::class." - resolveLead: Lead not found for uuid: {$uuid}");

            return apiResponse([], Response::HTTP_OK, "Lead not found for uuid: {$uuid}");
        }

        info(self::class." - resolveLead: uuid not found in subject: {$subject}");

        return apiResponse([], Response::HTTP_OK, "UUID & Quote Type could not be extracted from subject: {$subject}");
    }

    private function handleCar(CarQuote $lead)
    {
        if ($lead->source == LeadSourceEnum::REVIVAL) {
            info(self::class." - handleCar: Going to update Car Quote for uuid {$lead->uuid}");
            $lead->update(['source' => LeadSourceEnum::REVIVAL_REPLIED]);
            DttRevival::where('uuid', $lead->uuid)->update(['reply_received' => 1]);
            info(self::class." - handleCar: Car Quote Source updated for Revival for uuid {$lead->uuid}");

            $this->handleCarAllocation($lead);

            return apiResponse([], Response::HTTP_OK, 'Car Source Updated Successfully!');
        } else {
            try {
                info(self::class." - handleCar: Going to handle Car Quote for uuid {$lead->uuid}");

                $this->handleSicReplyToILA($lead);

                return apiResponse([], Response::HTTP_OK, 'Car Handled for SIC to ILA Successfully!');
            } catch (\Exception $e) {
                info(self::class." - handleCar: Error occurred in SIC Reply to ILA for uuid {$lead->uuid}");

                return apiResponse([], Response::HTTP_INTERNAL_SERVER_ERROR, 'Something went wrong!');
            }
        }
    }

    private function handleTravel(TravelQuote $lead)
    {
        info(self::class." - handleTravel: Going to Assign Advisor to uuid: {$lead->uuid}");

        if ($lead->advisor_id) {
            info(self::class." - handleTravel: Lead already has an advisor assigned: {$lead->uuid} - Advisor ID: {$lead->advisor_id}");

            return apiResponse([], Response::HTTP_OK, 'Lead already has an advisor assigned!');
        }

        info(self::class." - handleTravel: Allocation Process Executing for lead: {$lead->uuid}");

        $response = QuoteTypes::TRAVEL->allocate($lead->uuid);
        $assignedAdvisorId = $response['advisorId'] ?? '';
        info(self::class." - handleTravel: Allocation Executed for lead: {$lead->uuid} and assignedAdvisorId: {$assignedAdvisorId}");

        return apiResponse([], Response::HTTP_OK, 'Lead Assigned to Advisor Successfully!');
    }

    private function handleHealth(HealthQuote $lead)
    {
        if ($lead->source == LeadSourceEnum::REVIVAL) {
            Log::info(self::class." - handleHealth: Going to Assign Advisor to uuid: {$lead->uuid}");
            if ($lead->advisor_id) {
                Log::info(self::class." - handleHealth: Lead already has an advisor assigned: {$lead->uuid}");

                return apiResponse([], Response::HTTP_OK, 'Lead already has an advisor assigned!');
            }

            $lead->update(['source' => LeadSourceEnum::REVIVAL_REPLIED]);
            Log::info(self::class." - handleHealth: Car Quote Source updated for Revival for uuid {$lead->uuid} to REVIVAL_REPLIED");
            DttRevival::where('uuid', $lead->uuid)->update(['reply_received' => 1]);
            Log::info(self::class." - handleHealth: Health Quote Source updated for Revival for uuid {$lead->uuid}");

            info(self::class." - handleHealth: Allocation Process Executing for lead: {$lead->uuid}");
            $response = QuoteTypes::HEALTH->allocate($lead->uuid);
            $assignedAdvisorId = $response['advisorId'] ?? '';
            info(self::class." - handleHealth: AllocationStrategy Executed for lead: {$lead->uuid} and assignedAdvisorId: {$assignedAdvisorId}");

            return apiResponse([], Response::HTTP_OK, 'Lead Assigned to Advisor Successfully!');
        }
    }
    public function handleBirdWebhook($request)
    {
        try {
            $payload = collect($request->all());
            if ($payload->isEmpty()) {
                info('Bird Webhook Payload data is empty!');

                return apiResponse([], Response::HTTP_BAD_REQUEST, 'Webhook Payload is empty!');
            }
            // Extract the event type from the payload
            $type = $payload['payload']['type'] ?? null;

            if (! empty($type)) {
                info('Bird Webhook  Payload: '.json_encode($payload));
                // Extract necessary fields from the payload
                $messageId = $payload['payload']['messageId'] ?? null;
                $status = $type;
                $identifierValue = collect($payload['payload']['receiver']['contacts'])->first()['identifierValue'] ?? null;
                if ($messageId && $status) {
                    // Update the message interaction
                    $result = collect($payload['payload'])->only(['messageId', 'type'])
                        ->merge(['identifierValue' => $identifierValue])
                        ->filter();

                    $this->birdMessageInteractionsUpdate($result, $identifierValue);
                    // Handle specific status types if necessary
                    if (in_array($type, [ProcessStatusCode::UNSUBSCRIBED])) {
                        $this->sendUnsubscribeEmailNotification($messageId);
                    }
                } else {
                    info('Required fields missing in the payload.');

                    return apiResponse([], Response::HTTP_BAD_REQUEST, 'Invalid payload data.');
                }
            } else {
                // Handle cases where no type is provided in the payload
                info('Type not found in the payload.');

                return apiResponse([], Response::HTTP_BAD_REQUEST, 'Invalid webhook data.');
            }

            return apiResponse([], Response::HTTP_OK, 'Webhook Received Successfully!');
        } catch (\Throwable $th) {
            info("Bird Webhook Error: {$th->getMessage()} on line: {$th->getLine()} in file: {$th->getFile()} | ".PHP_EOL.$th->getTraceAsString());
            throw $th;
        }
    }
    public function birdMessageInteractionsUpdate($result, $identifierValue = null)
    {
        $result = (object) $result->all();
        info('Webhook birdMessageInteractionsUpdate Payload: '.json_encode($result));
        $messageId = $result->messageId ?? null;
        $status = $result->type ?? null;
        $emailSubject = $result->reason ?? null;

        if ($messageId && $status) {
            $emailData = (object) [
                'message_id' => $messageId,
                'status' => $status,
                'subject' => $emailSubject,
                'customer_email' => $identifierValue,
            ];
            // Dispatch the EmailStatusEventJob to handle the email status update
            info('EmailStatusEventJob sending job dispatch | Time: '.now());
            EmailStatusEventJob::dispatch($emailData)->delay(Carbon::now()->addSeconds(120));
            info('EmailStatusEventJob dispatched successfully!');
        } else {
            $msg = 'EmailData not found for msg_id: '.$messageId;
            info($msg);
        }
    }
    public function sendUnsubscribeEmailNotification($messageId)
    {
        $emailStatusData = EmailStatus::where('msg_id', $messageId)->first();
        if ($emailStatusData) {
            switch ($emailStatusData->quote_type_id) {
                case QuoteTypes::CAR->id():
                    $quote = CarQuote::where('id', $emailStatusData->quote_id)->first();
                    break;
                case QuoteTypes::HEALTH->id():
                    $quote = HealthQuote::where('id', $emailStatusData->quote_id)->first();
                    break;
                case QuoteTypes::HOME->id():
                    $quote = HomeQuote::where('id', $emailStatusData->quote_id)->first();
                    break;
                default:
                    $quote = null;
                    break;
            }
            if (! empty($quote)) {
                $advisor = User::where('id', $quote->advisor_id)->first();
                if (! empty($advisor)) {
                    $emailData = [
                        'advisorEmail' => $advisor->email,
                        'customerEmail' => $quote->email,
                        'quoteUID' => $quote->uuid,
                        'refID' => $quote->code,
                        'receivedDate' => now(),
                        'workflowType' => WorkflowTypeEnum::UNSUBSCRIBE_REQUESTED_NOTIFICATIION,
                    ];
                    $birdMotorEventNB = ApplicationStorage::where('key_name', ApplicationStorageEnums::BIRD_NB_MOTOR_WORKFLOW)->first();
                    app(BirdService::class)->triggerWebHookRequest($birdMotorEventNB->value, $emailData);
                } else {
                    info("Advisor not found for email: {$quote->uuid} | Time: ".now());
                }
            }
        } else {
            $msg = 'EmailStatus not found for msg_id: '.$messageId;
            info($msg);
        }
    }
}

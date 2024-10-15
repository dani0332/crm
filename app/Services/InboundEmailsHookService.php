<?php

namespace App\Services;

use App\Enums\LeadSourceEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Factories\AllocationFactory;
use App\Jobs\EmailStatusEventJob;
use App\Models\CarQuote;
use App\Models\DttRevival;
use App\Models\TravelQuote;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Response;
use App\Models\EmailStatus;
use App\Enums\ProcessStatusCode;
use App\Models\HealthQuote;
use App\Models\User;
use App\Models\ApplicationStorage;
use App\Enums\ApplicationStorageEnums;
use App\Enums\WorkflowTypeEnum;

class InboundEmailsHookService extends BaseService
{
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
                    default => apiResponse([], Response::HTTP_UNPROCESSABLE_ENTITY, "Unhandled quote type: {$quoteType->value}")
                };
            }

            info(self::class." - resolveLead: Lead not found for uuid: {$uuid}");

            return apiResponse([], Response::HTTP_NOT_FOUND, "Lead not found for uuid: {$uuid}");
        }

        info(self::class." - resolveLead: uuid not found in subject: {$subject}");

        return apiResponse([], Response::HTTP_NOT_FOUND, "UUID & Quote Type could not be extracted from subject: {$subject}");
    }

    private function handleCar(CarQuote $lead)
    {
        if ($lead->source == LeadSourceEnum::REVIVAL) {
            info(self::class." - handleCar: Going to update Car Quote for uuid {$lead->uuid}");
            $lead->update(['source' => LeadSourceEnum::REVIVAL_REPLIED]);
            DttRevival::where('uuid', $lead->uuid)->update(['reply_received' => 1]);
            info(self::class." - handleCar: Car Quote Source updated for Revival for uuid {$lead->uuid}");

            return apiResponse([], Response::HTTP_OK, 'Car Source Updated Successfully!');
        } else {
            try {
                info(self::class." - handleCar: Going to handle Car Quote for uuid {$lead->uuid}");
                (new ApiService)->sicReplyToILA($lead);

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
            info(self::class." - handleTravel: Lead already has an advisor assigned: {$lead->uuid}");

            return apiResponse([], Response::HTTP_OK, 'Lead already has an advisor assigned!');
        }

        info(self::class." - handleTravel: AllocationFactory Strategy Executing for lead: {$lead->uuid}");
        $allocationStrategy = AllocationFactory::createStrategy(QuoteTypeId::Travel, $lead->uuid);
        $assignedAdvisorId = $allocationStrategy->executeSteps();
        info(self::class." - handleTravel: AllocationStrategy Executed for lead: {$lead->uuid} and assignedAdvisorId: {$assignedAdvisorId}");

        return apiResponse([], Response::HTTP_OK, 'Lead Assigned to Advisor Successfully!');
    }

    public function handleBirdWebhook($request)
    {
        try {
            info('Bird Webhook Received Successfully!');
            $payload = collect($request);
            if (empty($payload)) {
                info('Webhook Payload data is empty!');

                return apiResponse([], Response::HTTP_BAD_REQUEST, 'Webhook Payload is empty!');
            }
            info('Webhook Payload: '.json_encode($payload));
            $type = (isset($payload['results'])) ? collect($payload['results'])->first()['type'] : null;
            if (! empty($type)) {
                // Extract and filter the required fields
                $payload = collect($payload['results'])->first();
                if (empty($payload)) {
                    info('Webhook Payload result is empty!');

                    return apiResponse([], Response::HTTP_BAD_REQUEST, 'Webhook Payload is empty!');
                }

                info("Webhook Payload type: ".$type);
                $result = collect($payload)->only(['messageId', 'type'])
                    ->filter();
                $this->birdMessageInteractionsUpdate($result);

                if(in_array($type,[ ProcessStatusCode::UNSUBSCRIBED, ProcessStatusCode::UNSUBSCRIBE_REQUESTED])){
                    $this->sendUnsubscribeEmailNotification($result['messageId']);
                }

            } else {
                $identifierValue = isset($payload['receiver']['contacts']) ? collect($payload['receiver']['contacts'])->first()['identifierValue'] : null;
                // Extract and filter therequired fields
                $result = $payload->only(['id', 'status', 'reason'])
                    ->merge(['identifierValue' => $identifierValue])
                    ->filter();
                $this->birdMessageStatusUpdate($result, $identifierValue);
            }

            return apiResponse([], Response::HTTP_OK, 'Webhook Received Successfully!');
        } catch (\Throwable $th) {
            info("Bird Webhook Error: {$th->getMessage()} on line: {$th->getLine()} in file: {$th->getFile()} | ".PHP_EOL.$th->getTraceAsString());
            throw $th;
        }
    }

    public function birdMessageStatusUpdate($result, $identifierValue = null)
    {
        $result = (object) $result->all();
        info('Webhook birdMessageStatusUpdate Payload: '.json_encode($result));
        $messageId = $result->id ?? null;
        $status = $result->status ?? null;
        $emailSubject = $result->reason ?? null;
        if ($messageId && $status) {
            $emailData = (object) ['message_id' => $messageId,
                'status' => $status,
                'subject' => $emailSubject,
                'customer_email' => $identifierValue];
            // Dispatch the EmailStatusEventJob to handle the email status update
            info('EmailStatusEventJob sending job dispatch | Time: '.now());
            EmailStatusEventJob::dispatch($emailData)->delay(Carbon::now()->addSeconds(90));
            info('EmailStatusEventJob dispatched successfully!');
        } else {
            $msg = 'EmailData not found for msg_id: '.$messageId;
            info($msg);
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
            $emailData = (object) ['message_id' => $messageId,
                'status' => $status,
                'subject' => $emailSubject,
                'customer_email' => $identifierValue];
            // Dispatch the EmailStatusEventJob to handle the email status update
            info('EmailStatusEventJob sending job dispatch | Time: '.now());
            EmailStatusEventJob::dispatch($emailData)->delay(Carbon::now()->addSeconds(90));
            info('EmailStatusEventJob dispatched successfully!');
        } else {
            $msg = 'EmailData not found for msg_id: '.$messageId;
            info($msg);
        }
    }

    public function sendUnsubscribeEmailNotification($messageId){
        $emailStatusData = EmailStatus::where('msg_id', $messageId)->first();
        if($emailStatusData){
            switch ($emailStatusData->quote_type_id) {
                case QuoteTypes::CAR->id():
                    $quote = CarQuote::where('id', $emailStatusData->quote_id)->first();
                break;
                case QuoteTypes::HEALTH->id():
                    $quote = HealthQuote::where('id', $emailStatusData->quote_id)->first();
                break;
                default:
                    $quote = null;
                break;
            }
            if (! empty($quote)) {
                $advisor = User::where('id', $quote->advisor_id)->first();
                if(!empty($advisor)){
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
                }
                else
                  info("Advisor not found for email: {$quote->uuid} | Time: ".now());
            }
        }
        else {
            $msg = 'EmailStatus not found for msg_id: '.$messageId;
            info($msg);
        }

    }

}

<?php

namespace App\Services;

use App\Jobs\EmailStatusEventJob;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Response;

class OutboundEmailsHookService
{

    public function handleOutboundEmailsHook($request)
    {
        try {
            info(self::class.' - handleOutboundEmailsHook Received webhook request: ' . json_encode($request->all()) . ' | Time: ' . now());
            $payload =(object) collect($request['payload']);
            if ($payload->isEmpty()) {
                info(self::class.' - handleOutboundEmailsHook Bird Webhook Payload data is empty!' . ' | Time: ' . now());

                return apiResponse([], Response::HTTP_BAD_REQUEST, ' Webhook Payload is empty!');
            }

            if (!empty($payload['id'])) {
                // Extract necessary fields from the payload
                $messageId = $payload['id'] ?? null;
                $status = $payload['status'];
                $reason = $payload['reason'] ?? null;
                $identifierValue = collect($payload['receiver']['contacts'])->first()['identifierValue'] ?? null;

                info('Extracted fields - Message ID: ' . $messageId . ', Status: ' . $status . ', Identifier Value: ' . $identifierValue);

                if ($messageId && $status) {
                    // Update the message interaction
                    $result = collect([
                        'messageId' => $messageId,
                        'status' => $status,
                        'reason' => $reason,
                        'identifierValue' => $identifierValue
                    ])->filter();

                    info('Result after merging and filtering: ' . json_encode($result));
                    $this->birdMessageStatusUpdate($result, $identifierValue);
                } else {
                    info('Required fields missing in the payload.');
                    return apiResponse([], Response::HTTP_BAD_REQUEST, 'Invalid payload data.');
                }
            } else {
                info(self::class . ' - Status not found in the payload.');
                return apiResponse([], Response::HTTP_BAD_REQUEST, 'Invalid webhook data.');
            }



            return apiResponse([], Response::HTTP_OK, 'Webhook Received Successfully!');
        } catch (\Throwable $th) {
            info("Bird Webhook Error: {$th->getMessage()} on line: {$th->getLine()} in file: {$th->getFile()} | ".PHP_EOL.$th->getTraceAsString());
            throw $th;

            return apiResponse([], Response::HTTP_INTERNAL_SERVER_ERROR, ' An error occurred while processing the webhook.');
        }
    }

    public function birdMessageStatusUpdate($result, $identifierValue = null)
    {
        info('birdMessageStatusUpdate called with result: ' . json_encode($result) . ' and identifierValue: ' . $identifierValue);
        $result = (object) $result->all();
        info(self::class.' - Webhook birdMessageStatusUpdate Payload: '.json_encode($result));
        $messageId = $result->messageId ?? null;
        $status = $result->status ?? null; // Changed from $result->type to $result->status
        $emailSubject = $result->reason ?? null;

        if ($messageId && $status) {
            $emailData = (object) [
                'message_id' => $messageId,
                'status' => $status,
                'subject' => $emailSubject,
                'customer_email' => $identifierValue,
            ];
            // Dispatch the EmailStatusEventJob to handle the email status update
            info(self::class.' - EmailStatusEventJob sending job dispatch | Time: '.now());
            EmailStatusEventJob::dispatch($emailData)->delay(Carbon::now()->addSeconds(60));
            info(self::class.' - EmailStatusEventJob dispatched successfully!');
        } else {
            info(self::class.' - EmailData not found for msg_id: ' . $messageId);
        }

    }
}

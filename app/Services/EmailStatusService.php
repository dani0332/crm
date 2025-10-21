<?php

namespace App\Services;

use App\Enums\ProcessStatusCode;
use App\Enums\QuoteTypeId;
use App\Models\CarQuote;
use App\Models\EmailStatus;
use App\Models\HealthQuote;
use App\Models\PersonalQuote;
use App\Models\TravelQuote;
use App\Services\Logger\LoggerService;
use Illuminate\Support\Facades\Cache;

class EmailStatusService extends BaseService
{
    public function getEmailStatus($quoteTypeId, $quoteId)
    {
        return Cache::remember("email_statuses_{$quoteTypeId}_{$quoteId}", now()->endOfDay(), function () use ($quoteTypeId, $quoteId) {
            return EmailStatus::where(['quote_type_id' => $quoteTypeId, 'quote_id' => $quoteId])
                ->orderBy('updated_at', 'desc')
                ->get();
        });
    }

    public function addEmailStatus($emailData, $messageId, $emailSubject, $status = ProcessStatusCode::IN_PROGRESS, $reason = null)
    {
        $newEmailStatus = new EmailStatus;
        $newEmailStatus->quote_type_id = $emailData->quoteTypeId;
        $newEmailStatus->quote_id = $emailData->quoteId;
        $newEmailStatus->email_address = $emailData->customerEmail;
        $newEmailStatus->msg_id = $messageId;
        $newEmailStatus->template_id = $emailData->templateId ?? $emailData->emailTemplateId;
        $newEmailStatus->customer_id = $emailData->customerId;
        $newEmailStatus->email_status = $status ?? ProcessStatusCode::IN_PROGRESS;
        $newEmailStatus->email_subject = $emailSubject;
        $newEmailStatus->reason = $reason;
        $newEmailStatus->save();

        Cache::forget("email_statuses_{$newEmailStatus->quote_type_id}_{$newEmailStatus->quote_id}");

        return $newEmailStatus->id;
    }

    public function addBirdEmailStatus($request)
    {
        switch (request('quoteTypeId')) {
            case QuoteTypeId::Car:
                $quote = CarQuote::where('uuid', $request->uuid)->first();
                break;
            case QuoteTypeId::Health:
                $quote = HealthQuote::where('uuid', $request->uuid)->first();
                break;
            case QuoteTypeId::Home:
            case QuoteTypeId::Savings:
            case QuoteTypeId::Life:
                $quote = PersonalQuote::where('uuid', $request->uuid)->first();
                break;
            case QuoteTypeId::Travel:
                $quote = TravelQuote::where('uuid', $request->uuid)->first();
                break;

            default:
                $quote = null;
                break;
        }
        if (! $quote) {
            LoggerService::warning("Lead not found for uuid: {$request->uuid} time: ".now(), [
                'uuid' => $request->uuid,
                'quoteTypeId' => request('quoteTypeId'),
            ]);

            return (object) ['message' => 'lead not found', 'status' => false];
        }
        if (! EmailStatus::where('email_status', ProcessStatusCode::SENT)
            ->where('msg_id', $request->message_id)
            ->where('quote_id', $quote->id)->exists()) {
            $request->quoteId = $quote->id;
            $request->customerEmail = $request->customer_email;
            $this->addEmailStatus($request, $request->message_id, $request->subject, ProcessStatusCode::SENT);

            return (object) ['message' => 'Email event logged successfully', 'status' => true];
        } else {

            return (object) ['message' => 'Email event already logged', 'status' => true];
        }
    }

    public function updateEmailStatus($emailData, $status)
    {
        $emailStatus = EmailStatus::where('id', $emailData->id)->first();
        if (empty($emailStatus)) {
            info(self::class.' - updateEmailStatus not found for msg_id: '.$emailData->message_id.' | Time: '.now());

            return;
        }
        $emailStatus->email_status = $status;
        $emailStatus->save();
        info('EmailStatusService - EmailStatus updated for msg_id: '.$emailData->message_id.' email_status: '.$emailStatus->email_status.' | Time:'.now());
    }

}

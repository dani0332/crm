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

    /**
     * Update customer replied status in email_status table
     */
    public function updateCustomerRepliedStatus(string $quoteUuid, ?string $messageId, int $quoteTypeId, ?string $emailSubject = null): object
    {
        try {
            // Get the quote based on quote type
            $quote = $this->getQuoteByUuidAndType($quoteUuid, $quoteTypeId);

            if (! $quote) {
                LoggerService::warning(self::class.' - updateCustomerRepliedStatus - Quote not found', [
                    'uuid' => $quoteUuid,
                    'quote_type_id' => $quoteTypeId,
                    'time' => now(),
                ]);

                return (object) [
                    'success' => false,
                    'message' => 'Quote not found',
                ];
            }

            // Find the email status record
            $query = EmailStatus::where('quote_id', $quote->id)
                ->where('quote_type_id', $quoteTypeId);

            // Add message_id condition if provided (original email)
            if (! empty($messageId)) {
                $query->where('msg_id', $messageId);
            }

            // Add email subject LIKE condition if provided (for replies)
            if (! empty($emailSubject)) {
                $query->where('email_subject', 'LIKE', "%{$emailSubject}%");
            }

            $emailStatus = $query->latest()->first();

            if (! $emailStatus) {
                LoggerService::warning(self::class.' - updateCustomerRepliedStatus - Email status not found', [
                    'uuid' => $quoteUuid,
                    'quote_id' => $quote->id,
                    'quote_type_id' => $quoteTypeId,
                    'message_id' => $messageId ?? 'not provided',
                    'email_subject' => $emailSubject ?? 'not provided',
                    'time' => now(),
                ]);

                return (object) [
                    'success' => false,
                    'message' => 'Email status record not found',
                ];
            }

            // Update the customer_replied field
            $emailStatus->customer_replied = true;
            $emailStatus->save();

            LoggerService::info(self::class.' - updateCustomerRepliedStatus - Customer replied status updated successfully', [
                'uuid' => $quoteUuid,
                'quote_id' => $quote->id,
                'message_id' => $messageId ?? 'not provided',
                'email_subject' => $emailSubject ?? 'not provided',
                'email_status_id' => $emailStatus->id,
                'time' => now(),
            ]);

            return (object) [
                'success' => true,
                'message' => 'Customer replied status updated successfully',
                'data' => [
                    'email_status_id' => $emailStatus->id,
                    'customer_replied' => $emailStatus->customer_replied,
                ],
            ];
        } catch (\Exception $e) {
            LoggerService::error(self::class.' - updateCustomerRepliedStatus - Error updating customer replied status', [
                'uuid' => $quoteUuid,
                'message_id' => $messageId,
                'quote_type_id' => $quoteTypeId,
                'email_subject' => $emailSubject ?? 'not provided',
                'error' => $e->getMessage(),
                'time' => now(),
            ], $e);

            return (object) [
                'success' => false,
                'message' => 'Error updating customer replied status: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Get quote by UUID and type
     *
     * @return mixed
     */
    private function getQuoteByUuidAndType(string $uuid, int $quoteTypeId)
    {
        return match ($quoteTypeId) {
            QuoteTypeId::Car => CarQuote::where('uuid', $uuid)->first(),
            QuoteTypeId::Health => HealthQuote::where('uuid', $uuid)->first(),
            QuoteTypeId::Home, QuoteTypeId::Savings, QuoteTypeId::Life => PersonalQuote::where('uuid', $uuid)->first(),
            QuoteTypeId::Travel => TravelQuote::where('uuid', $uuid)->first(),
            default => null,
        };
    }

}

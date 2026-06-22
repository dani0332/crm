<?php

namespace App\Services;

use App\Enums\EmailStatusTypeEnum;
use App\Enums\ProcessStatusCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
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
        $quoteTypeId = (int) $quoteTypeId;
        $quoteId = (int) $quoteId;
        $key = $this->emailStatusListCacheKey($quoteTypeId, $quoteId);

        $ids = Cache::remember(
            $key,
            now()->endOfDay(),
            fn () => EmailStatus::query()
                ->where(['quote_type_id' => $quoteTypeId, 'quote_id' => $quoteId])
                ->orderByDesc('updated_at')
                ->pluck('id')
                ->all()
        );

        return EmailStatus::query()
            ->whereIn('id', $ids)
            ->orderByDesc('updated_at')
            ->get();
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
        $newEmailStatus->type = EmailStatusTypeEnum::Email;
        $newEmailStatus->flow_type = $emailData->flow_type ?? null;
        $newEmailStatus->save();
        $this->forgetEmailStatusListCache((int) $newEmailStatus->quote_type_id, (int) $newEmailStatus->quote_id);

        return $newEmailStatus->id;
    }

    public function addBirdEmailStatus($request)
    {
        $quoteTypeId = (int) (
            $request->route('quoteTypeId')
            ?? $request->input('quoteTypeId')
            ?? $request->input('quote_type_id')
            ?? 0
        );
        $quoteType = QuoteTypes::getName($quoteTypeId);

        if (! $quoteType) {
            LoggerService::warning("Lead not found for uuid: {$request->uuid} time: ".now(), [
                'uuid' => $request->uuid,
                'quoteTypeId' => $quoteTypeId,
            ]);

            return (object) ['message' => 'lead not found', 'status' => false];
        }

        $model = $quoteType->model();
        $query = $model::where('uuid', $request->route('uuid') ?? $request->input('uuid'));

        if ($model instanceof PersonalQuote) {
            $query->where('quote_type_id', $quoteTypeId);
        }

        $quote = $query->first();

        if (! $quote) {
            LoggerService::warning("Lead not found for uuid: {$request->uuid} time: ".now(), [
                'uuid' => $request->uuid,
                'quoteTypeId' => $quoteTypeId,
            ]);

            return (object) ['message' => 'lead not found', 'status' => false];
        }

        $mobileRaw = $request->input('mobile_no');
        if (filled($mobileRaw) && is_string($mobileRaw) && trim($mobileRaw) !== '') {
            return $this->addBirdWhatsAppStatus($request, $quote, $quoteTypeId, $mobileRaw);
        }

        if (! EmailStatus::where('email_status', ProcessStatusCode::SENT)
            ->where('msg_id', $request->message_id)
            ->where('quote_id', $quote->id)
            ->where('type', EmailStatusTypeEnum::Email)
            ->exists()) {
            $request->quoteId = $quote->id;
            $request->customerEmail = $request->customer_email;
            $this->addEmailStatus($request, $request->message_id, $request->subject ?? '', ProcessStatusCode::SENT);

            return (object) ['message' => 'Email event logged successfully', 'status' => true];
        }

        return (object) ['message' => 'Email event already logged', 'status' => true];
    }

    private function addBirdWhatsAppStatus($request, $quote, int $quoteTypeId, string $mobileInput): object
    {
        $mobile = formatMobileNoWithoutPlus($mobileInput);

        $exists = EmailStatus::where('msg_id', $request->message_id)
            ->where('type', EmailStatusTypeEnum::WhatsApp)
            ->where('mobile_no', $mobile)
            ->where('email_status', ProcessStatusCode::SENT)
            ->exists();

        if ($exists) {
            return (object) ['message' => 'WhatsApp event already logged', 'status' => true];
        }

        $newEmailStatus = new EmailStatus;
        $newEmailStatus->type = EmailStatusTypeEnum::WhatsApp;
        $newEmailStatus->mobile_no = $mobile;
        $newEmailStatus->msg_id = $request->message_id;
        $newEmailStatus->email_status = ProcessStatusCode::SENT;
        $newEmailStatus->quote_type_id = $quoteTypeId;
        $newEmailStatus->quote_id = $quote->id;
        $newEmailStatus->flow_type = $request->flow_type;
        $newEmailStatus->email_subject = $request->subject ?? '';
        $newEmailStatus->save();
        $this->forgetEmailStatusListCache($quoteTypeId, (int) $quote->id);

        return (object) ['message' => 'WhatsApp event logged successfully', 'status' => true];
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
        $this->forgetEmailStatusListCache((int) $emailStatus->quote_type_id, (int) $emailStatus->quote_id);
        info('EmailStatusService - EmailStatus updated for msg_id: '.$emailData->message_id.' email_status: '.$emailStatus->email_status.' | Time:'.now());
    }

    public function updateCustomerRepliedStatus(string $quoteUuid, int $quoteTypeId, string $emailSubject): object
    {
        try {
            // Get the quote based on quote type
            $quote = $this->getQuoteByUuidAndType($quoteUuid, $quoteTypeId);

            if (! $quote) {
                LoggerService::warning(self::class.' - Quote not found', [
                    'uuid' => $quoteUuid,
                    'quote_type_id' => $quoteTypeId,
                ]);

                return (object) [
                    'success' => false,
                    'message' => 'Quote not found',
                ];
            }

            // Strip reply/forward prefixes (Re:, RE:, Fwd:, FW:, Fw:, etc.)
            $cleanSubject = preg_replace('/^(Re:|RE:|Fwd:|FW:|Fw:)\s*/i', '', trim($emailSubject));

            // Match by quote_id, quote_type_id, and cleaned subject
            $emailStatus = EmailStatus::where('quote_id', $quote->id)
                ->where('quote_type_id', $quoteTypeId)
                ->where('email_subject', 'LIKE', "%{$cleanSubject}%")
                ->latest()
                ->first();

            if (! $emailStatus) {
                LoggerService::warning(self::class.' - Email status not found', [
                    'uuid' => $quoteUuid,
                    'quote_type_id' => $quoteTypeId,
                ]);

                return (object) [
                    'success' => false,
                    'message' => 'Email status record not found',
                ];
            }

            // Update the customer_replied field
            $emailStatus->customer_replied = true;
            $emailStatus->save();
            $this->forgetEmailStatusListCache((int) $emailStatus->quote_type_id, (int) $emailStatus->quote_id);

            LoggerService::info(self::class.' - Customer replied status updated', [
                'uuid' => $quoteUuid,
                'email_status_id' => $emailStatus->id,
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
            LoggerService::error(self::class.' - Error updating customer replied status', [
                'uuid' => $quoteUuid,
                'error' => $e->getMessage(),
            ], $e);

            return (object) [
                'success' => false,
                'message' => 'Error updating customer replied status: '.$e->getMessage(),
            ];
        }
    }

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

    /**
     * Postmark webhook: update or create email_status from delivery / bounce / etc.
     * Matches Postmark MessageID to msg_id, or creates a row when Metadata contains quote_id and quote_type_id.
     *
     * @param  array<string, mixed>  $validated
     */
    public function logEpEmailStatuses(array $validated): void
    {
        $messageId = (string) $validated['MessageID'];
        $recordType = (string) $validated['RecordType'];

        if ($this->shouldSkipPostmarkEngagementWebhook($recordType, $messageId)) {
            return;
        }

        $statusForDb = $this->statusForDatabaseFromPostmarkRecordType($recordType);

        if ($this->tryUpdateEmailStatusForPostmarkMessage($messageId, $statusForDb, $recordType)) {
            return;
        }

        if ($this->tryCreateEmailStatusFromPostmarkMetadata($validated, $messageId, $recordType, $statusForDb)) {
            return;
        }

        LoggerService::warning(self::class.' - logEpEmailStatuses: no row and no usable Metadata', [
            'message_id' => $messageId,
            'record_type' => $recordType,
            'metadata' => $validated['Metadata'] ?? 'Metadata not found',
        ]);
    }

    private function shouldSkipPostmarkEngagementWebhook(string $recordType, string $messageId): bool
    {
        if (! in_array($recordType, ['Open', 'Click'], true)) {
            return false;
        }

        LoggerService::info(self::class.' - logEpEmailStatuses: skipping record type', [
            'record_type' => $recordType,
            'message_id' => $messageId,
        ]);

        return true;
    }

    private function statusForDatabaseFromPostmarkRecordType(string $recordType): string
    {
        return (string) match ($recordType) {
            'Delivery' => ProcessStatusCode::SENT,
            'Bounce', 'SpamComplaint' => ProcessStatusCode::FAILED,
            'SubscriptionChange' => ProcessStatusCode::UNSUBSCRIBED,
            default => ProcessStatusCode::IN_PROGRESS,
        };
    }

    private function tryUpdateEmailStatusForPostmarkMessage(
        string $messageId,
        string $statusForDb,
        string $recordType,
    ): bool {
        $row = EmailStatus::query()->where('msg_id', $messageId)->orderByDesc('id')->first();

        if (! $row) {
            return false;
        }

        $row->email_status = $statusForDb;
        $row->save();
        $this->forgetEmailStatusListCache((int) $row->quote_type_id, (int) $row->quote_id);

        LoggerService::info(self::class.' - logEpEmailStatuses: updated', [
            'record_type' => $recordType,
            'message_id' => $messageId,
            'email_status' => $statusForDb,
        ]);

        return true;
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array{quote_id: int, quote_type_id: int}|null
     */
    private function quoteContextFromPostmarkMetadata(array $metadata): ?array
    {
        $quoteId = isset($metadata['quote_id']) ? (int) $metadata['quote_id'] : null;
        $quoteTypeId = isset($metadata['quote_type_id']) ? (int) $metadata['quote_type_id'] : null;

        if ($quoteId === null || $quoteId === 0 || $quoteTypeId === null || $quoteTypeId === 0) {
            return null;
        }

        return [
            'quote_id' => $quoteId,
            'quote_type_id' => $quoteTypeId,
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function tryCreateEmailStatusFromPostmarkMetadata(
        array $validated,
        string $messageId,
        string $recordType,
        string $statusForDb,
    ): bool {
        $metadata = $validated['Metadata'] ?? [];
        $context = $this->quoteContextFromPostmarkMetadata($metadata);

        if ($context === null) {
            return false;
        }

        $email = $validated['Recipient'] ?? $validated['Email'] ?? null;
        $emailSubject = (string) ($metadata['subject'] ?? $validated['Subject'] ?? '');

        $this->addEmailStatus(
            (object) [
                'quoteTypeId' => $context['quote_type_id'],
                'quoteId' => $context['quote_id'],
                'customerEmail' => is_string($email) ? $email : null,
                'templateId' => null,
                'emailTemplateId' => null,
                'customerId' => null,
            ],
            $messageId,
            $emailSubject,
            $statusForDb,
        );

        LoggerService::info(self::class.' - logEpEmailStatuses: created from Metadata', [
            'record_type' => $recordType,
            'message_id' => $messageId,
            'quote_id' => $context['quote_id'],
            'quote_type_id' => $context['quote_type_id'],
        ]);

        return true;
    }

    public function forgetEmailStatusListCache(int $quoteTypeId, int $quoteId): void
    {
        Cache::forget($this->emailStatusListCacheKey($quoteTypeId, $quoteId));
    }

    private function emailStatusListCacheKey(int $quoteTypeId, int $quoteId): string
    {
        return 'email_statuses:'.today()->toDateString().":{$quoteTypeId}:{$quoteId}";
    }
}

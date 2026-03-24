<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\EmailStatusTypeEnum;
use App\Enums\ProcessStatusCode;
use App\Enums\QuoteFlowType;
use App\Models\EmailStatus;
use App\Models\QuoteFlowDetails;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class GoogleReviewCommunicationLogService
{
    private const DEFAULT_COURTESY_EMAIL_TEMPLATE_LABEL = 'Google review courtesy';

    public function __construct(
        private CourtesyEmailService $courtesyEmailService,
    ) {}

    public function getForQuote(
        string $quoteUuid,
        int $quoteTypeId,
        int $quoteId,
        ?string $recipientEmail = null,
        ?iterable $emailStatuses = null,
    ): array {
        if (! in_array($quoteTypeId, CourtesyEmailService::allowedQuoteTypeIds(), true)) {
            return [];
        }

        $rows = [];

        $formatDisplayDateTime = fn (mixed $v): string => $v === null ? '—' : Carbon::parse($v)->timezone(config('app.timezone'))->format(config('constants.DATETIME_DISPLAY_FORMAT'));

        $flowContext = $this->courtesyEmailService->getGoogleReviewFlowLogContext(
            $quoteUuid,
            $quoteTypeId,
            $recipientEmail
        );

        $flows = QuoteFlowDetails::query()
            ->where('quote_uuid', $quoteUuid)
            ->where('quote_type_id', $quoteTypeId)
            ->where('flow_type', QuoteFlowType::COURTESY_EMAIL->value)
            ->orderByDesc('started_at')
            ->orderByDesc('id')
            ->get();

        $normalizedRecipient = $recipientEmail !== null && $recipientEmail !== ''
            ? strtolower(trim($recipientEmail))
            : null;

        $latestFlowStartedAt = $flows->first()?->started_at;

        foreach ($flows as $flow) {
            $started = $flow->started_at;
            $suppressionExpiresForFlow = $started !== null
                ? $formatDisplayDateTime($started->copy()->addDays(7))
                : '—';
            $rows[] = [
                'sort_at' => $started?->timestamp ?? 0,
                'id' => $flow->id,
                'email_template' => self::DEFAULT_COURTESY_EMAIL_TEMPLATE_LABEL,
                'email_address' => $normalizedRecipient ?? '—',
                'status' => 'Workflow triggered',
                'sent_at' => $formatDisplayDateTime($started),
                'review_flow_status' => $flowContext['review_flow_status'],
                'reason_non_dispatch' => '—',
                'suppression_expires_at' => $suppressionExpiresForFlow,
                'review_clicked_at' => '—',
                'channel' => filled($flow->stopped_source) ? (string) $flow->stopped_source : '—',
            ];
        }

        $messages = $emailStatuses !== null
            ? Collection::make($emailStatuses)->sortByDesc('id')->values()
            : EmailStatus::query()
                ->where('quote_type_id', $quoteTypeId)
                ->where('quote_id', $quoteId)
                ->select([
                    'id',
                    'type',
                    'mobile_no',
                    'email_address',
                    'email_subject',
                    'template_id',
                    'email_status',
                    'reason',
                    'created_at',
                ])
                ->orderByDesc('id')
                ->get();

        $stoppedSourceForQuote = $flows->pluck('stopped_source')->filter()->first();

        $suppressionExpiresForMessages = $latestFlowStartedAt !== null
            ? $formatDisplayDateTime($latestFlowStartedAt->copy()->addDays(7))
            : $flowContext['suppression_expires_at'];

        $maxFlowEndedAt = $flows->max('ended_at');

        foreach ($messages as $msg) {
            $rawCreated = $msg->getRawOriginal('created_at');
            $sortAt = $rawCreated ? Carbon::parse($rawCreated)->timestamp : 0;
            $channel = $msg->type === EmailStatusTypeEnum::WhatsApp ? 'WhatsApp' : 'Email';
            $emailAddress = $msg->type === EmailStatusTypeEnum::WhatsApp
                ? ($msg->mobile_no ? '+'.preg_replace('/\D/', '', (string) $msg->mobile_no) : '—')
                : ($msg->email_address ?: '—');

            $emailTemplate = $msg->email_subject !== null && trim((string) $msg->email_subject) !== ''
                ? trim((string) $msg->email_subject)
                : ($msg->template_id !== null ? (string) $msg->template_id : '—');

            $status = (string) ($msg->email_status ?? '');

            $rows[] = [
                'sort_at' => $sortAt,
                'id' => $msg->id,
                'email_template' => $emailTemplate,
                'email_address' => $emailAddress,
                'status' => $status !== '' ? $status : '—',
                'sent_at' => $msg->created_at,
                'review_flow_status' => $flowContext['review_flow_status'],
                'reason_non_dispatch' => $msg->reason !== null && trim((string) $msg->reason) !== '' ? (string) $msg->reason : '—',
                'suppression_expires_at' => $suppressionExpiresForMessages,
                'review_clicked_at' => strtolower(trim($status)) === strtolower(ProcessStatusCode::CLICKED) ? $formatDisplayDateTime($maxFlowEndedAt) : '—',
                'channel' => filled($stoppedSourceForQuote) ? (string) $stoppedSourceForQuote : $channel,
            ];
        }

        usort($rows, fn (array $a, array $b): int => $b['sort_at'] <=> $a['sort_at']);

        return array_map(function (array $r): array {
            unset($r['sort_at']);

            return $r;
        }, $rows);
    }
}

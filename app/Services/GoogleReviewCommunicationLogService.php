<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\EmailStatusTypeEnum;
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

    /**
     * Rows for the Google Review communication log UI (schema-aligned keys only).
     * `status` is the single delivery/state column: courtesy workflow label, or `email_status.email_status` for rows from that table.
     * `review_clicked_at`: each workflow row uses that flow’s `ended_at`; message rows use `max(ended_at)` from the same `$flows` query. `channel`: workflow rows use each flow’s `stopped_source`; message rows use first non-empty `stopped_source` from `$flows`, else Email/WhatsApp.
     * (`touchpoint` omitted until data source exists; restore with Vue column.)
     *
     * @param  iterable<EmailStatus>|null  $emailStatuses  Prefer rows from {@see EmailStatusService::getEmailStatus()} (cached) to avoid a duplicate query on show pages.
     * @return list<array<string, mixed>>
     */
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

        foreach ($flows as $flow) {
            $started = $flow->started_at;
            $ended = $flow->ended_at;
            $rows[] = [
                'sort_at' => $started?->timestamp ?? 0,
                'id' => $flow->id,
                'email_template' => self::DEFAULT_COURTESY_EMAIL_TEMPLATE_LABEL,
                'email_address' => $normalizedRecipient ?? '—',
                'status' => 'Workflow triggered',
                'sent_at' => $this->formatDisplayDateTime($started),
                'review_flow_status' => $flowContext['review_flow_status'],
                'reason_non_dispatch' => '—',
                'suppression_expires_at' => $flowContext['suppression_expires_at'],
                'review_clicked_at' => $this->formatDisplayDateTime($ended),
                'channel' => filled($flow->stopped_source) ? (string) $flow->stopped_source : '—',
                // 'touchpoint' => '—', // TODO: include when touchpoint data is wired (see Vue table column)
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
                'suppression_expires_at' => $flowContext['suppression_expires_at'],
                'review_clicked_at' => $this->formatDisplayDateTime($flows->max('ended_at')),
                'channel' => filled($stoppedSourceForQuote) ? (string) $stoppedSourceForQuote : $channel,
                // 'touchpoint' => '—', // TODO: include when touchpoint data is wired (see Vue table column)
            ];
        }

        usort($rows, fn (array $a, array $b): int => $b['sort_at'] <=> $a['sort_at']);

        return array_map(function (array $r): array {
            unset($r['sort_at']);

            return $r;
        }, $rows);
    }

    private function formatDisplayDateTime(mixed $value): string
    {
        if ($value === null) {
            return '—';
        }

        $dt = $value instanceof Carbon
            ? $value->clone()
            : Carbon::parse($value);

        return $dt->timezone(config('app.timezone'))->format(config('constants.DATETIME_DISPLAY_FORMAT'));
    }
}

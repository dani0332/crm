<?php

declare(strict_types=1);

namespace App\Mail\NonCQF;

use App\Enums\ApplicationStorageEnums;
use App\Enums\RenewalProcessStatuses;
use App\Enums\RolesEnum;
use App\Enums\WorkflowTypeEnum;
use App\Exports\RenewalFailedValidationExport;
use App\Models\ApplicationStorage;
use App\Models\RenewalQuoteProcess;
use App\Models\RenewalsUploadLeads;
use App\Models\User;
use App\Services\CQF\NonMotor\NonCQFRenewalBrevoMailService;
use App\Services\Logger\LoggerService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;

class SendFailedNonCQFRenewal extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public int $renewalsUploadLeadsId
    ) {}

    public function build(): self
    {
        return $this->subject('Non-motor CQF renewals errors')
            ->view('email.non-cqf.send-failed-non-cqf-renewal')
            ->with([
                'renewalsUploadLeadsId' => $this->renewalsUploadLeadsId,
            ]);
    }

    public function sendViaBrevo(NonCQFRenewalBrevoMailService $emailService): bool
    {
        $templateId = (int) ($this->getBrevoTemplateId() ?? 918);

        if (! $templateId) {
            LoggerService::error(self::class.' - Brevo template ID not configured', [
                'renewals_upload_lead_id' => $this->renewalsUploadLeadsId,
            ]);

            return false;
        }

        try {
            $emailData = $this->buildEmailData();

            if (empty($emailData->renewalsManagersEmails)) {
                LoggerService::error(self::class.' - No RenewalsManager recipients found', [
                    'renewals_upload_lead_id' => $this->renewalsUploadLeadsId,
                ]);

                return false;
            }

            $body = [
                'to' => [['email' => $emailData->renewalManagerEmail]],
                'cc' => array_map(fn ($email) => ['email' => $email], $emailData->renewalsManagersEmails),
                'templateId' => $templateId,
                'params' => (array) $emailData,
                'tags' => ['non-motor-cqf-failed-renewal'],
                'attachment' => $emailData->attachment,
            ];

            $result = $emailService->send($body);

            LoggerService::info(self::class.' - Non-motor CQF renewals errors sent via Brevo', [
                'renewals_upload_lead_id' => $this->renewalsUploadLeadsId,
                'lob' => $emailData->lob ?? null,
                'failed_leads_count' => $emailData->failedLeadsCount ?? 0,
                'sent' => $result['sent'],
                'message_id' => $result['object']->messageId ?? null,
            ]);

            return $result['sent'] === 1;
        } catch (\Exception $e) {
            LoggerService::error(self::class.' - Non-motor CQF renewals errors not sent via Brevo', [
                'renewals_upload_lead_id' => $this->renewalsUploadLeadsId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    protected function buildAttachment(?RenewalsUploadLeads $lead, string $safeLobForFile, string $date): array
    {
        if (! $lead) {
            return [];
        }

        $content = Excel::raw(new RenewalFailedValidationExport($lead), \Maatwebsite\Excel\Excel::XLSX);

        return [
            [
                'content' => base64_encode($content),
                'name' => "cqf-renewals-failed-leads-{$safeLobForFile}-{$date}.xlsx",
            ],
        ];
    }

    protected function getBrevoTemplateId(): ?string
    {
        $config = ApplicationStorage::where('key_name', ApplicationStorageEnums::NON_MOTOR_CQF_FAILED_RENEWAL_BREVO_TEMPLATE)->first();

        return $config?->value;
    }

    /**
     * Build Bird email payload. validationErrors and fileName include LOB for clarity.
     *
     * @return object{failedQuotes: string, quoteUID: string, renewalsManagersEmails: array, validationErrors: string, fileName: string, lob: string, ...}
     */
    public function buildEmailData(): object
    {
        $lead = RenewalsUploadLeads::find($this->renewalsUploadLeadsId);
        $lob = $lead?->quote_type ?: 'Non-motor';

        $renewalsManagersEmails = User::role(RolesEnum::RenewalsManager)
            ->pluck('email')
            ->filter()
            ->values()
            ->all();

        $failedPolicyNumbers = RenewalQuoteProcess::where('renewals_upload_lead_id', $this->renewalsUploadLeadsId)
            ->where('status', RenewalProcessStatuses::BAD_DATA)
            ->pluck('policy_number')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $date = now()->format(config('constants.DATE_FORMAT_ONLY'));
        $safeLobForFile = preg_replace('/[^a-zA-Z0-9_-]/', '-', $lob);

        return (object) [
            'failedQuotes' => implode(', ', $failedPolicyNumbers),
            'quoteUID' => '',
            'renewalsManagersEmails' => $renewalsManagersEmails,
            'validationErrors' => "CQF renewal failed for {$lob} leads.",
            'lob' => $lob,
            'renewalManagerEmail' => $renewalsManagersEmails[0] ?? '',
            'workflowType' => WorkflowTypeEnum::CQF_NON_MOTOR_RENEWALS,
            'dateOfAttempt' => $date,
            'failedLeadsCount' => count($failedPolicyNumbers),
            'attachment' => $this->buildAttachment($lead, $safeLobForFile, $date),
        ];
    }
}

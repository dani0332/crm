<?php

namespace App\Mail\NonCQF;

use App\Enums\ApplicationStorageEnums;
use App\Enums\RenewalProcessStatuses;
use App\Enums\RolesEnum;
use App\Enums\WorkflowTypeEnum;
use App\Models\ApplicationStorage;
use App\Models\RenewalQuoteProcess;
use App\Models\RenewalsUploadLeads;
use App\Models\User;
use App\Services\BirdService;
use App\Services\Logger\LoggerService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendFailedNonCQFRenewal extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public int $renewalsUploadLeadsId
    ) {}

    public function build()
    {
        return $this->subject('Non-motor CQF renewals errors')
            ->view('email.non-cqf.send-failed-non-cqf-renewal')
            ->with([
                'renewalsUploadLeadsId' => $this->renewalsUploadLeadsId,
            ]);
    }

    /**
     * Send failed non-motor CQF renewal notification via Bird (same pattern as BorRequestMail).
     */
    public function sendViaBird(): bool
    {
        $workflowUrl = $this->getBirdWorkflowUrl();

        if (! $workflowUrl) {
            LoggerService::error(self::class.' - Bird workflow URL not configured', [
                'renewals_upload_lead_id' => $this->renewalsUploadLeadsId,
            ]);

            return false;
        }

        try {
            $birdData = $this->buildBirdEmailData();
            $response = app(BirdService::class)->triggerWebHookRequest($workflowUrl, $birdData);

            LoggerService::info(self::class.' - Non-motor CQF renewals errors sent via Bird', [
                'renewals_upload_lead_id' => $this->renewalsUploadLeadsId,
                'lob' => $birdData->lob ?? null,
                'failed_leads_count' => $birdData->failedLeadsCount ?? 0,
                'response_status' => $response->status_code,
            ]);

            return $response->status_code >= 200 && $response->status_code < 300;
        } catch (\Exception $e) {
            LoggerService::error(self::class.' - Non-motor CQF renewals errors not sent via Bird', [
                'renewals_upload_lead_id' => $this->renewalsUploadLeadsId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Bird workflow URL for non-motor CQF failed renewals (reuses customer notify unavailable advisor workflow).
     */
    protected function getBirdWorkflowUrl(): ?string
    {
        $config = ApplicationStorage::where('key_name', ApplicationStorageEnums::BIRD_CUSTOMER_NOTIFY_UNAVAILABLE_ADVIOSR_WORKFLOW)->first();

        return $config?->value;
    }

    /**
     * Build Bird email payload. validationErrors and fileName include LOB for clarity.
     *
     * @return object{failedQuotes: string, quoteUID: string, renewalsManagersEmails: array, validationErrors: string, fileName: string, lob: string, ...}
     */
    public function buildBirdEmailData(): object
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

        $failedPolicyNumbers = array_values(array_unique($failedPolicyNumbers));
        $date = now()->format('Y-m-d');
        $safeLobForFile = preg_replace('/[^a-zA-Z0-9_-]/', '-', $lob);

        return (object) [
            'failedQuotes' => implode(', ', $failedPolicyNumbers),
            'quoteUID' => '',
            'renewalsManagersEmails' => $renewalsManagersEmails,
            'validationErrors' => "CQF renewal failed for {$lob} leads.",
            'fileName' => "cqf-renewals-failed-leads-{$safeLobForFile}-{$date}.xlsx",
            'lob' => $lob,
            'renewalManagerEmail' => $renewalsManagersEmails[0] ?? '',
            'workflowType' => WorkflowTypeEnum::CQF_NON_MOTOR_RENEWALS,
            'dateOfAttempt' => $date,
            'failedLeadsCount' => count($failedPolicyNumbers),
            'fileDownloadUrl' => route('downloadValidationFailedFile', ['id' => $this->renewalsUploadLeadsId]),
        ];
    }
}

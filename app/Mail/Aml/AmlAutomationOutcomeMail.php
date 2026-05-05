<?php

declare(strict_types=1);

namespace App\Mail\Aml;

use App\Enums\ApplicationStorageEnums;
use App\Models\ApplicationStorage;
use App\Models\PersonalQuote;
use App\Services\BirdService;
use App\Services\Logger\LoggerService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AmlAutomationOutcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        private bool $isSuccess,
        private PersonalQuote $quote,
        private ?string $paymentLink,
        private ?string $failureReason,
        private ?string $kenResponseSummary,
    ) {}

    public function build(): self
    {
        return $this;
    }

    public function sendViaBird(): bool
    {
        try {
            $workflowUrl = $this->resolveWorkflowUrl();
            if ($workflowUrl === null || $workflowUrl === '') {
                LoggerService::error('AML automation outcome email: Bird workflow URL not configured');

                return false;
            }

            $payload = $this->buildBirdEmailData();

            LoggerService::info('AML automation outcome email payload', ['payload' => json_encode($payload)]);

            $response = app(BirdService::class)->triggerWebHookRequest($workflowUrl, $payload);

            return $response->status_code >= 200 && $response->status_code < 300;
        } catch (\Throwable $e) {
            LoggerService::error('AML automation outcome email failed', [
                'quote_uuid' => $this->quote->uuid,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function resolveWorkflowUrl(): ?string
    {
        $row = ApplicationStorage::query()
            ->where('key_name', ApplicationStorageEnums::BIRD_AML_AUTOMATION_OUTCOME_WORKFLOW_URL)
            ->first();

        return $row?->value;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildBirdEmailData(): array
    {
        $this->quote->loadMissing(['advisor', 'insuranceProvider', 'insuranceProviderPlan']);

        $advisorEmail = $this->quote?->advisor?->email;
        $advisorName = $this->quote?->advisor?->name;
        $customerEmail = $this->quote?->email;
        $customerName = trim((string) $this->quote?->first_name.' '.(string) $this->quote?->last_name);

        $toEmail = $customerEmail ?: $advisorEmail;
        $ccEmails = [];
        if ($advisorEmail && $toEmail && strcasecmp((string) $advisorEmail, (string) $toEmail) !== 0) {
            $ccEmails[] = $advisorEmail;
        }

        return [
            'refId' => $this->quote->code,
            'quoteUID' => $this->quote->uuid,
            'isSuccess' => (int) $this->isSuccess,
            'customerName' => $customerName,
            'insurerName' => $this->quote->insuranceProvider?->text ?? '',
            'planName' => $this->quote->insuranceProviderPlan?->text ?? '',
            'totalPremium' => $this->quote->total_premium ?? '',
            'advisorPhone' => $this->quote->advisor?->mobile_no ?? '',
            'advisorEmail' => $advisorEmail,
            'advisorName' => $advisorName,
            'workflowBranchType' => 'aml_automation_outcome',
            'paymentLink' => $this->paymentLink,
            'customerEmail' => $customerEmail,
            'toEmail' => $toEmail,
            'ccEmails' => array_values(array_filter($ccEmails)),
            'replyToEmail' => $advisorEmail ?? '',
        ];
    }
}

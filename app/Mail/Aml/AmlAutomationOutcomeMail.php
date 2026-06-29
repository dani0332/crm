<?php

declare(strict_types=1);

namespace App\Mail\Aml;

use App\Enums\ApplicationStorageEnums;
use App\Enums\WorkflowTypeEnum;
use App\Models\ApplicationStorage;
use App\Services\BirdService;
use App\Services\Logger\LoggerService;
use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AmlAutomationOutcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        private Model $quote,
        private ?string $paymentLink,
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
        $this->quote->loadMissing(['advisor', 'insuranceProvider', 'insuranceProviderPlan', 'savingsQuote', 'savingsQuote.currency']);

        $advisorEmail = $this->quote?->advisor?->email ?? '';
        $advisorName = $this->quote?->advisor?->name ?? '';
        $customerEmail = $this->quote?->email ?? '';
        $customerName = trim((string) $this->quote?->first_name.' '.(string) $this->quote?->last_name);

        return [
            'refId' => $this->quote->code,
            'quoteUID' => $this->quote->uuid,
            'customerName' => $customerName ?? '',
            'insurerName' => $this->quote?->insuranceProvider?->text ?? '',
            'planName' => $this->quote?->insuranceProviderPlan?->text ?? '',
            'totalPremium' => $this->quote?->premium ?? '',
            'advisorPhone' => $this->quote?->advisor?->mobile_no ?? '',
            'advisorEmail' => $advisorEmail,
            'advisorName' => $advisorName,
            'workflowType' => WorkflowTypeEnum::AML_AUTOMATION_OUTCOME,
            'paymentLink' => $this->paymentLink ?? '',
            'customerEmail' => $customerEmail ?? '',
            'currency' => $this->quote?->savingsQuote?->currency?->code ?? '',
        ];
    }
}

<?php

namespace App\Mail;

use App\Enums\ApplicationStorageEnums;
use App\Enums\QuoteTypeId;
use App\Models\ApplicationStorage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SukoonMedexEPFailureNotification extends Mailable
{
    use Queueable, SerializesModels;

    private $quoteObject;
    private $quoteTypeId;
    private array $epFailureEmailConfigs = [];

    /**
     * Create a new message instance.
     */
    public function __construct($quoteObject, $quoteTypeId = null)
    {
        $this->quoteObject = $quoteObject;
        $this->quoteTypeId = (int) $quoteTypeId;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $this->getEpFailureEmailConfigs();

        $refId = $this->quoteObject->code ?? $this->quoteObject->uuid ?? 'Unknown';
        $subject = "❗Action Required: Embedded Product for Medex has failed for REF-ID: {$refId} – Immediate Attention Needed";
        $viewData = $this->getViewData($refId);

        return $this->subject($subject)
            ->from(...array_slice($this->getRecipientAddress('from'), 0, 2)) // for email with name
            ->to(...array_slice($this->getRecipientAddress('to'), 0, 1)) // only email address
            ->replyTo(...array_slice($this->getRecipientAddress('reply_to'), 0, 2)) // for email with name
            ->cc($this->buildCcEmails()) // multiple cc emails
            ->view('email.sukoon-medex-ep-job-failed', $viewData);
    }

    private function buildCcEmails(): array
    {
        $ccEmails = $this->getRecipientAddress('cc');
        if ($advisorEmail = $this->getAdvisorEmail()) {
            $ccEmails[] = $advisorEmail;
        }

        return $ccEmails;
    }

    private function getAdvisorEmail(): ?string
    {
        return $this->quoteObject?->advisor?->email;
    }

    private function getViewData(string $refId): array
    {
        return [
            'refId' => $refId,
            'imcrmLink' => $this->generateImcrmLink()
        ];
    }

    private function getRecipientAddress($recipientType): array
    {
        $emails =  match ($recipientType) {
            'from' => $this->epFailureEmailConfigs[ApplicationStorageEnums::EP_FAILURE_EMAIL_FROM],
            'to' => $this->epFailureEmailConfigs[ApplicationStorageEnums::EP_FAILURE_EMAIL_TO],
            'reply_to' => $this->epFailureEmailConfigs[ApplicationStorageEnums::EP_FAILURE_EMAIL_REPLY_TO],
            'cc' => $this->epFailureEmailConfigs[ApplicationStorageEnums::EP_FAILURE_EMAIL_CC],
            default => [],
        };
        return array_filter(explode(',', str_replace(' ', '', $emails)));
    }

    private function getEpFailureEmailConfigs()
    {
        $epEcbAppStorageKeys = [
            ApplicationStorageEnums::EP_FAILURE_EMAIL_FROM,
            ApplicationStorageEnums::EP_FAILURE_EMAIL_TO,
            ApplicationStorageEnums::EP_FAILURE_EMAIL_REPLY_TO,
            ApplicationStorageEnums::EP_FAILURE_EMAIL_CC,
        ];
        $appStorageRecords = ApplicationStorage::select('value', 'key_name')
            ->where('is_active', ApplicationStorageEnums::ACTIVE)
            ->whereIn('key_name', $epEcbAppStorageKeys)
            ->whereNotNull('value')
            ->get();
        $missingAppStorageKeys = array_diff($epEcbAppStorageKeys, $appStorageRecords->pluck('key_name')->toArray());

        if (count($missingAppStorageKeys) > 0) {
            throw new \Exception('EP Failure Email configuration not found');
        }

        $this->epFailureEmailConfigs = $appStorageRecords->pluck('value', 'key_name')->toArray();
    }
    /**
     * Generate IMCRM link for the quote
     */
    private function generateImcrmLink(): string
    {
        $baseUrl = config('app.url', env('APP_URL'));
        $quoteId = $this->quoteObject->uuid ?? $this->quoteObject->id ?? '';

        if (empty($quoteId)) {
            return 'N/A';
        }

        // Generate appropriate link based on quote type
        $imcrmLink = match ($this->quoteTypeId) {
            QuoteTypeId::Car => "{$baseUrl}/quotes/car/{$quoteId}",           // Car quote type
            QuoteTypeId::Bike => "{$baseUrl}/personal-quotes/bike/{$quoteId}", // Bike quote type
            default => null
        };

        return $imcrmLink;
    }
}

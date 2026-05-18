<?php

namespace App\Mail;

use App\Enums\ApplicationStorageEnums;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Models\ApplicationStorage;
use App\Models\EmbeddedProduct;
use App\Services\Logger\LoggerService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EpFailureNotification extends Mailable
{
    use GenericQueriesAllLobs, Queueable, SerializesModels;

    private int $quoteId;
    private int $quoteTypeId;
    private int $etId;
    private mixed $quoteObject = null;
    private bool $isSageBooking = false;
    private string $logPrefix = 'EpFailureNotification - Mail:';
    private array $epFailureEmailConfigs = [];

    /**
     * Create a new message instance.
     */
    public function __construct($quoteId, $quoteTypeId, $etId, $isSageBooking = false)
    {
        $this->quoteId = (int) $quoteId;
        $this->quoteTypeId = (int) $quoteTypeId;
        $this->etId = (int) $etId;
        $this->isSageBooking = $isSageBooking;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        LoggerService::info("{$this->logPrefix} Sending failure email", extra: [
            'quoteId' => $this->quoteId,
            'quoteTypeId' => $this->quoteTypeId,
            'etId' => $this->etId,
        ]);

        $this->getEpFailureEmailConfigs();

        $epProductName = $this->getEmbeddedProductName();
        $refId = $this->initializeQuoteData();
        $viewData = $this->getViewData($refId, $epProductName);

        return $this->subject($this->getEmailSubject($epProductName, $refId))
            ->from(...array_slice($this->getRecipientAddress('from'), 0, 2)) // for email with name
            ->to(...array_slice($this->getRecipientAddress('to'), 0, 1)) // only email address
            ->replyTo(...array_slice($this->getRecipientAddress('reply_to'), 0, 2)) // for email with name
            ->cc($this->buildCcEmails()) // multiple cc emails
            ->view('email.ep-booking-job-failed', $viewData);
    }

    private function getEmbeddedProductName(): string
    {
        $ep = EmbeddedProduct::whereHas('prices.transactions', fn ($q) => $q->where('id', $this->etId))->first();

        return $ep?->product_name ?? 'Unknown';
    }

    private function initializeQuoteData(): string
    {
        $quoteType = QuoteTypes::getName($this->quoteTypeId)->value;
        $this->quoteObject = $this->getQuoteObject($quoteType, $this->quoteId);

        return $this->quoteObject?->code ?? $this->quoteObject?->uuid ?? 'Unknown';
    }

    private function getEmailSubject(string $epProductName, string $refId): string
    {
        $refSuffix = " for REF-ID: {$refId} – Immediate Attention Needed";

        if ($this->isSageBooking) {
            return "❗Action Required: Embedded Product for {$epProductName}: Sage booking failed{$refSuffix}";
        }

        return "❗Action Required: Embedded Product for {$epProductName} has failed{$refSuffix}";
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

    private function getRecipientAddress($recipientType): array
    {
        $emails = match ($recipientType) {
            'from' => $this->epFailureEmailConfigs[ApplicationStorageEnums::EP_FAILURE_EMAIL_FROM],
            'to' => $this->epFailureEmailConfigs[ApplicationStorageEnums::EP_FAILURE_EMAIL_TO],
            'reply_to' => $this->epFailureEmailConfigs[ApplicationStorageEnums::EP_FAILURE_EMAIL_REPLY_TO],
            'cc' => $this->epFailureEmailConfigs[ApplicationStorageEnums::EP_FAILURE_EMAIL_CC],
            default => [],
        };

        return array_filter(explode(',', str_replace(' ', '', $emails)));
    }

    private function getViewData(string $refId, string $epProductName): array
    {
        return [
            'refId' => $refId,
            'imcrmLink' => $this->generateImcrmLink(),
            'epProductName' => $epProductName,
            'isSageBooking' => $this->isSageBooking,
        ];
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
        $quoteId = $this->quoteObject?->uuid ?? $this->quoteObject?->id ?? '';

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

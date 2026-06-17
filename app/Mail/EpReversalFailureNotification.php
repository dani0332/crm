<?php

declare(strict_types=1);

namespace App\Mail;

use App\Enums\ApplicationStorageEnums;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Exceptions\EpReversalFailureEmailConfigNotFoundException;
use App\Models\ApplicationStorage;
use App\Models\EmbeddedProduct;
use App\Services\Logger\LoggerService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EpReversalFailureNotification extends Mailable
{
    use GenericQueriesAllLobs, Queueable, SerializesModels;

    private string $logPrefix = 'EpReversalFailureNotification - Mail:';
    private mixed $quoteObject = null;

    /** @var array<string, string> */
    private array $epFailureEmailConfigs = [];

    public function __construct(
        public int $quoteId,
        public int $quoteTypeId,
        public int $etId,
    ) {}

    public function build(): self
    {
        LoggerService::info("{$this->logPrefix} Sending EP Sage reversal failure email", extra: [
            'quoteId' => $this->quoteId,
            'quoteTypeId' => $this->quoteTypeId,
            'etId' => $this->etId,
        ]);

        $this->loadEpFailureEmailConfigs();

        $epProductName = $this->getEmbeddedProductName();
        $refId = $this->initializeQuoteData();
        $viewData = $this->getViewData($refId, $epProductName);

        return $this->subject($this->getEmailSubject($epProductName, $refId))
            ->from(...array_slice($this->getRecipientAddress('from'), 0, 2))
            ->to(...array_slice($this->getRecipientAddress('to'), 0, 1))
            ->replyTo(...array_slice($this->getRecipientAddress('reply_to'), 0, 2))
            ->cc($this->getRecipientAddress('cc'))
            ->view('email.ep-sage-reversal-failed', $viewData);
    }

    private function getEmailSubject(string $epProductName, string $refId): string
    {
        return "❗Action Required:  Embedded Product for {$epProductName} has Sage reversal booking failed for REF-ID: {$refId} – Immediate Attention Needed";
    }

    private function getEmbeddedProductName(): string
    {
        $ep = EmbeddedProduct::whereHas('prices.transactions', fn ($q) => $q->where('id', $this->etId))->first();

        return $ep->product_name ?? 'Unknown';
    }

    private function initializeQuoteData(): string
    {
        $quoteType = QuoteTypes::getName($this->quoteTypeId)->value;
        $this->quoteObject = $this->getQuoteObject($quoteType, $this->quoteId);

        return $this->quoteObject?->code ?? $this->quoteObject?->uuid ?? 'Unknown';
    }

    private function getViewData(string $refId, string $epProductName): array
    {
        return [
            'refId' => $refId,
            'imcrmLink' => $this->generateImcrmLink(),
            'epProductName' => $epProductName,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function getRecipientAddress(string $recipientType): array
    {
        $emails = match ($recipientType) {
            'from' => $this->epFailureEmailConfigs[ApplicationStorageEnums::EP_FAILURE_EMAIL_FROM] ?? '',
            'to' => $this->epFailureEmailConfigs[ApplicationStorageEnums::EP_FAILURE_EMAIL_TO] ?? '',
            'reply_to' => $this->epFailureEmailConfigs[ApplicationStorageEnums::EP_FAILURE_EMAIL_REPLY_TO] ?? '',
            'cc' => $this->epFailureEmailConfigs[ApplicationStorageEnums::EP_FAILURE_EMAIL_CC] ?? '',
            default => '',
        };

        return array_values(array_filter(explode(',', str_replace(' ', '', $emails))));
    }

    private function loadEpFailureEmailConfigs(): void
    {
        $keys = [
            ApplicationStorageEnums::EP_FAILURE_EMAIL_FROM,
            ApplicationStorageEnums::EP_FAILURE_EMAIL_TO,
            ApplicationStorageEnums::EP_FAILURE_EMAIL_REPLY_TO,
            ApplicationStorageEnums::EP_FAILURE_EMAIL_CC,
        ];

        $records = ApplicationStorage::query()
            ->select('value', 'key_name')
            ->where('is_active', ApplicationStorageEnums::ACTIVE)
            ->whereIn('key_name', $keys)
            ->whereNotNull('value')
            ->get();

        $missing = array_diff($keys, $records->pluck('key_name')->toArray());
        if ($missing !== []) {
            throw new EpReversalFailureEmailConfigNotFoundException(array_values($missing));
        }

        $this->epFailureEmailConfigs = $records->pluck('value', 'key_name')->toArray();
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
        return match ($this->quoteTypeId) {
            QuoteTypeId::Car => "{$baseUrl}/quotes/car/{$quoteId}",           // Car quote type
            QuoteTypeId::Bike => "{$baseUrl}/personal-quotes/bike/{$quoteId}", // Bike quote type
            default => null
        };
    }
}

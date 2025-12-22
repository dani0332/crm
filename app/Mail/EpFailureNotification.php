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
    private string $logPrefix = 'EpFailureNotification - Mail:';
    private array $epFailureEmailConfigs = [];

    /**
     * Create a new message instance.
     */
    public function __construct($quoteId, $quoteTypeId, $etId)
    {
        $this->quoteId = (int) $quoteId;
        $this->quoteTypeId = (int) $quoteTypeId;
        $this->etId = (int) $etId;
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
            ->from(...$this->getFromAddress())
            ->to($this->epFailureEmailConfigs[ApplicationStorageEnums::EP_FAILURE_EMAIL_TO])
            ->replyTo($this->epFailureEmailConfigs[ApplicationStorageEnums::EP_FAILURE_EMAIL_REPLY_TO])
            ->cc($this->buildCcEmails())
            ->view('email.ep-booking-job-failed', $viewData);
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

    private function getEmailSubject(string $epProductName, string $refId): string
    {
        return "❗Action Required: Embedded Product for {$epProductName} has failed for REF-ID: {$refId} – Immediate Attention Needed";
    }

    private function buildCcEmails(): array
    {
        $ccString = $this->epFailureEmailConfigs[ApplicationStorageEnums::EP_FAILURE_EMAIL_CC] ?? '';
        
        $ccEmails =  array_filter(explode(',', str_replace(' ', '', $ccString)));
        
        if ($advisorEmail = $this->getAdvisorEmail()) {
            $ccEmails[] = $advisorEmail;
        }
        
        return $ccEmails;
    }

    private function getAdvisorEmail(): ?string
    {
        return $this->quoteObject?->advisor?->email;
    }

    private function getFromAddress(): array
    {
        return app()->environment('production')
            ? ['alfred@notify.insurancemarket.ae', 'InsuranceMarket.ae']
            : ['alfred@testnotify.alfred.ae', 'InsuranceMarket Test'];
    }

    private function getViewData(string $refId, string $epProductName): array
    {
        return [
            'refId' => $refId,
            'imcrmLink' => $this->generateImcrmLink(),
            'epProductName' => $epProductName,
        ];
    }

    private function getEpFailureEmailConfigs()
    {
        $epEcbAppStorageKeys = [
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

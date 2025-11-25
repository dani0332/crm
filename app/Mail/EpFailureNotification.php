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

        $ep = EmbeddedProduct::whereHas('prices.transactions', fn ($q) => $q->where('id', $this->etId))->first();
        $epProductName = $ep->product_name ?? 'Unknown';

        $isProd = app()->environment('production');
        $this->getEpFailureEmailConfigs();

        // Get quote object first
        $quoteType = QuoteTypes::getName($this->quoteTypeId)->value;
        $this->quoteObject = $this->getQuoteObject($quoteType, $this->quoteId);
        $refId = $this->quoteObject?->code ?? $this->quoteObject?->uuid ?? 'Unknown';
        $subject = "❗Action Required: Embedded Product for {$epProductName} has failed for REF-ID: {$refId} – Immediate Attention Needed";

        // Generate IMCRM link based on quote
        $imcrmLink = $this->generateImcrmLink();
        $ccEmails = explode(',', str_replace(' ', '', $this->epFailureEmailConfigs[ApplicationStorageEnums::EP_FAILURE_EMAIL_CC]));
        $from = $isProd ? ['alfred@notify.insurancemarket.ae', 'InsuranceMarket.ae'] : ['alfred@testnotify.alfred.ae', 'InsuranceMarket Test'];

        return $this->subject($subject)
            ->from(...$from)
            ->to($this->epFailureEmailConfigs[ApplicationStorageEnums::EP_FAILURE_EMAIL_TO])
            ->replyTo($this->epFailureEmailConfigs[ApplicationStorageEnums::EP_FAILURE_EMAIL_REPLY_TO])
            ->cc($ccEmails)
            ->view('email.ep-booking-job-failed', [
                'refId' => $refId,
                'imcrmLink' => $imcrmLink,
                'epProductName' => $epProductName,
            ]);
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

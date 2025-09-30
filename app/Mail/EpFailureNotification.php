<?php

namespace App\Mail;

use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Models\EmbeddedProduct;
use App\Services\Logger\LoggerService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EpFailureNotification extends Mailable
{
    use Queueable, SerializesModels, GenericQueriesAllLobs;

    private int $quoteId;
    private int $quoteTypeId;
    private int $etId;
    private mixed $quoteObject = null;
    
    private string $logPrefix = 'EpFailureNotification - Mail:';


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

        $ep = EmbeddedProduct::whereHas('prices.transactions', fn($q) => $q->where('id', $this->etId))->first();
        $epProductName = $ep->product_name ?? 'Unknown';

        $isProd = app()->environment('production');
        
        // Get quote object first
        $quoteType = QuoteTypes::getName($this->quoteTypeId)->value;
        $this->quoteObject = $this->getQuoteObject($quoteType, $this->quoteId);
        $refId = $this->quoteObject?->code ?? $this->quoteObject?->uuid ?? 'Unknown';
        $subject = "❗Action Required: Embedded Product for {$epProductName} has failed for REF-ID: {$refId} – Immediate Attention Needed";

        // Generate IMCRM link based on quote
        $imcrmLink = $this->generateImcrmLink();

        if ($isProd) {
            // Production environment configuration
            return $this->subject($subject)
                ->from('alfred@notify.insurancemarket.ae', 'InsuranceMarket.ae')
                ->replyTo(['alfred@insurancemarket.ae'])
                ->to(['production.approval.team@insurancemarket.ae'])
                ->cc([
                    'dt.system.notifications@insurancemarket.ae',
                    'sic.car.team@insurancemarket.ae',
                    'diya.lekhwani@myalfred.com',
                    'rucha.keluskar@myalfred.com',
                    'sandeep.sharma@insurancemarket.ae',
                ])
                ->view('email.ep-booking-job-failed', [
                    'refId' => $refId,
                    'imcrmLink' => $imcrmLink,
                    'epProductName' => $epProductName
                ]);
        } else {
            // Non-prod environment configuration (test, uat, staging)
            return $this->subject($subject)
                ->from('alfred@testnotify.alfred.ae')
                ->replyTo(['test.emails@insurancemarket.ae'])
                ->to(['rucha.keluskar@myalfred.com', 'arsalan.mughal@myalfred.com'])
                ->cc(['diya.lekhwani@myalfred.com'])
                ->view('email.ep-booking-job-failed', [
                    'refId' => $refId,
                    'imcrmLink' => $imcrmLink,
                    'epProductName' => $epProductName
                ]);
        }
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

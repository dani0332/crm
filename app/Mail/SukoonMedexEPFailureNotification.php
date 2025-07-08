<?php

namespace App\Mail;

use App\Enums\QuoteTypeId;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SukoonMedexEPFailureNotification extends Mailable
{
    use Queueable, SerializesModels;

    private $quoteObject;
    private $quoteTypeId;
    private $fromIM;

    /**
     * Create a new message instance.
     */
    public function __construct($quoteObject, $quoteTypeId = null, $fromIM = false)
    {
        $this->quoteObject = $quoteObject;
        $this->quoteTypeId = $quoteTypeId;
        $this->fromIM = $fromIM;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $isProd = app()->environment('production');
        $refId = $this->quoteObject->code ?? $this->quoteObject->uuid ?? 'Unknown';
        $subject = "❗Action Required: Embedded Product for Medex has failed for REF-ID: {$refId} – Immediate Attention Needed";

        // Generate IMCRM link based on quote
        $imcrmLink = $this->generateImcrmLink();

        if ($isProd) {
            // Production environment configuration
            return $this->subject($subject)
                ->from('alfred@notify.insurancemarket.ae', 'InsuranceMarket.ae')
                ->replyTo(['instant@alfred.insurancemarket.ae', 'alfred@insurancemarket.ae'])
                ->to(['production.approval.team@insurancemarket.ae'])
                ->cc(['dt.system.notifications@insurancemarket.ae', 'sic.car.team@insurancemarket.ae'])
                ->view('email.sukoon-medex-ep-job-failed', [
                    'refId' => $refId,
                    'imcrmLink' => $imcrmLink,
                ]);
        } else {
            // Non-prod environment configuration (test, uat, staging, local)
            $from = $this->fromIM ? ['no-reply@notify.insurancemarket.ae', 'InsuranceMarket.ae'] : ['alfred@testnotify.alfred.ae'];

            return $this->subject($subject)
                ->from($from)
                ->replyTo(['test.emails@insurancemarket.ae'])
                ->to(['rucha.keluskar@myalfred.com', 'arsalan.mughal@myalfred.com'])
                ->cc(['diya.lekhwani@myalfred.com', 'jawad.arif@myalfred.com'])
                ->view('email.sukoon-medex-ep-job-failed', [
                    'refId' => $refId,
                    'imcrmLink' => $imcrmLink,
                ]);
        }
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
            default => '#',     // Default link (#)
        };

        return $imcrmLink;
    }
}

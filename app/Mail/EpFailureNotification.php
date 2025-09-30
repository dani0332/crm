<?php

namespace App\Mail;

use App\Models\EmbeddedProduct;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EpFailureNotification extends Mailable
{
    use Queueable, SerializesModels;

    private int $quoteId;
    private int $quoteTypeId;
    private int $etId;

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
        $ep = EmbeddedProduct::whereHas('prices.transactions', fn($q) => $q->where('id', $this->etId))->first();
        $epProductName = $ep->product_name ?? 'Unknown';

        $isProd = app()->environment('production');
        $refId = $this->quoteObject->code ?? $this->quoteObject->uuid ?? 'Unknown';
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
}

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
        $refId = $this->quoteObject->code ?? $this->quoteObject->uuid ?? 'Unknown';
        $subject = "❗Action Required: Embedded Product for Medex has failed for REF-ID: {$refId} – Immediate Attention Needed";

        // Generate IMCRM link based on quote
        $imcrmLink = $this->generateImcrmLink();

        $epFailureEmailConfigs = $this->getEpFailureEmailConfigs();
        $fromEmail = explode(',', str_replace(' ', '', $epFailureEmailConfigs[ApplicationStorageEnums::EP_FAILURE_EMAIL_FROM]));
        $toEmail = explode(',', str_replace(' ', '', $epFailureEmailConfigs[ApplicationStorageEnums::EP_FAILURE_EMAIL_TO]));
        $replyToEmail = explode(',', str_replace(' ', '', $epFailureEmailConfigs[ApplicationStorageEnums::EP_FAILURE_EMAIL_REPLY_TO]));
        $ccEmails = explode(',', str_replace(' ', '', $epFailureEmailConfigs[ApplicationStorageEnums::EP_FAILURE_EMAIL_CC]));

        return $this->subject($subject)
            ->from(...array_slice($fromEmail, 0, 2)) // for email & name
            ->to(...array_slice($toEmail, 0, 1)) // only email address
            ->replyTo(...array_slice($replyToEmail, 0, 2))
            ->cc($ccEmails) // multiple cc emails
            ->view('email.sukoon-medex-ep-job-failed', [
                'refId' => $refId,
                'imcrmLink' => $imcrmLink,
            ]);
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

        return $appStorageRecords->pluck('value', 'key_name')->toArray();
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

<?php

namespace App\Jobs\Revival;

use App\Enums\LeadSourceEnum;
use App\Enums\QuoteTypes;
use App\Facades\Capi;
use App\Models\PersonalQuote;
use App\Services\DTTRevivalService;
use App\Services\Logger\LoggerService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;

class LifeRevivalLeadsCreationJob implements ShouldQueue
{
    use Batchable, Dispatchable, GenericQueriesAllLobs, Queueable;

    private $lead = null;
    /**
     * Create a new job instance.
     */
    public function __construct($lead)
    {
        $this->lead = $lead;
        $this->onQueue('renewals');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $payload = [
            'firstName' => $this->lead->first_name,
            'lastName' => $this->lead->last_name,
            'email' => $this->lead->email,
            'mobileNo' => $this->lead->mobile_no,
            'gender' => $this->lead->gender,
            'dob' => $this->lead->dob,
            'nationalityId' => $this->lead->nationality_id,
            'paymentStatusId' => $this->lead->payment_status_id,
            'quoteStatusId' => $this->lead->quote_status_id,
            'quoteTypeId' => QuoteTypes::LIFE->id(),
            'othersInfo' => '',
            'isSmoker' => false,
            'sumInsuredValue' => (int) $this->lead->lifeQuote->sum_insured_value,
            'sumInsuredCurrencyId' => $this->lead->lifeQuote->sum_insured_currency_id,
            'maritalStatusId' => $this->lead->lifeQuote->marital_status_id,
            'purposeOfInsuranceId' => $this->lead->lifeQuote->purpose_of_insurance_id,
            'tenureOfInsuranceId' => $this->lead->lifeQuote->tenure_of_insurance_id,
            'numberOfYearsId' => $this->lead->lifeQuote->number_of_years_id,
            'source' => LeadSourceEnum::REVIVAL,
            'referenceUrl' => 'IMCRM',
            'lang' => 'EN',
            'height' => $this->lead->lifeQuote->height ?? 0,
            'weight' => $this->lead->lifeQuote->weight ?? 0,
            'typeOfInsurance' => 'Life Insurance',
            'whatsappConsent' => 1,
        ];

        $capiResponse = Capi::request('/api/v2-save-life-quote', 'post', $payload);

        if (isset($capiResponse->errors)) {
            LoggerService::error('Error Creating Life Revival Lead '.$this->lead->uuid, extra: [
                'payload' => $payload,
                'url' => '/api/v2-save-life-quote',
                'response' => $capiResponse,
            ]);

            return;
        }

        $lifeRevivalQuoteUUID = $capiResponse->quoteUID;
        LoggerService::info("New Life Revival Lead created successfully with quote UUID {$lifeRevivalQuoteUUID} from parent lead {$this->lead->uuid}");

        // Save DTT revival record
        $lifeRevivalQuote = PersonalQuote::where('uuid', $lifeRevivalQuoteUUID)->first();

        $dttRevivalService = app(DTTRevivalService::class);
        $dttRevivalService->create($lifeRevivalQuote?->id ?? 0, $lifeRevivalQuoteUUID, $this->lead->id, QuoteTypes::LIFE->id());

        // Mark life quote as revived
        $this->lead->update(['is_revived' => true]);
    }
}

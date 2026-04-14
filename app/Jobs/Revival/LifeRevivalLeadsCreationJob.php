<?php

declare(strict_types=1);

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

    public function __construct(
        private int $personalQuoteId,
    ) {
        $this->onQueue('renewals');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $lead = PersonalQuote::query()
            ->with('lifeQuote')
            ->find($this->personalQuoteId);

        if ($lead === null || $lead->lifeQuote === null) {
            LoggerService::error('Life revival job: lead or life quote not found', extra: [
                'personal_quote_id' => $this->personalQuoteId,
            ]);

            return;
        }

        $payload = [
            'firstName' => $lead->first_name,
            'lastName' => $lead->last_name,
            'email' => $lead->email,
            'mobileNo' => $lead->mobile_no ?? null,
            'gender' => $lead->gender ?? '',
            'dob' => $lead->dob ?? null,
            'nationalityId' => $lead->nationality_id ?? 0,
            'paymentStatusId' => $lead->payment_status_id ?? 0,
            'quoteStatusId' => $lead->quote_status_id ?? 0,
            'quoteTypeId' => QuoteTypes::LIFE->id(),
            'othersInfo' => '',
            'isSmoker' => false,
            'sumInsuredValue' => (int) $lead->lifeQuote->sum_insured_value,
            'sumInsuredCurrencyId' => $lead->lifeQuote->sum_insured_currency_id,
            'maritalStatusId' => $lead->lifeQuote->marital_status_id,
            'purposeOfInsuranceId' => $lead->lifeQuote->purpose_of_insurance_id ?? 0,
            'tenureOfInsuranceId' => $lead->lifeQuote->tenure_of_insurance_id ?? 0,
            'numberOfYearsId' => $lead->lifeQuote->number_of_years_id ?? 0,
            'source' => LeadSourceEnum::REVIVAL,
            'referenceUrl' => 'IMCRM',
            'lang' => 'EN',
            'height' => $lead->lifeQuote->height ?? 0,
            'weight' => $lead->lifeQuote->weight ?? 0,
            'typeOfInsurance' => 'Life Insurance',
            'whatsappConsent' => 1,
        ];

        $capiResponse = Capi::request('/api/v2-save-life-quote', 'post', $payload);

        if (isset($capiResponse->errors)) {
            LoggerService::error('Error Creating Life Revival Lead '.$lead->uuid, extra: [
                'payload' => $payload,
                'url' => '/api/v2-save-life-quote',
                'response' => $capiResponse,
            ]);

            return;
        }

        $lifeRevivalQuoteUUID = $capiResponse->quoteUID;
        LoggerService::info("New Life Revival Lead created successfully with quote UUID {$lifeRevivalQuoteUUID} from parent lead {$lead->uuid}");

        // Save DTT revival record
        $lifeRevivalQuote = PersonalQuote::where('uuid', $lifeRevivalQuoteUUID)->first();

        $dttRevivalService = app(DTTRevivalService::class);
        $dttRevivalService->create($lifeRevivalQuote?->id ?? 0, $lifeRevivalQuoteUUID, $lead->id, QuoteTypes::LIFE->id());
        LoggerService::info("DTT Revival record created successfully for quote UUID {$lifeRevivalQuoteUUID} from parent lead {$lead->uuid}");

        // Mark life quote as revived
        $lead->update(['is_revived' => true]);
        LoggerService::info("Life quote marked as revived for lead {$lead->uuid}");
    }
}

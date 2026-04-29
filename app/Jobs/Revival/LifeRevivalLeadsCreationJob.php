<?php

declare(strict_types=1);

namespace App\Jobs\Revival;

use App\Enums\LeadSourceEnum;
use App\Enums\QuoteTypes;
use App\Facades\Capi;
use App\Models\PersonalQuote;
use App\Services\DTTRevivalService;
use App\Services\LifeRevivalService;
use App\Services\Logger\LoggerService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\DB;
use Throwable;

class LifeRevivalLeadsCreationJob implements ShouldQueue
{
    use Batchable, GenericQueriesAllLobs, Queueable;

    public $tries = 3;
    public $timeout = 90;
    public $backoff = 300;
    protected $lead = null;

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
        $this->lead = PersonalQuote::query()
            ->with('lifeQuote')
            ->where('is_revived', false)
            ->find($this->personalQuoteId);

        $lead = $this->lead;

        if ($lead === null || $lead->lifeQuote === null) {
            LoggerService::info('Life revival job: lead or life quote not found', [
                'personal_quote_id' => $this->personalQuoteId,
            ]);

            return;
        }

        $lifeQuote = $lead->lifeQuote;

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
            'isSmoker' => $lifeQuote->is_smoker,
            'sumInsuredValue' => (int) $lifeQuote->sum_insured_value,
            'sumInsuredCurrencyId' => $lifeQuote->sum_insured_currency_id,
            'maritalStatusId' => $lifeQuote->marital_status_id,
            'purposeOfInsuranceId' => $lifeQuote->purpose_of_insurance_id ?? 0,
            'tenureOfInsuranceId' => $lifeQuote->tenure_of_insurance_id ?? 0,
            'numberOfYearsId' => $lifeQuote->number_of_years_id ?? 0,
            'source' => LeadSourceEnum::REVIVAL,
            'referenceUrl' => 'IMCRM',
            'lang' => 'EN',
            'typeOfInsurance' => 'Life Insurance',
            'whatsappConsent' => 1,
        ];

        if ($lifeQuote->height !== null) {
            $payload['height'] = $lifeQuote->height;
        }

        if ($lifeQuote->weight !== null) {
            $payload['weight'] = $lifeQuote->weight;
        }

        LoggerService::info('Creating Life Revival Lead', [
            'lead_uuid' => $lead->uuid,
            'payload' => $payload,
        ]);
        $capiResponse = Capi::request('/api/v2-save-life-quote', 'post', $payload);

        if (isset($capiResponse->errors)) {
            LoggerService::error('Error creating Life Revival Lead from CAPI response', [
                'lead_uuid' => $lead->uuid,
                'payload' => $payload,
                'url' => '/api/v2-save-life-quote',
                'response' => $capiResponse,
            ]);

            return;
        }

        try {
            app(LifeRevivalService::class)->sendLifeRevialEmail($capiResponse->quoteUID);
        } catch (Throwable $e) {
            LoggerService::warning('LifeRevivalLeadsCreationJob - Error sending DTT revival email', [
                'quote_uuid' => $capiResponse->quoteUID,
                'lead_uuid' => $lead->uuid,
                'personal_quote_id' => $this->personalQuoteId,
            ], $e);
        }

        $lifeRevivalQuoteUUID = $capiResponse->quoteUID;
        LoggerService::info('New Life Revival Lead created successfully', [
            'quote_uuid' => $lifeRevivalQuoteUUID,
            'parent_lead_uuid' => $lead->uuid,
        ]);

        DB::transaction(function () use ($lifeRevivalQuoteUUID, $lead) {
            // Get the life revival quote
            $lifeRevivalQuote = PersonalQuote::where('uuid', $lifeRevivalQuoteUUID)->first();
            LoggerService::info('Life revival child quote resolved', [
                'life_revival_quote_id' => $lifeRevivalQuote?->id,
                'quote_uuid' => $lifeRevivalQuoteUUID,
            ]);

            // Save DTT revival record
            $dttRevivalService = app(DTTRevivalService::class);
            $dttRevivalService->create($lifeRevivalQuote?->id ?? 0, $lifeRevivalQuoteUUID, $lead->id, QuoteTypes::LIFE->id());
            LoggerService::info('DTT Revival record created successfully', [
                'quote_uuid' => $lifeRevivalQuoteUUID,
                'parent_lead_uuid' => $lead->uuid,
            ]);

            // Mark life quote as revived
            $lead->update(['is_revived' => true]);
            LoggerService::info('Life quote marked as revived', [
                'lead_uuid' => $lead->uuid,
            ]);
        });
    }

    public function middleware(): array
    {
        return [(new WithoutOverlapping($this->personalQuoteId))->dontRelease()];
    }

    public function failed(Throwable $exception): void
    {
        LoggerService::error('LifeRevivalLeadsCreationJob failed', [
            'personal_quote_id' => $this->personalQuoteId,
        ], $exception);
    }
}

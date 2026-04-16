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
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\DB;
use Throwable;

class LifeRevivalLeadsCreationJob implements ShouldQueue
{
    use Batchable, Dispatchable, GenericQueriesAllLobs, Queueable;

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

        if ($this->lead === null || $this->lead->lifeQuote === null) {
            LoggerService::info('Life revival job: lead or life quote not found', extra: [
                'personal_quote_id' => $this->personalQuoteId,
            ]);

            return;
        }

        $payload = [
            'firstName' => $this->lead->first_name,
            'lastName' => $this->lead->last_name,
            'email' => $this->lead->email,
            'mobileNo' => $this->lead->mobile_no ?? null,
            'gender' => $this->lead->gender ?? '',
            'dob' => $this->lead->dob ?? null,
            'nationalityId' => $this->lead->nationality_id ?? 0,
            'paymentStatusId' => $this->lead->payment_status_id ?? 0,
            'quoteStatusId' => $this->lead->quote_status_id ?? 0,
            'quoteTypeId' => QuoteTypes::LIFE->id(),
            'othersInfo' => '',
            'isSmoker' => $this->lead->lifeQuote->is_smoker,
            'sumInsuredValue' => (int) $this->lead->lifeQuote->sum_insured_value,
            'sumInsuredCurrencyId' => $this->lead->lifeQuote->sum_insured_currency_id,
            'maritalStatusId' => $this->lead->lifeQuote->marital_status_id,
            'purposeOfInsuranceId' => $this->lead->lifeQuote->purpose_of_insurance_id ?? 0,
            'tenureOfInsuranceId' => $this->lead->lifeQuote->tenure_of_insurance_id ?? 0,
            'numberOfYearsId' => $this->lead->lifeQuote->number_of_years_id ?? 0,
            'source' => LeadSourceEnum::REVIVAL,
            'referenceUrl' => 'IMCRM',
            'lang' => 'EN',
            'height' => $this->lead->lifeQuote->height ?? 0,
            'weight' => $this->lead->lifeQuote->weight ?? 0,
            'typeOfInsurance' => 'Life Insurance',
            'whatsappConsent' => 1,
        ];

        LoggerService::info("Creating Life Revival Lead for lead {$this->lead->uuid}", extra: [
            'payload' => $payload,
        ]);
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

        DB::transaction(function () use ($lifeRevivalQuoteUUID) {
            // Get the life revival quote
            $lifeRevivalQuote = PersonalQuote::where('uuid', $lifeRevivalQuoteUUID)->first();
            LoggerService::info("Life Revival QuoteId: {$lifeRevivalQuote?->id}");

            // Save DTT revival record
            $dttRevivalService = app(DTTRevivalService::class);
            $dttRevivalService->create($lifeRevivalQuote?->id ?? 0, $lifeRevivalQuoteUUID, $this->lead->id, QuoteTypes::LIFE->id());
            LoggerService::info("DTT Revival record created successfully for quote UUID {$lifeRevivalQuoteUUID} from parent lead {$this->lead->uuid}");

            // Mark life quote as revived
            $this->lead->update(['is_revived' => true]);
            LoggerService::info("Life quote marked as revived for lead {$this->lead->uuid}");
        });
    }

    public function middleware()
    {
        return [(new WithoutOverlapping($this->personalQuoteId))->dontRelease()];
    }

    public function failed(Throwable $exception)
    {
        LoggerService::error('LifeRevivalLeadsCreationJob - Failed - '.$this->personalQuoteId.' Error: '.$exception->getMessage());
    }
}

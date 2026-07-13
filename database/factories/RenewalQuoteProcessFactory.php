<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\FetchPlansStatuses;
use App\Enums\QuoteTypeShortCode;
use App\Enums\RenewalProcessStatuses;
use App\Enums\RenewalsUploadType;
use App\Models\RenewalQuoteProcess;
use App\Models\RenewalsUploadLeads;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RenewalQuoteProcess>
 */
class RenewalQuoteProcessFactory extends Factory
{
    protected $model = RenewalQuoteProcess::class;

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (RenewalQuoteProcess $process) {
            if (app()->environment('testing')) {
                $process->setConnection('sqlite');
            }
        });
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'renewals_upload_lead_id' => RenewalsUploadLeads::factory(),
            'quote_type' => QuoteTypeShortCode::CAR,
            'status' => RenewalProcessStatuses::PLANS_FETCHED,
            'type' => RenewalsUploadType::UPDATE_LEADS,
            'fetch_plans_status' => FetchPlansStatuses::FETCHED,
            'email_sent' => false,
            'data' => [],
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function forLead(int $leadId): self
    {
        return $this->state(fn () => ['renewals_upload_lead_id' => $leadId]);
    }

    public function forQuote(?int $quoteId): self
    {
        return $this->state(fn () => ['quote_id' => $quoteId]);
    }

    public function withQuoteType(string $quoteType): self
    {
        return $this->state(fn () => ['quote_type' => $quoteType]);
    }

    public function processed(): self
    {
        return $this->state(fn () => ['status' => RenewalProcessStatuses::PROCESSED]);
    }

    public function badData(): self
    {
        return $this->state(fn () => ['status' => RenewalProcessStatuses::BAD_DATA]);
    }

    public function withFetchStatus(string $status): self
    {
        return $this->state(fn () => ['fetch_plans_status' => $status]);
    }

    public function withData(array $data): self
    {
        return $this->state(fn () => ['data' => $data]);
    }
}

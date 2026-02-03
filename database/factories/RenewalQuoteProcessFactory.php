<?php

namespace Database\Factories;

use App\Enums\FetchPlansStatuses;
use App\Enums\RenewalProcessStatuses;
use App\Enums\RenewalsUploadType;
use App\Models\RenewalQuoteProcess;
use Illuminate\Database\Eloquent\Factories\Factory;

class RenewalQuoteProcessFactory extends Factory
{
    protected $model = RenewalQuoteProcess::class;

    public function definition(): array
    {
        return [
            'renewals_upload_lead_id' => 1,
            'quote_id' => null,
            'policy_number' => null,
            'batch' => null,
            'renewal_batch_id' => null,
            'quote_type' => 'CAR',
            'status' => RenewalProcessStatuses::NEW,
            'type' => RenewalsUploadType::UPDATE_LEADS,
            'fetch_plans_status' => FetchPlansStatuses::PENDING,
            'data' => [],
            'validation_errors' => null,
            'step_errors' => null,
            'step' => null,
            'retry_count' => null,
            'last_step_attempted' => null,
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

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
}

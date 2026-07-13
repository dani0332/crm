<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ProcessStatusCode;
use App\Enums\QuoteTypeShortCode;
use App\Enums\RenewalsUploadType;
use App\Models\RenewalsUploadLeads;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RenewalsUploadLeads>
 */
class RenewalsUploadLeadsFactory extends Factory
{
    protected $model = RenewalsUploadLeads::class;

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (RenewalsUploadLeads $uploadLead) {
            if (app()->environment('testing')) {
                $uploadLead->setConnection('sqlite');
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
            'file_name' => $this->faker->word().'.xlsx',
            'file_path' => 'test/'.$this->faker->uuid().'.xlsx',
            'quote_type' => QuoteTypeShortCode::CAR,
            'status' => 'IN_PROGRESS',
            'renewal_import_type' => RenewalsUploadType::UPDATE_LEADS,
            'is_sic' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function forQuoteType(string $quoteType): self
    {
        return $this->state(fn () => ['quote_type' => $quoteType]);
    }

    public function completed(): self
    {
        return $this->state(fn () => ['status' => ProcessStatusCode::COMPLETED]);
    }
}

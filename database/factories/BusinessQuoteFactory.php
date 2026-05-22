<?php

namespace Database\Factories;

use App\Enums\BusinessTypeOfInsuranceIdEnum;
use App\Enums\EmirateEnum;
use App\Enums\QuoteStatusEnum;
use App\Models\BusinessQuote;
use Illuminate\Database\Eloquent\Factories\Factory;

class BusinessQuoteFactory extends Factory
{
    protected $model = BusinessQuote::class;

    public function configure(): static
    {
        return $this->afterMaking(function (BusinessQuote $quote) {
            if (app()->environment('testing')) {
                $quote->setConnection('sqlite');
            }
        });
    }

    public function definition(): array
    {
        $uuid = $this->faker->unique()->uuid();

        return [
            'uuid' => $uuid,
            'code' => 'BUS-'.strtoupper(substr($uuid, 0, 8)),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'mobile_no' => '+971'.$this->faker->numerify('#########'),
            'source' => 'IMCRM',
            'quote_status_id' => QuoteStatusEnum::NewLead,
            'business_type_of_insurance_id' => null,
            'emirate_of_registration_id' => null,
            'advisor_id' => null,
            'is_branch_applicable' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /** Set quote as Group Medical business type. */
    public function groupMedical(): static
    {
        return $this->state(fn (array $attributes) => [
            'business_type_of_insurance_id' => BusinessTypeOfInsuranceIdEnum::GROUP_MEDICAL,
        ]);
    }

    /** Set a specific emirate_of_registration_id. */
    public function withEmirate(int $emirateId): static
    {
        return $this->state(fn (array $attributes) => [
            'emirate_of_registration_id' => $emirateId,
        ]);
    }

    /** Shorthand: GM quote with Abu Dhabi emirate. */
    public function groupMedicalAbuDhabi(): static
    {
        return $this->groupMedical()->withEmirate(EmirateEnum::ABU_DHABI);
    }

    /** Mark quote as policy-booked. */
    public function policyBooked(): static
    {
        return $this->state(fn (array $attributes) => [
            'quote_status_id' => QuoteStatusEnum::PolicyBooked,
        ]);
    }

    /** Enable branch allocation. */
    public function branchApplicable(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_branch_applicable' => 1,
        ]);
    }
}

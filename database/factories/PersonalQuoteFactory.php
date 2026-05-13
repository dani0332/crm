<?php

namespace Database\Factories;

use App\Enums\DocumentTypeCode;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteTypeId;
use App\Models\Customer;
use App\Models\CyberQuote;
use App\Models\Emirate;
use App\Models\Nationality;
use App\Models\Payment;
use App\Models\PaymentSplits;
use App\Models\PersonalQuote;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PersonalQuoteFactory extends Factory
{
    protected $model = PersonalQuote::class;

    public function definition(): array
    {
        return [
            'uuid' => Str::upper(Str::random(6)),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'mobile_no' => $this->faker->numerify('05########'),
            'policy_number' => 'POL'.Str::upper(Str::random(5)),
            'policy_start_date' => now()->toDateString(),
            'policy_expiry_date' => now()->addYear()->toDateString(),
            'policy_issuance_date' => now()->toDateString(),
            'policy_issuance_status_id' => 1,
            'price_vat_applicable' => 1000,
            'price_with_vat' => 1100,
            'vat' => 100,
            'insurer_quote_number' => 'AWNIC-'.Str::upper(Str::random(4)),
            'quote_status_id' => 1,
            'quote_status_date' => now(),
        ];
    }

    public function createForSqlite(array $attributes = []): PersonalQuote
    {
        return PersonalQuote::query()->create(array_merge([
            'uuid' => 'PQ-'.Str::upper(Str::random(8)),
            'code' => 'CAR-'.Str::upper(Str::random(8)),
            'quote_type_id' => QuoteTypeId::Car,
            'customer_id' => Customer::factory()->create()->id,
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->safeEmail(),
            'mobile_no' => $this->faker->numerify('05########'),
            'advisor_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));
    }

    /**
     * Define the model's cyber quote state.
     */
    public function cyberQuote(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'CYB-'.$attributes['uuid'],
            'quote_type_id' => QuoteTypeId::Cyber,
        ]);
    }

    public function withCyberDependencies(): static
    {
        return $this->cyberQuote()->afterCreating(function (PersonalQuote $quote) {
            $nationality = Nationality::factory()->state([
                'text' => 'United Arab Emirates',
                'code' => 'AE',
                'is_active' => true,
            ])->create();

            $customer = Customer::withoutEvents(function () use ($quote, $nationality) {
                return Customer::factory()->create([
                    'first_name' => $quote->first_name,
                    'last_name' => $quote->last_name,
                    'email' => $quote->email,
                    'mobile_no' => $quote->mobile_no,
                    'dob' => '1990-01-01',
                    'nationality_id' => $nationality->id,
                ]);
            });

            $quote->update([
                'customer_id' => $customer->id,
                'nationality_id' => $nationality->id,
            ]);

            $emirate = Emirate::factory()->state([
                'text' => 'Dubai',
                'is_active' => true,
            ])->create();

            CyberQuote::factory()->create([
                'personal_quote_id' => $quote->id,
                'emirate_of_registration_id' => $emirate->id,
                'coverage_id' => 1,
            ]);

            $payment = Payment::factory()->cyberPayment($quote->code, $quote->id)->create();

            PaymentSplits::factory()->cyberPaymentSplit($payment->code)->create();

            $quote->setRelation('cyberPlanDetail', (object) [
                'coverage' => 500000,
                'planName' => 'Gold Plan',
            ]);

            $quote->documents()->create([
                'document_type_code' => DocumentTypeCode::CYB_EID,
                'doc_name' => 'EmiratesId.pdf',
                'doc_url' => 'documents/eid.pdf',
                'doc_mime_type' => 'application/pdf',
            ]);
        });
    }

    /**
     * Cyber quote with payment authorized 24+ hours ago and no documents.
     * Used for testing hasPaymentAuthorizedWithNoDocuments allocation flow.
     *
     * @param  Carbon|null  $authorizedAt  Override for testing (e.g. now()->subHours(12) for "less than 24h" case)
     */
    public function paymentAuthorizedWithNoDocuments(?Carbon $authorizedAt = null): static
    {
        $authorizedAt = $authorizedAt ?? now()->subHours(24);

        return $this->cyberQuote()->afterCreating(function (PersonalQuote $quote) use ($authorizedAt) {
            $nationality = Nationality::factory()->state([
                'text' => 'United Arab Emirates',
                'code' => 'AE',
                'is_active' => true,
            ])->create();

            $customer = Customer::withoutEvents(function () use ($quote, $nationality) {
                return Customer::factory()->create([
                    'first_name' => $quote->first_name,
                    'last_name' => $quote->last_name,
                    'email' => $quote->email,
                    'mobile_no' => $quote->mobile_no,
                    'dob' => '1990-01-01',
                    'nationality_id' => $nationality->id,
                ]);
            });

            $quote->update([
                'customer_id' => $customer->id,
                'nationality_id' => $nationality->id,
                'payment_status_id' => PaymentStatusEnum::AUTHORISED,
            ]);

            $emirate = Emirate::factory()->state([
                'text' => 'Dubai',
                'is_active' => true,
            ])->create();

            CyberQuote::factory()->create([
                'personal_quote_id' => $quote->id,
                'emirate_of_registration_id' => $emirate->id,
                'coverage_id' => 1,
            ]);

            $payment = Payment::factory()->cyberPayment($quote->code, $quote->id)->create([
                'authorized_at' => $authorizedAt,
                'payment_status_id' => PaymentStatusEnum::AUTHORISED,
            ]);

            PaymentSplits::factory()->cyberPaymentSplit($payment->code)->create([
                'authorized_at' => $authorizedAt,
                'payment_status_id' => PaymentStatusEnum::AUTHORISED,
            ]);

            $quote->setRelation('cyberPlanDetail', (object) [
                'coverage' => 500000,
                'planName' => 'Gold Plan',
            ]);
            // Intentionally no documents - tests hasPaymentAuthorizedWithNoDocuments flow
        });
    }
}

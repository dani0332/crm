<?php

namespace Database\Factories;

use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteTypeId;
use App\Models\CarQuote;
use App\Models\EmbeddedProductOption;
use App\Models\EmbeddedTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;

class EmbeddedTransactionFactory extends Factory
{
    protected $model = EmbeddedTransaction::class;

    /**
     * Configure the model factory.
     */
    public function configure()
    {
        return $this->afterMaking(function (EmbeddedTransaction $transaction) {
            if (app()->environment('testing')) {
                $transaction->setConnection('sqlite');
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
        $code = 'ET-'.strtoupper($this->faker->unique()->regexify('[A-Z0-9]{8}'));

        return [
            'code' => $code,
            'quote_request_id' => null,
            'quote_request_type' => null,
            'quote_type_id' => QuoteTypeId::Car,
            'product_id' => EmbeddedProductOption::factory(),
            'is_selected' => false,
            'payment_status_id' => PaymentStatusEnum::DRAFT,
            'policy_status' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the transaction is linked to a car quote (morph).
     */
    public function forCarQuote(CarQuote $carQuote): static
    {
        return $this->state(fn (array $attributes) => [
            'quote_request_id' => $carQuote->id,
            'quote_request_type' => CarQuote::class,
            'quote_type_id' => QuoteTypeId::Car,
        ]);
    }

    /**
     * Indicate that the transaction belongs to a specific embedded product option.
     */
    public function forProduct(int $embeddedProductOptionId): static
    {
        return $this->state(fn (array $attributes) => [
            'product_id' => $embeddedProductOptionId,
        ]);
    }

    /**
     * Indicate that the transaction has draft payment status.
     */
    public function nonDraft(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status_id' => Arr::random([PaymentStatusEnum::AUTHORISED, PaymentStatusEnum::CAPTURED]),
        ]);
    }

    /**
     * Indicate that the transaction is selected.
     */
    public function selected(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_selected' => true,
        ]);
    }
}

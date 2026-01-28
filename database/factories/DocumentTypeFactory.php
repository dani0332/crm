<?php

namespace Database\Factories;

use App\Enums\QuoteTypeId;
use App\Models\DocumentType;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentTypeFactory extends Factory
{
    protected $model = DocumentType::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper($this->faker->unique()->lexify('DOC_???')),
            'text' => $this->faker->words(3, true),
            'is_active' => 1,
            'receive_from_customer' => 1,
            'quote_type_id' => QuoteTypeId::CompanyCar,
            'registration_type' => null,
            'vehicle_use' => null,
            'sort_order' => $this->faker->numberBetween(1, 100),
        ];
    }

    /**
     * Indicate that the document type is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => 0,
        ]);
    }

    /**
     * Indicate that the document type should be received from customer.
     */
    public function receivedFromCustomer(): static
    {
        return $this->state(fn (array $attributes) => [
            'receive_from_customer' => 1,
        ]);
    }

    /**
     * Indicate that the document type should be sent to customer.
     */
    public function sentToCustomer(): static
    {
        return $this->state(fn (array $attributes) => [
            'send_to_customer' => 1,
        ]);
    }

    /**
     * Indicate that the document type is for a specific quote type.
     */
    public function forQuoteType($quoteTypeId): static
    {
        return $this->state(fn (array $attributes) => [
            'quote_type_id' => $quoteTypeId,
        ]);
    }

    /**
     * Indicate that the document type is for a specific registration type.
     */
    public function forRegistrationType($registrationType): static
    {
        return $this->state(fn (array $attributes) => [
            'registration_type' => $registrationType,
        ]);
    }

    /**
     * Indicate that the document type is for a specific vehicle use.
     */
    public function forVehicleUse($vehicleUse): static
    {
        return $this->state(fn (array $attributes) => [
            'vehicle_use' => $vehicleUse,
        ]);
    }
}

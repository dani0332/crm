<?php

namespace Database\Factories;

use App\Enums\EmbeddedProductEnum;
use App\Models\EmbeddedProduct;
use App\Models\InsuranceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmbeddedProductFactory extends Factory
{
    protected $model = EmbeddedProduct::class;

    /**
     * Configure the model factory.
     */
    public function configure()
    {
        return $this->afterMaking(function (EmbeddedProduct $embeddedProduct) {
            if (app()->environment('testing')) {
                $embeddedProduct->setConnection('sqlite');
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
            'insurance_provider_id' => InsuranceProvider::factory(),
            'short_code' => $this->faker->unique()->regexify('[A-Z]{3,5}'),
            'product_name' => $this->faker->words(3, true),
            'display_name' => $this->faker->words(2, true),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the embedded product belongs to a specific insurance provider.
     */
    public function forInsuranceProvider(int $insuranceProviderId): static
    {
        return $this->state(fn (array $attributes) => [
            'insurance_provider_id' => $insuranceProviderId,
        ]);
    }

    /**
     * Indicate that the embedded product has the MDX short code (retargeting allowed).
     */
    public function mdx(): static
    {
        return $this->state(fn (array $attributes) => [
            'short_code' => EmbeddedProductEnum::MDX,
        ]);
    }

    /**
     * Indicate that the embedded product has the RDX short code.
     */
    public function rdx(): static
    {
        return $this->state(fn (array $attributes) => [
            'short_code' => EmbeddedProductEnum::RDX,
        ]);
    }

    /**
     * Indicate that the embedded product has the ECB short code (retargeting allowed).
     */
    public function ecb(): static
    {
        return $this->state(fn (array $attributes) => [
            'short_code' => EmbeddedProductEnum::ECB,
        ]);
    }

    /**
     * Indicate that the embedded product has the COU short code (retargeting allowed).
     */
    public function cou(): static
    {
        return $this->state(fn (array $attributes) => [
            'short_code' => EmbeddedProductEnum::COURIER,
        ]);
    }
}

<?php

namespace Database\Factories;

use App\Models\EmbeddedProduct;
use App\Models\EmbeddedProductOption;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmbeddedProductOptionFactory extends Factory
{
    protected $model = EmbeddedProductOption::class;

    /**
     * Configure the model factory.
     */
    public function configure()
    {
        return $this->afterMaking(function (EmbeddedProductOption $option) {
            if (app()->environment('testing')) {
                $option->setConnection('sqlite');
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
            'embedded_product_id' => EmbeddedProduct::factory(),
            'price' => $this->faker->randomFloat(2, 10, 500),
            'variant' => $this->faker->word(),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the option belongs to a specific embedded product.
     */
    public function forEmbeddedProduct(int $embeddedProductId): static
    {
        return $this->state(fn (array $attributes) => [
            'embedded_product_id' => $embeddedProductId,
        ]);
    }
}

<?php

namespace Database\Factories;

use App\Models\BusinessTypeOfInsurance;
use App\Models\GenericDocument;
use App\Models\GenericDocumentType;
use Illuminate\Database\Eloquent\Factories\Factory;

class GenericDocumentFactory extends Factory
{
    protected $model = GenericDocument::class;

    public function definition()
    {
        return [
            'uuid' => $this->faker->uuid(),
            'documentable_type' => GenericDocumentType::class,
            'documentable_id' => 1, // Will be overridden in tests
            'quote_type_id' => null,
            'name' => $this->faker->word().'.pdf',
            'path' => 'documents/claims/'.$this->faker->word().'.pdf',
            'mime_type' => 'application/pdf',
            'created_by_id' => null,
            'insurance_provider_id' => null,
            'business_type_of_insurance_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function forHome()
    {
        return $this->state(function (array $attributes) {
            return [
                'quote_type_id' => 2, // Home
            ];
        });
    }

    public function forTravel()
    {
        return $this->state(function (array $attributes) {
            return [
                'quote_type_id' => 8, // Travel
            ];
        });
    }

    public function withBusinessType()
    {
        return $this->state(function (array $attributes) {
            return [
                'business_type_of_insurance_id' => BusinessTypeOfInsurance::factory(),
            ];
        });
    }
}

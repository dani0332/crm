<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DocumentTypeCode;
use App\Models\HealthQuote;
use App\Models\QuoteDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuoteDocumentFactory extends Factory
{
    protected $model = QuoteDocument::class;

    public function definition(): array
    {
        return [
            'quote_documentable_type' => HealthQuote::class,
            'quote_documentable_id' => HealthQuote::factory(),
            'document_type_code' => fake()->randomElement([
                DocumentTypeCode::HEA_EID,
                DocumentTypeCode::HEA_VISA,
                DocumentTypeCode::HEA_PAS,
            ]),
            'doc_url' => '/test/documents/'.uniqid().'.pdf',
            'doc_name' => 'test_document_'.uniqid().'.pdf',
            'original_name' => fake()->words(2, true).'.pdf',
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function emiratesId(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'document_type_code' => DocumentTypeCode::HEA_EID,
                'original_name' => 'Emirates_ID.pdf',
            ];
        });
    }

    public function visa(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'document_type_code' => DocumentTypeCode::HEA_VISA,
                'original_name' => 'Visa.pdf',
            ];
        });
    }

    public function passport(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'document_type_code' => DocumentTypeCode::HEA_PAS,
                'original_name' => 'Passport.pdf',
            ];
        });
    }
}

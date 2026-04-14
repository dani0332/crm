<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DocumentTypeCode;
use App\Models\CarQuote;
use App\Models\HealthQuote;
use App\Models\QuoteDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuoteDocumentFactory extends Factory
{
    protected $model = QuoteDocument::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
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

    /**
     * Default attributes for a motor (CarQuote) document row.
     */
    public function carQuote(): static
    {
        return $this->state(fn (array $attributes) => [
            'quote_documentable_type' => CarQuote::class,
            'quote_documentable_id' => null,
            'document_type_code' => DocumentTypeCode::REGISTRATION_CARD_MULKIYA,
            'doc_name' => $this->faker->uuid().'.pdf',
            'doc_url' => 'documents/'.$this->faker->uuid().'.pdf',
            'doc_mime_type' => 'application/pdf',
            'doc_uuid' => $this->faker->uuid(),
            'original_name' => 'document.pdf',
            'is_ocr_processed' => false,
        ]);
    }

    /**
     * Indicate that the document is OCR processed.
     */
    public function ocrProcessed(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_ocr_processed' => true,
        ]);
    }

    /**
     * Indicate that the document is for a specific quote.
     */
    public function forQuote($quoteId, $quoteType = CarQuote::class): static
    {
        return $this->state(fn (array $attributes) => [
            'quote_documentable_type' => $quoteType,
            'quote_documentable_id' => $quoteId,
        ]);
    }

    /**
     * Indicate that the document is of a specific type.
     */
    public function ofType(string $documentTypeCode): static
    {
        return $this->state(fn (array $attributes) => [
            'document_type_code' => $documentTypeCode,
        ]);
    }

    /**
     * Indicate that the document is a driver Emirates ID.
     */
    public function driverEmiratesId(): static
    {
        return $this->state(fn (array $attributes) => [
            'document_type_code' => DocumentTypeCode::DRIVER_EMIRATES_ID,
        ]);
    }

    /**
     * Indicate that the document is a driving license.
     */
    public function drivingLicense(): static
    {
        return $this->state(fn (array $attributes) => [
            'document_type_code' => DocumentTypeCode::DRIVING_LICENSE,
        ]);
    }

    /**
     * Indicate that the document is a mulkiya.
     */
    public function mulkiya(): static
    {
        return $this->state(fn (array $attributes) => [
            'document_type_code' => DocumentTypeCode::REGISTRATION_CARD_MULKIYA,
        ]);
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

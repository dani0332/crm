<?php

namespace Database\Factories;

use App\Enums\DocumentTypeCode;
use App\Models\CarQuote;
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
            'quote_documentable_type' => CarQuote::class,
            'quote_documentable_id' => null,
            'document_type_code' => DocumentTypeCode::REGISTRATION_CARD_MULKIYA,
            'doc_name' => $this->faker->uuid().'.pdf',
            'doc_url' => 'documents/'.$this->faker->uuid().'.pdf',
            'doc_mime_type' => 'application/pdf',
            'doc_uuid' => $this->faker->uuid(),
            'original_name' => 'document.pdf',
            'is_ocr_processed' => false,
        ];
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
}

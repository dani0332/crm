<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\GenericDocumentTypeCode;
use App\Models\GenericDocumentType;
use Illuminate\Database\Seeder;

/**
 * Generic Document Types Seeder
 *
 * Seeds the generic_document_types table with common document types
 * that can be used across different modules for document categorization.
 */
class GenericDocumentTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $genericDocumentTypes = [
            [
                'code' => GenericDocumentTypeCode::CLAIM_FORM->value,
                'text' => 'Claim form',
                'description' => 'download and upload your signed and completed claim form.',
            ],
        ];

        foreach ($genericDocumentTypes as $documentType) {
            GenericDocumentType::firstOrCreate(
                ['code' => $documentType['code']],
                $documentType
            );
        }
    }
}

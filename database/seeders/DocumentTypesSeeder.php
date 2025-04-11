<?php

namespace Database\Seeders;

use App\Enums\DocumentTypeCode;
use App\Models\DocumentType;
use Illuminate\Database\Seeder;

class DocumentTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $quoteDocuments = [
            [
                'code' => 'HOME_SAL',
                'text' => 'Home SAL Declaration',
                'is_active' => 1,
                'quote_type_id' => 2,
                'folder_path' => 'home_sal',
                'accepted_files' => '.pdf',
                'max_files' => 5,
                'max_size' => 5,
                'is_required' => 0,
                'category' => DocumentTypeCode::QUOTE,
            ],
        ];

        foreach ($quoteDocuments as $document) {
            DocumentType::firstOrCreate(
                ['code' => $document['code']],
                $document
            );
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use Illuminate\Database\Seeder;

class DocumentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $quoteDocuments = [];

        collect($quoteDocuments)->chunk(200)->each(function ($documents) {
            foreach ($documents as $document) {
                DocumentType::updateOrCreate(
                    [
                        'code' => $document['code'],
                        'quote_type_id' => $document['quote_type_id'],
                    ],
                    $document
                );
            }
        });
    }
}

<?php

namespace Database\Seeders;

use App\Enums\DocumentTypeCode;
use App\Models\DocumentType as ModelsDocumentType;
use Illuminate\Database\Seeder;

class BorDocumentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $documentTypes = [DocumentTypeCode::BAL_BIKE, DocumentTypeCode::BAL, DocumentTypeCode::BAL_HOME, DocumentTypeCode::BAL_LIFE, DocumentTypeCode::BAL_TRVL, DocumentTypeCode::BAL_HLTH, DocumentTypeCode::BAL_YCHT, DocumentTypeCode::BAL_CYCLE, DocumentTypeCode::BAL_PET, DocumentTypeCode::BAL_BS, DocumentTypeCode::BUS_BAL];

        foreach ($documentTypes as $documentType) {
            $existingDocumentType = ModelsDocumentType::where('code','like', '%' . $documentType . '%')->where('category', 'QUOTE')->get();
            foreach ($existingDocumentType as $documentType) {
                if ($documentType) {
                    $documentType->update([
                        'code' => $documentType,
                        'text' => 'Broker on Record Letter',
                        'description' => 'Please upload the BOR letter with the signature and stamp on your official company letterhead.',
                        'is_active' => 1,
                    ]);
                }
            }
        }
    }
}

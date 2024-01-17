<?php

namespace Database\Seeders;

use App\Enums\DocumentTypeCode;
use App\Models\DocumentType;
use Illuminate\Database\Seeder;

class AddNewDocumentTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $kycDocType = DocumentType::where('code', DocumentTypeCode::KYCDOC)->first();
        if (! $kycDocType) {
            \DB::table('document_types')->insert([
                'code' => DocumentTypeCode::KYCDOC,
                'text' => 'KYC Document',
                'is_active' => 1,
                'folder_path' => 'kyc',
                'accepted_files' => '.pdf',
                'max_files' => 1,
                'max_size' => 5,
                'is_required' => 0,
            ]);
        }
    }
}

<?php

namespace Database\Seeders;

use App\Enums\DocumentTypeCode;
use App\Enums\QuoteTypeId;
use App\Models\DocumentType;
use Illuminate\Database\Seeder;

class KycDocTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $docType = DocumentType::findOrCreate(DocumentTypeCode::KYCDOC);
        $docType->code = DocumentTypeCode::KYCDOC;
        $docType->text = 'KYC Document';
        $docType->is_active = 1;
        $docType->folder_path = 'kyc';
        $docType->accepted_files = '.pdf';
        $docType->max_files = 1;
        $docType->max_size = 5;
        $docType->is_required = 0;
        $docType->save();
    }
}

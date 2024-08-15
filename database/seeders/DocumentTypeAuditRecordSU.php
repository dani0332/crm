<?php

namespace Database\Seeders;

use App\Enums\DocumentTypeCategory;
use App\Enums\DocumentTypeCode;
use App\Enums\DocumentTypeEnum;
use App\Models\DocumentType;
use Illuminate\Database\Seeder;

class DocumentTypeAuditRecordSU extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DocumentType::firstOrCreate([
            'code' => DocumentTypeCode::SEND_UPDATE_AUDIT_RECORD,
            'category' => DocumentTypeCategory::QUOTE_AND_ENDORSEMENT,
        ], [
            'text' => DocumentTypeEnum::AUDIT_RECORD,
            'is_active' => 1,
            'folder_path' => 'send-update',
            'accepted_files' => '.txt,.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',
            'max_files' => 25,
            'max_size' => 25,
            'is_required' => 0,
            'sort_order' => 14,
        ]);
    }
}

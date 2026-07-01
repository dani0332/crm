<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\DocumentTypeCode;
use App\Enums\QuoteTypeId;
use App\Models\DocumentType;
use Illuminate\Database\Seeder;

class ActivateTravelDicDocumentTypesSeeder extends Seeder
{
    public function run(): void
    {
        DocumentType::query()
            ->where('quote_type_id', QuoteTypeId::Travel)
            ->whereIn('code', [
                DocumentTypeCode::CPS_TRVL,
                DocumentTypeCode::TI,
                DocumentTypeCode::CTIRBB,
            ])
            ->update(['is_active' => 1]);
    }
}

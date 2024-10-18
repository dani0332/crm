<?php

namespace Database\Seeders;

use App\Enums\DocumentTypeCode;
use App\Enums\QuoteTypeId;
use App\Models\DocumentType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AddeTicketDocumentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $eticketsDocType = DocumentType::where('code', DocumentTypeCode::E_TICKETS)
            ->where('quote_type_id', QuoteTypeId::Travel)->first();
        if (empty($eticketsDocType)) {
            DB::table('document_types')->insert([
                'code' => DocumentTypeCode::E_TICKETS,
                'text' => 'E-Tickets',
                'max_files' => 15,
                'max_size' => 30,
                'folder_path' => 'travel',
                'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',
                'quote_type_id' => QuoteTypeId::Travel,
                'send_to_customer' => false,
                'sort_order' => 2,
                'receive_from_customer' => 1,
                'is_required' => 0,
            ]);
        }
    }
}

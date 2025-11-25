<?php

namespace Database\Seeders;

use App\Enums\DocumentTypeCode;
use App\Enums\QuoteTypeId;
use App\Models\DocumentType;
use Illuminate\Database\Seeder;

class UpdateTooltipCarDocuments extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Update tooltip for Emirates ID
        DocumentType::where('quote_type_id', QuoteTypeId::Car)
            ->where('code', DocumentTypeCode::EMIRATES_ID)
            ->where('receive_from_customer', 1)
            ->update([
                'tool_tip' => 'Upload both sides of your Emirates ID with the residence ID number visible.',
            ]);

        // Update tooltip for Driving License
        DocumentType::where('quote_type_id', QuoteTypeId::Car)
            ->where('code', DocumentTypeCode::DRIVING_LICENSE)
            ->where('receive_from_customer', 1)
            ->update([
                'tool_tip' => 'Upload the front and back of your valid UAE driving license showing the license number.',
            ]);

        // Update tooltip for Registration Card Mulkiya
        DocumentType::where('quote_type_id', QuoteTypeId::Car)
            ->where('code', DocumentTypeCode::REGISTRATION_CARD_MULKIYA)
            ->where('receive_from_customer', 1)
            ->update([
                'tool_tip' => 'Upload your Mulkiya showing plate number and car details.',
            ]);
    }
}

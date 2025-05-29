<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Enums\RolesEnum;
use App\Enums\QuoteTypes;
use App\Models\QuoteType;
use App\Enums\TeamTypeEnum;
use App\Models\QuoteStatus;
use App\Models\DocumentType;
use App\Enums\PermissionsEnum;
use App\Models\QuoteStatusMap;
use App\Enums\DocumentTypeCode;
use Illuminate\Database\Seeder;
use Database\Seeders\Traits\PermissionableSeeder;

class SavingsQuoteDataSeeder extends Seeder
{
    use PermissionableSeeder;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->upsertQuoteType();
        $this->mapQuoteStatuses();

        $this->seedRoles([RolesEnum::SavingsAdvisor, RolesEnum::SavingsManager]);
        $this->seedPermissions([
            PermissionsEnum::SAVINGS_QUOTES_LIST,
            PermissionsEnum::SAVINGS_QUOTES_CREATE,
            PermissionsEnum::SAVINGS_QUOTES_EDIT,
            PermissionsEnum::SAVINGS_QUOTES_SHOW,
            PermissionsEnum::SAVINGS_COMPREHENSIVE_DASHBOARD,
            PermissionsEnum::SAVINGS_CONVERSION_REPORT,
            PermissionsEnum::SAVINGS_DISTRIBUTION_REPORT,
            PermissionsEnum::SAVINGS_LEAD_ALLOCATION_DASHBOARD,
        ], [RolesEnum::Engineering, RolesEnum::Admin]);
        $this->product();

        $this->seedDocumentTypes();
    }

    private function upsertQuoteType()
    {
        $quoteType = [
            'short_code' => 'SAV',
            'code' => QuoteTypes::SAVINGS->value,
            'text' => 'Savings Insurance',
            'is_active' => 1,
        ];

        if (! QuoteType::where('code', $quoteType['code'])->exists()) {
            QuoteType::create($quoteType);
        } else {
            QuoteType::where('code', $quoteType['code'])->update($quoteType);
        }
    }

    private function mapQuoteStatuses()
    {
        $quoteStatuses = QuoteStatus::oldest()->get();

        $sortOrder = 0;
        $quoteStatuses->each(function ($quoteStatus) use (&$sortOrder) {
            $quoteStatusMap = QuoteStatusMap::where('quote_status_id', $quoteStatus->id)
                ->where('quote_type_id', QuoteTypes::SAVINGS->id())
                ->exists();

            if (! $quoteStatusMap) {
                $sortOrder++;
                QuoteStatusMap::create([
                    'quote_status_id' => $quoteStatus->id,
                    'quote_type_id' => QuoteTypes::SAVINGS->id(),
                    'sort_order' => $sortOrder,
                    'created_by' => 'usman.iqbal@myalfred.com',
                    'updated_by' => 'usman.iqbal@myalfred.com',
                ]);
            }
        });
    }

    private function product()
    {
        if (! Team::where('code', 'Savings')->exists()) {
            Team::create([
                'name' => 'Savings',
                'code' => 'Savings',
                'type' => TeamTypeEnum::PRODUCT,
                'is_active' => 1,
            ]);
        }
    }

    private function seedDocumentTypes()
    {
        $quoteDocuments = [

            [
                'code' => 'TI',
                'text' => 'Tax Invoice',
                'description' => null,
                'is_active' => 1,
                'quote_type_id' => QuoteTypes::SAVINGS->id(),
                'folder_path' => 'savings',
                'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',
                'max_files' => 5,
                'max_size' => 25,
                'is_required' => 1,
                'send_to_customer' => 1,
                'sort_order' => 13,
                'receive_from_customer' => 0,
                'category' => DocumentTypeCode::ISSUING_DOCUMENTS,
                'is_required_for_send_policy' => 0,
                'business_type_of_insurance_id' => null,
                'business_type_of_customer' => null,
            ],
        ];

        foreach ($quoteDocuments as $document) {
            DocumentType::firstOrCreate(
                ['code' => $document['code'], 'quote_type_id' => $document['quote_type_id']],
                $document
            );
        }
    }
}

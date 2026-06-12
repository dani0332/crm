<?php

namespace Database\Seeders;

use App\Enums\DocumentTypeCode;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Enums\TeamTypeEnum;
use App\Models\DocumentType;
use App\Models\QuoteStatus;
use App\Models\QuoteStatusMap;
use App\Models\QuoteType;
use App\Models\Team;
use Database\Seeders\Traits\PermissionableSeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

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

        $this->product();

        $this->seedDocumentTypes();

        $this->seedSavingsPermissions();
    }

    private function upsertQuoteType()
    {
        $quoteType = [
            'short_code' => 'SAV',
            'code' => QuoteTypes::SAVINGS->value,
            'text' => 'Savings',
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
        if (! Team::where('name', 'Savings')->where('type', TeamTypeEnum::PRODUCT)->exists()) {
            Team::create([
                'name' => 'Savings',
                'type' => TeamTypeEnum::PRODUCT,
                'is_active' => 1,
            ]);
        }

        if (! Team::where('name', 'Savings - Team')->where('type', TeamTypeEnum::TEAM)->exists()) {
            Team::create([
                'name' => 'Savings - Team',
                'parent_team_id' => Team::where('name', 'Savings')->value('id'),
                'type' => TeamTypeEnum::TEAM,
                'is_active' => 1,
            ]);
        }
    }

    private function seedDocumentTypes()
    {
        $quoteDocuments = [

            // Quote Documents
            [
                'code' => 'SDPDR',
                'text' => 'Discount Proof',
                'description' => null,
                'is_active' => 1,
                'quote_type_id' => QuoteTypes::SAVINGS->id(),
                'folder_path' => 'savings',
                'accepted_files' => '.png,.pdf,.jpeg,.jpg',
                'max_files' => 5,
                'max_size' => 30,
                'is_required' => 0,
                'send_to_customer' => 1,
                'sort_order' => null,
                'receive_from_customer' => 0,
                'category' => DocumentTypeCode::QUOTE,
                'is_required_for_send_policy' => 0,
                'business_type_of_insurance_id' => null,
                'business_type_of_customer' => null,
            ],
            [
                'code' => 'SPD',
                'text' => 'Payment Proof',
                'description' => null,
                'is_active' => 1,
                'quote_type_id' => QuoteTypes::SAVINGS->id(),
                'folder_path' => 'savings',
                'accepted_files' => '.png,.pdf,.jpeg,.jpg',
                'max_files' => 5,
                'max_size' => 30,
                'is_required' => 0,
                'send_to_customer' => 0,
                'sort_order' => null,
                'receive_from_customer' => 1,
                'category' => DocumentTypeCode::QUOTE,
                'is_required_for_send_policy' => 0,
                'business_type_of_insurance_id' => null,
                'business_type_of_customer' => null,
            ],
            [
                'code' => 'PP_SAV',
                'text' => 'Passport',
                'description' => '',
                'is_active' => 1,
                'quote_type_id' => QuoteTypes::SAVINGS->id(),
                'folder_path' => 'savings',
                'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',
                'max_files' => 10,
                'max_size' => 25,
                'is_required' => 0,
                'send_to_customer' => 0,
                'sort_order' => null,
                'receive_from_customer' => 1,
                'category' => DocumentTypeCode::QUOTE,
                'is_required_for_send_policy' => 0,
                'business_type_of_insurance_id' => null,
                'business_type_of_customer' => null,
            ],
            [
                'code' => 'VISA_SAV',
                'text' => 'Visa',
                'description' => '',
                'is_active' => 1,
                'quote_type_id' => QuoteTypes::SAVINGS->id(),
                'folder_path' => 'savings',
                'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',
                'max_files' => 10,
                'max_size' => 25,
                'is_required' => 0,
                'send_to_customer' => 0,
                'sort_order' => null,
                'receive_from_customer' => 1,
                'category' => DocumentTypeCode::QUOTE,
                'is_required_for_send_policy' => 0,
                'business_type_of_insurance_id' => null,
                'business_type_of_customer' => null,
            ],
            [
                'code' => 'SAV_EID',
                'text' => 'Emirates ID (both sides)',
                'description' => 'Please share a copy of your valid Emirates ID with us. Awaiting receipt of your first or renewed ID? Please share a copy of your Emirates ID application form to enable us to proceed.',
                'is_active' => 1,
                'quote_type_id' => QuoteTypes::SAVINGS->id(),
                'folder_path' => 'savings',
                'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',
                'max_files' => 10,
                'max_size' => 25,
                'is_required' => 0,
                'send_to_customer' => 0,
                'sort_order' => null,
                'receive_from_customer' => 1,
                'category' => DocumentTypeCode::QUOTE,
                'is_required_for_send_policy' => 0,
                'business_type_of_insurance_id' => null,
                'business_type_of_customer' => null,
            ],
            [
                'code' => 'TL_SAV',
                'text' => 'Trade License',
                'description' => '',
                'is_active' => 1,
                'quote_type_id' => QuoteTypes::SAVINGS->id(),
                'folder_path' => 'savings',
                'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',
                'max_files' => 10,
                'max_size' => 25,
                'is_required' => 0,
                'send_to_customer' => 0,
                'sort_order' => null,
                'receive_from_customer' => 1,
                'category' => DocumentTypeCode::QUOTE,
                'is_required_for_send_policy' => 0,
                'business_type_of_insurance_id' => null,
                'business_type_of_customer' => null,
            ],
            [
                'code' => 'Others_SAV',
                'text' => 'Others',
                'description' => '',
                'is_active' => 1,
                'quote_type_id' => QuoteTypes::SAVINGS->id(),
                'folder_path' => 'savings',
                'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',
                'max_files' => 10,
                'max_size' => 25,
                'is_required' => 0,
                'send_to_customer' => 0,
                'sort_order' => null,
                'receive_from_customer' => 1,
                'category' => DocumentTypeCode::QUOTE,
                'is_required_for_send_policy' => 0,
                'business_type_of_insurance_id' => null,
                'business_type_of_customer' => null,
            ],

            // Issuing Documents
            [
                'code' => 'PS_SAV',
                'text' => 'Policy Schedule',
                'description' => '',
                'is_active' => 1,
                'quote_type_id' => QuoteTypes::SAVINGS->id(),
                'folder_path' => 'savings',
                'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',
                'max_files' => 5,
                'max_size' => 25,
                'is_required' => 1,
                'send_to_customer' => 1,
                'sort_order' => null,
                'receive_from_customer' => 0,
                'category' => DocumentTypeCode::ISSUING_DOCUMENTS,
                'is_required_for_send_policy' => 1,
                'business_type_of_insurance_id' => null,
                'business_type_of_customer' => null,
            ],
            [
                'code' => 'PC_SAV',
                'text' => 'Policy Certificate',
                'description' => '',
                'is_active' => 1,
                'quote_type_id' => QuoteTypes::SAVINGS->id(),
                'folder_path' => 'savings',
                'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',
                'max_files' => 5,
                'max_size' => 25,
                'is_required' => 1,
                'send_to_customer' => 1,
                'sort_order' => null,
                'receive_from_customer' => 0,
                'category' => DocumentTypeCode::ISSUING_DOCUMENTS,
                'is_required_for_send_policy' => 1,
                'business_type_of_insurance_id' => null,
                'business_type_of_customer' => null,
            ],
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
                'sort_order' => null,
                'receive_from_customer' => 0,
                'category' => DocumentTypeCode::ISSUING_DOCUMENTS,
                'is_required_for_send_policy' => 0,
                'business_type_of_insurance_id' => null,
                'business_type_of_customer' => null,
            ],
            [
                'code' => 'CTIRBB',
                'text' => 'Tax Invoice Raised By Buyer',
                'description' => '',
                'is_active' => 1,
                'quote_type_id' => QuoteTypes::SAVINGS->id(),
                'folder_path' => 'savings',
                'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',
                'max_files' => 5,
                'max_size' => 25,
                'is_required' => 1,
                'send_to_customer' => 0,
                'sort_order' => null,
                'receive_from_customer' => 0,
                'category' => DocumentTypeCode::ISSUING_DOCUMENTS,
                'is_required_for_send_policy' => 0,
                'business_type_of_insurance_id' => null,
                'business_type_of_customer' => null,
            ],
            [
                'code' => 'SPDR',
                'text' => 'Receipt',
                'description' => '',
                'is_active' => 1,
                'quote_type_id' => QuoteTypes::SAVINGS->id(),
                'folder_path' => 'savings',
                'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',
                'max_files' => 5,
                'max_size' => 25,
                'is_required' => 0,
                'send_to_customer' => 0,
                'sort_order' => null,
                'receive_from_customer' => 0,
                'category' => DocumentTypeCode::ISSUING_DOCUMENTS,
                'is_required_for_send_policy' => 0,
                'business_type_of_insurance_id' => null,
                'business_type_of_customer' => null,
            ],
            [
                'code' => 'TAEA',
                'text' => 'Additional Email Attachments',
                'description' => '',
                'is_active' => 1,
                'quote_type_id' => QuoteTypes::SAVINGS->id(),
                'folder_path' => 'savings',
                'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',
                'max_files' => 5,
                'max_size' => 25,
                'is_required' => 0,
                'send_to_customer' => 0,
                'sort_order' => null,
                'receive_from_customer' => 0,
                'category' => DocumentTypeCode::ISSUING_DOCUMENTS,
                'is_required_for_send_policy' => 0,
                'business_type_of_insurance_id' => null,
                'business_type_of_customer' => null,
            ],
            [
                'code' => 'SAV_AC_QT',
                'text' => 'Application copy',
                'description' => '',
                'is_active' => 1,
                'quote_type_id' => QuoteTypes::SAVINGS->id(),
                'folder_path' => 'savings',
                'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',
                'max_files' => 5,
                'max_size' => 25,
                'is_required' => 1,
                'send_to_customer' => 0,
                'sort_order' => null,
                'receive_from_customer' => 0,
                'category' => DocumentTypeCode::QUOTE,
                'is_required_for_send_policy' => 0,
                'business_type_of_insurance_id' => null,
                'business_type_of_customer' => null,
            ],
            [
                'code' => 'AC_SAV',
                'text' => 'Application copy',
                'description' => '',
                'is_active' => 1,
                'quote_type_id' => QuoteTypes::SAVINGS->id(),
                'folder_path' => 'savings',
                'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',
                'max_files' => 5,
                'max_size' => 25,
                'is_required' => 1,
                'send_to_customer' => 1,
                'sort_order' => null,
                'receive_from_customer' => 0,
                'category' => DocumentTypeCode::ISSUING_DOCUMENTS,
                'is_required_for_send_policy' => 1,
                'business_type_of_insurance_id' => null,
                'business_type_of_customer' => null,
            ],
            [
                'code' => 'EXL_SAV',
                'text' => 'Exclusion letter',
                'description' => '',
                'is_active' => 1,
                'quote_type_id' => QuoteTypes::SAVINGS->id(),
                'folder_path' => 'savings',
                'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',
                'max_files' => 5,
                'max_size' => 25,
                'is_required' => 0,
                'send_to_customer' => 0,
                'sort_order' => null,
                'receive_from_customer' => 0,
                'category' => DocumentTypeCode::ISSUING_DOCUMENTS,
                'is_required_for_send_policy' => 0,
                'business_type_of_insurance_id' => null,
                'business_type_of_customer' => null,
            ],
            [
                'code' => 'COL_SAV',
                'text' => 'Counter offer letter',
                'description' => '',
                'is_active' => 1,
                'quote_type_id' => QuoteTypes::SAVINGS->id(),
                'folder_path' => 'savings',
                'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',
                'max_files' => 5,
                'max_size' => 25,
                'is_required' => 0,
                'send_to_customer' => 0,
                'sort_order' => null,
                'receive_from_customer' => 0,
                'category' => DocumentTypeCode::ISSUING_DOCUMENTS,
                'is_required_for_send_policy' => 0,
                'business_type_of_insurance_id' => null,
                'business_type_of_customer' => null,
            ],
            [
                'code' => 'AML_SAV',
                'text' => 'Amendment letter',
                'description' => '',
                'is_active' => 1,
                'quote_type_id' => QuoteTypes::SAVINGS->id(),
                'folder_path' => 'savings',
                'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',
                'max_files' => 5,
                'max_size' => 25,
                'is_required' => 0,
                'send_to_customer' => 0,
                'sort_order' => null,
                'receive_from_customer' => 0,
                'category' => DocumentTypeCode::ISSUING_DOCUMENTS,
                'is_required_for_send_policy' => 0,
                'business_type_of_insurance_id' => null,
                'business_type_of_customer' => null,
            ],
            [
                'code' => 'STL_SAV',
                'text' => 'Special terms letter',
                'description' => '',
                'is_active' => 1,
                'quote_type_id' => QuoteTypes::SAVINGS->id(),
                'folder_path' => 'savings',
                'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',
                'max_files' => 5,
                'max_size' => 25,
                'is_required' => 0,
                'send_to_customer' => 0,
                'sort_order' => null,
                'receive_from_customer' => 0,
                'category' => DocumentTypeCode::ISSUING_DOCUMENTS,
                'is_required_for_send_policy' => 0,
                'business_type_of_insurance_id' => null,
                'business_type_of_customer' => null,
            ],
            [
                'code' => 'STIL_SAV',
                'text' => 'Special terms illustration',
                'description' => '',
                'is_active' => 1,
                'quote_type_id' => QuoteTypes::SAVINGS->id(),
                'folder_path' => 'savings',
                'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',
                'max_files' => 5,
                'max_size' => 25,
                'is_required' => 0,
                'send_to_customer' => 0,
                'sort_order' => null,
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

    private function seedSavingsPermissions()
    {
        $this->seedPermissions([
            PermissionsEnum::SAVINGS_QUOTES_LIST,
            PermissionsEnum::SAVINGS_QUOTES_CREATE,
            PermissionsEnum::SAVINGS_QUOTES_EDIT,
            PermissionsEnum::SAVINGS_QUOTES_SHOW,
            PermissionsEnum::SAVINGS_COMPREHENSIVE_DASHBOARD,
            PermissionsEnum::SAVINGS_CONVERSION_REPORT,
            PermissionsEnum::SAVINGS_DISTRIBUTION_REPORT,
            PermissionsEnum::SAVINGS_AS_AT_REPORT_MANAGER,
            PermissionsEnum::SAVINGS_LEAD_ALLOCATION_DASHBOARD,
            PermissionsEnum::ILA_CONFIG_ALL_LOB,
            PermissionsEnum::SAVINGS_LEADPOOL,
        ], [RolesEnum::Engineering, RolesEnum::Admin]);

        $this->seedPermissions([
            PermissionsEnum::SAVINGS_LEAD_ALLOCATION_DASHBOARD,
        ], [RolesEnum::LeadPool, RolesEnum::SeniorManagement]);

        $this->seedPermissions([
            PermissionsEnum::ILA_CONFIG_ALL_LOB,
        ], [RolesEnum::LeadPool]);

        $this->seedPermissions([
            PermissionsEnum::SAVINGS_QUOTES_LIST,
            PermissionsEnum::SAVINGS_QUOTES_CREATE,
            PermissionsEnum::SAVINGS_QUOTES_EDIT,
            PermissionsEnum::SAVINGS_QUOTES_SHOW,
            PermissionsEnum::SAVINGS_LEAD_ALLOCATION_DASHBOARD,
            PermissionsEnum::SAVINGS_CONVERSION_REPORT,
            PermissionsEnum::SAVINGS_COMPREHENSIVE_DASHBOARD,
            PermissionsEnum::SAVINGS_DISTRIBUTION_REPORT,
            PermissionsEnum::SAVINGS_AS_AT_REPORT_MANAGER,
            PermissionsEnum::SAVINGS_LEADPOOL,
        ], [RolesEnum::SavingsManager]);

        $this->seedPermissions([
            PermissionsEnum::SAVINGS_QUOTES_LIST,
            PermissionsEnum::SAVINGS_QUOTES_SHOW,
            PermissionsEnum::SAVINGS_QUOTES_CREATE,
            PermissionsEnum::SAVINGS_QUOTES_EDIT,
            PermissionsEnum::SAVINGS_CONVERSION_REPORT,
            PermissionsEnum::SAVINGS_DISTRIBUTION_REPORT,
        ], [RolesEnum::SavingsAdvisor]);

        $this->seedLifePermissionToSavings();
    }

    private function seedLifePermissionToSavings()
    {
        $lifeManagerPermissions = Role::with('permissions')->where('name', RolesEnum::LifeManager)->first();

        $savingManagerRole = Role::where('name', RolesEnum::SavingsManager)->first();

        $permissions = $lifeManagerPermissions->permissions;
        $permissions = $permissions->filter(fn ($permission) => ! Str::of($permission->name)->startsWith('life'))->values();

        $this->assignPermissionsToRole($permissions, $savingManagerRole);

        $lifeAdvisorRole = Role::with('permissions')->where('name', RolesEnum::LifeAdvisor)->first();

        $savingAdvisorRole = Role::where('name', RolesEnum::SavingsAdvisor)->first();

        $permissions = $lifeAdvisorRole->permissions;
        $permissions = $permissions->filter(fn ($permission) => ! Str::of($permission->name)->startsWith('life'))->values();

        $this->assignPermissionsToRole($permissions, $savingAdvisorRole);
    }
}

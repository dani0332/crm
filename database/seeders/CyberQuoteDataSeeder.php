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

class CyberQuoteDataSeeder extends Seeder
{
    use PermissionableSeeder;

    public function run(): void
    {
        $this->upsertQuoteType();
        $this->mapQuoteStatuses();

        $this->seedRoles([RolesEnum::CyberAdvisor, RolesEnum::CyberManager]);

        $this->product();

        $this->seedDocumentTypes();

        $this->seedCyberPermissions();
    }

    private function upsertQuoteType()
    {
        $quoteType = [
            'short_code' => 'CYB',
            'code' => QuoteTypes::CYBER->value,
            'text' => 'Cyber Insurance',
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
        // only to keep these statuses for cyber quote status mapping as per the business requirements for now
        $cyberStatusIds = [
            8,   // New Lead
            60,  // Renewal Terms Received
            63,  // Pending Renewal Information
            64,  // Additional Information Requested
            2,   // Quoted
            24,  // Followed Up
            43,  // For Follow-up
            25,  // In Negotiation
            66,  // Finalizing Terms
            14,  // Missing Documents Requested
            10,  // FTC Sent
            19,  // KYC Cleared
            6,   // AML Screening Cleared
            7,   // AML Screening Failed
            28,  // Payment Pending
            15,  // Transaction Approved
            29,  // Policy Documents Pending
            33,  // Policy Issued
            70,  // Policy Sent To Customer
            75,  // Policy Booking Queued
            76,  // Policy Booking Failed
            71,  // Policy Booked
            57,  // Cancellation Pending
            58,  // Policy Cancelled
            74,  // Policy Cancelled & Reissued
            17,  // Lost
            9,   // Fake
            35,  // Duplicate
        ];

        $quoteStatuses = QuoteStatus::whereIn('id', $cyberStatusIds)->oldest()->get();

        $sortOrder = 0;
        $quoteStatuses->each(function ($quoteStatus) use (&$sortOrder) {
            $quoteStatusMap = QuoteStatusMap::where('quote_status_id', $quoteStatus->id)
                ->where('quote_type_id', QuoteTypes::CYBER->id())
                ->exists();

            if (! $quoteStatusMap) {
                $sortOrder++;
                QuoteStatusMap::create([
                    'quote_status_id' => $quoteStatus->id,
                    'quote_type_id' => QuoteTypes::CYBER->id(),
                    'sort_order' => $sortOrder,
                    'created_by' => 'fahad.hussain@myalfred.com',
                    'updated_by' => 'fahad.hussain@myalfred.com',
                ]);
            }
        });
    }

    private function product()
    {
        if (! Team::where('name', 'Cyber Insurance')->where('type', TeamTypeEnum::PRODUCT)->exists()) {
            Team::create([
                'name' => 'Cyber Insurance',
                'type' => TeamTypeEnum::PRODUCT,
                'is_active' => 1,
            ]);
        }

        if (! Team::where('name', 'Cyber Insurance - Team')->where('type', TeamTypeEnum::TEAM)->exists()) {
            Team::create([
                'name' => 'Cyber Insurance - Team',
                'parent_team_id' => Team::where('name', 'Cyber Insurance')->value('id'),
                'type' => TeamTypeEnum::TEAM,
                'is_active' => 1,
            ]);
        }
    }

    private function seedDocumentTypes()
    {
        $quoteDocuments = [
            [
                'code' => 'EID_CYB',
                'text' => 'Emirates ID',
                'description' => 'Please share a copy of your valid Emirates ID with us. Awaiting receipt of your first or renewed ID? Please share a copy of your Emirates ID application form to enable us to proceed.',
                'is_active' => 1,
                'quote_type_id' => QuoteTypes::CYBER->id(),
                'folder_path' => 'cyber',
                'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',
                'max_files' => 10,
                'max_size' => 25,
                'is_required' => 1,
                'send_to_customer' => 0,
                'sort_order' => null,
                'receive_from_customer' => 1,
                'category' => DocumentTypeCode::QUOTE,
                'is_required_for_send_policy' => 0,
                'business_type_of_insurance_id' => null,
                'business_type_of_customer' => null,
            ],
            [
                'code' => 'KYC_CYB',
                'text' => 'KYC document',
                'description' => '',
                'is_active' => 1,
                'quote_type_id' => QuoteTypes::CYBER->id(),
                'folder_path' => 'cyber',
                'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',
                'max_files' => 10,
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
                'code' => 'PS_CYB',
                'text' => 'Policy schedule',
                'description' => '',
                'is_active' => 1,
                'quote_type_id' => QuoteTypes::CYBER->id(),
                'folder_path' => 'cyber',
                'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',
                'max_files' => 5,
                'max_size' => 25,
                'is_required' => 0,
                'send_to_customer' => 1,
                'sort_order' => null,
                'receive_from_customer' => 0,
                'category' => DocumentTypeCode::ISSUING_DOCUMENTS,
                'is_required_for_send_policy' => 0,
                'business_type_of_insurance_id' => null,
                'business_type_of_customer' => null,
            ],
            [
                'code' => 'PC_CYB',
                'text' => 'Policy certificate',
                'description' => '',
                'is_active' => 1,
                'quote_type_id' => QuoteTypes::CYBER->id(),
                'folder_path' => 'cyber',
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
                'code' => 'TI_ISS_CYB',
                'text' => 'Tax invoice',
                'description' => null,
                'is_active' => 1,
                'quote_type_id' => QuoteTypes::CYBER->id(),
                'folder_path' => 'cyber',
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
                'code' => 'TIRBB_ISS_CYB',
                'text' => 'Tax invoice raised by buyer',
                'description' => '',
                'is_active' => 1,
                'quote_type_id' => QuoteTypes::CYBER->id(),
                'folder_path' => 'cyber',
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
                'code' => 'EC_CYB',
                'text' => 'Endorsed certificate',
                'description' => '',
                'is_active' => 1,
                'quote_type_id' => QuoteTypes::CYBER->id(),
                'folder_path' => 'cyber',
                'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',
                'max_files' => 5,
                'max_size' => 25,
                'is_required' => 1,
                'send_to_customer' => 1,
                'sort_order' => null,
                'receive_from_customer' => 0,
                'category' => DocumentTypeCode::ENDORSEMENT_DOCUMENTS,
                'is_required_for_send_policy' => 0,
                'business_type_of_insurance_id' => null,
                'business_type_of_customer' => null,
            ],
            [
                'code' => 'ES_CYB',
                'text' => 'Endorsed schedule',
                'description' => '',
                'is_active' => 1,
                'quote_type_id' => QuoteTypes::CYBER->id(),
                'folder_path' => 'cyber',
                'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',
                'max_files' => 5,
                'max_size' => 25,
                'is_required' => 0,
                'send_to_customer' => 1,
                'sort_order' => null,
                'receive_from_customer' => 0,
                'category' => DocumentTypeCode::ENDORSEMENT_DOCUMENTS,
                'is_required_for_send_policy' => 0,
                'business_type_of_insurance_id' => null,
                'business_type_of_customer' => null,
            ],
            [
                'code' => 'TI_END_CYB',
                'text' => 'Tax invoice',
                'description' => null,
                'is_active' => 1,
                'quote_type_id' => QuoteTypes::CYBER->id(),
                'folder_path' => 'cyber',
                'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',
                'max_files' => 5,
                'max_size' => 25,
                'is_required' => 1,
                'send_to_customer' => 1,
                'sort_order' => null,
                'receive_from_customer' => 0,
                'category' => DocumentTypeCode::ENDORSEMENT_DOCUMENTS,
                'is_required_for_send_policy' => 0,
                'business_type_of_insurance_id' => null,
                'business_type_of_customer' => null,
            ],
            [
                'code' => 'TIRBB_END_CYB',
                'text' => 'Tax invoice raised by buyer',
                'description' => '',
                'is_active' => 1,
                'quote_type_id' => QuoteTypes::CYBER->id(),
                'folder_path' => 'cyber',
                'accepted_files' => '.pdf,.xlsx,.xls,.docx,.doc,.jpeg,.jpg,.png',
                'max_files' => 5,
                'max_size' => 25,
                'is_required' => 1,
                'send_to_customer' => 0,
                'sort_order' => null,
                'receive_from_customer' => 0,
                'category' => DocumentTypeCode::ENDORSEMENT_DOCUMENTS,
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

    private function seedCyberPermissions()
    {
        $this->seedPermissions([
            PermissionsEnum::CYBER_QUOTES_LIST,
            PermissionsEnum::CYBER_QUOTES_CREATE,
            PermissionsEnum::CYBER_QUOTES_EDIT,
            PermissionsEnum::CYBER_QUOTES_SHOW,
            PermissionsEnum::CYBER_COMPREHENSIVE_DASHBOARD,
            PermissionsEnum::CYBER_CONVERSION_REPORT,
            PermissionsEnum::CYBER_DISTRIBUTION_REPORT,
            PermissionsEnum::CYBER_AS_AT_REPORT_MANAGER,
            PermissionsEnum::CYBER_LEAD_ALLOCATION_DASHBOARD,
            PermissionsEnum::ILA_CONFIG_ALL_LOB,
            PermissionsEnum::CYBER_LEADPOOL,
        ], [RolesEnum::Engineering, RolesEnum::Admin]);

        $this->seedPermissions([
            PermissionsEnum::CYBER_LEAD_ALLOCATION_DASHBOARD,
        ], [RolesEnum::LeadPool, RolesEnum::SeniorManagement]);

        $this->seedPermissions([
            PermissionsEnum::ILA_CONFIG_ALL_LOB,
        ], [RolesEnum::LeadPool]);

        $this->seedPermissions([
            PermissionsEnum::CYBER_QUOTES_LIST,
            PermissionsEnum::CYBER_QUOTES_CREATE,
            PermissionsEnum::CYBER_QUOTES_EDIT,
            PermissionsEnum::CYBER_QUOTES_SHOW,
            PermissionsEnum::CYBER_LEAD_ALLOCATION_DASHBOARD,
            PermissionsEnum::CYBER_CONVERSION_REPORT,
            PermissionsEnum::CYBER_COMPREHENSIVE_DASHBOARD,
            PermissionsEnum::CYBER_DISTRIBUTION_REPORT,
            PermissionsEnum::CYBER_AS_AT_REPORT_MANAGER,
            PermissionsEnum::CYBER_LEADPOOL,
        ], [RolesEnum::CyberManager]);

        $this->seedPermissions([
            PermissionsEnum::CYBER_QUOTES_LIST,
            PermissionsEnum::CYBER_QUOTES_SHOW,
            PermissionsEnum::CYBER_QUOTES_CREATE,
            PermissionsEnum::CYBER_QUOTES_EDIT,
            PermissionsEnum::CYBER_CONVERSION_REPORT,
            PermissionsEnum::CYBER_DISTRIBUTION_REPORT,
        ], [RolesEnum::CyberAdvisor]);

        $this->seedCarPermissionToCyber();
    }

    private function seedCarPermissionToCyber()
    {
        $carManagerPermissions = Role::with('permissions')->where('name', RolesEnum::CarManager)->first();
        $cyberManagerRole = Role::where('name', RolesEnum::CyberManager)->first();

        if ($carManagerPermissions && $cyberManagerRole) {
            $permissions = $carManagerPermissions->permissions;
            $permissions = $permissions->filter(fn ($permission) => ! Str::of($permission->name)->startsWith('car'))->values();
            $this->assignPermissionsToRole($permissions, $cyberManagerRole);
        }

        $carAdvisorRole = Role::with('permissions')->where('name', RolesEnum::CarAdvisor)->first();
        $cyberAdvisorRole = Role::where('name', RolesEnum::CyberAdvisor)->first();

        if ($carAdvisorRole && $cyberAdvisorRole) {
            $permissions = $carAdvisorRole->permissions;
            $permissions = $permissions->filter(fn ($permission) => ! Str::of($permission->name)->startsWith('car'))->values();
            $this->assignPermissionsToRole($permissions, $cyberAdvisorRole);
        }
    }
}

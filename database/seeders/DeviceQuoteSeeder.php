<?php

namespace Database\Seeders;

use App\Enums\PermissionsEnum;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Models\QuoteType;
use App\Services\Logger\LoggerService;
use Database\Seeders\Traits\PermissionableSeeder;
use Illuminate\Database\Seeder;
use App\Models\Team;
use App\Enums\TeamTypeEnum;
use App\Models\QuoteStatusMap;
use App\Models\QuoteStatus;

class DeviceQuoteSeeder extends Seeder
{
    use PermissionableSeeder;
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        LoggerService::info(self::class.' - DeviceQuoteSeeder started');
        $this->upsertQuoteType();
        LoggerService::info(self::class.' - Device Quote type upserted');
        $this->mapQuoteStatuses();
        LoggerService::info(self::class.' - Device Quote statuses mapped');
        LoggerService::info(self::class.' - Device Roles seeded');
        $this->seedRoles([RolesEnum::SmartPhoneAdvisor, RolesEnum::SmartPhoneManager]);
        $this->seedDevicePermissions();
        LoggerService::info(self::class.' - Device Permissions seeded');
        $this->product();
        LoggerService::info(self::class.' - Device Product seeded');
        LoggerService::info(self::class.' - DeviceQuoteSeeder completed');
    }

    private function upsertQuoteType()
    {
        $quoteType = [
            'short_code' => 'DEV',
            'code' => QuoteTypes::DEVICE->value,
            'text' => 'Device Insurance',
            'is_active' => 1,
        ];

        if (! QuoteType::where('code', $quoteType['code'])->exists()) {
            QuoteType::create($quoteType);
        } else {
            QuoteType::where('code', $quoteType['code'])->update($quoteType);
        }
    }

    private function seedDevicePermissions()
    {
        $permissions = [
            PermissionsEnum::DEVICE_QUOTES_LIST,
            PermissionsEnum::DEVICE_QUOTES_CREATE,
            PermissionsEnum::DEVICE_QUOTES_EDIT,
            PermissionsEnum::DEVICE_QUOTES_SHOW,
            PermissionsEnum::DEVICE_LEAD_ALLOCATION_DASHBOARD,
            PermissionsEnum::DEVICE_LEADPOOL,
        ];
        $this->seedPermissions($permissions, [RolesEnum::Admin, RolesEnum::SmartPhoneAdvisor, RolesEnum::SmartPhoneManager, RolesEnum::Engineering]);

    }

    private function mapQuoteStatuses()
    {
        // only to keep these statuses for device quote status mapping as per the business requirements for now
        $deviceStatusIds = [
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

        $quoteStatuses = QuoteStatus::whereIn('id', $deviceStatusIds)->oldest()->get();

        $sortOrder = 0;
        $quoteStatuses->each(function ($quoteStatus) use (&$sortOrder) {
            $quoteStatusMap = QuoteStatusMap::where('quote_status_id', $quoteStatus->id)
                ->where('quote_type_id', QuoteTypes::DEVICE->id())
                ->exists();

            if (! $quoteStatusMap) {
                $sortOrder++;
                QuoteStatusMap::create([
                    'quote_status_id' => $quoteStatus->id,
                    'quote_type_id' => QuoteTypes::DEVICE->id(),
                    'sort_order' => $sortOrder,
                    'created_by' => 'system',
                    'updated_by' => 'system',
                ]);
            }
        });
    }

    private function product()
    {
        if (! Team::where('name', 'Device Insurance')->where('type', TeamTypeEnum::PRODUCT)->exists()) {
            Team::create([
                'name' => 'Device Insurance',
                'type' => TeamTypeEnum::PRODUCT,
                'is_active' => 1,
            ]);
        }

        if (! Team::where('name', 'Device Insurance - Team')->where('type', TeamTypeEnum::TEAM)->exists()) {
            Team::create([
                'name' => 'Device Insurance - Team',
                'parent_team_id' => Team::where('name', 'Device Insurance')->value('id'),
                'type' => TeamTypeEnum::TEAM,
                'is_active' => 1,
            ]);
        }
    }

}

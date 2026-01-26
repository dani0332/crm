<?php

namespace Database\Seeders;

use App\Enums\PermissionsEnum;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Models\QuoteType;
use App\Services\Logger\LoggerService;
use Database\Seeders\Traits\PermissionableSeeder;
use Illuminate\Database\Seeder;

class DeviceQuoteSeeder extends Seeder
{
    use PermissionableSeeder;
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        LoggerService::info('DeviceQuoteSeeder started');
        $this->upsertQuoteType();
        $this->seedRoles([RolesEnum::SmartPhoneAdvisor, RolesEnum::SmartPhoneManager]);
        $this->seedDevicePermissions();
        LoggerService::info('DeviceQuoteSeeder completed');
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
        ];
        $this->seedPermissions($permissions, [RolesEnum::Admin, RolesEnum::SmartPhoneAdvisor, RolesEnum::SmartPhoneManager, RolesEnum::Engineering]);

        /* Reports permission */
        $adminAndEngineeringPermissions = [
            PermissionsEnum::DEVICE_CONVERSION_REPORT,
            PermissionsEnum::DEVICE_DISTRIBUTION_REPORT,
        ];

        $this->seedPermissions($adminAndEngineeringPermissions, [RolesEnum::Admin, RolesEnum::Engineering]);

        $deviceManagerPermissions = [
            PermissionsEnum::SEARCH_ALL_LEAD_LOB,
            PermissionsEnum::ADVISOR_CONVERSION_REPORT_VIEW,
            PermissionsEnum::ADVISOR_PERFORMANCE_REPORT_VIEW,
            PermissionsEnum::ADVISOR_DISTRIBUTION_REPORT_VIEW,
            PermissionsEnum::LEAD_DISTRIBUTION_REPORT_VIEW,

            PermissionsEnum::MANAGER_RETENTION_REPORT_VIEW,
            PermissionsEnum::MANAGER_AUTHORISED_PAYMENT_SUMMARY,

            // PermissionsEnum::DEVICE_CONVERSION_REPORT,
        ];

        $deviceAdvisorPermissions = [
            PermissionsEnum::SEARCH_ALL_LEAD_LOB,
            PermissionsEnum::ADVISOR_CONVERSION_REPORT_VIEW,
            PermissionsEnum::ADVISOR_DISTRIBUTION_REPORT_VIEW,
        ];

        $this->seedPermissions($deviceManagerPermissions, [RolesEnum::SmartPhoneManager]);

        $this->seedPermissions($deviceAdvisorPermissions, [RolesEnum::SmartPhoneAdvisor]);

    }

}

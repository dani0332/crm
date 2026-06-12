<?php

namespace Database\Seeders;

use App\Enums\PermissionsEnum;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Enums\TeamTypeEnum;
use App\Models\QuoteStatus;
use App\Models\QuoteStatusMap;
use App\Models\QuoteType;
use App\Models\Team;
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

    /**
     * Map all quote statuses to DEVICE quote type so getQuoteStatuses() returns statuses for device flows.
     * QuoteStatusSeeder runs earlier and only maps statuses for quote types that exist at that time.
     */
    private function mapQuoteStatuses(): void
    {
        $quoteTypeId = (int) QuoteTypes::DEVICE->id();
        $quoteStatuses = QuoteStatus::oldest()->get();
        $sortOrder = 0;

        $quoteStatuses->each(function (QuoteStatus $quoteStatus) use ($quoteTypeId, &$sortOrder): void {
            $exists = QuoteStatusMap::where('quote_status_id', $quoteStatus->id)
                ->where('quote_type_id', $quoteTypeId)
                ->exists();

            if (! $exists) {
                $sortOrder++;
                QuoteStatusMap::create([
                    'quote_status_id' => $quoteStatus->id,
                    'quote_type_id' => $quoteTypeId,
                    'sort_order' => $sortOrder,
                    'created_by' => 'DeviceQuoteSeeder',
                    'updated_by' => 'DeviceQuoteSeeder',
                ]);
            }
        });
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

    /**
     * Permission name → role names assigned in {@see seedDevicePermissions()}.
     * Single source of truth so every device permission (including re-trigger) is explicitly mapped.
     *
     * @return array<string, list<string>>
     */
    public function deviceQuotePermissionRoleAssignments(): array
    {

        // The DEVICE_LEADPOOL permission should NOT be assigned to the SmartPhoneAdvisor role.
        $permissionRoleMap = [
            PermissionsEnum::DEVICE_LEADPOOL => [RolesEnum::Admin, RolesEnum::SmartPhoneManager, RolesEnum::Engineering],
            PermissionsEnum::DEVICE_QUOTES_LIST => [RolesEnum::Admin, RolesEnum::SmartPhoneAdvisor, RolesEnum::SmartPhoneManager, RolesEnum::Engineering],
            PermissionsEnum::DEVICE_QUOTES_CREATE => [RolesEnum::Admin, RolesEnum::SmartPhoneAdvisor, RolesEnum::SmartPhoneManager, RolesEnum::Engineering],
            PermissionsEnum::DEVICE_QUOTES_EDIT => [RolesEnum::Admin, RolesEnum::SmartPhoneAdvisor, RolesEnum::SmartPhoneManager, RolesEnum::Engineering],
            PermissionsEnum::DEVICE_QUOTES_SHOW => [RolesEnum::Admin, RolesEnum::SmartPhoneAdvisor, RolesEnum::SmartPhoneManager, RolesEnum::Engineering],
        ];

        foreach ($permissionRoleMap as $permission => $roles) {
            $this->seedPermissions([$permission], $roles);
            LoggerService::info('DeviceQuoteSeeder: '.$permission.' permissions seeded for roles: '.implode(', ', $roles));
        }

        /* Reports permission */
        $adminAndEngineeringPermissions = [
            PermissionsEnum::DEVICE_CONVERSION_REPORT,
            PermissionsEnum::DEVICE_DISTRIBUTION_REPORT,
        ];

        $this->seedPermissions($adminAndEngineeringPermissions, [RolesEnum::Admin, RolesEnum::Engineering]);

        $smartPhoneManagerPermissions = [
            PermissionsEnum::SEARCH_ALL_LEAD_LOB,
            PermissionsEnum::ADVISOR_CONVERSION_REPORT_VIEW,
            PermissionsEnum::ADVISOR_PERFORMANCE_REPORT_VIEW,
            PermissionsEnum::ADVISOR_DISTRIBUTION_REPORT_VIEW,
            PermissionsEnum::LEAD_DISTRIBUTION_REPORT_VIEW,

            PermissionsEnum::MANAGER_RETENTION_REPORT_VIEW,
            PermissionsEnum::MANAGER_AUTHORISED_PAYMENT_SUMMARY,

            // PermissionsEnum::DEVICE_CONVERSION_REPORT,
        ];

        $smartPhoneAdvisorPermissions = [
            PermissionsEnum::SEARCH_ALL_LEAD_LOB,
            PermissionsEnum::ADVISOR_CONVERSION_REPORT_VIEW,
            PermissionsEnum::ADVISOR_DISTRIBUTION_REPORT_VIEW,
        ];

        $this->seedPermissions($smartPhoneManagerPermissions, [RolesEnum::SmartPhoneManager]);

        $this->seedPermissions($smartPhoneAdvisorPermissions, [RolesEnum::SmartPhoneAdvisor]);

        return self::deviceQuotePermissionRoleAssignmentMap();
    }

    /**
     * Role map returned by {@see deviceQuotePermissionRoleAssignments()} (no DB side effects).
     *
     * @return array<string, list<string>>
     */
    public static function deviceQuotePermissionRoleAssignmentMap(): array
    {
        $rolesWithFullDeviceQuoteAccess = [
            RolesEnum::Admin,
            RolesEnum::SmartPhoneAdvisor,
            RolesEnum::SmartPhoneManager,
            RolesEnum::Engineering,
        ];

        return [
            PermissionsEnum::DEVICE_QUOTES_LIST => $rolesWithFullDeviceQuoteAccess,
            PermissionsEnum::DEVICE_QUOTES_CREATE => $rolesWithFullDeviceQuoteAccess,
            PermissionsEnum::DEVICE_QUOTES_EDIT => $rolesWithFullDeviceQuoteAccess,
            PermissionsEnum::DEVICE_QUOTES_SHOW => $rolesWithFullDeviceQuoteAccess,
            PermissionsEnum::RE_TRIGGER_POLICY_AUTOMATION_DEVICE => $rolesWithFullDeviceQuoteAccess,
            PermissionsEnum::DEVICE_LEAD_ALLOCATION_DASHBOARD => $rolesWithFullDeviceQuoteAccess,
            PermissionsEnum::DEVICE_LEADPOOL => $rolesWithFullDeviceQuoteAccess,
            PermissionsEnum::DEVICE_CONVERSION_REPORT => $rolesWithFullDeviceQuoteAccess,
            PermissionsEnum::DEVICE_DISTRIBUTION_REPORT => $rolesWithFullDeviceQuoteAccess,
        ];
    }

    private function seedDevicePermissions(): void
    {
        foreach ($this->deviceQuotePermissionRoleAssignments() as $permission => $roles) {
            $this->seedPermissions([$permission], $roles);
        }
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

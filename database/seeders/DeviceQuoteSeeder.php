<?php

namespace Database\Seeders;

use App\Enums\PermissionsEnum;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Models\QuoteStatus;
use App\Models\QuoteStatusMap;
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
        $this->mapQuoteStatuses();
        $this->seedRoles([RolesEnum::SmartPhoneAdvisor, RolesEnum::SmartPhoneManager]);
        $this->seedDevicePermissions();
        LoggerService::info('DeviceQuoteSeeder completed');
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

    private function seedDevicePermissions()
    {
        $permissions = [
            PermissionsEnum::DEVICE_QUOTES_LIST,
            PermissionsEnum::DEVICE_QUOTES_CREATE,
            PermissionsEnum::DEVICE_QUOTES_EDIT,
            PermissionsEnum::DEVICE_QUOTES_SHOW,
            PermissionsEnum::DEVICE_LEADPOOL,
        ];
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
            LoggerService::info('DeviceQuoteSeeder: ' . $permission . ' permissions seeded for roles: ' . implode(', ', $roles));
        }

    }

}

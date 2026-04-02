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
        $this->seedRoles([RolesEnum::DeviceAdvisor, RolesEnum::DeviceManager]);
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
            PermissionsEnum::RE_TRIGGER_POLICY_AUTOMATION_DEVICE,
        ];
        $this->seedPermissions($permissions, [RolesEnum::Admin, RolesEnum::DeviceAdvisor, RolesEnum::DeviceManager, RolesEnum::Engineering]);

    }

}

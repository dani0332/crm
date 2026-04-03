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

    /**
     * Permission name → role names assigned in {@see seedDevicePermissions()}.
     * Single source of truth so every device permission (including re-trigger) is explicitly mapped.
     *
     * @return array<string, list<string>>
     */
    public static function deviceQuotePermissionRoleAssignments(): array
    {
        $rolesWithFullDeviceQuoteAccess = [
            RolesEnum::Admin,
            RolesEnum::DeviceAdvisor,
            RolesEnum::DeviceManager,
            RolesEnum::Engineering,
        ];

        return [
            PermissionsEnum::DEVICE_QUOTES_LIST => $rolesWithFullDeviceQuoteAccess,
            PermissionsEnum::DEVICE_QUOTES_CREATE => $rolesWithFullDeviceQuoteAccess,
            PermissionsEnum::DEVICE_QUOTES_EDIT => $rolesWithFullDeviceQuoteAccess,
            PermissionsEnum::DEVICE_QUOTES_SHOW => $rolesWithFullDeviceQuoteAccess,
            PermissionsEnum::RE_TRIGGER_POLICY_AUTOMATION_DEVICE => $rolesWithFullDeviceQuoteAccess,
        ];
    }

    private function seedDevicePermissions(): void
    {
        foreach (self::deviceQuotePermissionRoleAssignments() as $permission => $roles) {
            $this->seedPermissions([$permission], $roles);
        }
    }

}

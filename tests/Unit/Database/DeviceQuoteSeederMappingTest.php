<?php

declare(strict_types=1);

namespace Tests\Unit\Database;

use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
use Database\Seeders\DeviceQuoteSeeder;
use PHPUnit\Framework\TestCase;

final class DeviceQuoteSeederMappingTest extends TestCase
{
    public function test_maps_re_trigger_policy_automation_device_to_the_same_roles_as_device_quote_crud(): void
    {
        $map = DeviceQuoteSeeder::deviceQuotePermissionRoleAssignmentMap();

        $expectedRoles = [
            RolesEnum::Admin,

            RolesEnum::SmartPhoneAdvisor,
            RolesEnum::SmartPhoneManager,
            RolesEnum::Engineering,
        ];

        $this->assertArrayHasKey(PermissionsEnum::RE_TRIGGER_POLICY_AUTOMATION_DEVICE, $map);
        $this->assertSame($expectedRoles, $map[PermissionsEnum::RE_TRIGGER_POLICY_AUTOMATION_DEVICE]);
        $this->assertSame($expectedRoles, $map[PermissionsEnum::DEVICE_QUOTES_SHOW]);
    }
}

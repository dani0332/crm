<?php

namespace Tests\Helpers;

use App\Services\CapiRequestService;
use Mockery;
use Mockery\MockInterface;

class AmtGroupMedicalMockHelper
{
    /**
     * Mock CapiRequestService for Group Medical (AMT) lead creation.
     * Asserts the CAPI payload includes emirateOfRegistrationId and returns a response without quoteUID
     * so savePremium/selfAssign are not triggered (no local quote creation).
     */
    public static function mockCapiRequestService(int $expectedEmirateOfRegistrationId): MockInterface
    {
        $mock = Mockery::mock('alias:'.CapiRequestService::class);
        $mock->shouldReceive('sendCAPIRequest')
            ->once()
            ->with('/api/v1-save-business-quote', Mockery::on(function (array $data) use ($expectedEmirateOfRegistrationId): bool {
                return isset($data['emirateOfRegistrationId'])
                    && (int) $data['emirateOfRegistrationId'] === $expectedEmirateOfRegistrationId;
            }))
            ->andReturn((object) []);

        return $mock;
    }
}

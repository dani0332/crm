<?php

namespace Tests\Helpers;

use App\Enums\WorkflowTypeEnum;
use App\Services\BirdService;
use Mockery;
use Mockery\MockInterface;

class BirdMockHelper
{
    public static function mock(): MockInterface
    {
        $mock = Mockery::mock(BirdService::class);
        app()->instance(BirdService::class, $mock);

        return $mock;
    }

    public static function expectManagerDeactivationAttemptEmailSent(string $workflowUrl, int $managerUserId): void
    {
        $mock = self::mock();

        $mock->shouldReceive('triggerWebHookRequest')
            ->once()
            ->withArgs(function ($url, $payload) use ($workflowUrl, $managerUserId) {
                if ($url !== $workflowUrl) {
                    return false;
                }

                if (! is_object($payload)) {
                    return false;
                }

                if (! property_exists($payload, 'recipientEmail') || empty($payload->recipientEmail)) {
                    return false;
                }

                if (! property_exists($payload, 'workflowType') || $payload->workflowType !== WorkflowTypeEnum::MANAGER_DEACTIVATION_EMAIL) {
                    return false;
                }

                if (! property_exists($payload, 'managerIds')) {
                    return false;
                }

                $managerIds = (string) $payload->managerIds;

                return str_contains($managerIds, (string) $managerUserId);
            })
            ->andReturn((object) [
                'headers' => [],
                'body' => '',
                'status_code' => 200,
            ]);
    }

    public static function expectNoBirdCalls(): void
    {
        self::mock()
            ->shouldReceive('triggerWebHookRequest')
            ->never();
    }
}


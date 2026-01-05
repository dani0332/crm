<?php

namespace Tests\Helpers;

use App\Models\QuoteAdditionalDetail;
use App\Services\BirdService;
use Mockery;

class CarAigWorkflowMockHelper
{
    /**
     * Mock BirdService used by CarEmailService::sendAIGWorkflow.
     */
    public static function mockBirdServiceTrigger(
        string $expectedUrl,
        ?string $runId = 'test-run-id',
        int $statusCode = 200,
        ?callable $payloadAssert = null
    ): \Mockery\MockInterface
    {
        $mock = Mockery::mock(BirdService::class);

        $mock->shouldReceive('triggerWebHookRequest')
            ->once()
            ->with(
                $expectedUrl,
                $payloadAssert
                    ? Mockery::on(function ($payload) use ($payloadAssert) {
                        return $payloadAssert($payload) === true;
                    })
                    : Mockery::type('object')
            )
            ->andReturn((object) [
                'headers' => $runId ? ['Run-Id' => [$runId]] : [],
                'status_code' => $statusCode,
            ]);

        app()->instance(BirdService::class, $mock);

        return $mock;
    }

    /**
     * Prevent MongoDB access from getWhatsappConsent() by mocking QuoteAdditionalDetail query chain.
     */
    public static function mockQuoteAdditionalDetailNoConsent(): \Mockery\MockInterface
    {
        $mock = Mockery::mock('alias:'.QuoteAdditionalDetail::class);

        $mock->shouldReceive('where')
            ->withAnyArgs()
            ->andReturnSelf();

        $mock->shouldReceive('first')
            ->andReturn(null);

        return $mock;
    }
}



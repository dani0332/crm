<?php

namespace Tests\Helpers\HealthILA;

use Illuminate\Testing\TestResponse;

class HealthILAAllocationAssertionHelper
{
    public static function assertAllocationSuccess(TestResponse $response)
    {
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Quote allocated successfully',
        ]);
    }

    public static function assertValidationError(TestResponse $response, $field)
    {
        $response->assertStatus(422);
        $response->assertJsonValidationErrors($field);
    }

    public static function assertServiceDisabled(TestResponse $response)
    {
        $response->assertStatus(503);
        $response->assertJson([
            'success' => false,
            'message' => 'Lead allocation service is disabled',
        ]);
    }

    public static function assertException(TestResponse $response)
    {
        $response->assertStatus(500);
        $response->assertJson([
            'success' => false,
            'message' => 'Internal server error',
        ]);
    }

    public static function assertTeamAssigned(TestResponse $response, string $expectedTeam)
    {
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'health_team_type' => $expectedTeam,
            ],
        ]);
    }
}

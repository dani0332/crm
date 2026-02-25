<?php

use App\Models\User;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
});

describe('UserController - Update Active State', function () {

    test('it updates user active state successfully', function () {
        // Create authenticated user
        $authUser = User::factory()->create();
        $this->actingAs($authUser);

        $user = User::factory()->create([
            'is_active' => false,
        ]);

        $response = $this->postJson(route('users.update-active-state'), [
            'id' => $user->id,
            'status' => true,
        ]);

        // Assert response
        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'User status updated successfully',
            ]);

        // Assert database updated
        expect($user->fresh()->is_active)->toBe(1);
    });

});

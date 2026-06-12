<?php

use App\Enums\RolesEnum;
use App\Http\Controllers\TmLeadController;
use App\Http\Middleware\PreventRequestForgery;
use App\Services\TMLeadsService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;
use Tests\Support\Schema\SchemaUtils;

/**
 * Tests that the TM lead show endpoint is protected against IDOR:
 * - The show action is protected by telemarketing permission middleware.
 * - Users without telemarketing permission get 403 when attempting to view a TM lead.
 */
beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
    $this->withoutMiddleware(PreventRequestForgery::class);
    ensureTmLeadsTableExists();
});

/**
 * Ensure tm_leads table exists with at least one row so route model binding
 * can resolve before the permission middleware runs (and returns 403).
 */
function ensureTmLeadsTableExists(): void
{
    SchemaUtils::ensureTables([
        'tm_leads' => function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        },
    ]);
    if (DB::connection('sqlite')->table('tm_leads')->where('id', 1)->doesntExist()) {
        DB::connection('sqlite')->table('tm_leads')->insert(['id' => 1, 'created_at' => now(), 'updated_at' => now()]);
    }
}

it('registers telemarketing permission middleware on show action to prevent IDOR', function () {
    $controller = new TmLeadController(app(TMLeadsService::class));
    $middleware = $controller->getMiddleware();

    $showMiddleware = collect($middleware)->first(function ($m) {
        $options = $m['options'] ?? [];
        $only = $options['only'] ?? null;

        return $only && in_array('show', (array) $only);
    });

    expect($showMiddleware)->not->toBeNull()
        ->and($showMiddleware['middleware'])->toContain('permission:telemarketing-list|telemarketing-create|telemarketing-edit|telemarketing-delete');
});

it('returns 403 when advisor without telemarketing permission tries to view a TM lead', function () {
    $carAdvisor = TestDataSeeder::createUserWithRole(RolesEnum::CarAdvisor, [
        'email' => fake()->unique()->safeEmail(),
    ]);
    $this->actingAs($carAdvisor);

    $response = $this->get(route('tmleads-show', 1));

    $response->assertForbidden();
});

it('returns 403 when travel advisor without telemarketing permission tries to view a TM lead', function () {
    $travelAdvisor = TestDataSeeder::createUserWithRole(RolesEnum::TravelAdvisor, [
        'email' => fake()->unique()->safeEmail(),
    ]);
    $this->actingAs($travelAdvisor);

    $response = $this->get(route('tmleads-show', 1));

    $response->assertForbidden();
});

it('returns 403 when RM advisor without telemarketing permission tries to view a TM lead', function () {
    $rmAdvisor = TestDataSeeder::createUserWithRole(RolesEnum::RMAdvisor, [
        'email' => fake()->unique()->safeEmail(),
    ]);
    $this->actingAs($rmAdvisor);

    $response = $this->get(route('tmleads-show', 1));

    $response->assertForbidden();
});

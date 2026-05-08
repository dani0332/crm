<?php

declare(strict_types=1);

use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\DB;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
    $this->withoutMiddleware(PreventRequestForgery::class);
});

it('returns advisors as a serialized collection and count matches data length', function () {
    $advisor = TestDataSeeder::createUserWithRole(RolesEnum::CarAdvisor, [
        'email' => fake()->unique()->safeEmail(),
        'name' => 'Alpha Advisor',
    ]);

    $this->actingAs($advisor);

    $response = $this->postJson(route('advisors.by-quote-type'), [
        'quote_type' => QuoteTypes::CAR->value,
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true);

    $data = $response->json('data');
    expect($data)->toBeArray();
    expect($response->json('count'))->toBe(count($data));
    expect(collect($data)->pluck('id')->all())->toContain($advisor->id);
});

it('filters advisors by department_ids when departments are provided', function () {
    $db = DB::connection('sqlite');

    $departmentAId = (int) $db->table('departments')->insertGetId([
        'name' => 'Department A',
        'is_active' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $departmentBId = (int) $db->table('departments')->insertGetId([
        'name' => 'Department B',
        'is_active' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $actor = TestDataSeeder::createUserWithRole(RolesEnum::CarAdvisor, [
        'email' => fake()->unique()->safeEmail(),
        'name' => 'Zulu Actor',
    ]);

    $advisorInDeptA = TestDataSeeder::createUserWithRole(RolesEnum::CarAdvisor, [
        'email' => fake()->unique()->safeEmail(),
        'name' => 'Alpha Advisor A',
    ]);
    $advisorInDeptB = TestDataSeeder::createUserWithRole(RolesEnum::CarAdvisor, [
        'email' => fake()->unique()->safeEmail(),
        'name' => 'Beta Advisor B',
    ]);

    $db->table('users')->where('id', $advisorInDeptA->id)->update(['department_id' => $departmentAId]);
    $db->table('users')->where('id', $advisorInDeptB->id)->update(['department_id' => $departmentBId]);

    $this->actingAs($actor);

    $response = $this->postJson(route('advisors.by-quote-type'), [
        'quote_type' => QuoteTypes::CAR->value,
        'department_ids' => [$departmentAId],
    ]);

    $response->assertOk()->assertJsonPath('success', true);

    $ids = collect($response->json('data'))->pluck('id')->all();

    expect($ids)->toContain($advisorInDeptA->id)
        ->and($ids)->not->toContain($advisorInDeptB->id);
});

it('rejects department_ids that do not exist on departments table', function () {
    $actor = TestDataSeeder::createUserWithRole(RolesEnum::CarAdvisor, [
        'email' => fake()->unique()->safeEmail(),
    ]);

    $this->actingAs($actor);

    $this->postJson(route('advisors.by-quote-type'), [
        'quote_type' => QuoteTypes::CAR->value,
        'department_ids' => [999_999],
    ])->assertUnprocessable();
});

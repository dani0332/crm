<?php

use App\Models\Team;
use Illuminate\Support\Facades\Cache;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function (): void {
    TestSchemaCreator::createMinimalSchema();
    Cache::flush();
});

it('returns team id and caches it when team exists', function (): void {
    $team = Team::create([
        'name' => 'Car Team',
        'code' => 'CAR',
        'is_active' => 1,
    ]);

    $result = getTeamId('Car Team', [], false);

    expect($result)->toBe($team->id);

    $cacheKey = 'getTeamId_'.md5('Car Team'.'|'.json_encode([]).'|0');
    expect(Cache::get($cacheKey))->toBe($team->id);
});

it('does not cache 0 for 24 hours when team does not exist', function (): void {
    $teamNameOrCode = 'NonExistentTeam_'.uniqid();
    $additionalWhere = [];
    $ignoreActive = false;

    $result = getTeamId($teamNameOrCode, $additionalWhere, $ignoreActive);

    expect($result)->toBe(0);

    $cacheKey = 'getTeamId_'.md5($teamNameOrCode.'|'.json_encode($additionalWhere).'|'.($ignoreActive ? '1' : '0'));
    expect(Cache::has($cacheKey))->toBeFalse('When team does not exist, 0 must not be cached for 24 hours.');
});

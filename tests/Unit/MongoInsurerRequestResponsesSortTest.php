<?php

declare(strict_types=1);

use App\Models\TravelInsurerRequestResponses;

test('travel insurer mongo query compiles descending created_at sort as BSON integer', function (): void {
    try {
        $mql = TravelInsurerRequestResponses::query()
            ->where('quote_uuid', '00000000-0000-0000-0000-000000000001')
            ->whereNotIn('call_type', ['oAuth', 'login'])
            ->orderBy('created_at', 'desc')
            ->toMql();
    } catch (InvalidArgumentException $e) {
        if (str_contains($e->getMessage(), 'requires "dsn" or "host"')) {
            $this->markTestSkipped('MongoDB connection not configured for tests (set MONGO_DSN or host).');
        }

        throw $e;
    }

    expect($mql)->toHaveKey('find')
        ->and($mql['find'][1]['sort']['created_at'] ?? null)->toBe(-1);
})->skip(
    ! class_exists('MongoDB\Driver\Manager'),
    'mongodb PHP extension not loaded',
);

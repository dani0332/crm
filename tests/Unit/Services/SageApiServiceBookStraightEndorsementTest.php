<?php

declare(strict_types=1);

use App\Models\EpLog;
use App\Models\SendUpdateLog;
use App\Services\SageApiService;
use Carbon\Carbon;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
});

afterEach(function () {
    Mockery::close();
});

describe('SageApiService - createEPLog Real Database Test', function () {

    test('createEPLog inserts actual entry into database and can be verified', function () {
        // Arrange - Create real SendUpdateLog in database using factory
        $sendUpdateLog = SendUpdateLog::factory()->create();

        $ePTransactionId = rand(1000, 9999);
        $event = 'reversal';
        $reversalAt = Carbon::now();
        $diffInDays = 18;
        $maxDays = 30;
        $values = [
            'reversal_at' => $reversalAt->toDateTimeString(),
            'diff_in_days' => $diffInDays,
            'message' => "Sage reversal triggered (≤ {$maxDays} days)",
            'test_data' => 'This is a real database test',
        ];

        // Act - Use partial mock of SageApiService
        $service = Mockery::mock(SageApiService::class)->makePartial();
        $reflection = new ReflectionClass(SageApiService::class);
        $method = $reflection->getMethod('createEPLog');
        $method->setAccessible(true);

        // Invoke the actual method
        $method->invoke($service, $sendUpdateLog, $ePTransactionId, $event, $values);

        // Assert - Verify the record exists in database using multiple methods

        // 1. Use assertDatabaseHas
        $this->assertDatabaseHas('ep_logs', [
            'embedded_transaction_id' => $ePTransactionId,
            'event' => 'reversal',
            'loggable_id' => $sendUpdateLog->id,
            'loggable_type' => SendUpdateLog::class,
        ]);

        // 2. Query the database directly and verify
        $epLog = EpLog::where('embedded_transaction_id', $ePTransactionId)
            ->where('event', 'reversal')
            ->first();

        expect($epLog)->not->toBeNull('EPLog should exist in database')
            ->and($epLog->embedded_transaction_id)->toBe($ePTransactionId, 'Embedded transaction ID should match')
            ->and($epLog->event)->toBe('reversal', 'Event should be reversal')
            ->and($epLog->loggable_id)->toBe($sendUpdateLog->id, 'Loggable ID should match SendUpdateLog ID')
            ->and($epLog->loggable_type)->toBe(SendUpdateLog::class, 'Loggable type should be SendUpdateLog');

        // 3. Verify JSON values are correctly stored and retrievable
        $decodedValues = json_decode($epLog->values, true);

        expect($decodedValues)->toBeArray()
            ->and($decodedValues)->toHaveKey('message')
            ->and($decodedValues['message'])->toContain('Sage reversal triggered')
            ->and($decodedValues['message'])->toContain('≤ 30 days')
            ->and($decodedValues['diff_in_days'])->toBe(18)
            ->and($decodedValues)->toHaveKey('reversal_at')
            ->and($decodedValues['test_data'])->toBe('This is a real database test');

        // 4. Verify relationship works - query through SendUpdateLog
        $sendUpdateLog->refresh();
        $relatedEpLogs = $sendUpdateLog->epLogs;

        expect($relatedEpLogs)->not->toBeNull()
            ->and($relatedEpLogs->count())->toBeGreaterThan(0)
            ->and($relatedEpLogs->first()->embedded_transaction_id)->toBe($ePTransactionId);

        // 5. Verify timestamps are set
        expect($epLog->created_at)->not->toBeNull()
            ->and($epLog->updated_at)->not->toBeNull()
            ->and($epLog->created_at)->toBeInstanceOf(Carbon::class)
            ->and($epLog->updated_at)->toBeInstanceOf(Carbon::class);

        // 6. Verify we can query by different fields
        $foundByEvent = EpLog::where('event', 'reversal')
            ->where('embedded_transaction_id', $ePTransactionId)
            ->exists();

        $foundByLoggable = EpLog::where('loggable_type', SendUpdateLog::class)
            ->where('loggable_id', $sendUpdateLog->id)
            ->where('embedded_transaction_id', $ePTransactionId)
            ->exists();

        expect($foundByEvent)->toBeTrue()
            ->and($foundByLoggable)->toBeTrue();

        // 7. Verify the exact count
        $totalEpLogsForThisSendUpdate = EpLog::where('loggable_id', $sendUpdateLog->id)
            ->where('loggable_type', SendUpdateLog::class)
            ->count();

        expect($totalEpLogsForThisSendUpdate)->toBe(1);

        // Cleanup - Delete test data
        $epLog->delete();
        $sendUpdateLog->delete();

        // Verify cleanup worked
        $this->assertDatabaseMissing('ep_logs', [
            'id' => $epLog->id,
        ]);

        $this->assertDatabaseMissing('send_update_logs', [
            'id' => $sendUpdateLog->id,
        ]);
    });

    test('createEPLog creates multiple entries for same SendUpdateLog correctly', function () {
        // Arrange - Create real SendUpdateLog in database using factory
        $sendUpdateLog = SendUpdateLog::factory()->create();

        $service = Mockery::mock(SageApiService::class)->makePartial();
        $reflection = new ReflectionClass(SageApiService::class);
        $method = $reflection->getMethod('createEPLog');
        $method->setAccessible(true);

        // Act - Create 3 different EP logs
        $transactionIds = [rand(5000, 5999), rand(6000, 6999), rand(7000, 7999)];

        $method->invoke($service, $sendUpdateLog, $transactionIds[0], 'reversal', [
            'reversal_at' => Carbon::now()->toDateTimeString(),
            'diff_in_days' => 10,
            'message' => 'Sage reversal triggered (≤ 30 days)',
        ]);

        $method->invoke($service, $sendUpdateLog, $transactionIds[1], 'reversal', [
            'reversal_at' => Carbon::now()->toDateTimeString(),
            'diff_in_days' => 40,
            'message' => 'Sage reversal suppressed (> 30 days; per insurer agreement).',
        ]);

        $method->invoke($service, $sendUpdateLog, $transactionIds[2], 'reversal', [
            'reversal_at' => Carbon::now()->toDateTimeString(),
            'diff_in_days' => 30,
            'message' => 'Sage reversal triggered (≤ 30 days)',
        ]);

        // Assert - Verify all 3 entries exist in database
        foreach ($transactionIds as $transId) {
            $this->assertDatabaseHas('ep_logs', [
                'embedded_transaction_id' => $transId,
                'event' => 'reversal',
                'loggable_id' => $sendUpdateLog->id,
                'loggable_type' => SendUpdateLog::class,
            ]);
        }

        // Verify through relationship
        $sendUpdateLog->refresh();
        $epLogs = $sendUpdateLog->epLogs;

        expect($epLogs->count())->toBe(3, 'Should have exactly 3 EPLogs')
            ->and($epLogs->pluck('embedded_transaction_id')->toArray())->toEqualCanonicalizing($transactionIds, 'All transaction IDs should be present');

        // Verify different messages are preserved
        $messages = $epLogs->map(function ($log) {
            return json_decode($log->values, true)['message'];
        })->toArray();

        expect($messages)->toHaveCount(3)
            ->and(in_array('Sage reversal triggered (≤ 30 days)', $messages))->toBeTrue('Should contain triggered message')
            ->and(in_array('Sage reversal suppressed (> 30 days; per insurer agreement).', $messages))->toBeTrue('Should contain suppressed message');

        // Cleanup
        $epLogs->each->delete();
        $sendUpdateLog->delete();
    });
});

describe('SageApiService - EP Log Database Integration Tests', function () {
    test('EPLog is actually inserted into database with triggered message', function () {
        // Arrange - Create real SendUpdateLog in database using factory
        $sendUpdateLog = SendUpdateLog::factory()->create();

        $ePTransactionId = 100;
        $event = 'reversal';
        $reversalAt = Carbon::now();
        $diffInDays = 15;
        $maxDays = 30;
        $values = [
            'reversal_at' => $reversalAt->toDateTimeString(),
            'diff_in_days' => $diffInDays,
            'message' => "Sage reversal triggered (≤ {$maxDays} days)",
        ];

        // Act - Call the actual createEPLog method
        $service = Mockery::mock(SageApiService::class)->makePartial();
        $reflection = new ReflectionClass(SageApiService::class);
        $method = $reflection->getMethod('createEPLog');
        $method->setAccessible(true);

        $method->invoke($service, $sendUpdateLog, $ePTransactionId, $event, $values);

        // Assert - Verify database insertion
        $this->assertDatabaseHas('ep_logs', [
            'embedded_transaction_id' => 100,
            'event' => 'reversal',
            'loggable_id' => $sendUpdateLog->id,
            'loggable_type' => SendUpdateLog::class,
        ]);

        // Verify the actual record
        $epLog = EpLog::where('embedded_transaction_id', 100)->first();

        expect($epLog)->not->toBeNull()
            ->and($epLog->event)->toBe('reversal')
            ->and($epLog->embedded_transaction_id)->toBe(100)
            ->and($epLog->loggable_id)->toBe($sendUpdateLog->id)
            ->and($epLog->loggable_type)->toBe(SendUpdateLog::class);

        $decodedValues = json_decode($epLog->values, true);
        expect($decodedValues['message'])->toContain('Sage reversal triggered')
            ->and($decodedValues['message'])->toContain('≤ 30 days')
            ->and($decodedValues['diff_in_days'])->toBe(15)
            ->and($decodedValues)->toHaveKey('reversal_at');

        // Cleanup
        $epLog->delete();
        $sendUpdateLog->delete();
    });

    test('EPLog is actually inserted into database with suppressed message', function () {
        // Arrange - Create real SendUpdateLog in database using factory
        $sendUpdateLog = SendUpdateLog::factory()->create();

        $ePTransactionId = 200;
        $event = 'reversal';
        $reversalAt = Carbon::now();
        $diffInDays = 45;
        $maxDays = 30;
        $values = [
            'reversal_at' => $reversalAt->toDateTimeString(),
            'diff_in_days' => $diffInDays,
            'message' => "Sage reversal suppressed (> {$maxDays} days; per insurer agreement).",
        ];

        // Act
        $service = Mockery::mock(SageApiService::class)->makePartial();
        $reflection = new ReflectionClass(SageApiService::class);
        $method = $reflection->getMethod('createEPLog');
        $method->setAccessible(true);

        $method->invoke($service, $sendUpdateLog, $ePTransactionId, $event, $values);

        // Assert - Verify database insertion
        $this->assertDatabaseHas('ep_logs', [
            'embedded_transaction_id' => 200,
            'event' => 'reversal',
            'loggable_id' => $sendUpdateLog->id,
            'loggable_type' => SendUpdateLog::class,
        ]);

        // Verify the actual record
        $epLog = EpLog::where('embedded_transaction_id', 200)->first();

        expect($epLog)->not->toBeNull()
            ->and($epLog->event)->toBe('reversal')
            ->and($epLog->embedded_transaction_id)->toBe(200);

        $decodedValues = json_decode($epLog->values, true);
        expect($decodedValues['message'])->toContain('Sage reversal suppressed')
            ->and($decodedValues['message'])->toContain('> 30 days')
            ->and($decodedValues['message'])->toContain('per insurer agreement')
            ->and($decodedValues['diff_in_days'])->toBe(45);

        // Cleanup
        $epLog->delete();
        $sendUpdateLog->delete();
    });

    test('EPLog can be queried through SendUpdateLog relationship', function () {
        // Arrange - Create real SendUpdateLog in database using factory
        $sendUpdateLog = SendUpdateLog::factory()->create();

        $values = [
            'reversal_at' => Carbon::now()->toDateTimeString(),
            'diff_in_days' => 20,
            'message' => 'Sage reversal triggered (≤ 30 days)',
        ];

        // Act
        $service = Mockery::mock(SageApiService::class)->makePartial();
        $reflection = new ReflectionClass(SageApiService::class);
        $method = $reflection->getMethod('createEPLog');
        $method->setAccessible(true);

        $method->invoke($service, $sendUpdateLog, 300, 'reversal', $values);

        // Assert - Verify relationship works
        $sendUpdateLog->refresh();
        $epLogs = $sendUpdateLog->epLogs;

        expect($epLogs)->toHaveCount(1)
            ->and($epLogs->first()->embedded_transaction_id)->toBe(300)
            ->and($epLogs->first()->event)->toBe('reversal');

        // Cleanup
        $epLogs->each->delete();
        $sendUpdateLog->delete();
    });

    test('Multiple EPLogs can be created for same SendUpdateLog', function () {
        // Arrange - Create real SendUpdateLog in database using factory
        $sendUpdateLog = SendUpdateLog::factory()->create();

        $service = Mockery::mock(SageApiService::class)->makePartial();
        $reflection = new ReflectionClass(SageApiService::class);
        $method = $reflection->getMethod('createEPLog');
        $method->setAccessible(true);

        // Act - Create multiple logs
        $method->invoke($service, $sendUpdateLog, 400, 'reversal', [
            'reversal_at' => Carbon::now()->toDateTimeString(),
            'diff_in_days' => 10,
            'message' => 'First reversal',
        ]);

        $method->invoke($service, $sendUpdateLog, 401, 'reversal', [
            'reversal_at' => Carbon::now()->toDateTimeString(),
            'diff_in_days' => 35,
            'message' => 'Second reversal',
        ]);

        // Assert
        $sendUpdateLog->refresh();
        $epLogs = $sendUpdateLog->epLogs;

        expect($epLogs)->toHaveCount(2)
            ->and($epLogs->pluck('embedded_transaction_id')->toArray())->toContain(400, 401);

        $decodedValues1 = json_decode($epLogs->where('embedded_transaction_id', 400)->first()->values, true);
        $decodedValues2 = json_decode($epLogs->where('embedded_transaction_id', 401)->first()->values, true);

        expect($decodedValues1['message'])->toBe('First reversal')
            ->and($decodedValues2['message'])->toBe('Second reversal');

        // Cleanup
        $epLogs->each->delete();
        $sendUpdateLog->delete();
    });

    test('EPLog timestamps are automatically set', function () {
        // Arrange - Create real SendUpdateLog in database using factory
        $sendUpdateLog = SendUpdateLog::factory()->create();

        $beforeCreation = Carbon::now()->subSecond();

        // Act
        $service = Mockery::mock(SageApiService::class)->makePartial();
        $reflection = new ReflectionClass(SageApiService::class);
        $method = $reflection->getMethod('createEPLog');
        $method->setAccessible(true);

        $method->invoke($service, $sendUpdateLog, 500, 'reversal', [
            'reversal_at' => Carbon::now()->toDateTimeString(),
            'diff_in_days' => 25,
            'message' => 'Test message',
        ]);

        $afterCreation = Carbon::now()->addSecond();

        // Assert
        $epLog = EpLog::where('embedded_transaction_id', 500)->first();

        expect($epLog->created_at)->not->toBeNull()
            ->and($epLog->updated_at)->not->toBeNull()
            ->and($epLog->created_at->between($beforeCreation, $afterCreation))->toBeTrue();

        // Cleanup
        $epLog->delete();
        $sendUpdateLog->delete();
    });
});

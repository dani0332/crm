<?php

declare(strict_types=1);

use App\Enums\SendUpdateLogStatusEnum;
use App\Models\SendUpdateLog;
use App\Models\SendUpdateStatusLog;
use App\Models\User;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function (): void {
    TestSchemaCreator::createMinimalSchema();
});

it('creates an initial send update status log on create', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $log = SendUpdateLog::factory()->create([
        'status' => SendUpdateLogStatusEnum::NEW_REQUEST,
    ]);

    $row = SendUpdateStatusLog::query()
        ->where('send_update_log_id', $log->id)
        ->sole();

    expect($row->previous_status)->toBe('')
        ->and($row->current_status)->toBe(SendUpdateLogStatusEnum::NEW_REQUEST)
        ->and((int) $row->created_by)->toBe((int) $user->id);
});

it('appends a send update status log when status changes', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $log = SendUpdateLog::factory()->create([
        'status' => SendUpdateLogStatusEnum::NEW_REQUEST,
    ]);

    SendUpdateStatusLog::query()->where('send_update_log_id', $log->id)->delete();

    $log->update(['status' => SendUpdateLogStatusEnum::REQUEST_IN_PROGRESS]);

    $row = SendUpdateStatusLog::query()
        ->where('send_update_log_id', $log->id)
        ->latest('id')
        ->first();

    expect($row)->not->toBeNull()
        ->and($row->previous_status)->toBe(SendUpdateLogStatusEnum::NEW_REQUEST)
        ->and($row->current_status)->toBe(SendUpdateLogStatusEnum::REQUEST_IN_PROGRESS)
        ->and((int) $row->created_by)->toBe((int) $user->id);
});

it('exposes send update status logs on the send update log model', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $log = SendUpdateLog::factory()->create([
        'status' => SendUpdateLogStatusEnum::NEW_REQUEST,
    ]);

    expect($log->sendUpdateStatusLogs)->toHaveCount(1)
        ->and($log->sendUpdateStatusLogs->first()->send_update_log_id)->toBe($log->id);
});

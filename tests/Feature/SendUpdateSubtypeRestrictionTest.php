<?php

declare(strict_types=1);

use App\Enums\SendUpdateLogStatusEnum;
use App\Models\Payment;
use App\Models\SendUpdateLog;
use Illuminate\Database\Schema\Blueprint;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;
use Tests\Support\Schema\SchemaUtils;

beforeEach(function (): void {
    TestSchemaCreator::createMinimalSchema();

    SchemaUtils::ensureColumns([
        'send_update_logs' => [
            'notes' => fn (Blueprint $t) => $t->text('notes')->nullable(),
            'emirates_id' => fn (Blueprint $t) => $t->unsignedBigInteger('emirates_id')->nullable(),
            'seating_capacity' => fn (Blueprint $t) => $t->unsignedSmallInteger('seating_capacity')->nullable(),
            'endorsement_number' => fn (Blueprint $t) => $t->string('endorsement_number')->nullable(),
        ],
    ]);
});

it('allows endorsement subtype change when no payments exist', function (): void {
    $user = TestDataSeeder::createUser();
    $this->actingAs($user);

    $log = SendUpdateLog::factory()->create(['option_id' => 1]);

    $response = $this->patch(route('send-update.update', $log->id), [
        'option_id' => 2,
        'notes' => 'test notes',
        'code' => $log->code,
        'quote_uuid' => $log->quote_uuid,
        'quote_type_id' => $log->quote_type_id,
        'status' => SendUpdateLogStatusEnum::NEW_REQUEST,
        'childCategory' => ['slug' => 'XX', 'option' => ['slug' => '']],
    ]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();

    expect($log->fresh()->option_id)->toBe(2);
});

it('blocks endorsement subtype change when payments already exist', function (): void {
    $user = TestDataSeeder::createUser();
    $this->actingAs($user);

    $log = SendUpdateLog::factory()->create(['option_id' => 1]);
    Payment::factory()->create([
        'send_update_log_id' => $log->id,
        'paymentable_type' => SendUpdateLog::class,
        'paymentable_id' => $log->id,
    ]);

    $response = $this->patch(route('send-update.update', $log->id), [
        'option_id' => 2,
        'notes' => 'test notes',
        'code' => $log->code,
        'quote_uuid' => $log->quote_uuid,
        'quote_type_id' => $log->quote_type_id,
        'status' => SendUpdateLogStatusEnum::NEW_REQUEST,
    ]);

    $response->assertSessionHasErrors(['error']);
    expect($response->getSession()->get('errors')->getBag('default')->first('error'))
        ->toContain('Endorsement subtype cannot be changed because payment has already been added');

    expect($log->fresh()->option_id)->toBe(1);
});

it('allows saving same subtype when payments already exist', function (): void {
    $user = TestDataSeeder::createUser();
    $this->actingAs($user);

    $log = SendUpdateLog::factory()->create(['option_id' => 1]);
    Payment::factory()->create([
        'send_update_log_id' => $log->id,
        'paymentable_type' => SendUpdateLog::class,
        'paymentable_id' => $log->id,
    ]);

    $response = $this->patch(route('send-update.update', $log->id), [
        'option_id' => 1,
        'notes' => 'updated notes',
        'code' => $log->code,
        'quote_uuid' => $log->quote_uuid,
        'quote_type_id' => $log->quote_type_id,
        'status' => SendUpdateLogStatusEnum::NEW_REQUEST,
        'childCategory' => ['slug' => 'XX', 'option' => ['slug' => '']],
    ]);

    $response->assertSessionHasNoErrors();
});

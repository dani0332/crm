<?php

declare(strict_types=1);

use App\Console\Commands\SageProcessesMarkFailedCommand;
use App\Enums\QuoteStatusEnum;
use App\Enums\SageEnum;
use App\Models\CarQuote;
use App\Models\Customer;
use App\Models\QuoteStatusLog;
use App\Models\SageProcess;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function (): void {
    TestSchemaCreator::createMinimalSchema();

    if (! Schema::hasTable('quote_status_log')) {
        Schema::create('quote_status_log', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('quote_type_id')->nullable();
            $table->unsignedBigInteger('quote_request_id')->nullable();
            $table->unsignedBigInteger('current_quote_status_id')->nullable();
            $table->unsignedBigInteger('previous_quote_status_id')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('personal_quote_id')->nullable();
            $table->timestamps();
        });
    }
});

test('mark-failed command records sage policy booking timeout action on quote status log', function (): void {
    Queue::fake();

    $customer = Customer::query()->create([
        'first_name' => 'Test',
        'last_name' => 'Customer',
        'email' => 'sage-timeout-test@example.com',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $quote = CarQuote::factory()->create([
        'customer_id' => $customer->id,
        'quote_status_id' => QuoteStatusEnum::POLICY_BOOKING_QUEUED,
    ]);

    $requestPayload = (object) [
        'model_type' => 'Car',
    ];
    $sagePayload = (object) [
        'sageProcessRequestType' => SageEnum::SAGE_PROCESS_BOOK_POLICY_REQUEST,
    ];

    $sageProcess = SageProcess::query()->create([
        'user_id' => null,
        'insurance_provider_id' => null,
        'model_type' => CarQuote::class,
        'model_id' => $quote->id,
        'request' => json_encode([
            'sagePayload' => $sagePayload,
            'requestPayload' => $requestPayload,
        ]),
        'status' => SageEnum::SAGE_PROCESS_PROCESSING_STATUS,
    ]);

    $sageProcess->forceFill(['updated_at' => now()->subMinutes(6)])->saveQuietly();

    app(SageProcessesMarkFailedCommand::class)->handle();

    expect($quote->fresh()->quote_status_id)->toBe(QuoteStatusEnum::POLICY_BOOKING_FAILED);

    $log = QuoteStatusLog::query()
        ->where('quote_request_id', $quote->id)
        ->where('current_quote_status_id', QuoteStatusEnum::POLICY_BOOKING_FAILED)
        ->latest('id')
        ->first();

    expect($log)->not->toBeNull();
});

<?php

declare(strict_types=1);

use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Models\CarQuote;
use App\Models\Customer;
use App\Models\QuoteStatusLog;
use App\Models\User;
use App\Services\SageApiService;
use Illuminate\Database\Schema\Blueprint;
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

afterEach(function (): void {
    Mockery::close();
});

test('updateAndLogQuoteStatus creates one status log via observer when status changes to policy booked', function (): void {
    User::factory()->create();

    $customer = Customer::query()->create([
        'first_name' => 'Test',
        'last_name' => 'Customer',
        'email' => 'sage-update-log-observer@example.com',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $quote = CarQuote::factory()->create([
        'customer_id' => $customer->id,
        'quote_status_id' => QuoteStatusEnum::POLICY_BOOKING_QUEUED,
    ]);

    $partial = Mockery::mock(SageApiService::class)->makePartial();
    $partial->shouldReceive('updateStatusesAndAllocate')->once();

    $partial->updateAndLogQuoteStatus($quote, QuoteTypeId::Car, QuoteStatusEnum::PolicyBooked);

    $quote->refresh();

    expect($quote->quote_status_id)->toBe(QuoteStatusEnum::PolicyBooked)
        ->and(QuoteStatusLog::query()->where('quote_request_id', $quote->id)->count())->toBe(1);
});

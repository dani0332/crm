<?php

declare(strict_types=1);

use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Models\CarQuote;
use App\Models\Customer;
use App\Models\QuoteStatusLog;
use App\Repositories\SendUpdateLogRepository;
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

test('updateQuoteStatusLog records send update booking sync via observer and context', function (): void {
    $customer = Customer::query()->create([
        'first_name' => 'SU',
        'last_name' => 'Quote',
        'email' => 'send-update-quote-test@example.com',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $quote = CarQuote::factory()->create([
        'customer_id' => $customer->id,
        'quote_status_id' => QuoteStatusEnum::PolicyBooked,
        'policy_booking_date' => now()->toDateString(),
    ]);

    $sendUpdateLogId = 9001;

    SendUpdateLogRepository::updateQuoteStatusLog(
        QuoteTypeId::Car,
        $quote->uuid,
        QuoteStatusEnum::PolicyCancelled,
        $sendUpdateLogId,
    );

    $log = QuoteStatusLog::query()
        ->where('quote_request_id', $quote->id)
        ->where('quote_type_id', QuoteTypeId::Car)
        ->latest('id')
        ->first();

    expect($log)->not->toBeNull();
});

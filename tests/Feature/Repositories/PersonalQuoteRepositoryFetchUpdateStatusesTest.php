<?php

declare(strict_types=1);

use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Events\QuoteEmailUpdated;
use App\Models\Customer;
use App\Models\PersonalQuote;
use App\Models\QuoteStatusLog;
use App\Repositories\PersonalQuoteRepository;
use App\Services\CentralService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\Helpers\TestSchemaCreator;

afterEach(function (): void {
    Mockery::close();
});

beforeEach(function (): void {
    TestSchemaCreator::createMinimalSchema();

    if (Schema::hasTable('personal_quotes') && ! Schema::hasColumn('personal_quotes', 'stale_at')) {
        Schema::table('personal_quotes', function (Blueprint $table): void {
            $table->timestamp('stale_at')->nullable();
        });
    }
    if (Schema::hasTable('personal_quotes') && ! Schema::hasColumn('personal_quotes', 'quote_status_date')) {
        Schema::table('personal_quotes', function (Blueprint $table): void {
            $table->timestamp('quote_status_date')->nullable();
        });
    }

    if (! Schema::hasTable('quote_status_log')) {
        Schema::create('quote_status_log', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('quote_type_id')->nullable();
            $table->unsignedBigInteger('quote_request_id')->nullable();
            $table->unsignedBigInteger('current_quote_status_id')->nullable();
            $table->unsignedBigInteger('previous_quote_status_id')->nullable();
            $table->string('status_change_source')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('personal_quote_id')->nullable();
            $table->timestamps();
        });
    }
});

test('fetchUpdateStatuses persists lead history via observer and status change action context', function (): void {
    Queue::fake();
    Event::fake([QuoteEmailUpdated::class]);

    $centralService = Mockery::mock(CentralService::class);
    $centralService->shouldReceive('saveAndAssignActivitesToAdvisor')->once()->andReturn(true);
    $this->instance(CentralService::class, $centralService);

    $customer = Customer::query()->create([
        'first_name' => 'PQ',
        'last_name' => 'Repo',
        'email' => 'personal-quote-repo-test@example.com',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $quote = PersonalQuote::query()->create([
        'uuid' => 'PQ-REPO-'.uniqid(),
        'code' => 'PQ-REPO-TEST',
        'quote_type_id' => QuoteTypeId::Health,
        'customer_id' => $customer->id,
        'first_name' => 'Test',
        'last_name' => 'Lead',
        'email' => 'lead@example.com',
        'mobile_no' => '+971500000000',
        'source' => 'IMCRM',
        'device' => 'web',
        'quote_status_id' => QuoteStatusEnum::NewLead,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    PersonalQuoteRepository::updateStatuses('Health', $quote->id, [
        'quote_status_id' => QuoteStatusEnum::Qualified,
    ]);

    $log = QuoteStatusLog::query()
        ->where('quote_request_id', $quote->id)
        ->where('quote_type_id', QuoteTypeId::Health)
        ->latest('id')
        ->first();

    expect($log)->not->toBeNull();
});

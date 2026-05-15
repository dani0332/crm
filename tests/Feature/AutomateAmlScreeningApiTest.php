<?php

use App\Enums\AmlAutomationStatus;
use App\Enums\AMLStatusCode;
use App\Enums\ApplicationStorageEnums;
use App\Enums\PolicyIssuanceEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Http\Middleware\BasicAuth;
use App\Jobs\AmlScreeningAutomationJob;
use App\Models\AmlAutomation;
use App\Models\ApplicationStorage;
use App\Models\CustomerInsured;
use App\Models\Insured;
use App\Models\PersonalQuote;
use App\Models\User;
use App\Support\AmlQuoteAutomation\AmlAutomatableLobRegistry;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Schema;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
    $this->withoutMiddleware(BasicAuth::class);

    Schema::dropIfExists('aml_automation');
    Schema::create('aml_automation', function (Blueprint $table) {
        $table->id();
        $table->string('code')->unique();
        $table->string('status')->nullable();
        $table->text('result')->nullable();
        $table->timestamps();
    });

    if (! Schema::hasColumn('personal_quotes', 'aml_status')) {
        Schema::table('personal_quotes', function (Blueprint $table) {
            $table->string('aml_status')->nullable()->after('insurer_aml_status');
        });
    }
    if (! Schema::hasColumn('personal_quotes', 'api_issuance_status_id')) {
        Schema::table('personal_quotes', function (Blueprint $table) {
            $table->unsignedBigInteger('api_issuance_status_id')->nullable()->after('payment_status_id');
        });
    }
    if (! Schema::hasColumn('personal_quotes', 'nationality_id')) {
        Schema::table('personal_quotes', function (Blueprint $table) {
            $table->unsignedBigInteger('nationality_id')->nullable()->after('customer_id');
        });
    }
    if (! Schema::hasColumn('personal_quotes', 'gender')) {
        Schema::table('personal_quotes', function (Blueprint $table) {
            $table->string('gender')->nullable()->after('nationality_id');
        });
    }

    ApplicationStorage::query()->updateOrInsert(
        ['key_name' => ApplicationStorageEnums::AML_AUTOMATION_ENABLED],
        [
            'value' => '1',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]
    );
});

afterEach(function () {
    Mockery::close();
});

test('returns 404 when quote uuid is unknown', function () {
    $response = $this->postJson('/api/v1/imcrm/quotes/automate-aml-screening', [
        'quoteUuid' => '01900000-0000-7000-8000-000000000001',
        'quoteType' => QuoteTypes::SAVINGS->value,
    ]);

    $response->assertStatus(404)
        ->assertJson(['success' => false]);
});

test('returns 422 when quoteType is not in the automatable LOB registry', function () {
    $uuid = '01900000-0000-7000-8000-000000000002';
    PersonalQuote::create([
        'uuid' => $uuid,
        'code' => 'CAR-TEST',
        'quote_type_id' => 1,
        'customer_id' => 1,
        'first_name' => 'A',
        'last_name' => 'B',
        'email' => 'c@example.com',
        'api_issuance_status_id' => PolicyIssuanceEnum::POLICY_ISSUANCE_API_STATUS_YES_ID,
        'aml_status' => AMLStatusCode::AMLPending,
        'nationality_id' => 1,
        'gender' => 'male',
    ]);

    $response = $this->postJson('/api/v1/imcrm/quotes/automate-aml-screening', [
        'quoteUuid' => $uuid,
        'quoteType' => QuoteTypes::CAR->value,
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['quoteType']);
});

test('returns 422 when customer insured data is missing', function () {
    $uuid = '01900000-0000-7000-8000-000000000003';
    PersonalQuote::create([
        'uuid' => $uuid,
        'code' => 'SAV-TEST',
        'quote_type_id' => QuoteTypeId::Savings,
        'customer_id' => 1,
        'first_name' => 'A',
        'last_name' => 'B',
        'email' => 'c@example.com',
        'dob' => '1990-01-01',
        'api_issuance_status_id' => PolicyIssuanceEnum::POLICY_ISSUANCE_API_STATUS_YES_ID,
        'aml_status' => AMLStatusCode::AMLPending,
        'nationality_id' => 1,
        'gender' => 'male',
    ]);

    $response = $this->postJson('/api/v1/imcrm/quotes/automate-aml-screening', [
        'quoteUuid' => $uuid,
        'quoteType' => QuoteTypes::SAVINGS->value,
    ]);

    $response->assertStatus(422)
        ->assertJsonFragment(['success' => false]);
});

test('dispatches aml screening job for valid savings quote', function () {
    Bus::fake();

    $advisor = User::factory()->create([
        'email' => 'adv@example.com',
        'last_login' => now(),
    ]);
    $uuid = '01900000-0000-7000-8000-000000000004';

    $personalQuote = PersonalQuote::create([
        'uuid' => $uuid,
        'code' => 'SAV-OK',
        'quote_type_id' => QuoteTypeId::Savings,
        'customer_id' => 1,
        'advisor_id' => $advisor->id,
        'first_name' => 'A',
        'last_name' => 'B',
        'email' => 'customer@example.com',
        'dob' => '1990-01-01',
        'api_issuance_status_id' => PolicyIssuanceEnum::POLICY_ISSUANCE_API_STATUS_YES_ID,
        'aml_status' => AMLStatusCode::AMLPending,
        'nationality_id' => 1,
        'gender' => 'male',
    ]);

    $insured = Insured::create([
        'customer_type' => 'Individual',
        'first_name' => 'A',
        'last_name' => 'B',
        'dob' => '1990-01-01',
        'nationality_id' => 1,
        'gender' => 'male',
        'id_type' => 'passport',
        'id_number' => 'AB1234567',
    ]);

    CustomerInsured::create([
        'quote_type_id' => QuoteTypeId::Savings,
        'quote_request_id' => $personalQuote->id,
        'insured_id' => $insured->id,
        'customer_id' => 1,
        'is_active' => true,
    ]);

    $response = $this->postJson('/api/v1/imcrm/quotes/automate-aml-screening', [
        'quoteUuid' => $uuid,
        'quoteType' => QuoteTypes::SAVINGS->value,
    ]);

    $response->assertSuccessful()
        ->assertJson(['success' => true]);

    Bus::assertDispatched(AmlScreeningAutomationJob::class);
});

test('returns 422 when aml automation row blocks re-dispatch', function (string $status, string $expectedMessage) {
    Bus::fake();

    $advisor = User::factory()->create([
        'email' => 'block@example.com',
        'last_login' => now(),
    ]);
    $uuid = '01900000-0000-7000-8000-000000000005';

    $personalQuote = PersonalQuote::create([
        'uuid' => $uuid,
        'code' => 'SAV-BLOCK',
        'quote_type_id' => QuoteTypeId::Savings,
        'customer_id' => 1,
        'advisor_id' => $advisor->id,
        'first_name' => 'A',
        'last_name' => 'B',
        'email' => 'customer@example.com',
        'dob' => '1990-01-01',
        'api_issuance_status_id' => PolicyIssuanceEnum::POLICY_ISSUANCE_API_STATUS_YES_ID,
        'aml_status' => AMLStatusCode::AMLPending,
        'nationality_id' => 1,
        'gender' => 'male',
    ]);

    $insured = Insured::create([
        'customer_type' => 'Individual',
        'first_name' => 'A',
        'last_name' => 'B',
        'dob' => '1990-01-01',
        'nationality_id' => 1,
        'gender' => 'male',
        'id_type' => 'passport',
        'id_number' => 'AB1234567',
    ]);

    CustomerInsured::create([
        'quote_type_id' => QuoteTypeId::Savings,
        'quote_request_id' => $personalQuote->id,
        'insured_id' => $insured->id,
        'customer_id' => 1,
        'is_active' => true,
    ]);

    AmlAutomation::create([
        'code' => 'SAV-BLOCK',
        'status' => $status,
        'result' => null,
    ]);

    $response = $this->postJson('/api/v1/imcrm/quotes/automate-aml-screening', [
        'quoteUuid' => $uuid,
        'quoteType' => QuoteTypes::SAVINGS->value,
    ]);

    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
            'message' => $expectedMessage,
        ]);

    Bus::assertNothingDispatched();
})->with([
    [AmlAutomationStatus::Complete->value, 'AML automation already completed or in progress'],
    [AmlAutomationStatus::Processing->value, 'AML automation already completed or in progress'],
    [AmlAutomationStatus::Queue->value, 'AML automation already queued'],
]);

test('aml automatable lob registry allows savings only', function () {
    expect(AmlAutomatableLobRegistry::allows(QuoteTypes::SAVINGS))->toBeTrue();
    expect(AmlAutomatableLobRegistry::allows(QuoteTypes::CAR))->toBeFalse();
});

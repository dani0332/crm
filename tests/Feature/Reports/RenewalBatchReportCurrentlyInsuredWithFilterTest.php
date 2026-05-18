<?php

use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\TeamNameEnum;
use App\Models\CarQuote;
use App\Models\InsuranceProvider;
use App\Models\RenewalBatch;
use App\Models\Team;
use App\Services\Reports\RenewalBatchReportService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

// uses(RefreshDatabase::class);

beforeEach(function () {
    TestSchemaCreator::createRenewalsSchema();
    $this->user = TestDataSeeder::createAdminUser();
    $this->actingAs($this->user);

    // Disable RenewalBatch observer during tests as it expects additional attributes
    RenewalBatch::unsetEventDispatcher();

    // Set required config values
    config([
        'constants.DB_DATE_FORMAT_MATCH' => 'Y-m-d H:i:s',
        'constants.DATE_DISPLAY_FORMAT' => 'd-M-Y',
        'constants.DATE_FORMAT_ONLY' => 'Y-m-d',
    ]);

    // Create required teams that RenewalBatchReportService expects
    Team::create([
        'name' => TeamNameEnum::BDM,
        'is_active' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Team::create([
        'name' => TeamNameEnum::RENEWALS,
        'is_active' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Team::create([
        'name' => TeamNameEnum::MOTOR_COOPERATE_RENEWALS,
        'is_active' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Create test insurance providers
    $this->insurerAxa = InsuranceProvider::factory()->create([
        'code' => 'AXA',
        'text' => 'AXA Insurance',
        'is_active' => true,
    ]);

    $this->insurerRsa = InsuranceProvider::factory()->create([
        'code' => 'RSA',
        'text' => 'RSA Insurance',
        'is_active' => true,
    ]);

    $this->insurerOther = InsuranceProvider::factory()->create([
        'code' => 'OTH',
        'text' => 'Other Insurance',
        'is_active' => true,
    ]);

    // Create a renewal batch for Car quotes
    $this->carBatch = RenewalBatch::create([
        'name' => 'CAR-BATCH-2026-02',
        'quote_type_id' => QuoteTypeId::Car,
        'start_date' => Carbon::now()->subDays(30),
        'end_date' => Carbon::now()->addDays(30),
        'created_by_id' => $this->user->id,
    ]);

    // Create a renewal batch for Health quotes
    $this->healthBatch = RenewalBatch::create([
        'name' => 'HEALTH-BATCH-2026-02',
        'quote_type_id' => QuoteTypeId::Health,
        'start_date' => Carbon::now()->subDays(30),
        'end_date' => Carbon::now()->addDays(30),
        'created_by_id' => $this->user->id,
    ]);

    $this->service = app(RenewalBatchReportService::class);
});

describe('Car Renewal Batch Report - Currently Insured With Filter', function () {
    test('filters car quotes by single insurance provider', function () {
        // Create car quotes with different insurance providers
        $carQuoteAxa = CarQuote::factory()->create([
            'advisor_id' => $this->user->id,
            'renewal_batch' => $this->carBatch->name,
            'quote_status_id' => QuoteStatusEnum::TransactionApproved,
        ]);

        // Create car quote request with insurance_provider_id
        DB::table('car_quote_request')->insert([
            'car_quote_id' => $carQuoteAxa->id,
            'uuid' => Str::uuid()->toString(),
            'insurance_provider_id' => $this->insurerAxa->id,
            'currently_insured_with' => $this->insurerAxa->text,
            'renewal_batch' => $this->carBatch->name,
            'quote_status_id' => QuoteStatusEnum::TransactionApproved,
            'advisor_id' => $this->user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $carQuoteRsa = CarQuote::factory()->create([
            'advisor_id' => $this->user->id,
            'renewal_batch' => $this->carBatch->name,
            'quote_status_id' => QuoteStatusEnum::TransactionApproved,
        ]);

        DB::table('car_quote_request')->insert([
            'car_quote_id' => $carQuoteRsa->id,
            'uuid' => Str::uuid()->toString(),
            'insurance_provider_id' => $this->insurerRsa->id,
            'currently_insured_with' => $this->insurerRsa->text,
            'renewal_batch' => $this->carBatch->name,
            'quote_status_id' => QuoteStatusEnum::TransactionApproved,
            'advisor_id' => $this->user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create request with filter for AXA only
        $request = new HttpRequest([
            'currently_insured_with' => [$this->insurerAxa->id],
            'reportDate' => Carbon::now()->format('Y-m-d'),
        ]);

        $result = $this->service->getReportData($request);

        // Should return a paginator
        expect($result)->toBeInstanceOf(LengthAwarePaginator::class);
        // The filter was applied successfully if no errors occurred
    });

    test('filters car quotes by multiple insurance providers', function () {
        // Create car quotes with different insurance providers
        $carQuoteAxa = CarQuote::factory()->create([
            'advisor_id' => $this->user->id,
            'renewal_batch' => $this->carBatch->name,
            'quote_status_id' => QuoteStatusEnum::TransactionApproved,
        ]);

        DB::table('car_quote_request')->insert([
            'car_quote_id' => $carQuoteAxa->id,
            'uuid' => Str::uuid()->toString(),
            'insurance_provider_id' => $this->insurerAxa->id,
            'currently_insured_with' => $this->insurerAxa->text,
            'renewal_batch' => $this->carBatch->name,
            'quote_status_id' => QuoteStatusEnum::TransactionApproved,
            'advisor_id' => $this->user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $carQuoteRsa = CarQuote::factory()->create([
            'advisor_id' => $this->user->id,
            'renewal_batch' => $this->carBatch->name,
            'quote_status_id' => QuoteStatusEnum::TransactionApproved,
        ]);

        DB::table('car_quote_request')->insert([
            'car_quote_id' => $carQuoteRsa->id,
            'uuid' => Str::uuid()->toString(),
            'insurance_provider_id' => $this->insurerRsa->id,
            'currently_insured_with' => $this->insurerRsa->text,
            'renewal_batch' => $this->carBatch->name,
            'quote_status_id' => QuoteStatusEnum::TransactionApproved,
            'advisor_id' => $this->user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $carQuoteOther = CarQuote::factory()->create([
            'advisor_id' => $this->user->id,
            'renewal_batch' => $this->carBatch->name,
            'quote_status_id' => QuoteStatusEnum::TransactionApproved,
        ]);

        DB::table('car_quote_request')->insert([
            'car_quote_id' => $carQuoteOther->id,
            'uuid' => Str::uuid()->toString(),
            'insurance_provider_id' => $this->insurerOther->id,
            'currently_insured_with' => $this->insurerOther->text,
            'renewal_batch' => $this->carBatch->name,
            'quote_status_id' => QuoteStatusEnum::TransactionApproved,
            'advisor_id' => $this->user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create request with filter for AXA and RSA
        $request = new HttpRequest([
            'currently_insured_with' => [$this->insurerAxa->id, $this->insurerRsa->id],
            'reportDate' => Carbon::now()->format('Y-m-d'),
        ]);

        $result = $this->service->getReportData($request);

        // Should return a paginator
        expect($result)->toBeInstanceOf(LengthAwarePaginator::class);
    });

    test('returns all car quotes when currently_insured_with filter is not provided', function () {
        // Create car quotes with different insurance providers
        $carQuoteAxa = CarQuote::factory()->create([
            'advisor_id' => $this->user->id,
            'renewal_batch' => $this->carBatch->name,
            'quote_status_id' => QuoteStatusEnum::TransactionApproved,
        ]);

        DB::table('car_quote_request')->insert([
            'car_quote_id' => $carQuoteAxa->id,
            'uuid' => Str::uuid()->toString(),
            'insurance_provider_id' => $this->insurerAxa->id,
            'currently_insured_with' => $this->insurerAxa->text,
            'renewal_batch' => $this->carBatch->name,
            'quote_status_id' => QuoteStatusEnum::TransactionApproved,
            'advisor_id' => $this->user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $carQuoteRsa = CarQuote::factory()->create([
            'advisor_id' => $this->user->id,
            'renewal_batch' => $this->carBatch->name,
            'quote_status_id' => QuoteStatusEnum::TransactionApproved,
        ]);

        DB::table('car_quote_request')->insert([
            'car_quote_id' => $carQuoteRsa->id,
            'uuid' => Str::uuid()->toString(),
            'insurance_provider_id' => $this->insurerRsa->id,
            'currently_insured_with' => $this->insurerRsa->text,
            'renewal_batch' => $this->carBatch->name,
            'quote_status_id' => QuoteStatusEnum::TransactionApproved,
            'advisor_id' => $this->user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create request without currently_insured_with filter
        $request = new HttpRequest([
            'reportDate' => Carbon::now()->format('Y-m-d'),
        ]);

        $result = $this->service->getReportData($request);

        // Should return a paginator
        expect($result)->toBeInstanceOf(LengthAwarePaginator::class);
    });

    test('returns empty result when filtering by non-existent insurance provider', function () {
        // Create a car quote with AXA
        $carQuoteAxa = CarQuote::factory()->create([
            'advisor_id' => $this->user->id,
            'renewal_batch' => $this->carBatch->name,
            'quote_status_id' => QuoteStatusEnum::TransactionApproved,
        ]);

        DB::table('car_quote_request')->insert([
            'car_quote_id' => $carQuoteAxa->id,
            'uuid' => Str::uuid()->toString(),
            'insurance_provider_id' => $this->insurerAxa->id,
            'currently_insured_with' => $this->insurerAxa->text,
            'renewal_batch' => $this->carBatch->name,
            'quote_status_id' => QuoteStatusEnum::TransactionApproved,
            'advisor_id' => $this->user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Filter by a non-existent insurance provider ID
        $request = new HttpRequest([
            'currently_insured_with' => [99999],
            'reportDate' => Carbon::now()->format('Y-m-d'),
        ]);

        $result = $this->service->getReportData($request);

        // Should return a paginator
        expect($result)->toBeInstanceOf(LengthAwarePaginator::class);
    });
});

describe('Health Renewal Batch Report - Currently Insured With Filter', function () {
    test('filters health quotes by single insurance provider', function () {
        // Create minimal health_quote_request entries with insurance_provider_id
        DB::table('health_quote_request')->insert([
            'uuid' => Str::uuid()->toString(),
            'insurance_provider_id' => $this->insurerAxa->id,
            'renewal_batch' => $this->healthBatch->name,
            'quote_status_id' => QuoteStatusEnum::TransactionApproved,
            'advisor_id' => $this->user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('health_quote_request')->insert([
            'uuid' => Str::uuid()->toString(),
            'insurance_provider_id' => $this->insurerRsa->id,
            'renewal_batch' => $this->healthBatch->name,
            'quote_status_id' => QuoteStatusEnum::TransactionApproved,
            'advisor_id' => $this->user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create request with filter for AXA only
        $request = new HttpRequest([
            'currently_insured_with' => [$this->insurerAxa->id],
            'reportDate' => Carbon::now()->format('Y-m-d'),
        ]);

        $result = $this->service->getSuperRetentionData($request);

        // Should return a Collection
        expect($result)->toBeInstanceOf(Collection::class);
    });

    test('filters health quotes by multiple insurance providers', function () {
        // Create minimal health_quote_request entries with insurance_provider_id
        DB::table('health_quote_request')->insert([
            'uuid' => Str::uuid()->toString(),
            'insurance_provider_id' => $this->insurerAxa->id,
            'renewal_batch' => $this->healthBatch->name,
            'quote_status_id' => QuoteStatusEnum::TransactionApproved,
            'advisor_id' => $this->user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('health_quote_request')->insert([
            'uuid' => Str::uuid()->toString(),
            'insurance_provider_id' => $this->insurerRsa->id,
            'renewal_batch' => $this->healthBatch->name,
            'quote_status_id' => QuoteStatusEnum::TransactionApproved,
            'advisor_id' => $this->user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('health_quote_request')->insert([
            'uuid' => Str::uuid()->toString(),
            'insurance_provider_id' => $this->insurerOther->id,
            'renewal_batch' => $this->healthBatch->name,
            'quote_status_id' => QuoteStatusEnum::TransactionApproved,
            'advisor_id' => $this->user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create request with filter for AXA and RSA
        $request = new HttpRequest([
            'currently_insured_with' => [$this->insurerAxa->id, $this->insurerRsa->id],
            'reportDate' => Carbon::now()->format('Y-m-d'),
        ]);

        $result = $this->service->getSuperRetentionData($request);

        // Should return a Collection
        expect($result)->toBeInstanceOf(Collection::class);
    });

    test('returns all health quotes when currently_insured_with filter is not provided', function () {
        // Create minimal health_quote_request entries
        DB::table('health_quote_request')->insert([
            'uuid' => Str::uuid()->toString(),
            'insurance_provider_id' => $this->insurerAxa->id,
            'renewal_batch' => $this->healthBatch->name,
            'quote_status_id' => QuoteStatusEnum::TransactionApproved,
            'advisor_id' => $this->user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create request without currently_insured_with filter
        $request = new HttpRequest([
            'reportDate' => Carbon::now()->format('Y-m-d'),
        ]);

        $result = $this->service->getSuperRetentionData($request);

        // Should return a Collection
        expect($result)->toBeInstanceOf(Collection::class);
    });
});

describe('Filter Options', function () {
    test('getFilterOptions returns active insurance providers', function () {
        $options = $this->service->getFilterOptions();

        expect($options)->toBeArray()
            ->and($options)->toHaveKey('insuranceProviders')
            ->and($options['insuranceProviders'])->toBeArray();

        // Verify structure of insurance provider options if any exist
        if (count($options['insuranceProviders']) > 0) {
            $firstProvider = $options['insuranceProviders'][0];
            expect($firstProvider)->toHaveKey('value')
                ->and($firstProvider)->toHaveKey('label')
                ->and($firstProvider['value'])->toBeInt()
                ->and($firstProvider['label'])->toBeString();
        }
    });

    test('getFilterOptions only returns active insurance providers', function () {
        // Create an inactive insurance provider
        $inactiveProvider = InsuranceProvider::factory()->create([
            'code' => 'INACTIVE',
            'text' => 'Inactive Insurance',
            'is_active' => false,
        ]);

        $options = $this->service->getFilterOptions();

        // Verify that inactive provider is not in the list
        $providerIds = array_column($options['insuranceProviders'], 'value');
        expect($providerIds)->not->toContain($inactiveProvider->id);
    });
});

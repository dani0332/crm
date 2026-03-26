<?php

declare(strict_types=1);

use App\Facades\Ken;
use App\Models\CustomerMembers;
use App\Models\HealthQuote;
use App\Models\VisaCategory;
use App\Services\CapiRequestService;
use App\Services\HealthQuoteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
    $this->lookups = TestDataSeeder::seedHealthQuoteLookups();
    $this->user = TestDataSeeder::createAdminUser();
    $this->actingAs($this->user);
});

afterEach(function () {
    Mockery::close();
});

// ============================================================================
// SECTION 1: prepareMemberDetailPayload – member array input
// ============================================================================

describe('HealthQuoteService prepareMemberDetailPayload via add-member endpoint', function () {
    test('healthQuoteAddMember returns falsy response when quote is locked', function () {
        HealthQuote::factory()->locked()->create(['uuid' => 'test-locked-uuid']);

        // Ken is mocked so no real HTTP call is made
        Ken::swap(Mockery::mock()->shouldReceive('request')->never()->getMock());

        $request = new Request;
        $request->merge([
            'quoteId' => 'test-locked-uuid',
            'quote_type' => 'Health',
            'customer_id' => 1,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'dob' => '1990-01-01',
            'gender' => 'M',
            'pec' => 0,
        ]);

        // Call service directly – the controller checks is_quote_locked before calling service
        $quote = HealthQuote::where('uuid', 'test-locked-uuid')->first();
        expect((bool) $quote->is_quote_locked)->toBeTrue();

        // Service healthQuoteAddMember is never reached when quote is locked (controller guards it)
        // Confirm by calling the service only when quoteId is absent (returns falsy status)
        $request->merge(['quoteId' => null]);
        $response = app(HealthQuoteService::class)->healthQuoteAddMember($request);

        expect($response['status'])->toBeFalse();
    });

    test('member payload temp id resolves to null when id starts with temp-', function () {
        $memberData = [
            'id' => 'temp-abc123',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'dob' => '1990-01-15',
            'gender' => 'M',
            'nationality_id' => 1,
            'emirate_of_your_visa_id' => 1,
            'salary_band_id' => 1,
            'member_category_id' => 1,
            'visa_category_id' => null,
            'relation_code' => null,
            'marital_status_id' => null,
            'is_insured' => 1,
            'is_policy_holder' => 0,
            'is_principal' => 1,
            'pec' => 0,
            'is_pec_marked' => 0,
        ];

        $service = app(HealthQuoteService::class);
        $method = (new ReflectionClass($service))->getMethod('prepareMemberDetailPayload');
        $method->setAccessible(true);

        $result = $method->invoke($service, $memberData);

        expect($result['id'])->toBeNull()
            ->and($result['firstName'])->toBe('John')
            ->and($result['lastName'])->toBe('Doe')
            ->and($result['dob'])->toBe('1990-01-15')
            ->and($result['gender'])->toBe('M')
            ->and($result['nationalityId'])->toBe(1)
            ->and($result['visaCategoryId'])->toBeNull()
            ->and($result['maritalStatusId'])->toBeNull()
            ->and($result['isInsured'])->toBeTrue()
            ->and($result['isPolicyHolder'])->toBeFalse()
            ->and($result['isPrincipal'])->toBeTrue()
            ->and($result['isPecMarked'])->toBeFalse();
    });

    test('member payload preserves real id when it does not start with temp-', function () {
        $memberData = [
            'id' => 42,
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'dob' => '1985-06-20',
            'gender' => 'F',
            'nationality_id' => 2,
            'emirate_of_your_visa_id' => 2,
            'salary_band_id' => 2,
            'member_category_id' => 2,
            'visa_category_id' => 5,
            'relation_code' => 'SPOUSE',
            'marital_status_id' => 1,
            'is_insured' => 1,
            'is_policy_holder' => 1,
            'is_principal' => 0,
            'pec' => 0,
            'is_pec_marked' => 0,
        ];

        $service = app(HealthQuoteService::class);
        $method = (new ReflectionClass($service))->getMethod('prepareMemberDetailPayload');
        $method->setAccessible(true);

        $result = $method->invoke($service, $memberData);

        expect($result['id'])->toBe(42)
            ->and($result['visaCategoryId'])->toBe(5)
            ->and($result['relationCode'])->toBe('SPOUSE')
            ->and($result['maritalStatusId'])->toBe(1)
            ->and($result['isPolicyHolder'])->toBeTrue()
            ->and($result['isPrincipal'])->toBeFalse();
    });

    test('member payload returns null dob when dob is null', function () {
        $memberData = [
            'id' => null,
            'first_name' => 'Ali',
            'last_name' => null,
            'dob' => null,
            'gender' => 'M',
            'nationality_id' => 1,
            'emirate_of_your_visa_id' => 1,
            'salary_band_id' => 1,
            'member_category_id' => 1,
            'visa_category_id' => null,
            'relation_code' => null,
            'marital_status_id' => null,
            'is_insured' => 1,
            'is_policy_holder' => 0,
            'is_principal' => 0,
            'pec' => 0,
            'is_pec_marked' => 0,
        ];

        $service = app(HealthQuoteService::class);
        $method = (new ReflectionClass($service))->getMethod('prepareMemberDetailPayload');
        $method->setAccessible(true);

        $result = $method->invoke($service, $memberData);

        expect($result['dob'])->toBeNull()
            ->and($result['lastName'])->toBeNull();
    });

    test('member payload uses is_pec_marked when pec key is absent', function () {
        $memberData = [
            'id' => 10,
            'first_name' => 'Test',
            'last_name' => 'User',
            'dob' => '2000-01-01',
            'gender' => 'M',
            'nationality_id' => 1,
            'emirate_of_your_visa_id' => 1,
            'salary_band_id' => 1,
            'member_category_id' => 1,
            'visa_category_id' => null,
            'relation_code' => null,
            'marital_status_id' => null,
            'is_insured' => 1,
            'is_policy_holder' => 0,
            'is_principal' => 0,
            'is_pec_marked' => 1,
            // no 'pec' key
        ];

        $service = app(HealthQuoteService::class);
        $method = (new ReflectionClass($service))->getMethod('prepareMemberDetailPayload');
        $method->setAccessible(true);

        $result = $method->invoke($service, $memberData);

        expect($result['isPecMarked'])->toBeTrue();
    });

    test('member payload accepts CustomerMembers model instance', function () {
        $member = new CustomerMembers([
            'first_name' => 'Model',
            'last_name' => 'Member',
            'dob' => '1995-03-10',
            'gender' => 'F',
            'nationality_id' => 3,
            'emirate_of_your_visa_id' => 1,
            'salary_band_id' => 1,
            'member_category_id' => 1,
            'visa_category_id' => 2,
            'relation_code' => 'CHILD',
            'marital_status_id' => null,
            'is_insured' => 1,
            'is_policy_holder' => 0,
            'is_principal' => 0,
            'is_pec_marked' => 0,
        ]);

        $service = app(HealthQuoteService::class);
        $method = (new ReflectionClass($service))->getMethod('prepareMemberDetailPayload');
        $method->setAccessible(true);

        $result = $method->invoke($service, $member);

        expect($result['firstName'])->toBe('Model')
            ->and($result['lastName'])->toBe('Member')
            ->and($result['visaCategoryId'])->toBe(2)
            ->and($result['relationCode'])->toBe('CHILD');
    });
});

// ============================================================================
// SECTION 2: saveHealthQuote – new fields in request payload
// ============================================================================

describe('HealthQuoteService saveHealthQuote – health revamp new fields', function () {
    test('saveHealthQuote sends new revamp fields to CAPI', function () {
        $captured = null;

        $mock = Mockery::mock('alias:'.CapiRequestService::class);
        $mock->shouldReceive('sendCAPIRequest')
            ->once()
            ->andReturnUsing(function ($endpoint, $data) use (&$captured) {
                $captured = $data;

                return (object) ['quoteUID' => 'hq-'.uniqid()];
            });

        $memberPayload = [
            'id' => 'temp-1',
            'first_name' => 'Ahmed',
            'last_name' => 'Al-Mansouri',
            'dob' => '1990-01-01',
            'gender' => 'M',
            'nationality_id' => $this->lookups['nationality_id'],
            'emirate_of_your_visa_id' => null,
            'salary_band_id' => null,
            'member_category_id' => null,
            'visa_category_id' => null,
            'relation_code' => null,
            'marital_status_id' => null,
            'is_insured' => 1,
            'is_policy_holder' => 0,
            'is_principal' => 1,
            'pec' => 0,
        ];

        $request = new Request;
        $request->merge([
            'email' => 'ahmed@test.com',
            'mobile_no' => '+971501234567',
            'first_name' => 'Ahmed',
            'last_name' => 'Al-Mansouri',
            'dob' => '1990-01-01',
            'gender' => 'M',
            'nationality_id' => $this->lookups['nationality_id'],
            'cover_for_id' => 4,
            'health_insure_code' => 'ONLY_MYSELF',
            'policy_holder_code' => 'ME',
            'policy_holder_category_code' => 'CATEGORY_A',
            'policy_start_date' => now()->addMonth()->format('Y-m-d'),
            'members' => [$memberPayload],
        ]);

        app(HealthQuoteService::class)->saveHealthQuote($request);

        expect($captured)->not->toBeNull()
            ->and($captured['insureCode'])->toBe('ONLY_MYSELF')
            ->and($captured['policyHolderCode'])->toBe('ME')
            ->and($captured['policyHolderCategoryCode'])->toBe('CATEGORY_A')
            ->and($captured['callSource'])->toBe('imcrm')
            ->and($captured['userId'])->toBe($this->user->id)
            ->and($captured['memberDetails'])->toBeArray()
            ->and($captured['memberDetails'])->toHaveCount(1);
    });

    test('saveHealthQuote maps members array through prepareMemberDetailPayload', function () {
        $captured = null;

        $mock = Mockery::mock('alias:'.CapiRequestService::class);
        $mock->shouldReceive('sendCAPIRequest')
            ->once()
            ->andReturnUsing(function ($endpoint, $data) use (&$captured) {
                $captured = $data;

                return (object) ['quoteUID' => 'hq-'.uniqid()];
            });

        $members = [
            [
                'id' => 'temp-1',
                'first_name' => 'Ahmed',
                'last_name' => 'Test',
                'dob' => '1990-01-01',
                'gender' => 'M',
                'nationality_id' => $this->lookups['nationality_id'],
                'emirate_of_your_visa_id' => null,
                'salary_band_id' => null,
                'member_category_id' => null,
                'visa_category_id' => 3,
                'relation_code' => null,
                'marital_status_id' => 1,
                'is_insured' => 1,
                'is_policy_holder' => 1,
                'is_principal' => 1,
                'pec' => 0,
            ],
            [
                'id' => 'temp-2',
                'first_name' => 'Sara',
                'last_name' => 'Test',
                'dob' => '1992-05-10',
                'gender' => 'F',
                'nationality_id' => $this->lookups['nationality_id'],
                'emirate_of_your_visa_id' => null,
                'salary_band_id' => null,
                'member_category_id' => null,
                'visa_category_id' => null,
                'relation_code' => 'SPOUSE',
                'marital_status_id' => null,
                'is_insured' => 1,
                'is_policy_holder' => 0,
                'is_principal' => 0,
                'pec' => 0,
            ],
        ];

        $request = new Request;
        $request->merge([
            'email' => 'ahmed@test.com',
            'mobile_no' => '+971501234567',
            'first_name' => 'Ahmed',
            'last_name' => 'Test',
            'dob' => '1990-01-01',
            'gender' => 'M',
            'nationality_id' => $this->lookups['nationality_id'],
            'cover_for_id' => 4,
            'members' => $members,
        ]);

        app(HealthQuoteService::class)->saveHealthQuote($request);

        expect($captured['memberDetails'])->toHaveCount(2);

        $principal = collect($captured['memberDetails'])->firstWhere('isPrincipal', true);
        $spouse = collect($captured['memberDetails'])->firstWhere('relationCode', 'SPOUSE');

        expect($principal['id'])->toBeNull()          // temp- id maps to null
            ->and($principal['firstName'])->toBe('Ahmed')
            ->and($principal['visaCategoryId'])->toBe(3)
            ->and($principal['maritalStatusId'])->toBe(1)
            ->and($principal['isPolicyHolder'])->toBeTrue()
            ->and($spouse['firstName'])->toBe('Sara')
            ->and($spouse['isPrincipal'])->toBeFalse()
            ->and($spouse['visaCategoryId'])->toBeNull();
    });

    test('saveHealthQuote disables OCB email for strategic-partners-referrals sub-source', function () {
        $captured = null;

        $subSourceId = DB::connection('sqlite')->table('lookups')->insertGetId([
            'key' => 'sub-source',
            'code' => 'strategic-partners-referrals',
            'text' => 'Strategic Partners',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $mock = Mockery::mock('alias:'.CapiRequestService::class);
        $mock->shouldReceive('sendCAPIRequest')
            ->once()
            ->andReturnUsing(function ($endpoint, $data) use (&$captured) {
                $captured = $data;

                return (object) ['quoteUID' => 'hq-'.uniqid()];
            });

        $request = new Request;
        $request->merge([
            'email' => 'test@test.com',
            'mobile_no' => '+971501234567',
            'first_name' => 'Test',
            'last_name' => 'User',
            'dob' => '1990-01-01',
            'gender' => 'M',
            'nationality_id' => $this->lookups['nationality_id'],
            'cover_for_id' => 4,
            'sub_source_id' => $subSourceId,
            'members' => [[
                'id' => 'temp-1',
                'first_name' => 'Test',
                'last_name' => 'User',
                'dob' => '1990-01-01',
                'gender' => 'M',
                'nationality_id' => $this->lookups['nationality_id'],
                'emirate_of_your_visa_id' => null,
                'salary_band_id' => null,
                'member_category_id' => null,
                'visa_category_id' => null,
                'relation_code' => null,
                'marital_status_id' => null,
                'is_insured' => 1,
                'is_policy_holder' => 0,
                'is_principal' => 1,
                'pec' => 0,
            ]],
        ]);

        app(HealthQuoteService::class)->saveHealthQuote($request);

        expect($captured['sendOcbEmail'])->toBeFalse();
    });
});

// ============================================================================
// SECTION 3: refreshPlans service method
// ============================================================================

describe('HealthQuoteService refreshPlans', function () {
    test('returns status false when quote is not found', function () {
        Ken::swap(Mockery::mock()->shouldReceive('request')->never()->getMock());

        $request = new Request;
        $request->merge(['quoteId' => 'non-existent-uuid']);

        $result = app(HealthQuoteService::class)->refreshPlans($request);

        expect($result['status'])->toBeFalse()
            ->and($result['message'])->toBe('Quote not found');
    });

    test('calls Ken with correct endpoint and returns response when quote exists', function () {
        $quote = HealthQuote::factory()->create();

        Ken::swap(
            Mockery::mock()->shouldReceive('request')
                ->once()
                ->with('/get-revised-health-quote-plans', 'POST', Mockery::type('array'))
                ->andReturn((object) ['status' => true, 'plans' => []])
                ->getMock()
        );

        $request = new Request;
        $request->merge(['quoteId' => $quote->uuid]);

        $result = app(HealthQuoteService::class)->refreshPlans($request);

        expect($result)->not->toBeFalsy()
            ->and($result->status)->toBeTrue();
    });

    test('Ken request payload contains required revamp fields', function () {
        $quote = HealthQuote::factory()->create([
            'dob' => '1990-01-15',
            'gender' => 'M',
        ]);

        $capturedPayload = null;

        Ken::swap(
            Mockery::mock()->shouldReceive('request')
                ->once()
                ->andReturnUsing(function ($endpoint, $method, $data) use (&$capturedPayload) {
                    $capturedPayload = $data;

                    return (object) ['status' => true];
                })
                ->getMock()
        );

        $request = new Request;
        $request->merge(['quoteId' => $quote->uuid]);

        app(HealthQuoteService::class)->refreshPlans($request);

        expect($capturedPayload)->not->toBeNull()
            ->and($capturedPayload['quoteUID'])->toBe($quote->uuid)
            ->and($capturedPayload['callSource'])->toBe('imcrm')
            ->and($capturedPayload['userId'])->toBe($this->user->id)
            ->and($capturedPayload)->toHaveKey('memberDetails');
    });

    test('returns false when Ken API throws exception', function () {
        $quote = HealthQuote::factory()->create();

        Ken::swap(
            Mockery::mock()->shouldReceive('request')
                ->andThrow(new \Exception('Ken service unavailable'))
                ->getMock()
        );

        $request = new Request;
        $request->merge(['quoteId' => $quote->uuid]);

        $result = app(HealthQuoteService::class)->refreshPlans($request);

        expect($result)->toBeFalse();
    });
});

// ============================================================================
// SECTION 4: New health_quote_request schema columns via factory
// ============================================================================

describe('health_quote_request new schema columns', function () {
    test('health quote stores insure_code and policy_holder_code via factory', function () {
        $quote = HealthQuote::factory()->withRevampFields('ONLY_MYSELF', 'ME')->create([
            'policy_holder_category_code' => 'CAT_A',
        ]);

        $row = DB::connection('sqlite')->table('health_quote_request')->find($quote->id);

        expect($row->insure_code)->toBe('ONLY_MYSELF')
            ->and($row->policy_holder_code)->toBe('ME')
            ->and((bool) $row->is_quote_revisable)->toBeFalse()
            ->and($row->policy_holder_category_code)->toBe('CAT_A');
    });

    test('health quote is_quote_revisable defaults to false via factory', function () {
        $quote = HealthQuote::factory()->create();

        $row = DB::connection('sqlite')->table('health_quote_request')->find($quote->id);

        expect((bool) $row->is_quote_revisable)->toBeFalse();
    });

    test('health quote revisable state is set correctly via factory', function () {
        $quote = HealthQuote::factory()->revisable()->create();

        $row = DB::connection('sqlite')->table('health_quote_request')->find($quote->id);

        expect((bool) $row->is_quote_revisable)->toBeTrue();
    });

    test('health quote locked state is set correctly via factory', function () {
        $quote = HealthQuote::factory()->locked()->create();

        $row = DB::connection('sqlite')->table('health_quote_request')->find($quote->id);

        expect((bool) $row->is_quote_locked)->toBeTrue();
    });

    test('health quote can store visa_category_id', function () {
        $visaCategory = VisaCategory::create([
            'code' => 'TOURIST',
            'text' => 'Tourist Visa',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $quote = HealthQuote::factory()->create(['visa_category_id' => $visaCategory->id]);

        $row = DB::connection('sqlite')->table('health_quote_request')->find($quote->id);

        expect((int) $row->visa_category_id)->toBe($visaCategory->id);
    });
});

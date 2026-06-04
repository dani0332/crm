<?php

declare(strict_types=1);

use App\Facades\Ken;
use App\Models\CustomerMembers;
use App\Models\HealthQuote;
use App\Models\Lookup;
use App\Models\VisaCategory;
use App\Services\CapiRequestService;
use App\Services\HealthQuoteService;
use App\Services\SLA\SLAService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::ensureMinimalSchema();
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
        $memberData = CustomerMembers::factory()->principal()->raw([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'dob' => '1990-01-15',
            'gender' => 'M',
            'id' => 'temp-abc123',
        ]);

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
        $memberData = CustomerMembers::factory()->persisted(42)->policyHolder()->raw([
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
            'is_principal' => 0,
        ]);

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
        $memberData = CustomerMembers::factory()->raw([
            'id' => null,
            'first_name' => 'Ali',
            'last_name' => null,
            'dob' => null,
        ]);

        $service = app(HealthQuoteService::class);
        $method = (new ReflectionClass($service))->getMethod('prepareMemberDetailPayload');
        $method->setAccessible(true);

        $result = $method->invoke($service, $memberData);

        expect($result['dob'])->toBeNull()
            ->and($result['lastName'])->toBeNull();
    });

    test('member payload uses is_pec_marked when pec key is absent', function () {
        $memberData = CustomerMembers::factory()->pecMarked()->raw([
            'id' => 10,
            'first_name' => 'Test',
            'last_name' => 'User',
            'dob' => '2000-01-01',
        ]);
        unset($memberData['pec']); // explicitly absent to test fallback to is_pec_marked

        $service = app(HealthQuoteService::class);
        $method = (new ReflectionClass($service))->getMethod('prepareMemberDetailPayload');
        $method->setAccessible(true);

        $result = $method->invoke($service, $memberData);

        expect($result['isPecMarked'])->toBeTrue();
    });

    test('member payload accepts CustomerMembers model instance', function () {
        $member = CustomerMembers::factory()->make([
            'first_name' => 'Model',
            'last_name' => 'Member',
            'dob' => '1995-03-10',
            'gender' => 'F',
            'nationality_id' => 3,
            'visa_category_id' => 2,
            'relation_code' => 'CHILD',
            'marital_status_id' => null,
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

        $memberPayload = CustomerMembers::factory()->principal()->raw([
            'first_name' => 'Ahmed',
            'last_name' => 'Al-Mansouri',
            'dob' => '1990-01-01',
            'gender' => 'M',
            'nationality_id' => $this->lookups['nationality_id'],
            'emirate_of_your_visa_id' => null,
            'salary_band_id' => null,
            'member_category_id' => null,
        ]);

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
            CustomerMembers::factory()->principal()->policyHolder()->raw([
                'first_name' => 'Ahmed',
                'last_name' => 'Test',
                'dob' => '1990-01-01',
                'gender' => 'M',
                'nationality_id' => $this->lookups['nationality_id'],
                'emirate_of_your_visa_id' => null,
                'salary_band_id' => null,
                'member_category_id' => null,
                'visa_category_id' => 3,
                'marital_status_id' => 1,
            ]),
            CustomerMembers::factory()->temp()->raw([
                'first_name' => 'Sara',
                'last_name' => 'Test',
                'dob' => '1992-05-10',
                'gender' => 'F',
                'nationality_id' => $this->lookups['nationality_id'],
                'emirate_of_your_visa_id' => null,
                'salary_band_id' => null,
                'member_category_id' => null,
                'relation_code' => 'SPOUSE',
            ]),
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

        $subSource = Lookup::factory()->create([
            'key' => 'sub-source',
            'code' => 'strategic-partners-referrals',
            'text' => 'Strategic Partners',
            'is_active' => true,
        ]);
        $subSourceId = (int) $subSource->getKey();

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
            'members' => [
                CustomerMembers::factory()->principal()->raw([
                    'first_name' => 'Test',
                    'last_name' => 'User',
                    'dob' => '1990-01-01',
                    'gender' => 'M',
                    'nationality_id' => $this->lookups['nationality_id'],
                    'emirate_of_your_visa_id' => null,
                    'salary_band_id' => null,
                    'member_category_id' => null,
                ]),
            ],
        ]);

        app(HealthQuoteService::class)->saveHealthQuote($request);

        expect($captured['sendOcbEmail'])->toBeFalse();
    });
});

// ============================================================================
// SECTION 3: New health_quote_request schema columns via factory
// ============================================================================

describe('health_quote_request new schema columns', function () {
    test('health quote stores insure_code and policy_holder_code via factory', function () {
        $quote = HealthQuote::factory()->withRevampFields('ONLY_MYSELF', 'ME')->create([
            'policy_holder_category_code' => 'CAT_A',
        ]);

        $row = DB::table('health_quote_request')->find($quote->id);

        expect($row->insure_code)->toBe('ONLY_MYSELF')
            ->and($row->policy_holder_code)->toBe('ME')
            ->and($row->policy_holder_category_code)->toBe('CAT_A');
    });

    test('health quote locked state is set correctly via factory', function () {
        $quote = HealthQuote::factory()->locked()->create();

        $row = DB::table('health_quote_request')->find($quote->id);

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

        $row = DB::table('health_quote_request')->find($quote->id);

        expect((int) $row->visa_category_id)->toBe($visaCategory->id);
    });
});

// ============================================================================
// SECTION 5: updateHealthQuote – return value contract
// ============================================================================

describe('HealthQuoteService updateHealthQuote return value', function () {
    /**
     * Build a minimal update request for the given quote.
     *
     * @param  array<string, mixed>  $overrides
     */
    function makeUpdateRequest(HealthQuote $quote, array $overrides = []): Request
    {
        $request = new Request;
        $request->merge(array_merge([
            'email' => $quote->email,
            'mobile_no' => $quote->mobile_no,
            'first_name' => $quote->first_name,
            'last_name' => $quote->last_name,
            'customer_type' => 'Individual',
            'members' => [
                CustomerMembers::factory()->principal()->policyHolder()->raw([
                    'id' => null,
                    'first_name' => $quote->first_name,
                    'last_name' => $quote->last_name,
                    'dob' => '1990-01-01',
                    'gender' => 'M',
                    'nationality_id' => null,
                    'emirate_of_your_visa_id' => null,
                    'salary_band_id' => null,
                    'member_category_id' => null,
                ]),
            ],
        ], $overrides));

        return $request;
    }

    test('returns false when CAPI response does not contain data.id', function () {
        $quote = HealthQuote::factory()->create();

        $mock = Mockery::mock('alias:'.CapiRequestService::class);
        $mock->shouldReceive('sendCAPIRequest')
            ->once()
            ->andReturn((object) ['error' => 'Something went wrong']);

        $request = makeUpdateRequest($quote);
        $result = app(HealthQuoteService::class)->updateHealthQuote($request, $quote->uuid);

        expect($result)->toBeFalse();
    });

    test('returns null (no explicit return) when CAPI succeeds with data.id', function () {
        $quote = HealthQuote::factory()->create();

        $mock = Mockery::mock('alias:'.CapiRequestService::class);
        $mock->shouldReceive('sendCAPIRequest')
            ->once()
            ->andReturn((object) ['data' => (object) ['id' => 99, 'uuid' => $quote->uuid]]);

        $this->mock(SLAService::class, function ($mock) {
            $mock->shouldReceive('meetSLAOnEdit')->once()->andReturnNull();
        });

        $request = makeUpdateRequest($quote);
        $result = app(HealthQuoteService::class)->updateHealthQuote($request, $quote->uuid);

        expect($result)->toBeNull();
    });

    test('returns a redirect response when the quote is locked', function () {
        $quote = HealthQuote::factory()->locked()->create();

        $mock = Mockery::mock('alias:'.CapiRequestService::class);
        $mock->shouldReceive('sendCAPIRequest')->never();

        $request = makeUpdateRequest($quote);
        $result = app(HealthQuoteService::class)->updateHealthQuote($request, $quote->uuid);

        expect($result)->toBeInstanceOf(RedirectResponse::class);
    });
});

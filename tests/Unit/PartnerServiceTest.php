<?php

use App\Enums\ApplicationStorageEnums;
use App\Enums\DocumentTypeCode;
use App\Enums\QuoteTypes;
use App\Models\CarQuote;
use App\Models\InsurancePartner;
use App\Models\InsurancePartnerProvider;
use App\Models\InsurancePartnerProviderPlan;
use App\Models\InsuranceProvider;
use App\Models\Payment;
use App\Services\PartnerService;
use Illuminate\Support\Facades\Auth;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;
use Tests\Support\Schema\PartnerSchema;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
    (new PartnerSchema)->register();

    $adminUser = TestDataSeeder::createAdminUser([
        'email' => 'admin@example.com',
    ]);
    Auth::guard('web')->login($adminUser);

    $this->insuranceProvider = InsuranceProvider::factory()->axa()->create();
    $this->quoteTypeId = QuoteTypes::CAR->id();

    $this->partner = InsurancePartner::factory()->create([
        'code' => 'TEST_PARTNER',
        'email' => 'partner@example.com',
    ]);

    $this->partnerProvider = InsurancePartnerProvider::factory()->create([
        'partner_id' => $this->partner->id,
        'provider_id' => $this->insuranceProvider->id,
        'quote_type_id' => $this->quoteTypeId,
    ]);

    $this->planId = 1;

    InsurancePartnerProviderPlan::factory()->create([
        'partner_provider_id' => $this->partnerProvider->id,
        'plan_id' => $this->planId,
    ]);

    TestDataSeeder::seedApplicationStorage([
        ApplicationStorageEnums::BIRD_PARTNER_AUTOMATION_COMPLETED_WORKFLOW_URL => 'https://example.test/bird/workflow',
        ApplicationStorageEnums::AXA_POLICY_MANDATORY_DOCUMENTS => json_encode([DocumentTypeCode::TI, DocumentTypeCode::CTIRBB, DocumentTypeCode::CPC, DocumentTypeCode::CPS]),
    ]);
});

describe('isPartnerActive', function () {
    it('returns active partner when conditions are met', function () {
        $service = app(PartnerService::class);

        $result = $service->isPartnerActive('TEST_PARTNER', $this->quoteTypeId, $this->insuranceProvider->id, $this->planId);

        expect($result)->not->toBeFalse()
            ->and($result->code)->toBe('TEST_PARTNER')
            ->and($result->email)->toBe('partner@example.com')
            ->and($result->is_active)->toBe(1);
    });

    it('returns false when partner is inactive', function () {
        $this->partner->update(['is_active' => false]);

        $result = app(PartnerService::class)->isPartnerActive('TEST_PARTNER', $this->quoteTypeId, $this->insuranceProvider->id, $this->planId);

        expect($result)->toBeFalse();
    });

    it('returns false when partner has no email', function () {
        $this->partner->update(['email' => null]);

        $result = app(PartnerService::class)->isPartnerActive('TEST_PARTNER', $this->quoteTypeId, $this->insuranceProvider->id, $this->planId);

        expect($result)->toBeFalse();
    });

    it('returns false when partner has an empty string email', function () {
        $this->partner->update(['email' => '']);

        $result = app(PartnerService::class)->isPartnerActive('TEST_PARTNER', $this->quoteTypeId, $this->insuranceProvider->id, $this->planId);

        expect($result)->toBeFalse();
    });

    it('returns false when partner does not exist', function () {
        $result = app(PartnerService::class)->isPartnerActive('NON_EXISTENT', $this->quoteTypeId, $this->insuranceProvider->id, $this->planId);

        expect($result)->toBeFalse();
    });

    it('returns false when partner has no provider for the given insurance provider', function () {
        $differentProvider = InsuranceProvider::factory()->create();

        $result = app(PartnerService::class)->isPartnerActive('TEST_PARTNER', $this->quoteTypeId, $differentProvider->id, $this->planId);

        expect($result)->toBeFalse();
    });

    it('returns false when partner provider has auto_issuance_enabled set to false', function () {
        $this->partnerProvider->update(['auto_issuance_enabled' => false]);

        $result = app(PartnerService::class)->isPartnerActive('TEST_PARTNER', $this->quoteTypeId, $this->insuranceProvider->id, $this->planId);

        expect($result)->toBeFalse();
    });

    it('returns false when partner provider is inactive', function () {
        $this->partnerProvider->update(['is_active' => false]);

        $result = app(PartnerService::class)->isPartnerActive('TEST_PARTNER', $this->quoteTypeId, $this->insuranceProvider->id, $this->planId);

        expect($result)->toBeFalse();
    });

    it('returns false when quote_type_id does not match', function () {
        $result = app(PartnerService::class)->isPartnerActive('TEST_PARTNER', QuoteTypes::HEALTH->id(), $this->insuranceProvider->id, $this->planId);

        expect($result)->toBeFalse();
    });

    it('returns false when plan is inactive', function () {
        InsurancePartnerProviderPlan::where('partner_provider_id', $this->partnerProvider->id)->update(['is_active' => false]);

        $result = app(PartnerService::class)->isPartnerActive('TEST_PARTNER', $this->quoteTypeId, $this->insuranceProvider->id, $this->planId);

        expect($result)->toBeFalse();
    });

    it('returns false when plan_id does not match', function () {
        $result = app(PartnerService::class)->isPartnerActive('TEST_PARTNER', $this->quoteTypeId, $this->insuranceProvider->id, 999);

        expect($result)->toBeFalse();
    });

    it('returns the partner when plan_id is null, skipping the plan filter', function () {
        $result = app(PartnerService::class)->isPartnerActive('TEST_PARTNER', $this->quoteTypeId, $this->insuranceProvider->id, null);

        expect($result)->not->toBeFalse()
            ->and($result->code)->toBe('TEST_PARTNER');
    });
});

describe('validatePartnerQuote', function () {
    it('returns quote when all validations pass', function () {
        $carQuote = TestDataSeeder::createCarQuote([
            'source' => 'TEST_PARTNER',
            'insurance_provider_id' => $this->insuranceProvider->id,
            'plan_id' => $this->planId,
        ]);

        Payment::factory()->createForSqlite($carQuote);

        $result = app(PartnerService::class)->validatePartnerQuote($carQuote->uuid, QuoteTypes::CAR);

        expect($result)->toBeArray()
            ->and($result['quote'])->toBeInstanceOf(CarQuote::class)
            ->and($result['quote']->uuid)->toBe($carQuote->uuid)
            ->and($result)->toHaveKeys(['quote', 'insuranceProvider', 'partnerEmail']);
    });

    it('returns false when quote does not exist', function () {
        $result = app(PartnerService::class)->validatePartnerQuote('non-existent-uuid', QuoteTypes::CAR);

        expect($result)->toBeFalse();
    });

    it('returns false when payment does not exist', function () {
        $carQuote = TestDataSeeder::createCarQuote(['source' => 'TEST_PARTNER']);

        $result = app(PartnerService::class)->validatePartnerQuote($carQuote->uuid, QuoteTypes::CAR);

        expect($result)->toBeFalse();
    });

    it('returns false when partner is not active', function () {
        $carQuote = TestDataSeeder::createCarQuote([
            'source' => 'INACTIVE_PARTNER',
            'insurance_provider_id' => $this->insuranceProvider->id,
            'plan_id' => $this->planId,
        ]);

        Payment::factory()->createForSqlite($carQuote);

        $result = app(PartnerService::class)->validatePartnerQuote($carQuote->uuid, QuoteTypes::CAR);

        expect($result)->toBeFalse();
    });
});

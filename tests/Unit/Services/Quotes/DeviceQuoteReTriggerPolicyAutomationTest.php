<?php

declare(strict_types=1);

use App\Enums\ApplicationStorageEnums;
use App\Enums\NgiEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Jobs\PolicyIssuanceJob;
use App\Models\ApplicationStorage;
use App\Models\InsuranceProvider;
use App\Models\PersonalQuote;
use App\Models\PolicyIssuance;
use App\Models\PolicyIssuanceLog;
use App\Services\PolicyIssuanceAutomation\Device\SmartPhone\NationalGeneralInsurance\NgiGetPolicyDocumentsJob;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use App\Services\Quotes\DeviceQuoteService;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
});

describe('PolicyIssuanceService IMCRM re-trigger helpers', function () {
    it('allows only timeout and failed for re-trigger status allowlist', function () {
        $service = app(PolicyIssuanceService::class);

        expect($service->isReTriggerPolicyAutomationStatusAllowed(PolicyIssuanceEnum::TIMEOUT_STATUS))->toBeTrue();
        expect($service->isReTriggerPolicyAutomationStatusAllowed(PolicyIssuanceEnum::FAILED_STATUS))->toBeTrue();

        expect($service->isReTriggerPolicyAutomationStatusAllowed(null))->toBeFalse();
        expect($service->isReTriggerPolicyAutomationStatusAllowed(''))->toBeFalse();
        expect($service->isReTriggerPolicyAutomationStatusAllowed(PolicyIssuanceEnum::PENDING_STATUS))->toBeFalse();
        expect($service->isReTriggerPolicyAutomationStatusAllowed(PolicyIssuanceEnum::PROCESSING_STATUS))->toBeFalse();
        expect($service->isReTriggerPolicyAutomationStatusAllowed(PolicyIssuanceEnum::BOOKING_PROCESSING_STATUS))->toBeFalse();
        expect($service->isReTriggerPolicyAutomationStatusAllowed(PolicyIssuanceEnum::COMPLETED_STATUS))->toBeFalse();
        expect($service->isReTriggerPolicyAutomationStatusAllowed(PolicyIssuanceEnum::ON_HOLD_STATUS))->toBeFalse();
    });
});

describe('DeviceQuoteService re-trigger GetAndUpload policy documents', function () {
    beforeEach(function () {
        InsuranceProvider::query()->insert([
            'id' => 1,
            'code' => 'NGI',
            'text' => 'National General Insurance',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        ApplicationStorage::factory()->create([
            'key_name' => ApplicationStorageEnums::ENABLE_NGI_SMARTPHONE_POLICY_ISSUANCE,
            'value' => '1',
            'is_active' => 1,
        ]);

        $quoteId = DB::table('personal_quotes')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'DEV-TEST-'.uniqid(),
            'quote_type_id' => QuoteTypeId::Device,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->quote = PersonalQuote::query()->findOrFail($quoteId);
    });

    it('is not eligible without three failed doc logs', function () {
        $process = PolicyIssuance::factory()->forQuote($this->quote)->create([
            'quote_type' => QuoteTypes::DEVICE->value,
            'insurance_provider_id' => 1,
            'status' => PolicyIssuanceEnum::FAILED_STATUS,
            'completed_step' => NgiEnum::STEP_CREATE_POLICY_FROM_QUOTE,
        ]);

        expect(app(DeviceQuoteService::class)->isEligibleForReTriggerGetAndUploadPolicyDocuments($process))->toBeFalse();

        expect(app(PolicyIssuanceService::class)->shouldOfferReTriggerPolicyAutomation($process->fresh()))->toBeTrue();
    });

    it('is eligible with three failed GetAndUploadPolicyDocs logs', function () {
        $process = PolicyIssuance::factory()->forQuote($this->quote)->create([
            'quote_type' => QuoteTypes::DEVICE->value,
            'insurance_provider_id' => 1,
            'status' => PolicyIssuanceEnum::FAILED_STATUS,
            'completed_step' => NgiEnum::STEP_CREATE_POLICY_FROM_QUOTE,
        ]);

        for ($i = 0; $i < 3; $i++) {
            PolicyIssuanceLog::query()->create([
                'policy_issuance_id' => $process->id,
                'step' => NgiEnum::STEP_GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM,
                'status' => PolicyIssuanceEnum::FAILED_STATUS,
                'payload' => '[]',
                'response' => '[]',
                'endPoint' => '/test',
                'model_type' => null,
                'model_id' => null,
            ]);
        }

        expect(app(DeviceQuoteService::class)->isEligibleForReTriggerGetAndUploadPolicyDocuments($process->fresh()))->toBeTrue();

        expect(app(PolicyIssuanceService::class)->shouldOfferReTriggerPolicyAutomation($process->fresh()))->toBeTrue();
    });

    it('dispatches NGI job and sets issuance to pending', function () {
        Bus::fake();

        $process = PolicyIssuance::factory()->forQuote($this->quote)->create([
            'quote_type' => QuoteTypes::DEVICE->value,
            'insurance_provider_id' => 1,
            'status' => PolicyIssuanceEnum::FAILED_STATUS,
            'completed_step' => NgiEnum::STEP_CREATE_POLICY_FROM_QUOTE,
        ]);

        for ($i = 0; $i < 3; $i++) {
            PolicyIssuanceLog::query()->create([
                'policy_issuance_id' => $process->id,
                'step' => NgiEnum::STEP_GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM,
                'status' => PolicyIssuanceEnum::FAILED_STATUS,
                'payload' => '[]',
                'response' => '[]',
                'endPoint' => '/test',
                'model_type' => null,
                'model_id' => null,
            ]);
        }

        app(DeviceQuoteService::class)->reTriggerGetAndUploadPolicyDocumentsAfterRepeatedFailures($process->fresh());

        Bus::assertDispatched(NgiGetPolicyDocumentsJob::class);

        expect($process->fresh()->status)->toBe(PolicyIssuanceEnum::PENDING_STATUS);
    });

    it('identifyAutomationStepToReTrigger throws when create-policy step failed but doc re-trigger is not eligible', function () {
        $process = PolicyIssuance::factory()->forQuote($this->quote)->create([
            'quote_type' => QuoteTypes::DEVICE->value,
            'insurance_provider_id' => 1,
            'status' => PolicyIssuanceEnum::FAILED_STATUS,
            'completed_step' => NgiEnum::STEP_CREATE_POLICY_FROM_QUOTE,
        ]);

        expect(fn () => app(DeviceQuoteService::class)->identifyAutomationStepToReTrigger($process->fresh()))
            ->toThrow(InvalidArgumentException::class, 'Policy issuance is not eligible for document sync re-trigger.');
    });

    it('identifyAutomationStepToReTrigger dispatches PolicyIssuanceJob when status is timeout at create-policy step', function () {
        Bus::fake();

        $process = PolicyIssuance::factory()->forQuote($this->quote)->create([
            'quote_type' => QuoteTypes::DEVICE->value,
            'insurance_provider_id' => 1,
            'status' => PolicyIssuanceEnum::TIMEOUT_STATUS,
            'completed_step' => NgiEnum::STEP_CREATE_POLICY_FROM_QUOTE,
        ]);

        app(DeviceQuoteService::class)->identifyAutomationStepToReTrigger($process->fresh());

        Bus::assertDispatched(PolicyIssuanceJob::class);
        Bus::assertNotDispatched(NgiGetPolicyDocumentsJob::class);

        expect($process->fresh()->status)->toBe(PolicyIssuanceEnum::PENDING_STATUS);
    });

    it('identifyAutomationStepToReTrigger dispatches PolicyIssuanceJob when failed on a step other than create-policy', function () {
        Bus::fake();

        $process = PolicyIssuance::factory()->forQuote($this->quote)->create([
            'quote_type' => QuoteTypes::DEVICE->value,
            'insurance_provider_id' => 1,
            'status' => PolicyIssuanceEnum::FAILED_STATUS,
            'completed_step' => NgiEnum::STEP_BOOK_POLICY,
        ]);

        app(DeviceQuoteService::class)->identifyAutomationStepToReTrigger($process->fresh());

        Bus::assertDispatched(PolicyIssuanceJob::class);
        Bus::assertNotDispatched(NgiGetPolicyDocumentsJob::class);

        expect($process->fresh()->status)->toBe(PolicyIssuanceEnum::PENDING_STATUS);
    });

    it('identifyAutomationStepToReTrigger dispatches NGI doc job when doc re-trigger path applies and is eligible', function () {
        Bus::fake();

        $process = PolicyIssuance::factory()->forQuote($this->quote)->create([
            'quote_type' => QuoteTypes::DEVICE->value,
            'insurance_provider_id' => 1,
            'status' => PolicyIssuanceEnum::FAILED_STATUS,
            'completed_step' => NgiEnum::STEP_CREATE_POLICY_FROM_QUOTE,
        ]);

        for ($i = 0; $i < 3; $i++) {
            PolicyIssuanceLog::query()->create([
                'policy_issuance_id' => $process->id,
                'step' => NgiEnum::STEP_GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM,
                'status' => PolicyIssuanceEnum::FAILED_STATUS,
                'payload' => '[]',
                'response' => '[]',
                'endPoint' => '/test',
                'model_type' => null,
                'model_id' => null,
            ]);
        }

        app(DeviceQuoteService::class)->identifyAutomationStepToReTrigger($process->fresh());

        Bus::assertDispatched(NgiGetPolicyDocumentsJob::class);
        Bus::assertNotDispatched(PolicyIssuanceJob::class);

        expect($process->fresh()->status)->toBe(PolicyIssuanceEnum::PENDING_STATUS);
    });
});

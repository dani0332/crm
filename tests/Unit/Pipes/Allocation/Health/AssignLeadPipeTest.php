<?php

declare(strict_types=1);

use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Exceptions\Allocation\AllocationException;
use App\Models\HealthQuote;
use App\Pipes\Allocation\Handlers\AllocationRequest;
use App\Pipes\Allocation\Health\AssignLeadPipe;
use Illuminate\Support\Str;

it('marks sic2 non-sic1 lead as qualified and saves when no advisor is available', function () {
    $lead = new class extends HealthQuote
    {
        public int $persistCount = 0;

        public function isSIC2(): bool
        {
            return true;
        }

        public function isSIC1(): bool
        {
            return false;
        }

        public function save(array $options = []): bool
        {
            $this->persistCount++;

            return true;
        }
    };

    $lead->code = 'HEA-ALLOC-TEST';
    $lead->advisor_id = null;
    $lead->quote_status_id = QuoteStatusEnum::NewLead;

    $allocationRequest = new AllocationRequest(QuoteTypes::HEALTH, Str::uuid()->toString());
    $allocationRequest->setLead($lead);

    expect(fn () => (new AssignLeadPipe)->handle($allocationRequest, fn ($request) => $request))
        ->toThrow(AllocationException::class, 'Advisor not found');

    expect($lead->quote_status_id)->toBe(QuoteStatusEnum::Qualified)
        ->and($lead->persistCount)->toBe(1)
        ->and($allocationRequest->isFailed())->toBeTrue();
});

it('does not change quote status when no advisor and lead is not sic2', function () {
    $lead = new class extends HealthQuote
    {
        public int $persistCount = 0;

        public function isSIC2(): bool
        {
            return false;
        }

        public function isSIC1(): bool
        {
            return false;
        }

        public function save(array $options = []): bool
        {
            $this->persistCount++;

            return true;
        }
    };

    $lead->code = 'HEA-ALLOC-TEST-2';
    $lead->advisor_id = null;
    $lead->quote_status_id = QuoteStatusEnum::NewLead;

    $allocationRequest = new AllocationRequest(QuoteTypes::HEALTH, Str::uuid()->toString());
    $allocationRequest->setLead($lead);

    try {
        (new AssignLeadPipe)->handle($allocationRequest, fn ($request) => $request);
    } catch (AllocationException $e) {
        expect($e->getMessage())->toBe('Advisor not found');
    }

    expect($lead->quote_status_id)->toBe(QuoteStatusEnum::NewLead)
        ->and($lead->persistCount)->toBe(0)
        ->and($allocationRequest->isFailed())->toBeTrue();
});

it('does not mark lead as qualified when no advisor and lead is sic1', function () {
    $lead = new class extends HealthQuote
    {
        public int $persistCount = 0;

        public function isSIC2(): bool
        {
            return true;
        }

        public function isSIC1(): bool
        {
            return true;
        }

        public function save(array $options = []): bool
        {
            $this->persistCount++;

            return true;
        }
    };

    $lead->code = 'HEA-ALLOC-TEST-3';
    $lead->advisor_id = null;
    $lead->quote_status_id = QuoteStatusEnum::NewLead;

    $allocationRequest = new AllocationRequest(QuoteTypes::HEALTH, Str::uuid()->toString());
    $allocationRequest->setLead($lead);

    try {
        (new AssignLeadPipe)->handle($allocationRequest, fn ($request) => $request);
    } catch (AllocationException $e) {
        expect($e->getMessage())->toBe('Advisor not found');
    }

    expect($lead->quote_status_id)->toBe(QuoteStatusEnum::NewLead)
        ->and($lead->persistCount)->toBe(0)
        ->and($allocationRequest->isFailed())->toBeTrue();
});

it('does not mark lead as qualified when no advisor but lead already has advisor_id', function () {
    $lead = new class extends HealthQuote
    {
        public int $persistCount = 0;

        public function isSIC2(): bool
        {
            return true;
        }

        public function isSIC1(): bool
        {
            return false;
        }

        public function save(array $options = []): bool
        {
            $this->persistCount++;

            return true;
        }
    };

    $lead->code = 'HEA-ALLOC-TEST-4';
    $lead->advisor_id = 99;
    $lead->quote_status_id = QuoteStatusEnum::NewLead;

    $allocationRequest = new AllocationRequest(QuoteTypes::HEALTH, Str::uuid()->toString());
    $allocationRequest->setLead($lead);

    try {
        (new AssignLeadPipe)->handle($allocationRequest, fn ($request) => $request);
    } catch (AllocationException $e) {
        expect($e->getMessage())->toBe('Advisor not found');
    }

    expect($lead->quote_status_id)->toBe(QuoteStatusEnum::NewLead)
        ->and($lead->persistCount)->toBe(0)
        ->and($allocationRequest->isFailed())->toBeTrue();
});

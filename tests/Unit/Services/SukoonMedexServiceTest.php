<?php

use App\Enums\CarRegistrationType;
use App\Enums\CarVehicleUse;
use App\Enums\EmbeddedTransactionEnum;
use App\Enums\QuoteDocumentsEnum;
use App\Enums\QuoteTypeId;
use App\Enums\VehicleTypeEnum;
use App\Models\EmbeddedTransaction;
use App\Services\SukoonMedexService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;

use function Pest\Laravel\partialMock;

beforeEach(function () {
    Config::set('constants.SUKOON_API_URL', 'https://test-api.sukoon.com');
    Config::set('constants.SUKOON_API_VERSION', '1');
    $this->service = partialMock(SukoonMedexService::class);
});

function setServiceProperties($service, array $properties): ReflectionMethod
{
    $reflection = new ReflectionClass(SukoonMedexService::class);

    foreach ($properties as $name => $value) {
        $property = $reflection->getProperty($name);
        $property->setAccessible(true);
        $property->setValue($service, $value);
    }

    $method = $reflection->getMethod('prepareAdditionalData');
    $method->setAccessible(true);

    return $method;
}

describe('prepareAdditionalData', function () {
    test('returns personal_sports_mc plan for bike quote type', function () {
        $method = setServiceProperties($this->service, [
            'quoteTypeId' => QuoteTypeId::Bike,
            'currentQuote' => (object) ['vehicle_type_id' => null],
            'productSlug' => 'sukoon',
            'paymentPlan' => 'monthly',
            'amountDisclaimerText' => 'Test disclaimer',
            'quotePolicy' => 'POL123',
        ]);

        $result = $method->invoke($this->service);

        expect($result['plan_option'])->toBe('sukoon-personal_sports_mc')
            ->and($result['form_name'])->toBe('plan_picker')
            ->and($result['payment_plan'])->toBe('monthly')
            ->and($result['policy_number'])->toBe('POL123');
    });

    test('returns personal_non_commercial_vehicles plan for regular car', function () {
        $method = setServiceProperties($this->service, [
            'quoteTypeId' => QuoteTypeId::Car,
            'currentQuote' => (object) ['vehicle_type_id' => 1],
            'productSlug' => 'medex',
            'paymentPlan' => 'annual',
            'amountDisclaimerText' => '',
            'quotePolicy' => 'POL456',
        ]);

        $result = $method->invoke($this->service);

        expect($result['plan_option'])->toBe('medex-personal_non_commercial_vehicles');
    });

    test('returns personal_sports_mc plan for car with motorcycle/bike vehicle types', function (int $vehicleTypeId) {
        $method = setServiceProperties($this->service, [
            'quoteTypeId' => QuoteTypeId::Car,
            'currentQuote' => (object) ['vehicle_type_id' => $vehicleTypeId],
            'productSlug' => 'sukoon',
            'paymentPlan' => 'monthly',
            'amountDisclaimerText' => 'Bike renewal',
            'quotePolicy' => 'POL789',
        ]);

        $result = $method->invoke($this->service);

        expect($result['plan_option'])->toBe('sukoon-personal_sports_mc');
    })->with([
        'MOTOR_CYCLE' => VehicleTypeEnum::MOTOR_CYCLE->id(),
        'BIKE' => VehicleTypeEnum::BIKE->id(),
        'MOTORCYCLES' => VehicleTypeEnum::MOTORCYCLES->id(),
    ]);

    test('returns null plan_option for unsupported quote types', function () {
        $method = setServiceProperties($this->service, [
            'quoteTypeId' => QuoteTypeId::Health,
            'currentQuote' => (object) ['vehicle_type_id' => null],
            'productSlug' => 'sukoon',
            'paymentPlan' => 'monthly',
            'amountDisclaimerText' => '',
            'quotePolicy' => 'POL999',
        ]);

        $result = $method->invoke($this->service);

        expect($result['plan_option'])->toBeNull();
    });
});

describe('prepareUserDetails company private driver_name', function () {
    /**
     * @return array{first_name: string, last_name: string}
     */
    function invokePrepareUserDetails(SukoonMedexService $service, object $quote, int $quoteTypeId): array
    {
        $reflection = new ReflectionClass(SukoonMedexService::class);
        $quoteTypeProperty = $reflection->getProperty('quoteTypeId');
        $quoteTypeProperty->setAccessible(true);
        $quoteTypeProperty->setValue($service, $quoteTypeId);

        $method = $reflection->getMethod('prepareUserDetails');
        $method->setAccessible(true);

        return $method->invoke($service, $quote);
    }

    function baseCompanyPrivateCarQuote(array $overrides = []): object
    {
        return (object) array_merge([
            'quoteRequestEntityMapping' => null,
            'latestInsured' => (object) [
                'first_name' => 'InsuredFirst',
                'last_name' => 'InsuredLast',
                'customer_type' => null,
                'insuredKyc' => null,
                'id_type' => 'passport',
                'gender' => 'Male',
            ],
            'quote_type_id' => QuoteTypeId::Car,
            'emirate' => null,
            'registration_type' => CarRegistrationType::COMPANY,
            'vehicle_use' => CarVehicleUse::PRIVATE,
            'vehicleDriverDetail' => (object) ['driver_eid_number' => '', 'driver_gender' => 'male'],
            'driver_name' => null,
            'dob' => '',
            'customer' => null,
        ], $overrides);
    }

    test('uses first token and remaining tokens for compound surnames', function () {
        $service = new SukoonMedexService;
        $quote = baseCompanyPrivateCarQuote(['driver_name' => 'John van der Berg']);

        $details = invokePrepareUserDetails($service, $quote, QuoteTypeId::Car);

        expect($details['first_name'])->toBe('John')
            ->and($details['last_name'])->toBe('van der Berg');
    });

    test('single-word driver_name updates first name and keeps insured last name', function () {
        $service = new SukoonMedexService;
        $quote = baseCompanyPrivateCarQuote(['driver_name' => 'Ahmed']);

        $details = invokePrepareUserDetails($service, $quote, QuoteTypeId::Car);

        expect($details['first_name'])->toBe('Ahmed')
            ->and($details['last_name'])->toBe('InsuredLast');
    });

    test('blank driver_name leaves names from insured', function () {
        $service = new SukoonMedexService;
        $quote = baseCompanyPrivateCarQuote(['driver_name' => '   ']);

        $details = invokePrepareUserDetails($service, $quote, QuoteTypeId::Car);

        expect($details['first_name'])->toBe('InsuredFirst')
            ->and($details['last_name'])->toBe('InsuredLast');
    });

    test('normalizes repeated spaces in driver_name', function () {
        $service = new SukoonMedexService;
        $quote = baseCompanyPrivateCarQuote(['driver_name' => "  Jane   Marie  \t Dupont "]);

        $details = invokePrepareUserDetails($service, $quote, QuoteTypeId::Car);

        expect($details['first_name'])->toBe('Jane')
            ->and($details['last_name'])->toBe('Marie Dupont');
    });
});

describe('processPurchaseFlow send guard after provider sync', function () {
    /**
     * Mirrors SukoonMedexService::processPurchaseFlow email branch conditions
     * (excluding watermarked document checks).
     */
    function shouldAttemptCertificateEmailAfterSync(
        string $initialPolicyStatusBeforeDocumentsSync,
        string $policyStatusAfterSync
    ): bool {
        $hasAlreadySentDocument = EmbeddedTransactionEnum::checkPolicyStatusPassed(
            $initialPolicyStatusBeforeDocumentsSync,
            EmbeddedTransactionEnum::STATUS_READY_FOR_SAGE
        );
        $isNowReadyForSage = $policyStatusAfterSync === EmbeddedTransactionEnum::STATUS_READY_FOR_SAGE;

        return ! $hasAlreadySentDocument && $isNowReadyForSage;
    }

    test('attempts send when status transitions to ready for sage from earlier stage', function () {
        expect(shouldAttemptCertificateEmailAfterSync(
            EmbeddedTransactionEnum::STATUS_BOOKED,
            EmbeddedTransactionEnum::STATUS_READY_FOR_SAGE
        ))->toBeTrue();
    });

    test('does not attempt send when already ready for sage before sync', function () {
        expect(shouldAttemptCertificateEmailAfterSync(
            EmbeddedTransactionEnum::STATUS_READY_FOR_SAGE,
            EmbeddedTransactionEnum::STATUS_READY_FOR_SAGE
        ))->toBeFalse();
    });

    test('does not attempt send when post-sync status is not yet ready for sage', function () {
        expect(shouldAttemptCertificateEmailAfterSync(
            EmbeddedTransactionEnum::STATUS_BOOKED,
            EmbeddedTransactionEnum::STATUS_BOOKED
        ))->toBeFalse();
    });
});

describe('sanitizeToLettersAndSpacesOnly', function () {
    test('keeps letters, spaces, and removes digits and punctuation', function (string $input, string $expected) {
        expect(sanitizeToLettersAndSpacesOnly($input))->toBe($expected);
    })->with([
        'ascii letters' => ['John Doe', 'John Doe'],
        'digits stripped' => ['John3 Doe2', 'John Doe'],
        'punctuation stripped' => ["O'Brien-Smith!", 'OBrienSmith'],
        'accented letters kept' => ['José Müller', 'José Müller'],
        'empty string' => ['', ''],
    ]);
});

describe('maybeSendDocumentsEmail', function () {
    test('does not queue jobs when policy was already ready for sage before sync', function () {
        Queue::fake();

        $transaction = EmbeddedTransaction::make([
            'policy_status' => EmbeddedTransactionEnum::STATUS_READY_FOR_SAGE,
        ]);
        $transaction->setRelation('documents', new EloquentCollection([
            (object) ['document_type_code' => QuoteDocumentsEnum::CAR_TAX_INVOICE, 'is_watermarked' => true],
            (object) ['document_type_code' => QuoteDocumentsEnum::POLICY_SCHEDULE, 'is_watermarked' => true],
        ]));

        $service = new SukoonMedexService;
        $reflection = new ReflectionClass(SukoonMedexService::class);
        $transactionProperty = $reflection->getProperty('transaction');
        $transactionProperty->setAccessible(true);
        $transactionProperty->setValue($service, $transaction);

        $policyStatusProperty = $reflection->getProperty('policyStatus');
        $policyStatusProperty->setAccessible(true);
        $policyStatusProperty->setValue($service, EmbeddedTransactionEnum::STATUS_READY_FOR_SAGE);

        $service->maybeSendDocumentsEmail(true, EmbeddedTransactionEnum::STATUS_READY_FOR_SAGE);

        Queue::assertNothingPushed();
    });
});

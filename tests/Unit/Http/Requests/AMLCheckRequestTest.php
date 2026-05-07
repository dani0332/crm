<?php

declare(strict_types=1);

use App\Enums\CustomerTypeEnum;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteTypes;
use App\Http\Requests\AMLCheckRequest;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function (): void {
    TestSchemaCreator::createMinimalSchema();
});

/**
 * @return array<string, mixed>
 */
function amlCheckRequestPayloadForIndividualTravel(): array
{
    return [
        'is_automation' => true,
        'customer_type' => CustomerTypeEnum::Individual,
        'quote_type' => QuoteTypes::TRAVEL->value,
        'screening_id_type' => 'passport',
        'screening_id_number' => 'AB1234567',
        'nationality_id' => 1,
        'dob' => '1990-01-01',
        'insured_first_name' => 'Test',
        'insured_last_name' => 'User',
    ];
}

it('still enforces AMLList when is_automation is present in input', function (): void {
    Permission::findOrCreate(PermissionsEnum::AMLList, 'web');

    $user = User::factory()->create();
    $this->actingAs($user);

    $request = AMLCheckRequest::create('https://example.test/kyc/aml/1/details/1/quoteUpdate', 'GET', amlCheckRequestPayloadForIndividualTravel());
    $request->setUserResolver(fn (): User => $user);

    $validator = Validator::make($request->all(), $request->rules());
    $request->withValidator($validator);
    $validator->passes();

    expect($validator->errors()->has('error'))->toBeTrue();
});

it('does not add AMLList error when internal automation attribute is set', function (): void {
    Permission::findOrCreate(PermissionsEnum::AMLList, 'web');

    $user = User::factory()->create();
    $this->actingAs($user);

    $request = AMLCheckRequest::create('https://example.test/kyc/aml/1/details/1/quoteUpdate', 'GET', amlCheckRequestPayloadForIndividualTravel());
    $request->attributes->set(AMLCheckRequest::INTERNAL_AUTOMATION_ATTRIBUTE, true);
    $request->setUserResolver(fn (): User => $user);

    $validator = Validator::make($request->all(), $request->rules());
    $request->withValidator($validator);
    $validator->passes();

    expect($validator->errors()->has('error'))->toBeFalse();
});

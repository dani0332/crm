<?php

declare(strict_types=1);

use App\Enums\PolicyIssuanceEnum;
use App\Models\PolicyIssuance;
use App\Models\TravelQuote;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use App\Services\PolicyIssuanceAutomation\Travel\Dic\DicApiService;
use App\Services\PolicyIssuanceAutomation\Travel\Dic\DicBookPolicyService;
use App\Services\PolicyIssuanceAutomation\Travel\Dic\DicDocumentService;
use App\Services\PolicyIssuanceAutomation\Travel\Dic\DicResponseHandler;
use App\Services\PolicyIssuanceAutomation\Travel\Dic\DicStepExecutor;

afterEach(function () {
    Mockery::close();
});

function makeDicStepExecutorForGetPolicyDocTest(
    ?DicApiService $api = null,
    ?DicDocumentService $documents = null,
): DicStepExecutor {
    return new DicStepExecutor(
        $api ?? Mockery::mock(DicApiService::class),
        new DicResponseHandler,
        $documents ?? Mockery::mock(DicDocumentService::class),
        new DicBookPolicyService(Mockery::mock(PolicyIssuanceService::class)),
        Mockery::mock(PolicyIssuanceService::class),
    );
}

it('executeGetPolicyDocStep fails when schedule document URL is missing from successful API payload', function () {
    $data = [];

    $api = Mockery::mock(DicApiService::class);
    $api->shouldReceive('getPolicyDoc')->once()->andReturn([
        'status' => true,
        'data' => $data,
    ]);
    $api->shouldReceive('extractDocumentUrlFromResponse')->once()->with($data)->andReturn(null);

    $executor = makeDicStepExecutorForGetPolicyDocTest($api);

    $quote = TravelQuote::factory()->make();
    $process = PolicyIssuance::factory()->make();

    $result = $executor->executeGetPolicyDocStep($quote, $process, false);

    expect($result['status'])->toBeFalse()
        ->and($result['completed_step'])->toBe(PolicyIssuanceEnum::DIC_TRAVEL_GET_POLICY_DOC)
        ->and($result['travel_dic_insurer_api_status_id'])->toBe(PolicyIssuanceEnum::UPLOAD_POLICY_DOCUMENTS_API_FAILED_STATUS_ID);
});

it('executeGetPolicyDocStep fails when tax invoice URL is missing from successful API payload', function () {
    $data = [
        'url' => 'https://example.test/certificate.pdf',
    ];

    $api = Mockery::mock(DicApiService::class);
    $api->shouldReceive('getPolicyDoc')->once()->andReturn([
        'status' => true,
        'data' => $data,
    ]);
    $api->shouldReceive('extractDocumentUrlFromResponse')->once()->with($data)->andReturn($data['url']);
    $api->shouldReceive('extractTaxInvoiceUrlFromGetPolicyDocResponse')->once()->with($data)->andReturn(null);

    $executor = makeDicStepExecutorForGetPolicyDocTest($api);

    $quote = TravelQuote::factory()->make();
    $process = PolicyIssuance::factory()->make();

    $result = $executor->executeGetPolicyDocStep($quote, $process, false);

    expect($result['status'])->toBeFalse()
        ->and($result['message'])->toContain('Tax Invoice URL missing');
});

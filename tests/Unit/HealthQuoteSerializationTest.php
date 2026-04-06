<?php

use App\Enums\PolicyIssuanceEnum;
use App\Models\HealthQuote;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;

it('does not include api issuance appends by default so list views avoid resolving PolicyIssuanceService per row', function () {
    $quote = new HealthQuote;

    expect($quote->getAppends())->not->toContain('api_issuance_status', 'insurer_api_status');
});

it('can append api issuance attributes for detail views when needed', function () {
    $quote = new HealthQuote;
    $quote->append(['api_issuance_status', 'insurer_api_status']);

    expect($quote->getAppends())->toContain('api_issuance_status', 'insurer_api_status');
});

it('computes api issuance display fields on stdClass the same as HealthQuote accessors', function () {
    $model = new HealthQuote;
    $model->api_issuance_status_id = PolicyIssuanceEnum::POLICY_ISSUANCE_API_STATUS_YES_ID;
    $model->insurer_api_status_id = null;

    $plain = (object) [
        'api_issuance_status_id' => $model->api_issuance_status_id,
        'insurer_api_status_id' => $model->insurer_api_status_id,
    ];
    $plain->api_issuance_status = $plain->api_issuance_status_id ? PolicyIssuanceEnum::getAPIIssuanceStatuses($plain->api_issuance_status_id) : null;
    $plain->insurer_api_status = $plain->insurer_api_status_id ? app(PolicyIssuanceService::class)->getInsurerAPIStatuses($plain->insurer_api_status_id) : null;

    expect($plain->api_issuance_status)->toBe($model->api_issuance_status)
        ->and($plain->insurer_api_status)->toBe($model->insurer_api_status);
});

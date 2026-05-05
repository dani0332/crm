<?php

use App\Strategies\ManagementReport;

// Expose the protected method under test.
$reportSubclass = new class extends ManagementReport
{
    public function exposedGetBranchMappingCTE(): string
    {
        return $this->getBranchMappingCTE();
    }
};

test('getBranchMappingCTE selects resolved_branch_id via COALESCE', function () use ($reportSubclass) {
    $sql = $reportSubclass->exposedGetBranchMappingCTE();

    expect($sql)
        ->toContain('resolved_branch_id')
        ->toContain('COALESCE');
});

test('getBranchMappingCTE joins health_quote_request for health emirate resolution', function () use ($reportSubclass) {
    $sql = $reportSubclass->exposedGetBranchMappingCTE();

    expect($sql)
        ->toContain('health_quote_request')
        ->toContain('hqr.emirate_of_your_visa_id');
});

test('getBranchMappingCTE joins business_quote_request for group medical emirate resolution', function () use ($reportSubclass) {
    $sql = $reportSubclass->exposedGetBranchMappingCTE();

    expect($sql)
        ->toContain('business_quote_request')
        ->toContain('bqr.emirate_of_registration_id');
});

test('getBranchMappingCTE does not reference customer_insured or insured tables', function () use ($reportSubclass) {
    $sql = $reportSubclass->exposedGetBranchMappingCTE();

    expect($sql)
        ->not->toContain('customer_insured')
        ->not->toContain('latest_customer_insured')
        ->not->toContain('JOIN insured');
});

test('getBranchMappingCTE joins user_branches and branch_override_config', function () use ($reportSubclass) {
    $sql = $reportSubclass->exposedGetBranchMappingCTE();

    expect($sql)
        ->toContain('user_branches')
        ->toContain('branch_override_config');
});

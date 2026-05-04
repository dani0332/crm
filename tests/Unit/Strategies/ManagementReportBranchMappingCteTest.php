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

// Commit ecb530975: CTE reads pq.emirate_of_registration_id directly — no latestInsured join, no latest_customer_insured CTE.

test('getBranchMappingCTE uses pq.emirate_of_registration_id instead of i.emirate_of_registration_id', function () use ($reportSubclass) {
    $sql = $reportSubclass->exposedGetBranchMappingCTE();

    expect($sql)->toContain('pq.emirate_of_registration_id');
});

test('getBranchMappingCTE does not reference i.emirate_of_registration_id from an insured join', function () use ($reportSubclass) {
    $sql = $reportSubclass->exposedGetBranchMappingCTE();

    expect($sql)->not->toContain('i.emirate_of_registration_id');
});

test('getBranchMappingCTE does not join customer_insured or insured tables', function () use ($reportSubclass) {
    $sql = $reportSubclass->exposedGetBranchMappingCTE();

    expect($sql)
        ->not->toContain('customer_insured')
        ->not->toContain('latest_customer_insured')
        ->not->toContain('JOIN insured');
});

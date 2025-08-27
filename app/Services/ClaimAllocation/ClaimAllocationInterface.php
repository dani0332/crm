<?php

declare(strict_types=1);

namespace App\Services\ClaimAllocation;

interface ClaimAllocationInterface
{
    /**
     * Execute claim allocation for a given claim UUID
     *
     * @param string $claimUuid
     * @param int|null $teamId
     * @param bool $overrideAdvisorId
     * @param bool $isReAssignment
     * @return array
     */
    public function execute(string $claimUuid, ?int $teamId = null, bool $overrideAdvisorId = false, bool $isReAssignment = false): array;

    /**
     * Get claim allocation statistics
     *
     * @param int|null $teamId
     * @return array
     */
    public function getClaimAllocationStats(?int $teamId = null): array;

    /**
     * Reassign claim to different advisor
     *
     * @param string $claimUuid
     * @param int $newAdvisorId
     * @return array
     */
    public function reassignClaim(string $claimUuid, int $newAdvisorId): array;
}

<?php

declare(strict_types=1);

use App\Services\ClaimsService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

test('updateClaim throws ModelNotFoundException when claim does not exist', function () {
    $service = new class(app(\App\Services\ClaimStatusesService::class), app(\App\Services\LookupService::class), app(\App\Services\UserService::class)) extends ClaimsService
    {
        public function __construct(
            \App\Services\ClaimStatusesService $claimStatusesService,
            \App\Services\LookupService $lookupService,
            \App\Services\UserService $userService,
        ) {
            parent::__construct($claimStatusesService, $lookupService, $userService);
        }

        public function getClaimById($uuid): ?\App\Models\ClaimRequest
        {
            return null;
        }
    };

    $service->updateClaim('00000000-0000-0000-0000-000000000000', []);
})->throws(ModelNotFoundException::class, 'Claim not found.');

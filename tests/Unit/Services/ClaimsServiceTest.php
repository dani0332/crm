<?php

declare(strict_types=1);

use App\Models\ClaimRequest;
use App\Services\ClaimsService;
use App\Services\ClaimStatusesService;
use App\Services\LookupService;
use App\Services\UserService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

test('updateClaim throws ModelNotFoundException when claim does not exist', function () {
    $service = new class(app(ClaimStatusesService::class), app(LookupService::class), app(UserService::class)) extends ClaimsService
    {
        public function __construct(
            ClaimStatusesService $claimStatusesService,
            LookupService $lookupService,
            UserService $userService,
        ) {
            parent::__construct($claimStatusesService, $lookupService, $userService);
        }

        public function getClaimById($uuid): ?ClaimRequest
        {
            return null;
        }
    };

    $service->updateClaim('00000000-0000-0000-0000-000000000000', []);
})->throws(ModelNotFoundException::class, 'Claim not found.');

<?php

declare(strict_types=1);

use App\Services\ClaimsService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

test('updateClaim throws ModelNotFoundException when claim does not exist', function () {
    $service = new class(app(\App\Services\ClaimStatusesService::class)) extends ClaimsService
    {
        public function getClaimById($uuid): ?\App\Models\ClaimRequest
        {
            return null;
        }
    };

    $service->updateClaim('00000000-0000-0000-0000-000000000000', []);
})->throws(ModelNotFoundException::class, 'Claim not found.');

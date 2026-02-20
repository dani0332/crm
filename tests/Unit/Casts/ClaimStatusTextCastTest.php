<?php

declare(strict_types=1);

use App\Casts\ClaimStatusText;
use App\Models\ClaimStatus;

describe('ClaimStatusText Cast', function () {
    it('handles string input in set without error', function () {
        $cast = new ClaimStatusText;
        $model = new ClaimStatus;

        $result = $cast->set($model, 'text', 'Claim-Registered', []);

        expect($result)->toBe('claim-registered');
    });

    it('handles array input from get return in set without TypeError', function () {
        $cast = new ClaimStatusText;
        $model = new ClaimStatus;

        $arrayFromGet = ['value' => 'claim-registered', 'label' => 'Claim Registered'];

        $result = $cast->set($model, 'text', $arrayFromGet, []);

        expect($result)->toBe('claim-registered');
    });

    it('handles null input in set', function () {
        $cast = new ClaimStatusText;
        $model = new ClaimStatus;

        $result = $cast->set($model, 'text', null, []);

        expect($result)->toBe('');
    });
});

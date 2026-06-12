<?php

declare(strict_types=1);

use App\Casts\ClaimStatusText;
use App\Models\ClaimStatus;

describe('ClaimStatusText Cast', function () {
    it('returns null from get when value is empty', function () {
        $cast = new ClaimStatusText;
        $model = new ClaimStatus;

        $result = $cast->get($model, 'text', null, []);

        expect($result)->toBeNull();
    });

    it('returns enum-based label from get when value matches ClaimsEnum', function () {
        $cast = new ClaimStatusText;
        $model = new ClaimStatus;

        $result = $cast->get($model, 'text', 'claim registered', []);

        expect($result)->toBe([
            'value' => 'claim registered',
            'label' => 'Claim Registered',
        ]);
    });

    it('returns raw value as label from get when value does not match ClaimsEnum', function () {
        $cast = new ClaimStatusText;
        $model = new ClaimStatus;

        $rawStatus = 'custom new status';

        $result = $cast->get($model, 'text', $rawStatus, []);

        expect($result)->toBe([
            'value' => $rawStatus,
            'label' => $rawStatus,
        ]);
    });

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

    it('handles null input in set by returning null', function () {
        $cast = new ClaimStatusText;
        $model = new ClaimStatus;

        $result = $cast->set($model, 'text', null, []);

        expect($result)->toBeNull();
    });

    it('handles empty string input in set by returning null', function () {
        $cast = new ClaimStatusText;
        $model = new ClaimStatus;

        $result = $cast->set($model, 'text', '', []);

        expect($result)->toBeNull();
    });
});

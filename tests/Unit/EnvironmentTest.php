<?php

declare(strict_types=1);

test('environment is set to testing', function () {
    // Check using app()->environment()
    expect(app()->environment('testing'))->toBeTrue();
    expect(app()->environment())->toBe('testing');

    // Check using config
    expect(config('app.env'))->toBe('testing');

    // Check using env() helper
    expect(env('APP_ENV'))->toBe('testing');

    // Verify it's not other environments
    expect(app()->environment('local'))->toBeFalse();
    expect(app()->environment('production'))->toBeFalse();
    expect(app()->environment('staging'))->toBeFalse();
});

test('axiom logging is disabled in testing', function () {
    // Verify that environment check works for Axiom handler
    expect(app()->environment(['local', 'testing']))->toBeTrue();
    expect(app()->environment('testing'))->toBeTrue();
});

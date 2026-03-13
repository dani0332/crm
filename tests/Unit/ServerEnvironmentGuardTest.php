<?php

use App\Enums\EnvEnum;
use App\Services\ServerEnvironmentGuard;
use Illuminate\Support\Facades\Config;

uses()->beforeEach(function () {
    setApplicationEnvironment('testing');
});

it('allows execution when a default environment is active', function () {
    setApplicationEnvironment('production');

    expect(ServerEnvironmentGuard::isAllowed())->toBeTrue();
});

it('blocks execution when the environment is not allowed by default', function () {
    setApplicationEnvironment('testing');

    expect(ServerEnvironmentGuard::isAllowed())->toBeFalse();
});

it('allows execution when an extra allowed environment is provided', function () {
    setApplicationEnvironment(EnvEnum::TEST);

    expect(ServerEnvironmentGuard::isAllowed([EnvEnum::TEST]))->toBeTrue();
});

it('throws when invalid environment values are supplied', function () {
    setApplicationEnvironment('testing');

    ServerEnvironmentGuard::isAllowed(['non-existent-env']);
})->throws(InvalidArgumentException::class);

function setApplicationEnvironment(string $environment): void
{
    putenv("APP_ENV={$environment}");
    $_ENV['APP_ENV'] = $environment;
    $_SERVER['APP_ENV'] = $environment;
    app()->detectEnvironment(fn () => $environment);
    Config::set('app.env', $environment);
}

<?php

declare(strict_types=1);

use App\Enums\DatabaseConnectionEnum;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

beforeEach(function (): void {
    $GLOBALS['__originalDefaultDbConnection'] = DB::getDefaultConnection();
    Log::spy();
});

afterEach(function (): void {
    if (isset($GLOBALS['__originalDefaultDbConnection'])) {
        DB::setDefaultConnection($GLOBALS['__originalDefaultDbConnection']);
        unset($GLOBALS['__originalDefaultDbConnection']);
    }
});

it('does not throw when default is mysql_read', function (): void {
    DB::setDefaultConnection(DatabaseConnectionEnum::MYSQL_READ->value);

    expect(fn () => ensureWriteDefaultConnection())->not->toThrow(Throwable::class);
});

it('switches mysql_read to mysql', function (): void {
    DB::setDefaultConnection(DatabaseConnectionEnum::MYSQL_READ->value);

    ensureWriteDefaultConnection();

    expect(DB::getDefaultConnection())->toBe(DatabaseConnectionEnum::MYSQL->value);
});

it('does not throw and does not switch when default is mysql', function (): void {
    DB::setDefaultConnection(DatabaseConnectionEnum::MYSQL->value);

    expect(fn () => ensureWriteDefaultConnection())->not->toThrow(Throwable::class);

    expect(DB::getDefaultConnection())->toBe(DatabaseConnectionEnum::MYSQL->value);
});

it('logs warning with from/to and merges provided context when switching', function (): void {
    DB::setDefaultConnection(DatabaseConnectionEnum::MYSQL_READ->value);

    $customContext = [
        'job' => 'MAWelcomeJob',
        'sentry_issue' => '7034710591',
    ];

    ensureWriteDefaultConnection($customContext);

    Log::shouldHaveReceived('warning')
        ->once()
        ->withArgs(function (string $message, array $context) use ($customContext): bool {
            return str_contains($message, 'ensureWriteDefaultConnection')
                && ($context['from'] ?? null) === DatabaseConnectionEnum::MYSQL_READ->value
                && ($context['to'] ?? null) === DatabaseConnectionEnum::MYSQL->value
                && ($context['job'] ?? null) === $customContext['job']
                && ($context['sentry_issue'] ?? null) === $customContext['sentry_issue'];
        });
});

it('does not log warning when no switch occurs', function (): void {
    DB::setDefaultConnection(DatabaseConnectionEnum::MYSQL->value);

    ensureWriteDefaultConnection();

    Log::shouldNotHaveReceived('warning');
});

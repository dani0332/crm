<?php

declare(strict_types=1);

use App\Models\User;
use App\Repositories\AuditRepository;
use Illuminate\Contracts\Container\BindingResolutionException;

test('primaryAuditableTypeForQuoteType returns null for empty quote type', function () {
    expect(AuditRepository::primaryAuditableTypeForQuoteType(null))->toBeNull();
    expect(AuditRepository::primaryAuditableTypeForQuoteType(''))->toBeNull();
});

test('primaryAuditableTypeForQuoteType resolves User auditable type', function () {
    expect(AuditRepository::primaryAuditableTypeForQuoteType('User'))->toBe(User::class);
});

test('primaryAuditableTypeForQuoteType throws when quote model cannot be resolved', function () {
    expect(fn () => AuditRepository::primaryAuditableTypeForQuoteType('NotARealQuoteModelClassName'))
        ->toThrow(BindingResolutionException::class);
});

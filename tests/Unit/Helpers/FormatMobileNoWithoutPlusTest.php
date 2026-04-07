<?php

it('strips leading single quote before normalizing UAE local numbers', function (): void {
    expect(formatMobileNoWithoutPlus("'0551091652"))->toBe('971551091652');
});

it('strips trailing single quote before normalizing UAE local numbers', function (): void {
    expect(formatMobileNoWithoutPlus("0551091652'"))->toBe('971551091652');
});

it('strips wrapping quotes before normalizing UAE local numbers', function (): void {
    expect(formatMobileNoWithoutPlus("'0551091652'"))->toBe('971551091652');
});

it('strips wrapping double quotes before normalizing UAE local numbers', function (): void {
    expect(formatMobileNoWithoutPlus('"0551091652"'))->toBe('971551091652');
});

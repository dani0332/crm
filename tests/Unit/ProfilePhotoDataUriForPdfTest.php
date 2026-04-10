<?php

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

it('returns null for empty path', function () {
    expect(profilePhotoDataUriForPdf(null))->toBeNull();
    expect(profilePhotoDataUriForPdf(''))->toBeNull();
});

it('returns null when remote response is unsuccessful', function () {
    Http::fake([
        'https://example.com/avatar.jpg' => Http::response('', 503),
    ]);

    expect(profilePhotoDataUriForPdf('https://example.com/avatar.jpg'))->toBeNull();
});

it('returns null when remote request throws', function () {
    Http::fake(function () {
        throw new ConnectionException('Network is unreachable');
    });

    expect(profilePhotoDataUriForPdf('https://lh3.googleusercontent.com/test'))->toBeNull();
});

it('builds data uri from successful remote response', function () {
    Http::fake([
        'https://example.com/avatar.jpg' => Http::response('fake-bytes', 200, ['Content-Type' => 'image/jpeg']),
    ]);

    $uri = profilePhotoDataUriForPdf('https://example.com/avatar.jpg');

    expect($uri)->toStartWith('data:image/jpeg;base64,');
    $b64 = substr($uri, strlen('data:image/jpeg;base64,'));
    expect(base64_decode($b64))->toBe('fake-bytes');
});

it('builds data uri from local file', function () {
    $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'blanka-pdf-photo-test.png';
    file_put_contents($path, 'png-bytes');

    $uri = profilePhotoDataUriForPdf($path);

    @unlink($path);

    expect($uri)->toStartWith('data:');
    expect($uri)->toContain('base64,');
});

it('returns null for missing local path', function () {
    expect(profilePhotoDataUriForPdf('/nonexistent/path/to/nothing.png'))->toBeNull();
});

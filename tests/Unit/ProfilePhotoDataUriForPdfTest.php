<?php

use App\Services\UserService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

it('returns null for empty path', function () {
    $userService = app(UserService::class);

    expect($userService->profilePhotoDataUriForPdf(null))->toBeNull();
    expect($userService->profilePhotoDataUriForPdf(''))->toBeNull();
});

it('returns null when remote response is unsuccessful', function () {
    Http::fake([
        'https://example.com/avatar.jpg' => Http::response('', 503),
    ]);

    expect(app(UserService::class)->profilePhotoDataUriForPdf('https://example.com/avatar.jpg'))->toBeNull();
});

it('returns null when remote request throws', function () {
    Http::fake(function () {
        throw new ConnectionException('Network is unreachable');
    });

    expect(app(UserService::class)->profilePhotoDataUriForPdf('https://lh3.googleusercontent.com/test'))->toBeNull();
});

it('builds data uri from successful remote response', function () {
    Http::fake([
        'https://example.com/avatar.jpg' => Http::response('fake-bytes', 200, ['Content-Type' => 'image/jpeg']),
    ]);

    $uri = app(UserService::class)->profilePhotoDataUriForPdf('https://example.com/avatar.jpg');

    expect($uri)->toStartWith('data:image/jpeg;base64,');
    $b64 = substr($uri, strlen('data:image/jpeg;base64,'));
    expect(base64_decode($b64))->toBe('fake-bytes');
});

it('builds data uri from local file', function () {
    $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'blanka-pdf-photo-test.png';
    file_put_contents($path, 'png-bytes');

    $uri = app(UserService::class)->profilePhotoDataUriForPdf($path);

    @unlink($path);

    expect($uri)->toStartWith('data:');
    expect($uri)->toContain('base64,');
});

it('returns null for missing local path', function () {
    expect(app(UserService::class)->profilePhotoDataUriForPdf('/nonexistent/path/to/nothing.png'))->toBeNull();
});

<?php

use App\Http\Controllers\V2\BorController;
use App\Services\Bor\BorService;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

afterEach(function () {
    Mockery::close();
});

it('rejects a png that parses as an image but embeds script-like markup', function () {
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
    $tmp = tempnam(sys_get_temp_dir(), 'bor');
    file_put_contents($tmp, $png.'<script>evil</script>');
    $uploaded = new UploadedFile($tmp, 'test.png', 'image/png', null, true);

    $controller = new BorController(Mockery::mock(BorService::class));
    $method = new ReflectionMethod(BorController::class, 'validateBorUploadFileContents');
    $method->setAccessible(true);

    try {
        expect(fn () => $method->invoke($controller, $uploaded))
            ->toThrow(ValidationException::class);
    } finally {
        @unlink($tmp);
    }
});

it('rejects a png containing php open tag', function () {
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
    $tmp = tempnam(sys_get_temp_dir(), 'bor');
    file_put_contents($tmp, $png.'<?php echo 1; ?>');
    $uploaded = new UploadedFile($tmp, 'test.png', 'image/png', null, true);

    $controller = new BorController(Mockery::mock(BorService::class));
    $method = new ReflectionMethod(BorController::class, 'validateBorUploadFileContents');
    $method->setAccessible(true);

    try {
        expect(fn () => $method->invoke($controller, $uploaded))
            ->toThrow(ValidationException::class);
    } finally {
        @unlink($tmp);
    }
});

it('accepts a minimal valid png without disallowed markup', function () {
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
    $tmp = tempnam(sys_get_temp_dir(), 'bor');
    file_put_contents($tmp, $png);
    $uploaded = new UploadedFile($tmp, 'test.png', 'image/png', null, true);

    $controller = new BorController(Mockery::mock(BorService::class));
    $method = new ReflectionMethod(BorController::class, 'validateBorUploadFileContents');
    $method->setAccessible(true);
    $method->invoke($controller, $uploaded);

    @unlink($tmp);

    expect(true)->toBeTrue();
});

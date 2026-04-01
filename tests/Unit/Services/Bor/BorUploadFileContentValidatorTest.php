<?php

use App\Services\Bor\BorUploadFileContentValidator;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

it('rejects a png that parses as an image but embeds script-like markup', function () {
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
    $tmp = tempnam(sys_get_temp_dir(), 'bor');
    file_put_contents($tmp, $png.'<script>evil</script>');
    $uploaded = new UploadedFile($tmp, 'test.png', 'image/png', null, true);

    $validator = new BorUploadFileContentValidator;

    try {
        expect(fn () => $validator->validate($uploaded))
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

    $validator = new BorUploadFileContentValidator;

    try {
        expect(fn () => $validator->validate($uploaded))
            ->toThrow(ValidationException::class);
    } finally {
        @unlink($tmp);
    }
});

it('rejects a png with zTXt chunk whose zlib payload fails decode but contains raw markup', function () {
    $basePng = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
    $iendPos = strpos($basePng, "\x00\x00\x00\x00IEND");
    expect($iendPos)->not->toBeFalse();
    // zTXt: keyword + NUL + compression 0 + bytes that are not valid zlib (raw script markup).
    $ztxtData = "T\0\0<script>evil</script>";
    $crc = crc32('zTXt'.$ztxtData);
    if ($crc < 0) {
        $crc += (1 << 32);
    }
    $ztxtChunk = pack('N', strlen($ztxtData)).'zTXt'.$ztxtData.pack('N', $crc);
    $png = substr($basePng, 0, $iendPos).$ztxtChunk.substr($basePng, $iendPos);
    $tmp = tempnam(sys_get_temp_dir(), 'bor');
    file_put_contents($tmp, $png);
    $uploaded = new UploadedFile($tmp, 'test.png', 'image/png', null, true);

    $validator = new BorUploadFileContentValidator;

    try {
        expect(fn () => $validator->validate($uploaded))
            ->toThrow(ValidationException::class);
    } finally {
        @unlink($tmp);
    }
});

it('rejects a raster image when full file contents cannot be read for validation', function () {
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
    $tmp = tempnam(sys_get_temp_dir(), 'bor');
    file_put_contents($tmp, $png);
    $uploaded = new UploadedFile($tmp, 'test.png', 'image/png', null, true);

    $validator = new class extends BorUploadFileContentValidator
    {
        protected function readFileContentsForValidation(string $path): string|false
        {
            return false;
        }
    };

    try {
        expect(fn () => $validator->validate($uploaded))
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

    (new BorUploadFileContentValidator)->validate($uploaded);

    @unlink($tmp);

    expect(true)->toBeTrue();
});

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

it('rejects a png with zTXt chunk using non-zero compression method and raw markup in data', function () {
    $basePng = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
    $iendPos = strpos($basePng, "\x00\x00\x00\x00IEND");
    expect($iendPos)->not->toBeFalse();
    // zTXt: only method 0 is defined; reserved/non-zero must still be scanned as raw bytes.
    $ztxtData = "T\0\x01<script>evil</script>";
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

it('detects COM segments after APP1 when APP1 payload contains an embedded FF DA sequence', function () {
    // Simulates EXIF thumbnail bytes inside APP1: first \xFF\xDA in the file is not the real SOS.
    // A naive strpos(\xFF\xDA) header cut would skip the COM that follows APP1.
    $jpeg = "\xFF\xD8";
    $app1Payload = "\xFF\xDA".str_repeat("\x00", 16);
    $app1SegLen = 2 + strlen($app1Payload);
    $jpeg .= "\xFF\xE1".pack('n', $app1SegLen).$app1Payload;
    $comPayload = '<script>x</script>';
    $jpeg .= "\xFF\xFE".pack('n', 2 + strlen($comPayload)).$comPayload;
    $jpeg .= "\xFF\xD9";

    expect(strpos($jpeg, "\xFF\xDA"))->toBe(6);

    $validator = new BorUploadFileContentValidator;
    $method = new ReflectionMethod(BorUploadFileContentValidator::class, 'jpegComSegmentsInHeaderContainDisallowedMarkup');
    $method->setAccessible(true);

    expect($method->invoke($validator, $jpeg))->toBeTrue();
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

it('accepts a typical pdf by header magic without treating it as a raster image', function () {
    $tmp = tempnam(sys_get_temp_dir(), 'bor');
    file_put_contents($tmp, "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF");
    expect(@getimagesize($tmp))->toBeFalse();

    $uploaded = new UploadedFile($tmp, 'bor.pdf', 'application/pdf', null, true);
    (new BorUploadFileContentValidator)->validate($uploaded);

    @unlink($tmp);

    expect(true)->toBeTrue();
});

it('accepts a minimal valid docx containing OOXML parts', function () {
    $tmp = tempnam(sys_get_temp_dir(), 'bor').'.docx';
    $zip = new ZipArchive;
    expect($zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE))->toBeTrue();
    $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>');
    $zip->addFromString('word/document.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body><w:p><w:r><w:t>x</w:t></w:r></w:p></w:body></w:document>');
    $zip->close();
    expect(@getimagesize($tmp))->toBeFalse();

    $uploaded = new UploadedFile($tmp, 'bor.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true);
    (new BorUploadFileContentValidator)->validate($uploaded);

    @unlink($tmp);

    expect(true)->toBeTrue();
})->skip(
    ! extension_loaded('zip'),
    'ext-zip required'
);

it('rejects docx extension when archive is not OOXML Word', function () {
    $tmp = tempnam(sys_get_temp_dir(), 'bor').'.docx';
    file_put_contents($tmp, "PK\x03\x04\x14\x00\x00\x00\x08\x00".str_repeat("\x00", 18).'word/');
    expect(@getimagesize($tmp))->toBeFalse();

    $uploaded = new UploadedFile($tmp, 'bor.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true);

    try {
        expect(fn () => (new BorUploadFileContentValidator)->validate($uploaded))
            ->toThrow(ValidationException::class);
    } finally {
        @unlink($tmp);
    }
});

it('rejects disallowed client file extension', function () {
    $tmp = tempnam(sys_get_temp_dir(), 'bor').'.html';
    file_put_contents($tmp, '<html><body></body></html>');
    $uploaded = new UploadedFile($tmp, 'x.html', 'text/html', null, true);

    try {
        expect(fn () => (new BorUploadFileContentValidator)->validate($uploaded))
            ->toThrow(ValidationException::class);
    } finally {
        @unlink($tmp);
    }
});

it('rejects pdf with html-like payload in leading bytes', function () {
    $tmp = tempnam(sys_get_temp_dir(), 'bor');
    file_put_contents($tmp, "%PDF-1.4\n<html><body></body></html>");
    $uploaded = new UploadedFile($tmp, 'x.pdf', 'application/pdf', null, true);

    try {
        expect(fn () => (new BorUploadFileContentValidator)->validate($uploaded))
            ->toThrow(ValidationException::class);
    } finally {
        @unlink($tmp);
    }
});

it('accepts a legacy ole compound document by header magic', function () {
    $tmp = tempnam(sys_get_temp_dir(), 'bor');
    file_put_contents($tmp, "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1".str_repeat("\x00", 64));
    expect(@getimagesize($tmp))->toBeFalse();

    $uploaded = new UploadedFile($tmp, 'bor.doc', 'application/msword', null, true);
    (new BorUploadFileContentValidator)->validate($uploaded);

    @unlink($tmp);

    expect(true)->toBeTrue();
});

it('accepts a jpeg produced by gd as a normal raster upload', function () {
    $tmp = tempnam(sys_get_temp_dir(), 'bor');
    $im = imagecreatetruecolor(8, 8);
    $white = imagecolorallocate($im, 255, 255, 255);
    imagefill($im, 0, 0, $white);
    imagejpeg($im, $tmp, 90);
    imagedestroy($im);

    expect(@getimagesize($tmp))->not->toBeFalse();

    $uploaded = new UploadedFile($tmp, 'photo.jpg', 'image/jpeg', null, true);
    (new BorUploadFileContentValidator)->validate($uploaded);

    @unlink($tmp);

    expect(true)->toBeTrue();
})->skip(
    fn () => ! extension_loaded('gd') || ! function_exists('imagejpeg'),
    'GD with JPEG support required'
);

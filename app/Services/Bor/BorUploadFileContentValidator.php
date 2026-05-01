<?php

namespace App\Services\Bor;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class BorUploadFileContentValidator
{
    private const ALLOWED_EXTENSIONS = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];

    /**
     * @var array<string, list<string>>
     */
    private const REPORTED_MIMES_BY_EXTENSION = [
        'pdf' => ['application/pdf'],
        'doc' => ['application/msword'],
        'docx' => [
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/zip',
            'application/x-zip-compressed',
        ],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png', 'image/x-png'],
    ];

    /**
     * Verify client extension, reported MIME, file signatures (magic bytes), and type-specific structure.
     * Blocks generic ZIP/HTML/script polyglots that only matched loose PK/%PDF checks before.
     *
     * @throws ValidationException
     */
    public function validate(UploadedFile $file): void
    {
        $path = $file->getRealPath();
        if (! is_string($path) || $path === '' || ! is_readable($path)) {
            throw ValidationException::withMessages([
                'file' => ['The file could not be read for validation.'],
            ]);
        }

        $extension = strtolower($file->getClientOriginalExtension());
        if ($extension === '' || ! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw ValidationException::withMessages([
                'file' => ['The file extension is not allowed.'],
            ]);
        }

        $reportedMime = strtolower((string) $file->getMimeType());
        if (! $this->reportedMimeIsAllowedForExtension($reportedMime, $extension)) {
            throw ValidationException::withMessages([
                'file' => ['The file type does not match an allowed document format.'],
            ]);
        }

        match ($extension) {
            'jpg', 'jpeg' => $this->validateJpegUpload($path),
            'png' => $this->validatePngUpload($path),
            'pdf' => $this->validatePdfUpload($path),
            'docx' => $this->validateDocxUpload($path),
            'doc' => $this->validateLegacyWordUpload($path),
            default => throw ValidationException::withMessages([
                'file' => ['The file extension is not allowed.'],
            ]),
        };
    }

    private function reportedMimeIsAllowedForExtension(string $reportedMime, string $extension): bool
    {
        $allowed = self::REPORTED_MIMES_BY_EXTENSION[$extension] ?? [];

        if (in_array($reportedMime, $allowed, true)) {
            return true;
        }

        return $reportedMime === 'application/octet-stream';
    }

    private function validateJpegUpload(string $path): void
    {
        $head = $this->readLeadingBytes($path, 3);
        if ($head === null || ! str_starts_with($head, "\xFF\xD8\xFF")) {
            throw ValidationException::withMessages([
                'file' => ['The file is not a valid JPEG image.'],
            ]);
        }

        $info = @getimagesize($path);
        if ($info === false || ($info[2] ?? null) !== IMAGETYPE_JPEG) {
            throw ValidationException::withMessages([
                'file' => ['The file is not a valid JPEG image.'],
            ]);
        }

        $this->assertRasterHasNoDisallowedPayload($path);
    }

    private function validatePngUpload(string $path): void
    {
        $head = $this->readLeadingBytes($path, 8);
        if ($head === null || ! str_starts_with($head, "\x89PNG\r\n\x1a\n")) {
            throw ValidationException::withMessages([
                'file' => ['The file is not a valid PNG image.'],
            ]);
        }

        $info = @getimagesize($path);
        if ($info === false || ($info[2] ?? null) !== IMAGETYPE_PNG) {
            throw ValidationException::withMessages([
                'file' => ['The file is not a valid PNG image.'],
            ]);
        }

        $this->assertRasterHasNoDisallowedPayload($path);
    }

    private function assertRasterHasNoDisallowedPayload(string $path): void
    {
        $contents = $this->readFileContentsForValidation($path);
        if ($contents === false) {
            throw ValidationException::withMessages([
                'file' => ['The file could not be read for validation.'],
            ]);
        }

        if ($this->rasterImageContainsDisallowedPayload($contents)) {
            throw ValidationException::withMessages([
                'file' => ['The file contains disallowed content.'],
            ]);
        }
    }

    private function validatePdfUpload(string $path): void
    {
        $head = $this->readLeadingBytes($path, 5);
        if ($head === null || ! str_starts_with($head, '%PDF')) {
            throw ValidationException::withMessages([
                'file' => ['The file is not a valid PDF.'],
            ]);
        }

        $probe = $this->readLeadingBytes($path, 8192);
        if ($probe !== null && $this->asciiLooksLikeWebOrPhpPayload($probe)) {
            throw ValidationException::withMessages([
                'file' => ['The file content is not allowed for PDF uploads.'],
            ]);
        }
    }

    private function validateDocxUpload(string $path): void
    {
        $head = $this->readLeadingBytes($path, 4);
        if ($head === null || ! str_starts_with($head, "PK\x03\x04")) {
            throw ValidationException::withMessages([
                'file' => ['The file is not a valid Word document (OOXML).'],
            ]);
        }

        if (! class_exists(\ZipArchive::class)) {
            throw ValidationException::withMessages([
                'file' => ['Document validation is temporarily unavailable.'],
            ]);
        }

        if (! $this->zipArchiveIsOfficeOpenXmlWordDocument($path)) {
            throw ValidationException::withMessages([
                'file' => ['The file is not a valid Word document (.docx).'],
            ]);
        }
    }

    private function validateLegacyWordUpload(string $path): void
    {
        $head = $this->readLeadingBytes($path, 8);
        $oleMagic = "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1";
        if ($head === null || ! str_starts_with($head, $oleMagic)) {
            throw ValidationException::withMessages([
                'file' => ['The file is not a valid Word document (.doc).'],
            ]);
        }
    }

    /**
     * OOXML wordprocessing package: [Content_Types].xml plus main document part.
     */
    private function zipArchiveIsOfficeOpenXmlWordDocument(string $path): bool
    {
        $zip = new \ZipArchive;
        if ($zip->open($path, \ZipArchive::RDONLY) !== true) {
            return false;
        }

        $hasContentTypes = $zip->locateName('[Content_Types].xml') !== false;
        $hasWordDocument = $zip->locateName('word/document.xml') !== false;
        $zip->close();

        return $hasContentTypes && $hasWordDocument;
    }

    /**
     * High-confidence markers in the leading bytes (polyglot HTML/PHP in a disguised binary upload).
     */
    private function asciiLooksLikeWebOrPhpPayload(string $bytes): bool
    {
        return (bool) preg_match(
            '/<\?php\b|<\?=\s*|\b<!DOCTYPE\s+html\b|<\s*html[\s>]|<\s*head[\s>]|<\s*body[\s>]|<\s*script\b|<\s*svg\b/i',
            $bytes
        );
    }

    private function readLeadingBytes(string $path, int $maxLength): ?string
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            return null;
        }

        $data = fread($handle, $maxLength);
        fclose($handle);

        if ($data === false || $data === '') {
            return null;
        }

        return $data;
    }

    /**
     * Full file read for raster deep validation. Overridable in tests.
     *
     * @return string|false Same contract as file_get_contents()
     */
    protected function readFileContentsForValidation(string $path): string|false
    {
        return file_get_contents($path);
    }

    /**
     * Raster safety: do not regex-scan compressed pixel/entropy data (false positives).
     * PNG — only textual chunks (tEXt, zTXt, iTXt) get full markup rules; JPEG — COM markers in the header (before SOS) only; both — high-confidence patterns on trailing bytes after IEND/EOI.
     */
    private function rasterImageContainsDisallowedPayload(string $contents): bool
    {
        if (str_starts_with($contents, "\x89PNG\r\n\x1a\n")) {
            if ($this->pngTextualChunksContainDisallowedMarkup($contents)) {
                return true;
            }

            return $this->binaryContainsHighConfidenceInjection($this->pngTrailingBytesAfterIend($contents));
        }

        if (str_starts_with($contents, "\xFF\xD8")) {
            if ($this->jpegComSegmentsInHeaderContainDisallowedMarkup($contents)) {
                return true;
            }

            $eoi = strrpos($contents, "\xFF\xD9");
            $trailer = $eoi !== false ? substr($contents, $eoi + 2) : '';

            return $this->binaryContainsHighConfidenceInjection($trailer);
        }

        return $this->binaryContainsHighConfidenceInjection($contents);
    }

    /**
     * Patterns unlikely to appear in random compressed image bits; used for post-IEND / post-EOI junk only.
     */
    private function binaryContainsHighConfidenceInjection(string $blob): bool
    {
        if ($blob === '') {
            return false;
        }

        return (bool) preg_match(
            '/<\s*script\b|<\s*\/\s*script\s*>|<\?php\b|<\?=\s*|(?:^|[\s"\'`=:(])\s*javascript\s*:/i',
            $blob
        );
    }

    private function pngTrailingBytesAfterIend(string $contents): string
    {
        $lenTotal = strlen($contents);
        $offset = 8;
        while ($offset + 12 <= $lenTotal) {
            $chunkLen = (int) unpack('N', substr($contents, $offset, 4))[1];
            $type = substr($contents, $offset + 4, 4);
            if ($chunkLen < 0 || $offset + 8 + $chunkLen + 4 > $lenTotal) {
                break;
            }
            $offset += 8 + $chunkLen + 4;
            if ($type === 'IEND') {
                return $offset < $lenTotal ? substr($contents, $offset) : '';
            }
        }

        return '';
    }

    private function pngTextualChunksContainDisallowedMarkup(string $contents): bool
    {
        $lenTotal = strlen($contents);
        $offset = 8;
        while ($offset + 12 <= $lenTotal) {
            $chunkLen = (int) unpack('N', substr($contents, $offset, 4))[1];
            $type = substr($contents, $offset + 4, 4);
            if ($chunkLen < 0 || $offset + 8 + $chunkLen + 4 > $lenTotal) {
                break;
            }
            $chunkData = substr($contents, $offset + 8, $chunkLen);
            if ($type === 'tEXt' && $this->textPayloadContainsDisallowedMarkup($chunkData)) {
                return true;
            }
            if ($type === 'zTXt') {
                $decoded = $this->pngDecodeZtxtPayload($chunkData);
                if ($decoded !== null && $this->textPayloadContainsDisallowedMarkup($decoded)) {
                    return true;
                }
            }
            if ($type === 'iTXt') {
                $text = $this->pngExtractITxtText($chunkData);
                if ($text !== null && $this->textPayloadContainsDisallowedMarkup($text)) {
                    return true;
                }
            }
            $offset += 8 + $chunkLen + 4;
        }

        return false;
    }

    private function pngDecodeZtxtPayload(string $chunkData): ?string
    {
        $nul = strpos($chunkData, "\0");
        if ($nul === false || ! isset($chunkData[$nul + 1])) {
            return null;
        }
        $compressionMethod = ord($chunkData[$nul + 1]);
        $afterMethod = substr($chunkData, $nul + 2);
        if ($afterMethod === '') {
            return null;
        }
        if ($compressionMethod !== 0) {
            return $afterMethod;
        }

        $plain = @zlib_decode($afterMethod);

        return is_string($plain) ? $plain : $afterMethod;
    }

    private function pngExtractITxtText(string $chunkData): ?string
    {
        $nul = strpos($chunkData, "\0");
        if ($nul === false || $nul === 0) {
            return null;
        }
        $rest = substr($chunkData, $nul + 1);
        if ($rest === '' || strlen($rest) < 2) {
            return null;
        }
        $compressed = ord($rest[0]) === 1;
        $method = ord($rest[1]);
        $rest = substr($rest, 2);
        $nul = strpos($rest, "\0");
        if ($nul === false) {
            return null;
        }
        $rest = substr($rest, $nul + 1);
        $nul = strpos($rest, "\0");
        if ($nul === false) {
            return null;
        }
        $text = substr($rest, $nul + 1);
        if ($compressed && $method === 0) {
            $dec = @zlib_decode($text);

            return is_string($dec) ? $dec : $text;
        }

        return $text;
    }

    /**
     * JPEG COM (0xFFFE) in the scan header only (markers before SOS).
     * Parsed segment-by-segment using length fields so a fake \xFF\xDA inside APP1/EXIF
     * (e.g. embedded thumbnail JPEG) does not truncate the region and hide COM segments after APP1.
     */
    private function jpegComSegmentsInHeaderContainDisallowedMarkup(string $contents): bool
    {
        if (! str_starts_with($contents, "\xFF\xD8")) {
            return false;
        }

        $n = strlen($contents);
        $i = 2;

        while ($i < $n) {
            if ($contents[$i] !== "\xFF") {
                break;
            }
            $i++;
            while ($i < $n && $contents[$i] === "\xFF") {
                $i++;
            }
            if ($i >= $n) {
                break;
            }

            $marker = ord($contents[$i]);
            $i++;

            if ($marker === 0x00) {
                continue;
            }

            if ($marker === 0xD9) {
                break;
            }

            if ($marker === 0xDA) {
                break;
            }

            if (($marker >= 0xD0 && $marker <= 0xD7) || $marker === 0x01) {
                continue;
            }

            if ($i + 2 > $n) {
                break;
            }

            $segLen = (ord($contents[$i]) << 8) | ord($contents[$i + 1]);
            if ($segLen < 2 || $i + $segLen > $n) {
                break;
            }

            if ($marker === 0xFE) {
                $payload = substr($contents, $i + 2, $segLen - 2);
                if ($this->textPayloadContainsDisallowedMarkup($payload)) {
                    return true;
                }
            }

            $i += $segLen;
        }

        return false;
    }

    /**
     * Full rules for decoded text / metadata only (not raw IDAT / JPEG scan bytes).
     */
    private function textPayloadContainsDisallowedMarkup(string $contents): bool
    {
        static $patterns = [
            '/<\s*(?:script|\/\s*script|iframe|object|embed|frameset|frame|applet|svg|math)\b/i',
            '/<\s*(?:link|meta|base|form|input|button|textarea|select|option|style|video|audio|source|picture)\b/i',
            '/(?:^|[\s"\'`=:(])\s*javascript\s*:/i',
            '/(?:^|[\s"\'`=:(])\s*(?:vbscript|jscript)\s*:/i',
            '/(?:^|[\s"\'`=:(])\s*data\s*:\s*(?:text\/html|application\/(?:xhtml\+xml|javascript|ecmascript)|image\/svg\+xml)\b/i',
            '/\bon\w+\s*=/i',
            '/<\?php\b|<\?=\s*|<\?(?!xml\b)/i',
            '/<%(?:@|=|--|\s)/i',
            '/expression\s*\(\s*[\'"]/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $contents) === 1) {
                return true;
            }
        }

        return false;
    }
}

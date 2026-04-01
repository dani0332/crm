<?php

namespace App\Services\Bor;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class BorUploadFileContentValidator
{
    /**
     * Verify file structure beyond MIME sniffing: real image/PDF/Office headers, and block HTML/script
     * in PNG text chunks / JPEG COM comments and high-confidence payloads after IEND/EOI — not raw pixel data.
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

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw ValidationException::withMessages([
                'file' => ['The file could not be read for validation.'],
            ]);
        }

        $head = fread($handle, 8);
        fclose($handle);

        if ($head === false || $head === '') {
            throw ValidationException::withMessages([
                'file' => ['The uploaded file is empty.'],
            ]);
        }

        $isRasterImage = @getimagesize($path) !== false;

        if ($isRasterImage) {
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

            return;
        }

        if (str_starts_with($head, '%PDF')) {
            return;
        }

        if (str_starts_with($head, "PK\x03\x04")) {
            return;
        }

        if (str_starts_with($head, "\xD0\xCF\x11\xE0")) {
            return;
        }

        throw ValidationException::withMessages([
            'file' => ['The file could not be verified as an allowed document type.'],
        ]);
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
        if (ord($chunkData[$nul + 1]) !== 0) {
            return null;
        }
        $compressed = substr($chunkData, $nul + 2);
        if ($compressed === '') {
            return null;
        }
        $plain = @zlib_decode($compressed);

        return is_string($plain) ? $plain : $compressed;
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
     * JPEG COM (0xFFFE) only in the header region before SOS — avoids false matches inside entropy-coded scan data.
     */
    private function jpegComSegmentsInHeaderContainDisallowedMarkup(string $contents): bool
    {
        $sos = strpos($contents, "\xFF\xDA");
        $header = $sos !== false ? substr($contents, 0, $sos) : $contents;
        $offset = 0;
        $headerLen = strlen($header);
        while ($offset + 4 <= $headerLen) {
            $pos = strpos($header, "\xFF\xFE", $offset);
            if ($pos === false) {
                break;
            }
            if ($pos + 4 > $headerLen) {
                break;
            }
            $segLen = (ord($header[$pos + 2]) << 8) | ord($header[$pos + 3]);
            if ($segLen < 2) {
                break;
            }
            $payload = substr($header, $pos + 4, $segLen - 2);
            if ($this->textPayloadContainsDisallowedMarkup($payload)) {
                return true;
            }
            $offset = $pos + 2 + $segLen;
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

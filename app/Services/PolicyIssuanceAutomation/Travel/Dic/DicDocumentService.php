<?php

declare(strict_types=1);

namespace App\Services\PolicyIssuanceAutomation\Travel\Dic;

use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Jobs\WatermarkDocumentsJob;
use App\Models\DocumentType;
use App\Models\TravelQuote;
use App\Services\Logger\LoggerService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use setasign\Fpdi\Fpdi;

class DicDocumentService
{
    private const TYPE = quoteTypeCode::Travel;
    private const TYPE_ID = QuoteTypeId::Travel;

    public function attachFromUrl(TravelQuote $quote, string $documentUrl, string $documentCode, string $originalName): void
    {
        LoggerService::info('DIC Travel: attaching document from URL', [
            'quote_code' => $quote->code,
            'document_code' => $documentCode,
        ]);

        $documentType = DocumentType::query()
            ->where([
                'quote_type_id' => self::TYPE_ID,
                'code' => $documentCode,
                'is_active' => true,
            ])
            ->first();

        if (! $documentType) {
            LoggerService::error('DIC Travel: document type not found', [
                'quote_code' => $quote->code,
                'document_code' => $documentCode,
            ]);

            throw new RuntimeException('DIC Travel: document type not found for code '.$documentCode);
        }

        // S3 pre-signed URLs: one GET; avoid a separate HEAD (often not allowed on the same presigned request).
        $download = Http::timeout(120)
            ->withOptions(['allow_redirects' => true])
            ->get($documentUrl);

        if (! $download->successful() || $download->body() === '') {
            throw new RuntimeException(
                'DIC Travel: failed to download document from URL (HTTP '.($download->status() ?? 0).')',
            );
        }

        $mimeType = $this->mimeTypeFromResponseHeader($download->header('Content-Type'));

        $docName = pathinfo($originalName, PATHINFO_EXTENSION) !== ''
            ? $originalName
            : $originalName.'.pdf';

        $fileNameAzure = uniqid('', true).'_'.$quote->uuid.'_'.str_replace(' ', '_', $docName);
        $filePathAzure = 'documents/'.ucwords(self::TYPE).'/'.$fileNameAzure;
        $fileContent = $this->rewritePdfTitle($download->body(), $docName, $mimeType);
        Storage::disk('azureIMPrivate')->put($filePathAzure, $fileContent);

        $newDocument = $quote->documents()->create([
            'doc_name' => $docName,
            'original_name' => $docName,
            'doc_url' => $filePathAzure,
            'doc_mime_type' => $mimeType,
            'document_type_code' => $documentType->code,
            'document_type_text' => $documentType->text,
            'doc_uuid' => generateUUID(),
        ]);

        if ($newDocument->exists) {
            WatermarkDocumentsJob::dispatch($newDocument->id, $quote->uuid, $documentType->id)
                ->delay(now()->addSeconds(10))
                ->afterCommit();
        }
    }

    private function rewritePdfTitle(string $content, string $title, ?string $mimeType): string
    {
        if ($mimeType !== 'application/pdf') {
            return $content;
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'dic_pdf_');

        try {
            file_put_contents($tempPath, $content);

            $pdf = new Fpdi;
            $pdf->SetTitle($title);
            $pdf->SetAuthor('');
            $pdf->SetSubject('');
            $pdf->SetCreator('');

            $pageCount = $pdf->setSourceFile($tempPath);

            for ($i = 1; $i <= $pageCount; $i++) {
                $tplIdx = $pdf->importPage($i);
                $size = $pdf->getTemplateSize($tplIdx);
                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($tplIdx);
            }

            return $pdf->Output('S');
        } catch (\Throwable $e) {
            LoggerService::warning('DIC Travel: failed to rewrite PDF title, using original', [
                'title' => $title,
                'error' => $e->getMessage(),
            ]);

            return $content;
        } finally {
            @unlink($tempPath);
        }
    }

    private function mimeTypeFromResponseHeader(null|string|array $contentType): ?string
    {
        if (is_array($contentType)) {
            $contentType = $contentType[0] ?? null;
        }
        if (! is_string($contentType) || $contentType === '') {
            return 'application/pdf';
        }

        return trim(explode(';', $contentType, 2)[0]);
    }
}

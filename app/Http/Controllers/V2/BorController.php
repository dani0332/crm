<?php

namespace App\Http\Controllers\V2;

use App\Enums\BorStatusEnum;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteTypes;
use App\Http\Controllers\Controller;
use App\Http\Requests\Bor\BorFormRequest;
use App\Models\BorLog;
use App\Services\Bor\BorPdfService;
use App\Services\Bor\BorService;
use App\Services\Logger\LoggerService;
use App\Traits\GenericQueriesAllLobs;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class BorController extends Controller
{
    use GenericQueriesAllLobs;

    protected $borService;

    public function __construct(
        BorService $borService,
    ) {
        $this->borService = $borService;
        // Apply BOR document upload permission to upload method
        $this->middleware('permission:'.PermissionsEnum::BOR_DOCUMENT_UPLOAD, ['only' => ['uploadDocument']]);
    }

    /**
     * Get BOR logs for a specific lead
     */
    public function index(Request $request): JsonResponse
    {
        try {
            [$logs, $total] = $this->borService->getBorLogs($request->only('lob', 'leadId'));

            return response()->json([
                'success' => true,
                'data' => $logs,
                'total' => $total,
                'bor_status_enum' => BorStatusEnum::asArray(),
            ]);
        } catch (Exception $th) {
            LoggerService::info('Failed to fetch BOR logs', [
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch BOR logs',
            ], 500);
        }

    }

    /**
     * Create a new BOR request
     */
    public function store(BorFormRequest $request)
    {
        try {
            $payload = $request->only('personal_quote_id', 'lob', 'customer_type', 'company_name', 'insurer_name', 'insurance_provider_id', 'policy_number', 'policy_expiry', 'chassis_number', 'insurance_contact_id');
            $borLog = $this->borService->createBorLog($payload);

            // Return successful response
            return redirect()->back()->with([
                'success' => 'BOR request created successfully'.($borLog['emailSent'] ? ' and email sent to customer.' : ', but email failed to send.'),
                'newBorLog' => $borLog['borLog']->fresh(['insuranceProvider']),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            LoggerService::error('BOR request creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'line' => $e->getLine(),
                'request_data' => $request->except(['password']),
            ]);

            return redirect()->back()->withErrors([
                'general' => 'Failed to create BOR request. Please try again.',
            ])->withInput();
        }
    }

    /**
     * Update a BOR request
     */
    public function update(BorFormRequest $request, $id)
    {
        try {
            $payload = $request->only('personal_quote_id', 'lob', 'customer_type', 'company_name', 'insurer_name', 'insurance_provider_id', 'policy_number', 'policy_expiry', 'chassis_number', 'insurance_contact_id');
            $borLog = $this->borService->updateBorLog($payload, $id);

            return redirect()->back()->with([
                'success' => 'BOR request update successfully',
                'updatedBorLog' => $borLog['borLog']->fresh(['insuranceProvider']),
            ]);

        } catch (\Exception $e) {
            LoggerService::error('Failed to update BOR request', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'line' => $e->getLine(),
                'request' => $request->all(),
            ]);

            return redirect()->back()->withErrors([
                'general' => 'Failed to create BOR request. Please try again.',
            ])->withInput();
        }
    }

    public function downloadDocument(Request $request)
    {
        $file_content = Storage::disk('azureIMPrivate')->get($request->path);
        $file = explode('/', $request->path);
        $lastIndex = count($file);

        return response()
            ->streamDownload(
                function () use ($file_content) {
                    echo $file_content;
                },
                $file[$lastIndex - 1]
            );
    }

    public function getRepresentor(Request $request)
    {
        $insuranceProviderId = $request->insurance_provider_id;
        $quoteType = $request->quote_type;
        $quoteTypeId = QuoteTypes::getIdFromValue($quoteType);
        $representor = $this->borService->getRepresentor($insuranceProviderId, $quoteTypeId);

        return response()->json([
            'success' => true,
            'providerRepresentor' => $representor,
        ]);
    }

    /**
     * This function is used to generate a link for the BOR request
     *
     * @param  BorLog  $borLog
     * @return void
     */
    public function generateLink($borLogId)
    {
        $borLog = BorLog::findOrFail($borLogId);
        $borLog->load('personalQuote');
        $quote = $borLog->personalQuote;
        $quoteType = strtolower(QuoteTypes::getName($quote->quote_type_id)->value).'-insurance';
        $quoteUuid = $quote->uuid;
        $ecomUrl = config('constants.AFIA_WEBSITE_DOMAIN') ?? '';
        $requestLink = $ecomUrl.'/'.$quoteType.'/quote/'.$quoteUuid.'/bor/'.$borLog->bor_reference;

        return response()->json([
            'success' => true,
            'data' => $requestLink,
        ]);
    }

    /**
     * Upload BOR document
     */
    public function uploadDocument(Request $request, $id = null): JsonResponse
    {
        // Support both route parameter and request parameter for flexibility
        $borLogId = $id ?? $request->input('bor_log_id');

        $validated = $request->validate([
            'file' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240', // 10MB max
            'document_type_code' => 'nullable|string', // Will be auto-determined if not provided
            'bor_log_id' => $borLogId ? 'nullable' : 'required|exists:bor_logs,id',
        ]);

        $this->validateBorUploadFileContents($request->file('file'));

        try {
            $result = $this->borService->uploadBorDocument(
                $request->file('file'),
                $validated,
                $borLogId ?: $validated['bor_log_id']
            );

            return response()->json([
                'success' => true,
                'message' => 'BOR document uploaded successfully',
                'borLog' => $result['borLog'],
                'document' => $result['document'],
            ]);

        } catch (\Exception $e) {
            LoggerService::error('BOR document upload failed', [
                'bor_log_id' => $borLogId ?? $request->input('bor_log_id'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'line' => $e->getLine(),
                'request_data' => $request->except(['file', 'password']),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * View BOR PDF document (generates PDF on-the-fly like API - base64 preview only)
     */
    public function viewSignedPdf(Request $request, $borLogId)
    {
        try {
            $borLog = BorLog::findOrFail($borLogId);
            $borPdfService = new BorPdfService;

            // Generate BOR PDF using the same service as API
            $pdf = $borPdfService->generatePreviewBorPdf($borLog);

            if (! $pdf) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to generate BOR PDF',
                ], 500);
            }

            // Return base64 encoded PDF content for viewing only (same as API)
            return response()->json([
                'success' => true,
                'data' => 'data:application/pdf;base64,'.base64_encode($pdf['pdf']->output()),
                'name' => $pdf['name'],
                'message' => 'BOR PDF generated successfully for viewing',
            ]);

        } catch (\Exception $e) {
            LoggerService::error('Failed to generate BOR PDF for viewing', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'bor_log_id' => $borLogId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate PDF for viewing: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel a BOR request
     */
    public function cancelBor(Request $request, $id)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $result = $this->borService->cancelBorLog($validated, $id);

            return redirect()->back()->with([
                'success' => 'BOR request cancelled successfully.',
                'updatedBorLog' => $result['borLog'],
            ]);

        } catch (\Exception $e) {
            if ($e->getCode() !== 200) {
                LoggerService::error('BOR cancellation failed', [
                    'bor_id' => $id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'line' => $e->getLine(),
                    'request_data' => $request->except(['password']),
                ]);
            }

            return redirect()->back()->withErrors([
                'general' => $e->getMessage(),
            ])->withInput();
        }
    }

    /**
     * Mark a BOR as done (complete the upload flow)
     */
    public function markDone(Request $request, $id)
    {
        $validated = $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            $result = $this->borService->markBorLogAsDone($validated, $id);

            return redirect()->back()->with([
                'success' => 'BOR request marked as completed successfully.',
                'updatedBorLog' => $result['borLog'],
            ]);

        } catch (\Exception $e) {
            LoggerService::error('BOR completion failed', [
                'bor_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'line' => $e->getLine(),
                'request_data' => $request->except(['password']),
            ]);

            return redirect()->back()->withErrors([
                'general' => $e->getMessage(),
            ])->withInput();
        }
    }

    /**
     * Verify file structure beyond MIME sniffing: real image/PDF/Office headers, and block HTML/script
     * in PNG text chunks / JPEG COM comments and high-confidence payloads after IEND/EOI — not raw pixel data.
     *
     * @throws ValidationException
     */
    private function validateBorUploadFileContents(UploadedFile $file): void
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
            $contents = file_get_contents($path);
            if ($contents !== false && self::borRasterImageContainsDisallowedPayload($contents)) {
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
     * Raster safety: do not regex-scan compressed pixel/entropy data (false positives).
     * PNG — only textual chunks (tEXt, zTXt, iTXt) get full markup rules; JPEG — COM markers in the header (before SOS) only; both — high-confidence patterns on trailing bytes after IEND/EOI.
     */
    private static function borRasterImageContainsDisallowedPayload(string $contents): bool
    {
        if (str_starts_with($contents, "\x89PNG\r\n\x1a\n")) {
            if (self::borPngTextualChunksContainDisallowedMarkup($contents)) {
                return true;
            }

            return self::borBinaryContainsHighConfidenceInjection(self::borPngTrailingBytesAfterIend($contents));
        }

        if (str_starts_with($contents, "\xFF\xD8")) {
            if (self::borJpegComSegmentsInHeaderContainDisallowedMarkup($contents)) {
                return true;
            }

            $eoi = strrpos($contents, "\xFF\xD9");
            $trailer = $eoi !== false ? substr($contents, $eoi + 2) : '';

            return self::borBinaryContainsHighConfidenceInjection($trailer);
        }

        return self::borBinaryContainsHighConfidenceInjection($contents);
    }

    /**
     * Patterns unlikely to appear in random compressed image bits; used for post-IEND / post-EOI junk only.
     */
    private static function borBinaryContainsHighConfidenceInjection(string $blob): bool
    {
        if ($blob === '') {
            return false;
        }

        return (bool) preg_match(
            '/<\s*script\b|<\s*\/\s*script\s*>|<\?php\b|<\?=\s*|(?:^|[\s"\'`=:(])\s*javascript\s*:/i',
            $blob
        );
    }

    private static function borPngTrailingBytesAfterIend(string $contents): string
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

    private static function borPngTextualChunksContainDisallowedMarkup(string $contents): bool
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
            if ($type === 'tEXt' && self::borTextPayloadContainsDisallowedMarkup($chunkData)) {
                return true;
            }
            if ($type === 'zTXt') {
                $decoded = self::borPngDecodeZtxtPayload($chunkData);
                if ($decoded !== null && self::borTextPayloadContainsDisallowedMarkup($decoded)) {
                    return true;
                }
            }
            if ($type === 'iTXt') {
                $text = self::borPngExtractITxtText($chunkData);
                if ($text !== null && self::borTextPayloadContainsDisallowedMarkup($text)) {
                    return true;
                }
            }
            $offset += 8 + $chunkLen + 4;
        }

        return false;
    }

    private static function borPngDecodeZtxtPayload(string $chunkData): ?string
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

        return is_string($plain) ? $plain : null;
    }

    private static function borPngExtractITxtText(string $chunkData): ?string
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
    private static function borJpegComSegmentsInHeaderContainDisallowedMarkup(string $contents): bool
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
            if (self::borTextPayloadContainsDisallowedMarkup($payload)) {
                return true;
            }
            $offset = $pos + 2 + $segLen;
        }

        return false;
    }

    /**
     * Full rules for decoded text / metadata only (not raw IDAT / JPEG scan bytes).
     */
    private static function borTextPayloadContainsDisallowedMarkup(string $contents): bool
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

<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\Bor\BorFormRequest;
use App\Models\BorLog;
use App\Services\Bor\BorEmailService;
use App\Services\Bor\BorPdfService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Enums\BorStatusEnum;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteTypes;
use App\Http\Controllers\Controller;
use App\Services\Bor\BorService;
use App\Services\Logger\LoggerService;
use App\Traits\GenericQueriesAllLobs;
use Exception;

class BorController extends Controller
{
    use GenericQueriesAllLobs;
    
    protected $borService;

    public function __construct(
        BorService $borService,
    ) {
        $this->borService = $borService;
        // Apply BOR document upload permission to upload method
        $this->middleware('permission:' . PermissionsEnum::BOR_DOCUMENT_UPLOAD, ['only' => ['uploadDocument']]);
    }

    /**
     * Get BOR logs for a specific lead
     */
    public function index(Request $request): JsonResponse
    {
        try {
            [$logs, $total] = $this->borService->getBorLogs($request->all());

            return response()->json([
                'success' => true,
                'data' => $logs,
                'total' => $total,
                'bor_status_enum' => BorStatusEnum::asArray(),
            ]);
        } catch (Exception $th) {
            LoggerService::error('Failed to fetch BOR logs', [
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
            $payload = $request->only('lead_id', 'lob', 'customer_type', 'company_name', 'insurer_name', 'insurance_provider_id', 'policy_number', 'policy_expiry', 'chassis_number', 'insurance_contact_id');
            $borLog = $this->borService->createBorLog($payload);

            // Return successful response
            return redirect()->back()->with([
                'success' => 'BOR request created successfully' . ($borLog['emailSent'] ? ' and email sent to customer.' : ', but email failed to send.'),
                'newBorLog' => $borLog['borLog']->fresh(['insuranceProvider'])
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
                'general' => 'Failed to create BOR request. Please try again.'
            ])->withInput();
        }
    }

    /**
     * Update a BOR request
     */
    public function update(BorFormRequest $request, $id)
    {
        try {
            $payload = $request->only('lead_id', 'lob', 'customer_type', 'company_name', 'insurer_name', 'insurance_provider_id', 'policy_number', 'policy_expiry', 'chassis_number', 'insurance_contact_id');
            $borLog = $this->borService->updateBorLog($payload, $id);

            return redirect()->back()->with([
                'success' => 'BOR request update successfully',
                'updatedBorLog' => $borLog['borLog']->fresh(['insuranceProvider'])
            ]);

        } catch (\Exception $e) {
            LoggerService::error('Failed to update BOR request', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'line' => $e->getLine(),
                'request' => $request->all(),
            ]);

            return redirect()->back()->withErrors([
                'general' => 'Failed to create BOR request. Please try again.'
            ])->withInput();
        }
    }

    public function downloadDocument(Request $request)
    {
        $file_content = Storage::disk('azureIM')->get($request->path);
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
     * @param BorLog $borLog
     * @return void
     */
    public function generateLink($borLogId)
    {
        $borLog = BorLog::findOrFail($borLogId);
        $borLog->load('personalQuote');
        $quote = $borLog->personalQuote;
        $quoteType = strtolower(QuoteTypes::getName($quote->quote_type_id)->value) .'-insurance';
        $quoteUuid = $quote->uuid;
        $ecomUrl = config('constants.AFIA_WEBSITE_DOMAIN') ?? '';
        $requestLink = $ecomUrl .'/'. $quoteType .'/quote/'. $quoteUuid .'/bor/'. $borLog->bor_reference;
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
                'document' => $result['document']
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
                'message' => $e->getMessage()
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
            $borPdfService = new BorPdfService();
            
            // Generate BOR PDF using the same service as API
            $pdf = $borPdfService->generatePreviewBorPdf($borLog);
            
            if (!$pdf) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to generate BOR PDF',
                ], 500);
            }

            // Return base64 encoded PDF content for viewing only (same as API)
            return response()->json([
                'success' => true,
                'data' => 'data:application/pdf;base64,' . base64_encode($pdf['pdf']->output()),
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
                'message' => 'Failed to generate PDF for viewing: ' . $e->getMessage(),
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
                'updatedBorLog' => $result['borLog']
            ]);

        } catch (\Exception $e) {
            if($e->getCode() !== 200) {
                LoggerService::error('BOR cancellation failed', [
                    'bor_id' => $id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'line' => $e->getLine(),
                    'request_data' => $request->except(['password']),
                ]);
            }
            

            return redirect()->back()->withErrors([
                'general' => $e->getMessage()
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
                'updatedBorLog' => $result['borLog']
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
                'general' => $e->getMessage()
            ])->withInput();
        }
    }
}

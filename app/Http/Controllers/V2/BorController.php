<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\Bor\BorFormRequest;
use App\Models\BorLog;
use App\Models\DocumentType;
use App\Models\PersonalQuote;
use App\Services\Bor\BorEmailService;
use App\Services\Bor\BorPdfService;
use App\Services\QuoteDocumentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use App\Enums\BorStatusEnum;
use App\Enums\DocumentTypeCode;
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
    protected $borEmailService;
    protected $borPdfService;

    public function __construct(
        BorService $borService,
        BorEmailService $borEmailService,
        BorPdfService $borPdfService
    ) {
        $this->borService = $borService;
        $this->borEmailService = $borEmailService;
        $this->borPdfService = $borPdfService;
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
            $payload = $request->only('lead_id', 'lob', 'customer_type', 'company_name', 'insurer_name', 'insurance_provider_id', 'policy_number', 'policy_expiry', 'chassis_number');
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
    public function update(Request $request, $id)
    {
        try {
            $payload = $request->only('lead_id', 'lob', 'customer_type', 'company_name', 'insurer_name', 'insurance_provider_id', 'policy_number', 'policy_expiry', 'chassis_number');
            $borLog = $this->borService->updateBorLog($payload, $id);

            return redirect()->back()->with([
                'success' => 'BOR request update successfully' . ($borLog['emailSent'] ? ' and email sent to customer.' : ', but email failed to send.'),
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

    /**
     * Upload BOR document leveraging existing document infrastructure
     * Uses the polymorphic relationship with quote_documents table
     */
    public function uploadDocument(Request $request, $id = null): JsonResponse
    {
        try {
            // Support both route parameter and request parameter for flexibility
            $borLogId = $id ?? $request->input('bor_log_id');
            
            $validated = $request->validate([
                'file' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240', // 10MB max
                'document_type_code' => 'nullable|string', // Will be auto-determined if not provided
                'bor_log_id' => $borLogId ? 'nullable' : 'required|exists:bor_logs,id',
            ]);

            $borLog = BorLog::findOrFail($borLogId ?: $validated['bor_log_id']);
            $personalQuote = $borLog->personalQuote;
            $quoteType = QuoteTypes::getName($personalQuote->quote_type_id)->value;
            $quoteObject = $this->getQuoteObject($quoteType, $personalQuote->quote_id);
            
            // Auto-determine document type code based on the lead's LOB if not provided
            $documentTypeCode = $validated['document_type_code'] ?? $this->determineBorDocumentType($quoteType);
            
            // Get the document type for this LOB  
            $documentType = DocumentType::where('code', $documentTypeCode)->first();
            
            if (!$documentType) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid document type for BOR upload: ' . $documentTypeCode
                ], 400);
            }

            // Leverage existing document upload service
            $quoteDocumentService = app(QuoteDocumentService::class);
            
            // Prepare data for existing upload logic
            $uploadData = [
                'document_type_code' => $documentTypeCode,
                'quote_uuid' => $borLog->bor_reference, // Use BOR reference as identifier
                'bor_ref_id' => $borLog->bor_reference,
            ];

            // Upload using existing service, leveraging polymorphic relationship
            $uploadedDocument = $quoteDocumentService->uploadQuoteDocument(
                $request->file('file'),
                $uploadData,
                $quoteObject
            );

            if ($uploadedDocument) {
                // Update BOR log status
                $borLog->update([
                    'status' => 'Completed',
                    'date_uploaded' => now(),
                ]);

                // Send completion notifications
                $this->borEmailService->sendBorCompletionEmail($borLog);
                
                // Send insurer notification if insurer email is available
                if ($borLog->insurer_name) {
                    $this->borEmailService->sendBorInsurerNotification(
                        $borLog,
                        'insurer@example.com' // This should be configurable or retrieved from insurer data
                    );
                }

                return response()->json([
                    'success' => true,
                    'message' => 'BOR document uploaded successfully',
                    'data' => [
                        'bor_log' => $borLog->fresh(['personalQuote']),
                        'document' => $uploadedDocument
                    ]
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to upload document'
            ], 500);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->validator->errors()
            ], 422);
        } catch (\Exception $e) {
            dd($e);
            LoggerService::error('BOR document upload failed', [
                'bor_log_id' => $borLogId ?? $request->input('bor_log_id'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $request->file('file'),
                'line' => $e->getLine(),
                'request' => $request->except(['file']) // Exclude file data from logs
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Document upload failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Determine the appropriate BOR document type code based on lead LOB
     */
    private function determineBorDocumentType($quoteType): string
    {
        if (!$quoteType) {
            return 'BAL'; // Default to general BAL
        }

        // Map LOB to document type code
        $lobToDocumentType = [
            'car' => DocumentTypeCode::BAL,
            'bike' => DocumentTypeCode::BAL_BIKE, 
            'travel' => DocumentTypeCode::BAL_TRVL,
            'home' => DocumentTypeCode::BAL_HOME,
            'pet' => DocumentTypeCode::BAL_PET,
            'health' => DocumentTypeCode::BAL_HLTH,
            'life' => DocumentTypeCode::BAL_LIFE,
            'cycle' => DocumentTypeCode::BAL_CYCLE,
            'yacht' => DocumentTypeCode::BAL_YACHT,
        ];

        return $lobToDocumentType[strtolower($quoteType)] ?? 'BAL';
    }

    /**
     * Get BOR by document ID (for customer portal)
     */
    public function getByToken(Request $request, $token): JsonResponse
    {
        try {
            $borLog = BorLog::where('document_id', $token)->firstOrFail();
            
            // Load related lead data
            $lead = PersonalQuote::find($borLog->lead_id);

            return response()->json([
                'success' => true,
                'data' => [
                    'bor_log' => $borLog,
                    'lead' => $lead ? [
                        'id' => $lead->id,
                        'first_name' => $lead->first_name,
                        'last_name' => $lead->last_name,
                        'email' => $lead->email,
                        'phone' => $lead->phone,
                        'lob' => $lead->lob,
                    ] : null,
                ],
            ]);

        } catch (\Exception $e) {
            LoggerService::error('Failed to fetch BOR by document ID', [
                'document_id' => $token,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired BOR document ID',
            ], 404);
        }
    }


    /**
     * Generate preview PDF for BOR document (before signing)
     */
    public function generatePreviewPdf(Request $request, $token): JsonResponse
    {
        try {
            $borLog = BorLog::where('document_id', $token)->firstOrFail();

            // Generate preview PDF
            $pdfUrl = $this->borPdfService->generatePreviewBorPdf($borLog);

            if (!$pdfUrl) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to generate PDF preview',
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'PDF preview generated successfully',
                'data' => [
                    'pdf_url' => $pdfUrl,
                    'bor_reference' => $borLog->bor_reference,
                ],
            ]);

        } catch (\Exception $e) {
            LoggerService::error('BOR PDF preview generation failed', [
                'token' => $token,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'message' => 'Failed to generate PDF preview',
            ], 500);
        }
    }


    /**
     * API endpoint to update BOR status with validation and automatic transitions
     */
    public function updateStatus(Request $request, $id): JsonResponse
    {
        try {
            $borLog = BorLog::findOrFail($id);
            
            $validated = $request->validate([
                'status' => 'required|string|in:' . implode(',', BorStatusEnum::values()),
                'reason' => 'nullable|string|max:500',
            ]);

            $oldStatus = $borLog->status;
            $newStatus = $validated['status'];

            // Check if the status transition is allowed
            $allowedNextStatuses = $borLog->getNextStatuses();
            if (!empty($allowedNextStatuses) && !in_array($newStatus, $allowedNextStatuses)) {
                return response()->json([
                    'success' => false,
                    'message' => "Invalid status transition from '{$oldStatus}' to '{$newStatus}'",
                    'allowed_statuses' => $allowedNextStatuses,
                ], 422);
            }

            DB::beginTransaction();

            // Update status using the appropriate method
            $success = match ($newStatus) {
                BorStatusEnum::DOCUMENT_SIGNED => $borLog->markAsSigned(),
                BorStatusEnum::DOCUMENT_UPLOADED => $borLog->markAsUploaded(),
                BorStatusEnum::COMPLETED => $borLog->markAsCompleted(),
                BorStatusEnum::CANCELLED => $borLog->markAsCancelled($validated['reason'] ?? null),
                default => (function () use ($borLog, $newStatus) {
                    $borLog->status = $newStatus;
                    return $borLog->save();
                })(),
            };

            if (!$success) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update BOR status. Invalid transition.',
                ], 422);
            }

            // Send status update email for significant changes
            if ($oldStatus !== $newStatus && in_array($newStatus, [
                BorStatusEnum::DOCUMENT_SIGNED,
                BorStatusEnum::COMPLETED,
                BorStatusEnum::CANCELLED
            ])) {
                $this->borEmailService->sendBorStatusUpdateEmail(
                    $borLog,
                    $oldStatus,
                    $newStatus
                );
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'BOR status updated successfully',
                'data' => [
                    'id' => $borLog->id,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'status_info' => $borLog->getStatusInfo(),
                    'next_statuses' => $borLog->getNextStatuses(),
                    'actions' => $this->getAvailableActions($borLog),
                ],
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'BOR not found',
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            LoggerService::error('BOR status update failed', [
                'bor_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update BOR status. Please try again.',
            ], 500);
        }
    }

    /**
     * API endpoint to cancel a BOR with reason
     */
    public function cancelBor(Request $request, $id): JsonResponse
    {
        try {
            $borLog = BorLog::findOrFail($id);

            if (!$borLog->allowsCancellation()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This BOR cannot be cancelled in its current status',
                    'current_status' => $borLog->status,
                ], 422);
            }

            $validated = $request->validate([
                'reason' => 'required|string|max:500',
            ]);

            DB::beginTransaction();

            $oldStatus = $borLog->status;
            $success = $borLog->markAsCancelled($validated['reason']);

            if (!$success) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to cancel BOR',
                ], 500);
            }

            // Send cancellation notification
            $this->borEmailService->sendBorStatusUpdateEmail(
                $borLog,
                $oldStatus,
                BorStatusEnum::CANCELLED
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'BOR cancelled successfully',
                'data' => [
                    'id' => $borLog->id,
                    'status' => $borLog->status,
                    'cancellation_reason' => $borLog->cancellation_reason,
                    'status_info' => $borLog->getStatusInfo(),
                    'actions' => $this->getAvailableActions($borLog),
                ],
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'BOR not found',
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            LoggerService::error('BOR cancellation failed', [
                'bor_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel BOR. Please try again.',
            ], 500);
        }
    }

    /**
     * API endpoint to mark a BOR as done (complete the upload flow)
     */
    public function markDone(Request $request, $id): JsonResponse
    {
        try {
            $borLog = BorLog::findOrFail($id);

            if (!$borLog->allowsMarkingDone()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This BOR cannot be marked as done in its current status',
                    'current_status' => $borLog->status,
                    'required_status' => BorStatusEnum::DOCUMENT_UPLOADED,
                ], 422);
            }

            DB::beginTransaction();

            $oldStatus = $borLog->status;
            $success = $borLog->markAsCompleted();

            if (!$success) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to mark BOR as completed',
                ], 500);
            }

            // Send completion notifications
            $this->borEmailService->sendBorCompletionNotifications($borLog);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'BOR marked as completed successfully',
                'data' => [
                    'id' => $borLog->id,
                    'status' => $borLog->status,
                    'status_info' => $borLog->getStatusInfo(),
                    'actions' => $this->getAvailableActions($borLog),
                ],
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'BOR not found',
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            LoggerService::error('BOR completion failed', [
                'bor_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to mark BOR as done. Please try again.',
            ], 500);
        }
    }

    /**
     * Get available actions for a BOR based on its current status
     */
    private function getAvailableActions(BorLog $borLog): array
    {
        $actions = [];
        $status = $borLog->status;

        // Define actions based on status and permissions
        $editAndCopyLinkCondition = !in_array($status, [BorStatusEnum::COMPLETED, BorStatusEnum::CANCELLED]);
        $uploadAndDoneCondition = !in_array($status, [BorStatusEnum::DOCUMENT_SIGNED, BorStatusEnum::DOCUMENT_UPLOADED]);

        if ($editAndCopyLinkCondition) {
            $actions[] = 'edit';
            $actions[] = 'copy_link';
        }

        if ($uploadAndDoneCondition) {
            $actions[] = 'upload';
            $actions[] = 'done';
        }

        if (!in_array($status, [BorStatusEnum::CANCELLED, BorStatusEnum::COMPLETED])) {
            $actions[] = 'cancel';
        }

        if ($borLog->signed_pdf_path || $borLog->document_path) {
            $actions[] = 'view_document';
        }

        return $actions;
    }
}

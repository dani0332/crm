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
use App\Enums\PermissionsEnum;
use App\Http\Controllers\Controller;
use App\Services\Bor\BorService;
use App\Traits\GenericQueriesAllLobs;

class BorController extends Controller
{
    use GenericQueriesAllLobs;
    protected $borService;

    public function __construct(BorService $borService)
    {
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
            ]);
        } catch (\Throwable $th) {
            Log::error('Failed to fetch BOR logs', [
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
            Log::error('BOR request creation failed', [
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
            
            // Auto-determine document type code based on the lead's LOB if not provided
            $documentTypeCode = $validated['document_type_code'] ?? $this->determineBorDocumentType($borLog);
            
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
            ];

            // Upload using existing service, leveraging polymorphic relationship
            $uploadedDocument = $quoteDocumentService->uploadQuoteDocument(
                $request->file('file'),
                $uploadData,
                $borLog // Pass BorLog as the "quote" object for polymorphic relationship
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
                        'bor_log' => $borLog->fresh(['documents']),
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
            Log::error('BOR document upload failed', [
                'bor_log_id' => $borLogId ?? $request->input('bor_log_id'),
                'error' => $e->getMessage(),
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
    private function determineBorDocumentType(BorLog $borLog): string
    {
        $lead = $borLog->personalQuote;
        if (!$lead) {
            return 'BAL'; // Default to general BAL
        }

        // Map LOB to document type code
        $lobToDocumentType = [
            'car' => 'BAL',
            'bike' => 'BAL_Bike', 
            'travel' => 'BAL_TRVL',
            'home' => 'BAL_HOME',
            'pet' => 'BAL_PET',
            'commercial' => 'BAL_COMM',
            'health' => 'BAL_HLTH',
        ];

        return $lobToDocumentType[strtolower($lead->lob)] ?? 'BAL';
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
            Log::error('Failed to fetch BOR by document ID', [
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
     * Validate signature data before submission
     */
    public function validateSignature(Request $request, $token): JsonResponse
    {
        try {
            $validated = $request->validate([
                'signature_data' => 'required|string',
            ]);

            $borLog = BorLog::where('document_id', $token)->firstOrFail();

            // Verify BOR log is in the correct status for signing
            if (!in_array($borLog->status, ['pending', 'sent'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'This BOR document is no longer available for signing',
                ], 400);
            }

            // Validate signature data format
            $signatureData = $validated['signature_data'];
            if (!str_contains($signatureData, 'data:image/png;base64,') && !str_contains($signatureData, 'data:image/jpeg;base64,')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid signature format. Only PNG and JPEG signatures are accepted.',
                ], 422);
            }

            // Extract and validate base64 data
            $base64Data = preg_replace('#^data:image/[^;]+;base64,#', '', $signatureData);
            $decodedData = base64_decode($base64Data, true);
            
            if ($decodedData === false || strlen($decodedData) < 100) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or corrupted signature data',
                ], 422);
            }

            // Check image dimensions (optional validation)
            $imageInfo = getimagesizefromstring($decodedData);
            if (!$imageInfo || $imageInfo[0] < 50 || $imageInfo[1] < 20) {
                return response()->json([
                    'success' => false,
                    'message' => 'Signature is too small or invalid',
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => 'Signature is valid',
                'data' => [
                    'width' => $imageInfo[0],
                    'height' => $imageInfo[1],
                    'mime_type' => $imageInfo['mime'],
                    'size_bytes' => strlen($decodedData),
                ],
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('BOR signature validation failed', [
                'token' => $token,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Signature validation failed',
            ], 500);
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
            Log::error('BOR PDF preview generation failed', [
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
     * Submit customer signature (for customer portal)
     */
    public function submitSignature(Request $request, $token): JsonResponse
    {
        try {
            $validated = $request->validate([
                'signature_data' => 'required|string',
                'customer_name' => 'required|string|max:255',
                'ip_address' => 'nullable|ip',
                'user_agent' => 'nullable|string|max:1000',
            ]);

            $borLog = BorLog::where('document_id', $token)->firstOrFail();

            // Verify BOR log is in the correct status for signing
            if (!in_array($borLog->status, ['pending', 'sent'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'This BOR document is no longer available for signing',
                ], 400);
            }

            DB::beginTransaction();

            // Store signature data as a file
            $signatureFilename = 'signature_' . $borLog->id . '_' . time() . '.png';
            $signaturePath = 'bor-signatures/' . $signatureFilename;
            
            // Decode base64 signature and save
            $signatureData = str_replace('data:image/png;base64,', '', $validated['signature_data']);
            $signatureDecoded = base64_decode($signatureData);
            
            if ($signatureDecoded === false) {
                throw new \Exception('Invalid signature data format');
            }
            
            Storage::disk('public')->put($signaturePath, $signatureDecoded);

            // Update BOR log with signature information
            $borLog->update([
                'status' => 'completed',
                'signature_path' => Storage::url($signaturePath),
                'date_signed' => now(), // Use current timestamp
                'customer_signature_name' => $validated['customer_name'],
                'user_agent' => $validated['user_agent'] ?? $request->header('User-Agent'),
            ]);

            // Generate signed PDF document
            $pdfUrl = $this->borPdfService->generateSignedBorPdf($borLog);
            
            if (!$pdfUrl) {
                Log::warning('BOR PDF generation failed but signature was submitted', [
                    'bor_log_id' => $borLog->id,
                    'token' => $token,
                ]);
            }

            // Send completion notification emails
            $this->borEmailService->sendBorCompletionEmail($borLog);
            
            // Send insurer notification if needed
            if ($borLog->insurer_name) {
                // TODO: Get actual insurer email from insurance provider configuration
                $insurerEmail = 'insurer@example.com'; // This should be configurable
                $this->borEmailService->sendBorInsurerNotification($borLog, $insurerEmail);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Signature submitted successfully',
                'data' => [
                    'bor_log_id' => $borLog->id,
                    'status' => $borLog->status,
                    'date_signed' => $borLog->date_signed,
                    'customer_name' => $borLog->customer_signature_name,
                ],
            ]);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('BOR signature submission failed', [
                'token' => $token,
                'error' => $e->getMessage(),
                'request_data' => $request->except(['signature_data']), // Exclude large signature data from logs
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit signature: ' . $e->getMessage(),
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
            Log::error('BOR status update failed', [
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
            Log::error('BOR cancellation failed', [
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
            Log::error('BOR completion failed', [
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
     * API endpoint to view/download BOR document
     */
    public function viewDocument(Request $request, $id): JsonResponse
    {
        try {
            $borLog = BorLog::findOrFail($id);

            if (!$borLog->allowsViewDocument()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document cannot be viewed in the current status',
                    'current_status' => $borLog->status,
                ], 422);
            }

            $documentData = [
                'bor_id' => $borLog->id,
                'bor_reference' => $borLog->bor_reference,
                'status' => $borLog->status,
            ];

            // Check for signed PDF
            if ($borLog->signed_pdf_path && Storage::exists($borLog->signed_pdf_path)) {
                $documentData['signed_pdf'] = [
                    'url' => Storage::url($borLog->signed_pdf_path),
                    'path' => $borLog->signed_pdf_path,
                    'type' => 'signed_pdf',
                ];
            }

            // Check for uploaded documents
            $uploadedDocuments = $borLog->documents()->with('documentType')->get();
            if ($uploadedDocuments->isNotEmpty()) {
                $documentData['uploaded_documents'] = $uploadedDocuments->map(function ($doc) {
                    return [
                        'id' => $doc->id,
                        'name' => $doc->document_name,
                        'type' => $doc->documentType->name ?? 'Unknown',
                        'url' => $doc->document_url,
                        'uploaded_at' => $doc->created_at,
                    ];
                });
            }

            // Check if there are any documents to view
            if (!isset($documentData['signed_pdf']) && empty($documentData['uploaded_documents'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'No documents available to view',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Document(s) retrieved successfully',
                'data' => $documentData,
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'BOR not found',
            ], 404);
        } catch (\Exception $e) {
            Log::error('BOR document view failed', [
                'bor_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve document. Please try again.',
            ], 500);
        }
    }

    /**
     * Get available actions for a BOR based on its current status
     */
    private function getAvailableActions(BorLog $borLog): array
    {
        $actions = [];

        if ($borLog->allowsEditing()) {
            $actions[] = [
                'key' => 'edit',
                'label' => 'Edit',
                'icon' => 'edit',
                'color' => 'primary',
            ];
        }

        if ($borLog->allowsCopyLink()) {
            $actions[] = [
                'key' => 'copy_link',
                'label' => 'Copy Link',
                'icon' => 'link',
                'color' => 'info',
            ];
        }

        if ($borLog->allowsUpload()) {
            $actions[] = [
                'key' => 'upload',
                'label' => 'Upload',
                'icon' => 'upload',
                'color' => 'success',
            ];
        }

        if ($borLog->allowsViewDocument()) {
            $actions[] = [
                'key' => 'view',
                'label' => 'View Document',
                'icon' => 'eye',
                'color' => 'info',
            ];
        }

        if ($borLog->allowsMarkingDone()) {
            $actions[] = [
                'key' => 'done',
                'label' => 'Mark Done',
                'icon' => 'check',
                'color' => 'success',
            ];
        }

        if ($borLog->allowsCancellation()) {
            $actions[] = [
                'key' => 'cancel',
                'label' => 'Cancel',
                'icon' => 'x',
                'color' => 'error',
            ];
        }

        return $actions;
    }
}

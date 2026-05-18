<?php

namespace App\Http\Controllers\V2;

use App\Enums\CourierSyncStatusEnum;
use App\Enums\EmbeddedProductEnum;
use App\Enums\EmbeddedTransactionEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\PermissionsEnum;
use App\Enums\SageEmbeddedProductEnum;
use App\Exports\EmbeddedProductReport;
use App\Http\Controllers\Controller;
use App\Http\Requests\AlfredProtectDocumentSyncRequest;
use App\Http\Requests\EmbeddedProducDocumentRequest;
use App\Http\Requests\EmbeddedProductRequest;
use App\Http\Requests\UpdateEpDocumentRequest;
use App\Jobs\MACRM\SyncCourierQuoteWithMacrm;
use App\Models\EmbeddedProduct;
use App\Models\EmbeddedTransaction;
use App\Repositories\EmbeddedProductRepository;
use App\Services\EmbeddedTransactionService;
use App\Services\QuoteDocumentService;
use App\Services\SageApiEmbeddedProductService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;
use Inertia\ResponseFactory;

class EmbeddedProductController extends Controller
{
    public function __construct(
        private EmbeddedTransactionService $embeddedTransactionService,
    ) {
        $this->middleware('permission:'.PermissionsEnum::EMBEDDED_PRODUCT_CONFIG, ['except' => ['sendDocument', 'cancelPayment', 'voidPayment', 'getDocuments', 'uploadQuoteDocument', 'force', 'getByQuote', 'updateEpDocument']]);
        $this->middleware('permission:'.PermissionsEnum::EMBEDDED_PRODUCT_PAYMENT_CANCEL, ['only' => ['cancelPayment']]);
        $this->middleware('permission:'.PermissionsEnum::PAYMENTS_VOID, ['only' => ['voidPayment']]);
        $this->middleware('permission:'.PermissionsEnum::EMBEDDED_PRODUCT_VIEW, ['only' => ['sendDocument', 'getDocuments', 'uploadQuoteDocument', 'force', 'getByQuote']]);
        $this->middleware('permission:'.PermissionsEnum::EP_DOCUMENT_MANUAL_OVERRIDE, ['only' => ['updateEpDocument']]);
    }

    /**
     * @return Response|ResponseFactory
     */
    public function index()
    {
        $data = EmbeddedProductRepository::getData();

        return inertia('EmbeddedProducts/Index', [
            'embeddedProducts' => $data,
        ]);
    }

    /**
     * @return Response|ResponseFactory
     */
    public function create()
    {
        $data = EmbeddedProductRepository::getFormOptions();

        return inertia('EmbeddedProducts/Form', $data);
    }

    /**
     * @param  $quoteTypeCode
     * @return RedirectResponse
     */
    public function store(EmbeddedProductRequest $request)
    {
        EmbeddedProductRepository::create($request->validated());

        return redirect()->route('embedded-products.index')->with('message', 'Embedded Product created successfully');
    }

    /**
     * @return Response|ResponseFactory
     */
    public function edit($id)
    {
        $embeddedProduct = EmbeddedProductRepository::getBy('id', $id);
        $data = EmbeddedProductRepository::getFormOptions();

        return inertia('EmbeddedProducts/Form', array_merge($data, [
            'embeddedProduct' => $embeddedProduct,
        ]));
    }

    /**
     * @return Response|ResponseFactory
     */
    public function show($id)
    {
        $data = EmbeddedProductRepository::getBy('id', $id);

        return inertia('EmbeddedProducts/Show', [
            'embeddedProduct' => $data,

        ]);
    }

    public function update($id, EmbeddedProductRequest $request)
    {
        EmbeddedProductRepository::update($id, $request->validated());

        return redirect()->route('embedded-products.index')->with('message', 'Embedded Product updated successfully');
    }

    public function destroy($id)
    {
        $product = EmbeddedProductRepository::findOrFail($id);
        $product->delete();

        return back()->with('message', 'Embedded Product has been deleted');
    }

    public function uploadDocument()
    {
        $file = request()->file('file');
        $title = request()->input('title');

        $data = EmbeddedProductRepository::uploadDocument($file, $title);

        return response()->json($data);
    }

    public function toggleStatus($id)
    {
        EmbeddedProductRepository::where('id', $id)->update([
            'is_active' => request()->input('is_active'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Embedded Product status updated',
        ]);
    }

    public function sendDocument(EmbeddedProducDocumentRequest $request)
    {
        $data = $request->validated();
        $quoteId = $data['quoteId'];
        $modelType = $data['modelType'];
        $epId = $data['epId'];
        $result = EmbeddedProductRepository::SendDocumentsByLead($quoteId, $modelType, $epId);

        if ($result['success']) {
            return redirect()->back()->with('success', $result['message'] ?? 'Certificate send successfully');
        } else {
            return redirect()->back()->with('error', $result['message'] ?? 'Certificate send failed');
        }
    }

    public function syncDocument(AlfredProtectDocumentSyncRequest $request)
    {
        $result = EmbeddedProductRepository::syncDocument($request->validated());

        if ($result['success']) {
            return redirect()->back()->with('success', $result['message'] ?? 'Re-gerating request processing');
        } else {
            return redirect()->back()->with('error', $result['message'] ?? 'Re-gerating request failed');
        }
    }

    /**
     * Get the list of reports for embedded products.
     *
     * @return Response
     */
    public function reportsList()
    {
        $data = EmbeddedProductRepository::getData('all');

        return inertia('EmbeddedProducts/ReportsList', [
            'embeddedProducts' => $data,
        ]);
    }

    /**
     * Report transactions for an embedded product.
     *
     * @return Response
     */
    public function reportTransactions(EmbeddedProduct $ep, Request $request)
    {
        $filters = $request->all();
        $dataset = EmbeddedProductRepository::getSoldTransactionList($ep, $filters);

        return inertia('EmbeddedProducts/Transactions', [
            'embeddedProduct' => [
                'detail' => $ep,
                'transactions' => $dataset,
            ],
            'reportLobFilterOptions' => EmbeddedProductRepository::quoteTypeReportLobFilterOptions($ep->short_code),
            'ep_enums' => EmbeddedProductEnum::asArray(),
            'sync_statuses' => CourierSyncStatusEnum::withLabels(),
            'sage_statuses' => SageEmbeddedProductEnum::withLabels(),
            // BenSampo enums expose withLabels() helpers (built from asArray())
            'payment_statuses' => PaymentStatusEnum::withLabels(),
            'policy_statuses' => EmbeddedTransactionEnum::withLabels(),
        ]);
    }

    /**
     * Export a report for the given EmbeddedProduct and request filters.
     */
    public function reportExport(EmbeddedProduct $ep, Request $request)
    {
        $filters = $request->all();

        return (new EmbeddedProductReport($ep, $filters))->download("Export-{$ep->short_code}-Report");
    }

    public function cancelPayment(Request $request)
    {
        $response = EmbeddedProductRepository::cancelPayment($request->all());

        return response($response['data'], $response['code']);
    }

    public function voidPayment(Request $request)
    {
        $response = EmbeddedProductRepository::voidPayment($request->all());

        return response($response['data'], $response['code']);
    }

    public function force(Request $request)
    {
        // Determine storage disk based on is_policy_wordings
        $storageDisk = $request->boolean('is_policy_wordings') ? 'azureIM' : 'azureIMPrivate';

        // Generate a temporary URL for the file
        $documentUrl = app(QuoteDocumentService::class)->getDocumentUrl($request->path, $storageDisk);

        // Check if the file exists
        if ($documentUrl === null) {
            return response()->json([
                'error' => 'File not found on storage disk',
            ], 404);
        }

        // Get the file content using the temporary URL
        $file_content = file_get_contents($documentUrl);

        // Check if file_get_contents failed
        if ($file_content === false) {
            return response()->json([
                'error' => 'Failed to retrieve file content',
            ], 500);
        }
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

    public function getDocuments(EmbeddedProducDocumentRequest $request)
    {
        $documents = EmbeddedProductRepository::getDocuments($request->validated());

        return response()->json($documents);
    }

    public function uploadQuoteDocument(Request $request)
    {
        try {
            EmbeddedProductRepository::uploadQuoteDocument($request->all());
        } catch (Exception $e) {
            info('Documents upload failed - '.json_encode($request->all()).' - '.$e->getMessage());

            return redirect()->back()->with('error', 'Document uploaded failed!');
        }

        return redirect()->back()->with('success', 'Document uploaded successfully!');
    }

    public function getByQuote(Request $request)
    {
        $embeddedProducts = EmbeddedProductRepository::byQuoteType($request->quote_type_id, $request->quote_id);

        return response()->json($embeddedProducts);
    }

    public function reSyncCourier(string $code)
    {
        $et = EmbeddedTransaction::whereCode($code)->firstOrFail();

        if (! $et->isSyncable()) {
            return response()->json([
                'ok' => false,
                'message' => 'Request is not syncable. Please check the status and try again.',
            ]);
        }

        $et->courier_sync_started_at = now();
        $et->save();

        SyncCourierQuoteWithMacrm::dispatch($et->quoteRequest, $et->quote_type_id);

        return response()->json([
            'ok' => true,
            'message' => 'Re-syncing Request Submitted Successfully. Please Wait for the process to complete.',
        ]);
    }

    public function scheduleEPSageBooking(Request $request)
    {
        try {
            $scheduledResponse = (new SageApiEmbeddedProductService)->scheduleBookingOfEmbeddedProduct($request->all());

            return response()->json([
                'success' => true,
                'message' => $scheduledResponse['message'],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }

    }

    public function updateEpDocument(UpdateEpDocumentRequest $request)
    {
        try {
            $result = $this->embeddedTransactionService->updateEpDocument($request->validated());

            if (! $result) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update document.',
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => 'Document updated successfully.',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}

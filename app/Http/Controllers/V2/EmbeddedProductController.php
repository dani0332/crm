<?php

namespace App\Http\Controllers\V2;

use App\Enums\CourierSyncStatusEnum;
use App\Enums\EmbeddedProductEnum;
use App\Enums\PermissionsEnum;
use App\Exports\EmbeddedProductReport;
use App\Http\Controllers\Controller;
use App\Http\Requests\AlfredProtectDocumentSyncRequest;
use App\Http\Requests\EmbeddedProducDocumentRequest;
use App\Http\Requests\EmbeddedProductRequest;
use App\Jobs\MACRM\SyncCourierQuoteWithMacrm;
use App\Models\EmbeddedProduct;
use App\Models\EmbeddedTransaction;
use App\Repositories\EmbeddedProductRepository;
use App\Services\SageApiEmbeddedProductService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EmbeddedProductController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:'.PermissionsEnum::EMBEDDED_PRODUCT_CONFIG, ['except' => ['sendDocument', 'cancelPayment', 'voidPayment', 'getDocuments', 'uploadQuoteDocument', 'force', 'getByQuote']]);
        $this->middleware('permission:'.PermissionsEnum::EMBEDDED_PRODUCT_PAYMENT_CANCEL, ['only' => ['cancelPayment']]);
        $this->middleware('permission:'.PermissionsEnum::PAYMENTS_VOID, ['only' => ['voidPayment']]);
        $this->middleware('permission:'.PermissionsEnum::EMBEDDED_PRODUCT_VIEW, ['only' => ['sendDocument', 'getDocuments', 'uploadQuoteDocument', 'force', 'getByQuote']]);
    }

    /**
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function index()
    {
        $data = EmbeddedProductRepository::getData();

        return inertia('EmbeddedProducts/Index', [
            'embeddedProducts' => $data,
        ]);
    }

    /**
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function create()
    {
        $data = EmbeddedProductRepository::getFormOptions();

        return inertia('EmbeddedProducts/Form', $data);
    }

    /**
     * @param  $quoteTypeCode
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(EmbeddedProductRequest $request)
    {
        EmbeddedProductRepository::create($request->validated());

        return redirect()->route('embedded-products.index')->with('message', 'Embedded Product created successfully');
    }

    /**
     * @return \Inertia\Response|\Inertia\ResponseFactory
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
     * @return \Inertia\Response|\Inertia\ResponseFactory
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
     * @return \Inertia\Response
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
     * @return \Inertia\Response
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
            'ep_enums' => EmbeddedProductEnum::asArray(),
            'sync_statuses' => CourierSyncStatusEnum::withLabels(),
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
}

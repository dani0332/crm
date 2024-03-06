<?php

namespace App\Http\Controllers\V2;

use App\Enums\GenericRequestEnum;
use App\Exports\MDXReport;
use App\Http\Controllers\Controller;
use App\Http\Requests\EmbeddedProducDocumentRequest;
use App\Http\Requests\EmbeddedProductRequest;
use App\Models\EmbeddedProduct;
use App\Repositories\EmbeddedProductRepository;
use Illuminate\Http\Request;

class EmbeddedProductController extends Controller
{
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
     * @param    $quoteTypeCode
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
        EmbeddedProductRepository::sendDocument($request->validated());

        return redirect()->back()->with('success', 'Certificate send Successfully');
    }

    public function downloadDocument(EmbeddedProducDocumentRequest $request)
    {
        return EmbeddedProductRepository::downloadCertificate($request->validated());
    }

    /**
     * Get the list of reports for embedded products.
     *
     * @return \Inertia\Response
     */
    public function reportsList()
    {
        $config = config('embedded-products.reports');
        $data = EmbeddedProductRepository::getData('all', array_keys($config));

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
        $config = config('embedded-products.reports');
        $viewFile = $config[strtoupper($ep->short_code)]['view_file'] ?? $ep->short_code;

        return inertia("EmbeddedProducts/Reports/{$viewFile}", [
            'embeddedProduct' => [
                'detail' => $ep,
                'transactions' => $dataset,
            ],
        ]);
    }

    /**
     * Export a report for the given EmbeddedProduct and request filters.
     */
    public function reportExport(EmbeddedProduct $ep, Request $request)
    {
        $filters = $request->all();
        return (new MDXReport($ep, $filters))->download('Export-Medex-Report');
    }
}

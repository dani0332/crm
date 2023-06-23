<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmbeddedProductRequest;
use App\Repositories\EmbeddedProductRepository;

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
     * @param $quoteTypeCode
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

        return back()->with('message', 'Embedded Product updated successfully');
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
}

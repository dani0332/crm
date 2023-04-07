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
        return inertia('EmbeddedProducts/Index');
    }
       /**
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function create()
    {

        return inertia('EmbeddedProducts/Form');
    }
    public function store(EmbeddedProductRequest $request)
    {
        dd('m here');
        EmbeddedProductRepository::create($request->validated());

        return back()->with('message', 'Embedded Product created successfully');
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
}

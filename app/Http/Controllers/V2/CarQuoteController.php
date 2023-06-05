<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Http\Requests\CarQuoteRequest;
use App\Http\Requests\ChangeInsurerRequest;
use App\Repositories\CarQuoteRepository;

class CarQuoteController extends Controller
{
    /**
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function index(CarQuoteRequest $request)
    {
        $personalQuotes = [];

        if ($request->ajax) {
            $personalQuotes = CarQuoteRepository::getData();
        }

        return inertia('CarQuote/Index', [
            'quotes' => $personalQuotes,
        ]);
    }

    /**
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function create()
    {
        $data = CarQuoteRepository::getFormOptions();

        return inertia('CarQuote/Form', $data);
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(CarQuoteRequest $request)
    {
        $response = CarQuoteRepository::create($request->validated());

        if (! empty($response->errors) || ! empty($response->msg)) {
            vAbort($response->msg);
        }

        return back()->with('message', 'Quote created successfully');
    }

    /**
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function edit($uuid)
    {
        $data = CarQuoteRepository::getFormOptions();

        $quote = CarQuoteRepository::getBy('uuid', $uuid);

        return inertia('CarQuote/Form', array_merge($data, [
            'quote' => $quote,
        ])
        );
    }

    /**
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function show($uuid)
    {
        return inertia('CarQuote/Show', []);
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update($uuid, CarQuoteRequest $request)
    {
        CarQuoteRepository::update($uuid, $request->validated());

        return back();
    }

    /**
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function changeInsurer(ChangeInsurerRequest $request)
    {
        $response = CarQuoteRepository::changeInsurer($request->validated());

        return response()->json($response);
    }

    public function search(CarQuoteRequest $request)
    {
        $personalQuotes = [];

        if ($request->ajax) {
            $personalQuotes = CarQuoteRepository::getData();
        }

        return inertia('CarQuote/Index', [
            'quotes' => $personalQuotes,
        ]);
    }
}

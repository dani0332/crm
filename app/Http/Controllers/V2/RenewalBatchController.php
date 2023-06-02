<?php

namespace App\Http\Controllers\V2;

use App\Enums\QuoteStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\RenewalBatchRequest;
use App\Repositories\QuoteStatusRepository;
use App\Repositories\RenewalBatchRepository;

class RenewalBatchController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $quoteStatuses = QuoteStatusRepository::getQuoteStatusesByIds([QuoteStatusEnum::CarSold, QuoteStatusEnum::Uncontactable]);
        $renewalBatches = RenewalBatchRepository::getData();

        return inertia('Renewals/Index', [
            'renewalBatches' => $renewalBatches ?? [],
            'leadStatuses' => $quoteStatuses
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $quoteStatuses = QuoteStatusRepository::getQuoteStatusesByIds([QuoteStatusEnum::CarSold, QuoteStatusEnum::Uncontactable]);

        return inertia('Renewals/Form', [
            'leadStatuses' => $quoteStatuses
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(RenewalBatchRequest $request)
    {
        $response = RenewalBatchRepository::create($request->validated());

        if (! empty($response->errors) || ! empty($response->msg)) {
            vAbort($response->msg);
        }

        return back()->with('message', 'Renewal Batch is created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $renewalBatch = RenewalBatchRepository::getBy('id', $id);
        $quoteStatuses = QuoteStatusRepository::getQuoteStatusesByIds([QuoteStatusEnum::CarSold, QuoteStatusEnum::Uncontactable]);

        return inertia('Renewals/Form', [
            'leadStatuses' => $quoteStatuses,
            'renewalBatch' => $renewalBatch
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(RenewalBatchRequest $request, $id)
    {
        RenewalBatchRepository::update($id, $request->validated());

        return back()->with('message', 'Renewal Batch is updated successfully.');
    }

}

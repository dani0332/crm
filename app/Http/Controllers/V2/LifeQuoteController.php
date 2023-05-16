<?php

namespace App\Http\Controllers\V2;

use App\Enums\quoteStatusCode;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Http\Controllers\Controller;
use App\Http\Requests\LifeQuoteRequest;
use App\Repositories\ActivityRepository;
use App\Repositories\CustomerRepository;
use App\Repositories\LifeQuoteRepository;
use App\Repositories\LostReasonRepository;
use App\Repositories\QuoteStatusRepository;
use App\Repositories\UserRepository;
use App\Services\CentralService;
use Illuminate\Http\Request;

class LifeQuoteController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $lifeQuotes = LifeQuoteRepository::getData();
        $advisors = UserRepository::getPersonalQuoteAdvisors(QuoteTypes::LIFE->value);
        $quoteStatuses = QuoteStatusRepository::byQuoteTypeId(QuoteTypes::LIFE->id())->get();

        return inertia('LifeQuote/Index', [
            'quotes' => $lifeQuotes,
            'quoteStatuses' => $quoteStatuses,
            'advisors' => $advisors,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $data = LifeQuoteRepository::getFormOptions();

        return inertia('LifeQuote/Form', $data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(LifeQuoteRequest $request)
    {
        $response = LifeQuoteRepository::create($request->validated());

        if (! empty($response->errors) || ! empty($response->msg)) {
            vAbort($response->msg);
        }

        return back()->with('message', 'Quote is created successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($uuid)
    {
        $quote = LifeQuoteRepository::getBy('uuid', $uuid);

        $duplicateAllowedLobs = (new CentralService())->duplicateAllowedLobsList(QuoteTypes::LIFE->value, $quote->code);

        $advisors = UserRepository::getPersonalQuoteAdvisors(QuoteTypes::LIFE->value);

        $quoteStatuses = QuoteStatusRepository::byQuoteTypeId(QuoteTypes::LIFE->id())->get();
        $lostReasons = LostReasonRepository::orderBy('text', 'asc')->get();

        $activities = ActivityRepository::where([
            'quote_type_id' => QuoteTypes::LIFE->id(),
            'quote_request_id' => $quote->id,
        ])->with('assignee')->orderBy('created_at', 'desc')->get();

        return inertia('LifeQuote/Show', [
            'quoteType' => QuoteTypes::LIFE,
            'quoteStatuses' => $quoteStatuses,
            'quote' => $quote,
            'activities' => $activities,
            'advisors' => $advisors,
            'allowedDuplicateLOB' => $duplicateAllowedLobs,
            'customerAdditionalContacts' => CustomerRepository::GetAdditionalContacts($quote->customer_id, $quote),
            'lostReasons' => $lostReasons,
            'quoteStatusEnum' => QuoteStatusEnum::asArray(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($uuid)
    {
        $data = LifeQuoteRepository::getFormOptions();
        $quote = LifeQuoteRepository::getBy('uuid', $uuid);

        return inertia('LifeQuote/Form', array_merge($data, [
            'quote' => $quote,
        ]));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(LifeQuoteRequest $request, $uuid)
    {
        LifeQuoteRepository::update($uuid, $request->validated());

        return back()->with('message', 'Quote is updated successfully.');
    }

    public function cardsView(Request $request)
    {
        $leadStatuses = QuoteStatusRepository::byQuoteTypeId(QuoteTypes::LIFE->id())
            ->whereIn('text', [quoteStatusCode::NEWLEAD, quoteStatusCode::QUOTED, quoteStatusCode::FOLLOWEDUP, quoteStatusCode::NEGOTIATION])
            ->get()->toArray();

        $leadStatuses = array_map(function ($item) {
            $item['data'] = getDataAgainstStatus(QuoteTypes::LIFE->value, $item['id']);

            return $item;
        }, $leadStatuses);

        return inertia('LifeQuote/Cards', [
            'quotes' => array_values($leadStatuses),
        ]);
    }
}

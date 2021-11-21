<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\User;
use App\Services\CRUDService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use DataTables;

class MyLeadsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $roles = Auth::user()->roles;
        $filtered_collection = $roles->filter(function ($item)           {
            return str_contains($item->name, 'ADVISOR');
        })->values();
        $leadTypes = [];
        foreach ($filtered_collection as $item) {
            array_push($leadTypes, explode('_', $item->name)[0]);
        }
        if($request->ajax())
        {
            $leadType = strtolower($request->leadType);
            $resultSet = $this->getMyLeadsByType($leadType);
            return DataTables::of($resultSet->sortBy('assignedBy')->toArray())
                    ->addIndexColumn()
                    ->make(true);
        }
        return view('myleads.view', compact('leadTypes'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id, CRUDService $crudService)
    {
        $queryString = explode('&', $id);
        $recordId = $queryString[0];
        $leadType = strtolower($queryString[1]);
        $record = $crudService->getEntity($leadType, $recordId);
        $resultSet = $this->getMyLeadsByType(strtolower($leadType))->where('uuid', $recordId);
        return view('myleads.show', compact('record', 'resultSet', 'leadType'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    private function getMyLeadsByType($leadType)
    {
        $quotes = Customer::with([
            $leadType.'Quotes' => function($q) {
                $q->where('advisor_id', '=', Auth::user()->id);
            },
            $leadType.'Quotes.quoteStatus',
            $leadType.'Quotes.'.$leadType.'QuoteRequestDetail',
            $leadType.'Quotes.'.$leadType.'QuoteRequestDetail.assignedBy']);
        $resultSet = collect([]);
        foreach ($quotes->{$leadType.'Quotes'} as $item) {
            $dataObject = [
                'id' => $item->id,
                'uuid' => $item->uuid,
                'clientName' => $item->first_name. ' ' .$item->last_name,
                'leadStatus' => $item->quoteStatus != null ? $item->quoteStatus->first()->text : '',
                'createdAt' => $item->created_at,
                'assignedDate' => $item->{$leadType.'QuoteRequestDetail'}->assignedBy != null ? $item->{$leadType.'QuoteRequestDetail'}->advisor_assigned_date : '',
                'assignedBy' => $item->{$leadType.'QuoteRequestDetail'}->assignedBy != null ? $item->{$leadType.'QuoteRequestDetail'}->assignedBy->name : '',
                'leadSource' => $item->source,
            ];
            $resultSet->push($dataObject);
        }
        return $resultSet;
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\QuoteStatus;
use App\Services\CRUDService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use DataTables;

class MyLeadsController extends Controller
{
    protected $crudService;
    public function __construct(CRUDService $crudService)
    {
        $this->crudService = $crudService;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $roles = Auth::user()->roles;
        $filtered_collection = $roles->filter(function ($item) {
            return str_contains($item->name, 'ADVISOR');
        })->values();
        $leadTypes = [];
        foreach ($filtered_collection as $item) {
            array_push($leadTypes, explode('_', $item->name)[0]);
        }
        $leadStatusList = QuoteStatus::select('id', 'text')->get();
        if ($request->ajax()) {
            $leadType = strtolower($request->leadType);
            $gridData = $this->crudService->getAdvisorLeads($request, $leadType);
            return DataTables::of($gridData)
                ->addIndexColumn()
                ->make(true);
        }
        return view('myleads.view', compact('leadTypes', 'leadStatusList'));
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
}

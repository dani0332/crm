<?php

namespace App\Http\Controllers;

use App\Enums\quoteTypeCode;
use App\Http\Requests\LeadSearch;
use App\Models\UserTeams;
use App\Services\CRUDService;
use Illuminate\Http\Request;
use DataTables;
use Illuminate\Support\Facades\Auth;

use function PHPUnit\Framework\isEmpty;

class LeadSearchController extends Controller
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
        $leadType = $request->leadType ?? '';
        $userRoles = Auth::user()->usersroles()->get();
        $managerRole = '';
        foreach ($userRoles as $userRole) {
            if (str_contains(strtolower($userRole->name), 'manager')) {
                $managerRole = explode('_', $userRole->name)[0];
            }
        }
        if (
            strtolower($managerRole) == strtolower(quoteTypeCode::GM) || strtolower($managerRole == quoteTypeCode::GroupMedical)
            || strtolower($managerRole) == strtolower(quoteTypeCode::CORPLINE)
        ) {
            $managerRole = 'BUSINESS';
        }
        if (
            strtolower($managerRole) == strtolower(quoteTypeCode::RM_NB) || strtolower($managerRole) == strtolower(quoteTypeCode::RM_SPEED) ||
            strtolower($managerRole) == strtolower(quoteTypeCode::RetailMedical) || strtolower($managerRole) == strtolower(quoteTypeCode::EBP)
        ) {
            $managerRole = 'HEALTH';
        }
        $isManager = Auth::user()->isManagerOrDeputy();
        if ($request->ajax()) {
            if (isset($leadType) && !empty($leadType)) {
                $quoteResults = $this->crudService->getLeads($request->cdbID, $request->email, $request->phnNumber, $leadType);
                return DataTables::of($quoteResults)
                    ->addIndexColumn()
                    ->make(true);
            }
        }
        return view('leadsearch.view', compact('leadType', 'isManager', 'managerRole'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
        dd('test');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\
     * Response
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
    public function show($id)
    {
        //
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

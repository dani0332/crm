<?php

namespace App\Http\Controllers;

use App\Models\InsuranceCompany;
use Illuminate\Http\Request;
use DataTables;
use Auth;

class InsuranceCompanyController extends Controller
{
    /**

     * Display a listing of the resource.

     *

     * @return \Illuminate\Http\Response

     */

    public function __construct()
    {

        $this->middleware('permission:insurance-company-list|insurance-company-create|insurance-company-edit|insurance-company-delete', ['only' => ['index', 'store']]);

        $this->middleware('permission:insurance-company-create', ['only' => ['create', 'store']]);

        $this->middleware('permission:insurance-company-edit', ['only' => ['edit', 'update']]);

        $this->middleware('permission:insurance-company-delete', ['only' => ['destroy']]);
    }

    /**

     * Display a listing of the resource.

     *

     * @return \Illuminate\Http\Response

     */

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = InsuranceCompany::select('*');
            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    return view('Insurancecompany.actions', compact('row'))->render();
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        return view('Insurancecompany.view');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function create()
    {
        return view('Insurancecompany.add');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function store(Request $request)
    {

        $this->validate($request, [

            'name' => 'required',

            'is_active' => 'required',

        ]);

        $insurancecompany = new InsuranceCompany;
        $insurancecompany->name = $request->name;
        $insurancecompany->is_active = $request->is_active == 'on' ? 1 : 0;
        $insurancecompany->created_by = Auth::user()->email;
        $insurancecompany->save();
        if(isset($request->return_to_view))
            return redirect("transapp/insurancecompany");

        return redirect()->back()
        ->with('success', 'Insurance Company created successfully');
    }

    /**
     * Display the specified resource.
     *
     * @param  InsuranceCompany  $insurancecompany
     * @return \Illuminate\Http\Response
     */

    public function show(InsuranceCompany $insurancecompany)
    {
        return view('Insurancecompany.show', compact('insurancecompany'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  InsuranceCompany  $insurancecompany
     * @return \Illuminate\Http\Response
     */

    public function edit(InsuranceCompany $insurancecompany)
    {
        return view('Insurancecompany.edit', compact('insurancecompany'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  InsuranceCompany  $insurancecompany
     * @return \Illuminate\Http\Response
     */

    public function update(Request $request,InsuranceCompany $insurancecompany)
    {
        $this->validate($request, [

            'name' => 'required',
        ]);
        // return $request->is_active;
        $insurancecompany->name = $request->name;
        $insurancecompany->is_active = $request->is_active == 'on' ? 1 : 0;
        $insurancecompany->updated_by = Auth::user()->email;
        $insurancecompany->save();
        if(isset($request->return_to_view))
        return redirect("transapp/insurancecompany");

        return redirect()->back()
            ->with('success', 'Insurance Company updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  InsuranceCompany  $insurancecompany
     * @return \Illuminate\Http\Response
     */

    public function destroy(InsuranceCompany $insurancecompany)
    {

        $insurancecompany->delete();
        return redirect()->route('Insurancecompany.index')
turn redirect()->route('Insurancecompany.index')

<?php

namespace App\Http\Controllers;

use App\Models\InsuranceCompany;
use Auth;
use DataTables;
use Illuminate\Http\Request;

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
            $data = InsuranceCompany::select('*')->where('is_deleted', 0)->orderBy('created_at', 'desc');

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
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|max:150',
        ]);

        $insurancecompany = new InsuranceCompany;
        $insurancecompany->name = $request->name;
        $insurancecompany->is_active = $request->is_active == 'on' ? 1 : 0;
        $insurancecompany->created_by = Auth::user()->email;
        $insurancecompany->updated_by = Auth::user()->email;
        $insurancecompany->save();
        if (isset($request->return_to_view)) {
            return redirect('transapp/insurancecompany/'.$insurancecompany->id)->with('success', 'Insurance Company has been stored');
        }

        return redirect()->back()->with('success', 'Insurance Company has been stored');
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(InsuranceCompany $insurancecompany)
    {
        return view('Insurancecompany.show', compact('insurancecompany'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(InsuranceCompany $insurancecompany)
    {
        return view('Insurancecompany.edit', compact('insurancecompany'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, InsuranceCompany $insurancecompany)
    {
        $this->validate($request, [
            'name' => 'required|max:150',
        ]);

        $insurancecompany->name = $request->name;
        $insurancecompany->is_active = $request->is_active == 'on' ? 1 : 0;
        $insurancecompany->updated_by = Auth::user()->email;
        $insurancecompany->save();

        if (isset($request->return_to_view)) {
            return redirect('transapp/insurancecompany/'.$insurancecompany->id)->with('success', 'Insurance Company has been updated');
        }

        return redirect()->back()->with('success', 'Insurance Company has been updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(InsuranceCompany $insurancecompany)
    {
        $insurancecompany->is_deleted = 1;
        $insurancecompany->save();

        return redirect()->route('insurancecompany.index')->with('message', 'Insurance Company has been deleted');
    }
}

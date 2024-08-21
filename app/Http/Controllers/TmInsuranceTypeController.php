<?php

namespace App\Http\Controllers;

use App\Models\TmInsuranceType;
use Illuminate\Http\Request;

class TmInsuranceTypeController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:tm-insurance-type-list|tm-insurance-type-create|tm-insurance-type-edit|tm-insurance-type-delete', ['only' => ['index', 'store']]);
        $this->middleware('permission:tm-insurance-type-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:tm-insurance-type-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:tm-insurance-type-delete', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $data = TmInsuranceType::orderBy('sort_order', 'asc')->paginate();

        return inertia('Telemarketing/TmInsuranceType/Index', ['tminsurancetype' => $data]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return inertia('Telemarketing/TmInsuranceType/Form', ['tminsurancetype' => null]);

    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'code' => 'required|max:100',
            'text' => 'required|max:100',
            'text_ar' => 'required|max:100',
            'sort_order' => 'required|max:5',
        ]);

        $TmInsuranceType = new TmInsuranceType;
        $TmInsuranceType->code = $request->code;
        $TmInsuranceType->text = $request->text;
        $TmInsuranceType->text_ar = $request->text_ar;
        $TmInsuranceType->is_active = $request->is_active == 'on' ? 1 : 0;
        $TmInsuranceType->sort_order = $request->sort_order;
        $TmInsuranceType->save();

        if (isset($request->return_to_view)) {
            return redirect('telemarketing/tminsurancetype/'.$TmInsuranceType->id)->with('success', 'TM Insurance Type has been stored');
        }

        return redirect()->back()->with('success', 'TM Insurance Type has been stored');
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(TmInsuranceType $tminsurancetype)
    {
        return inertia('Telemarketing/TmInsuranceType/Show', ['tminsurancetype' => $tminsurancetype]);

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(TmInsuranceType $tminsurancetype)
    {
        return inertia('Telemarketing/TmInsuranceType/Form', ['tminsurancetype' => $tminsurancetype]);

    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, TmInsuranceType $tminsurancetype)
    {
        $this->validate($request, [
            'code' => 'required|max:100',
            'text' => 'required|max:100',
            'text_ar' => 'required|max:100',
            'sort_order' => 'required|max:5',
        ]);

        $tminsurancetype->code = $request->code;
        $tminsurancetype->text = $request->text;
        $tminsurancetype->text_ar = $request->text_ar;
        $tminsurancetype->is_active = $request->is_active == 'on' ? 1 : 0;
        $tminsurancetype->sort_order = $request->sort_order;
        $tminsurancetype->save();

        if (isset($request->return_to_view)) {
            return redirect('telemarketing/tminsurancetype/'.$tminsurancetype->id)->with('success', 'TM Insurance Type has been updated');
        }

        return redirect()->back()->with('success', 'TM Insurance Type has been updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(TmInsuranceType $tminsurancetype)
    {
        $tminsurancetype->is_deleted = 1;
        $tminsurancetype->save();

        return redirect()->route('tminsurancetype.index')->with('message', 'TM Insurance Type has been deleted');
    }
}

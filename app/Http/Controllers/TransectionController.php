<?php

namespace App\Http\Controllers;

use App\Models\Transection;
use Illuminate\Http\Request;
use DataTables;
use Auth;
class TransectionController extends Controller
{
    /**

     * Display a listing of the resource.

     *

     * @return \Illuminate\Http\Response

     */

    public function __construct()
    {

        $this->middleware('permission:transection-list|transection-create|transection-edit|transection-delete', ['only' => ['index', 'store']]);

        $this->middleware('permission:transection-create', ['only' => ['create', 'store']]);

        $this->middleware('permission:transection-edit', ['only' => ['edit', 'update']]);

        $this->middleware('permission:transection-delete', ['only' => ['destroy']]);
    }

    /**

     * Display a listing of the resource.

     *

     * @return \Illuminate\Http\Response

     */

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Transection::select('*');
            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    return view('transection.actions', compact('row'))->render();
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        return view('transection.view');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function create()
    {
        return view('transection.add');
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

        $transection = new Transection;
        $transection->name = $request->name;
        $transection->is_active = $request->is_active == 'on' ? 1 : 0;
        $transection->created_by = Auth::user()->email;
        $transection->save();
        if(isset($request->return_to_view))
            return redirect("transapp/Transection");

        return redirect()->back()
        ->with('success', 'Transection created successfully');
    }

    /**
     * Display the specified resource.
     *
     * @param  Transection  $transection
     * @return \Illuminate\Http\Response
     */

    public function show(Transection $transection)
    {
        return view('transection.show', compact('Transection'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  Transection  $transection
     * @return \Illuminate\Http\Response
     */

    public function edit(Transection $transection)
    {
        return view('transection.edit', compact('Transection'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Transection  $transection
     * @return \Illuminate\Http\Response
     */

    public function update(Request $request,Transection $transection)
    {
        $this->validate($request, [

            'name' => 'required',
        ]);
        // return $request->is_active;
        $transection->name = $request->name;
        $transection->is_active = $request->is_active == 'on' ? 1 : 0;
        $transection->updated_by = Auth::user()->email;
        $transection->save();
        if(isset($request->return_to_view))
        return redirect("transapp/Transection");

        return redirect()->back()
            ->with('success', 'Transection updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Transection  $transection
     * @return \Illuminate\Http\Response
     */

    public function destroy(Transection $transection)
    {

        $transection->delete();
        return redirect()->route('transection.index')
            ->with('success', 'Transection deleted successfully');
    }
}

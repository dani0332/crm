<?php

namespace App\Http\Controllers;

use App\Models\PaymentMode;
use Illuminate\Http\Request;
use DataTables;
use Auth;
use DB;
class PaymentModeController extends Controller
{
    /**

     * Display a listing of the resource.

     *

     * @return \Illuminate\Http\Response

     */

    public function __construct()
    {

        $this->middleware('permission:payment-mode-list|payment-mode-create|payment-mode-edit|payment-mode-delete', ['only' => ['index', 'store']]);

        $this->middleware('permission:payment-mode-create', ['only' => ['create', 'store']]);

        $this->middleware('permission:payment-mode-edit', ['only' => ['edit', 'update']]);

        $this->middleware('permission:payment-mode-delete', ['only' => ['destroy']]);
    }

    /**

     * Display a listing of the resource.

     *

     * @return \Illuminate\Http\Response

     */

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = PaymentMode::select('*');
            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    return view('paymentmode.actions', compact('row'))->render();
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        return view('paymentmode.view');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function create()
    {
        return view('paymentmode.add');
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

        ]);

        $paymentmode = new PaymentMode;
        $paymentmode->name = $request->name;
        $paymentmode->is_active = $request->is_active == 'on' ? 1 : 0;
        $paymentmode->created_by = Auth::user()->email;
        $paymentmode->save();
        if(isset($request->return_to_view))
            return redirect("transapp/paymentmode");

        return redirect()->back()
        ->with('success', 'Insurance Company created successfully');
    }

    /**
     * Display the specified resource.
     *
     * @param  PaymentMode  $paymentmode
     * @return \Illuminate\Http\Response
     */

    public function show(PaymentMode $paymentmode)
    {
        return view('paymentmode.show', compact('paymentmode'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  PaymentMode  $paymentmode
     * @return \Illuminate\Http\Response
     */

    public function edit(PaymentMode $paymentmode)
    {
        return view('paymentmode.edit', compact('paymentmode'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  PaymentMode  $paymentmode
     * @return \Illuminate\Http\Response
     */

    public function update(Request $request,PaymentMode $paymentmode)
    {
        $this->validate($request, [

            'name' => 'required',
        ]);
        // return $request->is_active;
        $paymentmode->name = $request->name;
        $paymentmode->is_active = $request->is_active == 'on' ? 1 : 0;
        $paymentmode->updated_by = Auth::user()->email;
        $paymentmode->save();
        if(isset($request->return_to_view))
        return redirect("transapp/paymentmode");

        return redirect()->back()
            ->with('success', 'Insurance Company updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  PaymentMode  $paymentmode
     * @return \Illuminate\Http\Response
     */

    public function destroy(PaymentMode $paymentmode)
    {

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        $paymentmode->delete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');;
        return redirect()->route('paymentmode.index')
            ->with('success', 'Insurance Company deleted successfully');
    }
}

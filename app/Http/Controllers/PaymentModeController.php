<?php

namespace App\Http\Controllers;

use App\Models\PaymentMode;
use Auth;
use DataTables;
use Illuminate\Http\Request;

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
            $data = PaymentMode::select('*')->where('is_deleted', 0)->orderBy('created_at', 'desc');

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
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|max:150',
        ]);

        $paymentmode = new PaymentMode;
        $paymentmode->name = $request->name;
        $paymentmode->is_active = $request->is_active == 'on' ? 1 : 0;
        $paymentmode->created_by = Auth::user()->email;
        $paymentmode->updated_by = Auth::user()->email;
        $paymentmode->save();

        if (isset($request->return_to_view)) {
            return redirect('transapp/paymentmode/'.$paymentmode->id)->with('success', 'Payment Mode has been stored');
        }

        return redirect()->back()->with('success', 'Payment Mode has been stored');
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(PaymentMode $paymentmode)
    {
        return view('paymentmode.show', compact('paymentmode'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(PaymentMode $paymentmode)
    {
        return view('paymentmode.edit', compact('paymentmode'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, PaymentMode $paymentmode)
    {
        $this->validate($request, [
            'name' => 'required|max:150',
        ]);
        $paymentmode->name = $request->name;
        $paymentmode->is_active = $request->is_active == 'on' ? 1 : 0;
        $paymentmode->updated_by = Auth::user()->email;
        $paymentmode->save();
        if (isset($request->return_to_view)) {
            return redirect('transapp/paymentmode/'.$paymentmode->id)->with('success', 'Payment Mode has been updated');
        }

        return redirect()->back()->with('success', 'Payment Mode has been updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(PaymentMode $paymentmode)
    {
        $paymentmode->is_deleted = 1;
        $paymentmode->save();

        return redirect()->route('paymentmode.index')->with('message', 'Payment Mode has been deleted');
    }
}

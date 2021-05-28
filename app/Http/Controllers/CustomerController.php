<?php
namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Nationality;
use DataTables;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    /**

     * Display a listing of the resource.

     *

     * @return \Illuminate\Http\Response

     */

    public function __construct()
    {

        $this->middleware('permission:customers-list', ['only' => ['index', 'store']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Customer::select('*');
            if (isset($request->searchtype) && !empty($request->searchtype)
            && isset($request->searchfield) && !empty($request->searchfield)) {
                $data->where($request->searchtype, $request->searchfield);
            }

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    return view('customers.actions', compact('row'))->render();
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        return view('customers.view');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Customer  $carquote
     * @return \Illuminate\Http\Response
     */
    public function show(Customer $customer)
    {
        return view('customers.show', compact('customer'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Customer  $customer
     * @return \Illuminate\Http\Response
     */
    public function edit(Customer $customer)
    {
        $nationalities = Nationality::all();
        return view('customers.edit', compact('customer', 'nationalities'));
    }
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Customer  $customer
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Customer $customer)
    {
        $this->validate($request, [
            'first_name' => 'required|max:120',
            'last_name' => 'required|max:120',
            'mobile_no' => 'required|max:120',
            'lang' => 'required|max:2',
            'gender' => 'required|max:120',
            'dob' => 'required|max:120',
            'nationality_id' => 'required|max:120',

        ]);
        $customer->first_name = $request->first_name;
        $customer->last_name = $request->last_name;
        $customer->mobile_no = $request->mobile_no;
        $customer->lang = $request->lang;
        $customer->gender = $request->gender;
        $customer->dob = $request->dob;
        $customer->nationality_id = $request->nationality_id;
        $customer->has_alfred_access = $request->has_alfred_access == 'on' ? 1 : 0;
        $customer->save();
        return back()
            ->with('success', 'Customer has been Updated');
    }
}

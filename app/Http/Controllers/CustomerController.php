<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use DataTables;
use Spatie\Permission\Models\Role;
use DB;
use Hash;
use Illuminate\Support\Arr;
use Config;
use Str;
class CustomerController extends Controller
{
     /**

     * Display a listing of the resource.

     *

     * @return \Illuminate\Http\Response

     */

    function __construct()

    {

         $this->middleware('permission:customers-list', ['only' => ['index','store']]);
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
            if(isset($request->searchtype) && !empty($request->searchtype) && isset($request->searchfield) && !empty($request->searchfield))
                $data->where($request->searchtype,$request->searchfield);
                return DataTables::of($data)
                        ->addIndexColumn()
                        ->make(true);
            }
        return view('customers.view');
    }

    
    
}

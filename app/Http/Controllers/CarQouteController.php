<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CarQoute;
use DataTables;
use Spatie\Permission\Models\Role;
use DB;
use Hash;
use Illuminate\Support\Arr;

class CarQouteController extends Controller
{
     /**

     * Display a listing of the resource.

     *

     * @return \Illuminate\Http\Response

     */

    function __construct()

    {

         $this->middleware('permission:users-list|users-create|users-edit|users-delete', ['only' => ['index','store']]);

         $this->middleware('permission:users-create', ['only' => ['create','store']]);

         $this->middleware('permission:users-edit', ['only' => ['edit','update']]);

         $this->middleware('permission:users-delete', ['only' => ['destroy']]);

    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = CarQoute::select('*');
            return Datatables::of($data)
                    ->addIndexColumn()
                    ->rawColumns(['action'])
                    ->make(true);
        }
        
        return view('carqoutes.view');
    }
    
}

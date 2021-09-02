<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;

class FtcFormController extends Controller
{
    /**

     * Display a listing of the resource.

     *

     * @return \Illuminate\Http\Response

     */

    public function __construct()
    {
       // $this->middleware('permission:customers-list', ['only' => ['index', 'store']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return view('ftc.home');
    }


}

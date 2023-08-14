<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CommercialKeywordsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        return view('commercialkeywords.index');
    }
}

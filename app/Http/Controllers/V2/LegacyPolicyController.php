<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\InslyDetail;
use Illuminate\Http\Request;

class LegacyPolicyController extends Controller
{
   /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        $policies=InslyDetail::simplePaginate()->withQueryString()->toArray();

        return inertia('LegacyPolicy/Index', ['policies'=>$policies]);

    }

     /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(int $policyNo)
    {

        $policy=InslyDetail::where('policy_oid','=',$policyNo)->first();
        
        return inertia('LegacyPolicy/Show', ['policy'=>$policy]);

    }
}

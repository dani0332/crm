<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\InslyDetail;
use App\Repositories\InslyDetailRepository;

class LegacyPolicyController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        $policies = InslyDetailRepository::getData();

        return inertia('LegacyPolicy/Index', ['policies' => $policies]);

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(int $policyNo)
    {

        $policy = InslyDetailRepository::getBy('policy_oid',$policyNo);

        return inertia('LegacyPolicy/Show', ['policy' => $policy]);

    }
}

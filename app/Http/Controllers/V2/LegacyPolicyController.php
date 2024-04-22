<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Repositories\InslyDetailRepository;
use Illuminate\Http\Request;

class LegacyPolicyController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $policies = [];
        if (! empty($request->all())) {
            $policies = InslyDetailRepository::getData();
        }

        return inertia('LegacyPolicy/Index', ['policies' => $policies]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($mongoId)
    {

        $expiryDate = now()->addDay(); //The link will be expire after 1
        $url = 'afia/2020_09/21/10037772/31948184.png';

        $policy = InslyDetailRepository::getBy('_id', $mongoId);

        return inertia('LegacyPolicy/Show', ['policy' => $policy]);
    }

    public function moveToImcrm(Request $request)
    {

        $policy = InslyDetailRepository::saveToImcrm($request->toArray());

        return $policy;
    }
}

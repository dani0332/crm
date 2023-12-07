<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Repositories\InslyDetailRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
        $policy = InslyDetailRepository::getBy('_id', $mongoId);

        return inertia('LegacyPolicy/Show', ['policy' => $policy]);
    }

    public function moveToImcrm(Request $request)
    {

        $policy = InslyDetailRepository::saveToImcrm($request->toArray());

        return $policy;
    }

    public function getS3TempUrl(Request $request)
    {
        $expiryDate = now()->addMinutes(40);
        $fileName = $request->fileName;
        // $fileName = 'afia/2020_09/21/10037772/31948184.png'; //FILE EXISTS ON AWS SERVER
        $temporaryUrl = null;
        if (Storage::disk('s3')->has($fileName)) {
            $temporaryUrl = Storage::disk('s3')->temporaryUrl($fileName, $expiryDate);
        }
        // Check if a temporary URL was generated
        if ($temporaryUrl) {
            return response()->json(['url' => $temporaryUrl]);
        } else {
            return response()->json(['error' => 'File does not exists on server']);
        }
    }
}

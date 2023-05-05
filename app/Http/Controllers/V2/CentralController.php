<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Http\Requests\DuplicateLobRequest;

class CentralController extends Controller
{
    public function createDuplicate(DuplicateLobRequest $request)
    {

        $quoteType = 'App\\Repositories\\'.ucfirst(request()->modelType).'QuoteRepository';
       $response= $quoteType::saveDuplicateLeads($request->validated());

       if(!empty($response['errors'])){
        return redirect()->back()->withErrors($response['errors']);
       }
        return back()->with('message', 'Quote is created successfully.');
    }
}

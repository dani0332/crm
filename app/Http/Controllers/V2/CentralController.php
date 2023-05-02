<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Http\Requests\DuplicateLobRequest;

class CentralController extends Controller
{
    public function createDuplicate(DuplicateLobRequest $request)
    {
        $quoteType = 'App\\Repositories\\'.ucfirst(request()->modelType).'QuoteRepository';
        $quoteType::saveDuplicateLeads($request->validated());
    }
}

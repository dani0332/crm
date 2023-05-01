<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Http\Requests\DuplicateLobRequest;
use App\Repositories\LifeQuoteRepository;
use App\Traits\CentralTrait;

class CentralController extends Controller
{
    // use CentralTrait;
   public function createDuplicate(DuplicateLobRequest $request){

       $quoteType = 'Life';
       $quoteType = 'App\\Repositories\\'. $quoteType . 'QuoteRepository';
       $quoteType::saveDuplicateLeads($request->validated());
       // $this->saveDuplicateLeads($request->validated());


   }
}

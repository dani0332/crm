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
    
    LifeQuoteRepository::duplicateAllowedLobs('Life','1122');
    // $this->saveDuplicateLeads($request->validated());


   }
}

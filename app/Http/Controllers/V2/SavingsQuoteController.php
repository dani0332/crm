<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Services\Quotes\SavingsQuoteService;

class SavingsQuoteController extends Controller
{
    public function __construct(public SavingsQuoteService $savingsQuoteService)
    {
        
    }

    public function index()
    {
        dd('SavingsQuoteController@index');
    }
}

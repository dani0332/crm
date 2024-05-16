<?php

namespace App\Http\Controllers;

use App\Repositories\HealthRevivalQuoteRepository;

class HealthRevivalQuoteController extends Controller
{
    //

    public function index()
    {

        $formOptions = HealthRevivalQuoteRepository::getFormOptions();
        $quotes = HealthRevivalQuoteRepository::getData();

        return inertia('HealthRevivalQuote/Index', [
            'quotes' => $quotes,
            'formOptions' => $formOptions,
        ]);
        // dd('Health Revival Quote Controller');
    }
}

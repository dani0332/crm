<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CarQuoteResource;
use App\Repositories\CarQuoteRepository;
use Illuminate\Http\Request;

class CarQuoteController extends Controller
{
    /**
     * @return void
     */
    public function index()
    {
        $quotes = CarQuoteRepository::select(
            ['id', 'code', 'uuid', 'advisor_id']
        )->filter()
        ->simplePaginate();

        //return CarQuoteResource::collection($quotes);
        return response()->json($quotes);
    }
}

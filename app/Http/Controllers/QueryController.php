<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QueryController extends Controller
{
    public function executeQuery(Request $request)
    {
        $query = DB::table('health_quote_request');
        $results = $query->where('code', $request->code)->get();

        return response()->json($results);
    }
}

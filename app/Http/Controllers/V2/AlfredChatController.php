<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Repositories\AlfredChatRepository;
use Illuminate\Http\Request;

class AlfredChatController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if (! empty($request->all())) {
            $chat = AlfredChatRepository::getData();
        }

        return response()->json($chat);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($mongoId)
    {

     
    }


}

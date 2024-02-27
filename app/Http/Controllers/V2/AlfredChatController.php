<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Http\Requests\AlfredChatRequest;
use App\Repositories\AlfredChatRepository;
use Illuminate\Http\Request;
use App\Models\AlfredChat;
class AlfredChatController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(AlfredChatRequest $request)
    {      
        $quoteId = $request->quoteId;

        $chat = AlfredChat::where('quote_id', $quoteId)
        ->with('customer')
        ->select('role', 'msg', 'created_at')
        ->simplePaginate()
        ->withQueryString()
        ->toArray();

        if (empty($chat['data'])) 
            return response()->json(['message' => 'No chat available'], 404);
        else 
            return response()->json($chat);  

    }

}

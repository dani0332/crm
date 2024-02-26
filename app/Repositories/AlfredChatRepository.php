<?php

namespace App\Repositories;

use App\Models\AlfredChat;
use App\Traits\GenericQueriesAllLobs;

class AlfredChatRepository extends BaseRepository
{
    use GenericQueriesAllLobs;
    public function model()
    {
        return AlfredChat::class;
    }

    public static function fetchGetData()
    {
        $query = AlfredChat::query();
        $query->where('quote_id', '=', request()->quoteId)->with('customer')->select('role', 'msg', 'created_at');

        $data = $query->simplePaginate()->withQueryString()->toArray();

        return $data;
    }

}

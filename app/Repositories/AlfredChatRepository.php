<?php

namespace App\Repositories;

use App\Enums\LeadSourceEnum;
use App\Enums\QuoteTypes;
use App\Models\AlfredChat;
use App\Services\CapiRequestService;
use App\Traits\GenericQueriesAllLobs;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AlfredChatRepository extends BaseRepository
{
    use GenericQueriesAllLobs;
    public function model()
    {
        return AlfredChat::class;
    }

    static function fetchGetData()
    {
        $query = AlfredChat::query();
        $query->where('quote_id', '=', request()->quoteId)->with('customer')->select('role', 'msg', 'created_at');

        $data = $query->simplePaginate()->withQueryString()->toArray();

        return $data;
    }

}

<?php

namespace App\Services;

use App\Models\CarQuote;
use Illuminate\Support\Facades\Cache;

class CacheService extends BaseService
{
    public function getLeadSources()
    {
        $value = '';
        if (Cache::has('leadSources')) {
            $value = Cache::get('leadSources');
        } else {
            $value = Cache::remember('leadSources', 86400, function () {
                return CarQuote::where('source', '!=', 'test')
                    ->where('source', '!=', 'postman')
                    ->where('source', 'not like', '%vercel%')
                    ->where('source', 'not like', '%localhost%')
                    ->groupBy('source')
                    ->get()
                    ->pluck('source');
            });
        }

        return $value;
    }
}

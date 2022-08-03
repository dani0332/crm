<?php

namespace App\Services;

use DB;

class SanctionListService
{
    public function fetchNationality()
    {
        return DB::table('sanction_list')
            ->selectRaw('DISTINCT nationality')
            ->whereRaw("nationality != 'na' AND nationality IS NOT NULL")
            ->get()
            ->pluck('nationality');
    }

    public function years()
    {
        return range(date('Y'), 1900);
    }
}

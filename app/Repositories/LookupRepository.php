<?php

namespace App\Repositories;

use App\Enums\QuoteTypes;
use App\Facades\Capi;
use App\Models\Lookup;
use App\Models\PersonalQuote;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

class LookupRepository extends BaseRepository
{
    public function model()
    {
        return Lookup::class;
    }
}

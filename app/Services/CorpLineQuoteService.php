<?php

namespace App\Services;

use App\Models\BusinessQuote;
use Illuminate\Support\Arr;

class CorpLineQuoteService
{
    public function updateQuote(array $data, string $uuid): void
    {
        Arr::forget($data, 'modelType');
        BusinessQuote::where('uuid', $uuid)->update($data);
    }
}

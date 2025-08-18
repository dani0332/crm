<?php

namespace App\Services;

use App\Models\Lookup;
use App\Models\RangeLookup;

class RenewalsHelperService
{
    public function getLookupByText(string $key, ?string $text): ?Lookup
    {
        if (empty($text)) {
            return null;
        }
        
        return Lookup::where('key', $key)
            ->where('text', trim($text))
            ->first();
    }
    
    public function getRangeLookupByText(string $key, ?string $text): ?RangeLookup
    {
        if (empty($text)) {
            return null;
        }
        
        return RangeLookup::where('key', $key)
            ->where('text', trim($text))
            ->first();
    }
}

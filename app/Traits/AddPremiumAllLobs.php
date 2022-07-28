<?php

namespace App\Traits;

trait AddPremiumAllLobs
{
    public function savePremium($quoteType, $request, $response)
    {
        $nameSpace = '\\App\\Models\\';
        $modelType = $nameSpace.$quoteType;
        $quote = $modelType::where('uuid', $response->quoteUID)->update(['premium' => $request->premium]);
    }
}

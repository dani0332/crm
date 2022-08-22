<?php

namespace App\Traits;

trait GenericQueriesAllLobs
{
    public function getCode($quoteType, $id)
    {
        $nameSpace = '\\App\\Models\\';
        $modelType = $nameSpace.$quoteType;
        return $modelType::whereId($id)->value('code');
    }
    public function getModelObject($quoteType, $id)
    {
        $nameSpace = '\\App\\Models\\';
        $modelType = $nameSpace.$quoteType."Quote";
        return $modelType::find($id);
    }
}

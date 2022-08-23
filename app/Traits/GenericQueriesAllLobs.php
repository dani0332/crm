<?php

namespace App\Traits;

trait GenericQueriesAllLobs
{
    public function getCode($quoteType, $id)
    {
        $nameSpace = '\\App\\Models\\';
        $modelType = $nameSpace.$quoteType;
        $result = $modelType::whereId($id)->value('code');
        if($result) {
            return $result;
        } else {
            return false;
        }
    }
    public function getModelObject($quoteType, $id)
    {
        $nameSpace = '\\App\\Models\\';
        $modelType = $nameSpace.$quoteType."Quote";
        $result = $modelType::find($id);
        if($result) {
            return $result;
        } else {
            return false;
        }
    }
}

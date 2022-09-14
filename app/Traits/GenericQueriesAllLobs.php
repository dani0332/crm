<?php

namespace App\Traits;

trait GenericQueriesAllLobs
{
    /**
     * get quote record using quote type
     *
     * @param $quoteType e.g car, health etc
     * @param $key could be any column, as for now id, uuid is required to match
     * @param $value to be matched
     * @return mixed
     */
    public function getQuote($quoteType, $key, $value)
    {
        $model = '\\App\\Models\\'.ucwords($quoteType).'Quote';

        if (! class_exists($model)) {
            return false;
        }

        return $model::where($key, $value)->first();
    }

    public function getQuoteCode($quoteType, $id)
    {
        $nameSpace = '\\App\\Models\\';
        $modelType = $nameSpace.$quoteType.'Quote';
        $result = $modelType::whereId($id)->value('code');
        if ($result) {
            return $result;
        } else {
            return false;
        }
    }

    public function getQuoteObject($quoteType, $id)
    {
        $nameSpace = '\\App\\Models\\';
        $modelType = $nameSpace.$quoteType.'Quote';
        $result = $modelType::find($id);
        if ($result) {
            return $result;
        } else {
            return false;
        }
    }
}

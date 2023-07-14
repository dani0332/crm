<?php

namespace App\helpers;

use Carbon\Carbon;
use Exception;

class LookUpModelHelper
{
    public static function getLookModel($model, $query)
    {
        $Model = '\\App\\Models\\'.$model;
        $result = $Model::where([$query])->first();
        if (! $result) {
            throw new Exception('Not found');
        }

        return $result->id;
    }

    // ftc-form-delete schedule on 7th June 2023
//    public static function subjectForFTCEmailCarQuote($carQuote)
//    {
//        $dateTime = Carbon::parse($carQuote->created_at)->format(config('constants.FTC_EMAIL_FORMAT'));
//        $subject = 'Lead Updates of Ref-ID: '.$carQuote->code.' Customer Name: '.ucwords($carQuote->first_name).' '.ucwords($carQuote->last_name).'  Created on '.$dateTime;
//
//        return $subject;
//    }
}

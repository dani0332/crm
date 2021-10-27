<?php

namespace App\Services;

use App\Models\BikeQuote;
use App\Models\BusinessQuote;
use App\Models\CarMake;
use App\Models\CarModel;
use App\Models\CarQuote;
use App\Models\HealthQuote;
use App\Models\HomeQuote;
use App\Models\LifeQuote;
use App\Models\TravelQuote;
use App\Models\YachtQuote;
use App\Models\CarTypeInsurance;
use App\Models\VehicleType;
use App\Models\User;
use App\Models\QuoteType;


class RenewalsAddonServices
{

    function getCarMake($text)
    {
        return CarMake::where('text', '=', $text)->get()->first();
    }

    function getCarModel($text)
    {
        return CarModel::where('text', '=', $text)->get()->first();
    }

    function getCarTypeOfInsurance($text)
    {
        return CarTypeInsurance::where('code', '=', $text)->get()->first();
    }

    function getVehicleType($id)
    {
        return VehicleType::where('id', '=', $id)->get()->first();
    }

    function getUserInfo($email)
    {
        return User::where('email', '=', $email)->value('id');
    }

    function updateBikeQuoteRequestCode($id)
    {
        $code = "BIK-" . $id;
        $updateQuote = BikeQuote::where('id', '=', $id)->get()->first();
        $updateQuote->code = $code;
        $updateQuote->save();
        return $updateQuote;
    }

    function updateBusinessQuoteRequestCode($id)
    {
        $code = "BUS-" . $id;
        $updateQuote = BusinessQuote::where('id', '=', $id)->get()->first();
        $updateQuote->code = $code;
        $updateQuote->save();
        return $updateQuote;
    }

    function updateCarQuoteRequestCode($id)
    {
        $code = "CAR-" . $id;
        $updateQuote = CarQuote::where('id', '=', $id)->get()->first();
        $updateQuote->code = $code;
        $updateQuote->save();
        return $updateQuote;
    }

    function updateHealthQuoteRequestCode($id)
    {
        $code = "HEA-" . $id;
        $updateQuote = HealthQuote::where('id', '=', $id)->get()->first();
        $updateQuote->code = $code;
        $updateQuote->save();
        return $updateQuote;
    }    

    function updateHomeQuoteRequestCode($id)
    {
        $code = "HOM-" . $id;
        $updateQuote = HomeQuote::where('id', '=', $id)->get()->first();
        $updateQuote->code = $code;
        $updateQuote->save();
        return $updateQuote;
    }

    function updateLifeQuoteRequestCode($id)
    {
        $code = "LIF-" . $id;
        $updateQuote = LifeQuote::where('id', '=', $id)->get()->first();
        $updateQuote->code = $code;
        $updateQuote->save();
        return $updateQuote;
    }

    function updateTravelQuoteRequestCode($id)
    {
        $code = "TRA-" . $id;
        $updateQuote = TravelQuote::where('id', '=', $id)->get()->first();
        $updateQuote->code = $code;
        $updateQuote->save();
        return $updateQuote;
    }

    function updateYachtQuoteRequestCode($id)
    {
        $code = "YAC-" . $id;
        $updateQuote = YachtQuote::where('id', '=', $id)->get()->first();
        $updateQuote->code = $code;
        $updateQuote->save();
        return $updateQuote;
    }

    function getQuoteType($type) 
    {
        return QuoteType::where('code','=',$type)->get()->first();
    }

}
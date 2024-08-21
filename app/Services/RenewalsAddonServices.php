<?php

namespace App\Services;

use App\Models\BikeQuote;
use App\Models\BusinessInsuranceType;
use App\Models\BusinessQuote;
use App\Models\CarMake;
use App\Models\CarModel;
use App\Models\CarQuote;
use App\Models\CarTypeInsurance;
use App\Models\HealthQuote;
use App\Models\HomeQuote;
use App\Models\LifeQuote;
use App\Models\QuoteType;
use App\Models\TravelQuote;
use App\Models\User;
use App\Models\VehicleType;
use App\Models\YachtQuote;

class RenewalsAddonServices
{
    public function getCarMake($text)
    {
        return CarMake::where('text', '=', $text)->get()->first();
    }

    public function getCarModel($text, $carMake = null)
    {
        $query = CarModel::where('text', '=', $text);
        if ($carMake != null) {
            $query = $query->where('car_make_code', $carMake->code);
        }

        return $query->get()->first();
    }

    public function getCarTypeOfInsurance($text)
    {
        return CarTypeInsurance::where('code', '=', $text)->get()->first();
    }

    public function getVehicleType($id)
    {
        return VehicleType::where('id', '=', $id)->get()->first();
    }

    public function getUserInfo($email)
    {
        return User::where('email', '=', $email)->value('id');
    }

    public function getUser($email)
    {
        return User::where('email', '=', $email)->first();
    }

    public function updateBikeQuoteRequestCode($id)
    {
        $code = 'BIK-'.$id;
        $updateQuote = BikeQuote::where('id', '=', $id)->get()->first();
        $updateQuote->code = $code;
        $updateQuote->save();

        return $updateQuote;
    }

    public function updateBusinessQuoteRequestCode($id)
    {
        $code = 'BUS-'.$id;
        $updateQuote = BusinessQuote::where('id', '=', $id)->get()->first();
        $updateQuote->code = $code;
        $updateQuote->save();

        return $updateQuote;
    }

    public function updateCarQuoteRequestCode($id)
    {
        $code = 'CAR-'.$id;
        $updateQuote = CarQuote::where('id', '=', $id)->get()->first();
        $updateQuote->code = $code;
        $updateQuote->save();

        return $updateQuote;
    }

    public function updateHealthQuoteRequestCode($id)
    {
        $code = 'HEA-'.$id;
        $updateQuote = HealthQuote::where('id', '=', $id)->get()->first();
        $updateQuote->code = $code;
        $updateQuote->save();

        return $updateQuote;
    }

    public function updateHomeQuoteRequestCode($id)
    {
        $code = 'HOM-'.$id;
        $updateQuote = HomeQuote::where('id', '=', $id)->get()->first();
        $updateQuote->code = $code;
        $updateQuote->save();

        return $updateQuote;
    }

    public function updateLifeQuoteRequestCode($id)
    {
        $code = 'LIF-'.$id;
        $updateQuote = LifeQuote::where('id', '=', $id)->get()->first();
        $updateQuote->code = $code;
        $updateQuote->save();

        return $updateQuote;
    }

    public function updateTravelQuoteRequestCode($id)
    {
        $code = 'TRA-'.$id;
        $updateQuote = TravelQuote::where('id', '=', $id)->get()->first();
        $updateQuote->code = $code;
        $updateQuote->save();

        return $updateQuote;
    }

    public function updateYachtQuoteRequestCode($id)
    {
        $code = 'YAC-'.$id;
        $updateQuote = YachtQuote::where('id', '=', $id)->get()->first();
        $updateQuote->code = $code;
        $updateQuote->save();

        return $updateQuote;
    }

    public function getQuoteType($type)
    {
        return QuoteType::where('code', '=', $type)->get()->first();
    }

    public function getBusinessSublineInsurance($text)
    {
        return BusinessInsuranceType::where('text', '=', $text)->get()->first();
    }
}

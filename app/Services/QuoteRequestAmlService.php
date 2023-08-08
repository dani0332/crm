<?php

namespace App\Services;

use App\Models\BikeQuote;
use App\Models\BusinessQuote;
use App\Models\CarQuote;
use App\Models\HealthQuote;
use App\Models\HomeQuote;
use App\Models\LifeQuote;
use App\Models\PetQuote;
use App\Models\TravelQuote;
use App\Models\YachtQuote;

class QuoteRequestAmlService
{
    public static function getCarQuoteRequest($id)
    {
        return CarQuote::select(
            'car_quote_request.*',
            'quote_status.text as quote_status_text',
            'payment_status.text as payment_status_text',
            'customer.first_name as cust_f_name',
            'customer.last_name as cust_l_name',
            'uae_license_held_for.text as uae_license_text',
            'car_make.text as car_make_text',
            'car_model.text as car_model_text',
            'emirates.text as emirates_text',
            'car_type_insurance.text as car_type_ins_text',
            'claim_history.text as claim_history_text',
            'nationality.text as nationality_text'
        )
            ->leftjoin('quote_status', 'car_quote_request.quote_status_id', 'quote_status.id')
            ->leftjoin('payment_status', 'car_quote_request.payment_status_id', 'payment_status.id')
            ->leftjoin('customer', 'car_quote_request.customer_id', 'customer.id')
            ->leftjoin('uae_license_held_for', 'car_quote_request.uae_license_held_for_id', 'uae_license_held_for.id')
            ->leftjoin('car_make', 'car_quote_request.car_make_id', 'car_make.id')
            ->leftjoin('car_model', 'car_quote_request.car_model_id', 'car_model.id')
            ->leftjoin('emirates', 'car_quote_request.emirate_of_registration_id', 'emirates.id')
            ->leftjoin('car_type_insurance', 'car_quote_request.car_type_insurance_id', 'car_type_insurance.id')
            ->leftjoin('claim_history', 'car_quote_request.claim_history_id', 'claim_history.id')
            ->leftjoin('nationality', 'car_quote_request.nationality_id', 'nationality.id')
            ->where('car_quote_request.id', $id)
            ->first();
    }

    public static function getHealthQuoteRequest($id)
    {
        return HealthQuote::select(
            'health_quote_request.*',
            'quote_status.text as quote_status_text',
            'payment_status.text as payment_status_text',
            'customer.first_name as cust_f_name',
            'customer.last_name as cust_l_name',
            'health_cover_for.text as health_cover_text',
            'marital_status.text as marital_status_text',
            'emirates.text as emirates_text',
            'nationality.text as nationality_text'
        )
            ->leftjoin('quote_status', 'health_quote_request.quote_status_id', 'quote_status.id')
            ->leftjoin('payment_status', 'health_quote_request.payment_status_id', 'payment_status.id')
            ->leftjoin('customer', 'health_quote_request.customer_id', 'customer.id')
            ->leftjoin('health_cover_for', 'health_quote_request.cover_for_id', 'health_cover_for.id')
            ->leftjoin('marital_status', 'health_quote_request.marital_status_id', 'marital_status.id')
            ->leftjoin('emirates', 'health_quote_request.emirate_of_your_visa_id', 'emirates.id')
            ->leftjoin('nationality', 'health_quote_request.nationality_id', 'nationality.id')
            ->where('health_quote_request.id', $id)
            ->first();
    }

    public static function getHomeQuoteRequest($id)
    {
        return HomeQuote::select(
            'home_quote_request.*',
            'quote_status.text as quote_status_text',
            'payment_status.text as payment_status_text',
            'customer.first_name as cust_f_name',
            'customer.last_name as cust_l_name',
            'home_possession_type.text as home_possession_type_text',
            'home_accommodation_type.text as home_accommodation_type_text'
        )
            ->leftjoin('quote_status', 'home_quote_request.quote_status_id', 'quote_status.id')
            ->leftjoin('payment_status', 'home_quote_request.payment_status_id', 'payment_status.id')
            ->leftjoin('customer', 'home_quote_request.customer_id', 'customer.id')
            ->leftjoin('home_possession_type', 'home_quote_request.iam_possesion_type_id', 'home_possession_type.id')
            ->leftjoin('home_accommodation_type', 'home_quote_request.ilivein_accommodation_type_id', 'home_accommodation_type.id')
            ->where('home_quote_request.id', $id)->first();
    }

    public static function getTravelQuoteRequest($id)
    {
        return TravelQuote::select(
            'travel_quote_request.*',
            'quote_status.text as quote_status_text',
            'payment_status.text as payment_status_text',
            'customer.first_name as cust_f_name',
            'customer.last_name as cust_l_name',
            'region.text as region_cover_text',
            'travel_cover_for.text as cover_for_text',
            'nationality.text as nationality_text'
        )
            ->leftjoin('quote_status', 'travel_quote_request.quote_status_id', 'quote_status.id')
            ->leftjoin('payment_status', 'travel_quote_request.payment_status_id', 'payment_status.id')
            ->leftjoin('customer', 'travel_quote_request.customer_id', 'customer.id')
            ->leftjoin('region', 'travel_quote_request.region_cover_for_id', 'region.id')
            ->leftjoin('travel_cover_for', 'travel_quote_request.travel_cover_for_id', 'travel_cover_for.id')
            ->leftjoin('nationality', 'travel_quote_request.nationality_id', 'nationality.id')
            ->where('travel_quote_request.id', $id)
            ->first();
    }

    public static function getLifeQuoteRequest($id)
    {
        return LifeQuote::select(
            'life_quote_request.*',
            'quote_status.text as quote_status_text',
            'payment_status.text as payment_status_text',
            'customer.first_name as cust_f_name',
            'customer.last_name as cust_l_name',
            'life_insurance_purpose.text as purpose_text',
            'life_children.text as children_text',
            'marital_status.text as marital_text',
            'life_insurance_tenure.text as tenure_text',
            'life_number_of_year.text as number_of_year_text',
            'currency_type.text as currency_text',
            'nationality.text as nationality_text'
        )
            ->leftjoin('quote_status', 'life_quote_request.quote_status_id', 'quote_status.id')
            ->leftjoin('payment_status', 'life_quote_request.payment_status_id', 'payment_status.id')
            ->leftjoin('customer', 'life_quote_request.customer_id', 'customer.id')
            ->leftjoin('life_insurance_purpose', 'life_quote_request.purpose_of_insurance_id', 'life_insurance_purpose.id')
            ->leftjoin('life_children', 'life_quote_request.children_id', 'life_children.id')
            ->leftjoin('marital_status', 'life_quote_request.marital_status_id', 'marital_status.id')
            ->leftjoin('life_insurance_tenure', 'life_quote_request.tenure_of_insurance_id', 'life_insurance_tenure.id')
            ->leftjoin('life_number_of_year', 'life_quote_request.number_of_years_id', 'life_number_of_year.id')
            ->leftjoin('currency_type', 'life_quote_request.sum_insured_currency_id', 'currency_type.id')
            ->leftjoin('nationality', 'life_quote_request.nationality_id', 'nationality.id')
            ->where('life_quote_request.id', $id)
            ->first();
    }

    public static function getBikeQuoteRequest($id)
    {
        return BikeQuote::select(
            'bike_quote_request.*',
            'quote_status.text as quote_status_text',
            'payment_status.text as payment_status_text',
            'customer.first_name as cust_f_name',
            'customer.last_name as cust_l_name',
            'nationality.text as nationality_text',
            'uae_license_held_for.text as uae_license_text'
        )
            ->leftjoin('quote_status', 'bike_quote_request.quote_status_id', 'quote_status.id')
            ->leftjoin('payment_status', 'bike_quote_request.payment_status_id', 'payment_status.id')
            ->leftjoin('customer', 'bike_quote_request.customer_id', 'customer.id')
            ->leftjoin('nationality', 'bike_quote_request.nationality_id', 'nationality.id')
            ->leftjoin('uae_license_held_for', 'bike_quote_request.uae_license_held_for_id', 'uae_license_held_for.id')
            ->where('bike_quote_request.id', $id)
            ->first();
    }

    public static function getYachtQuoteRequest($id)
    {
        return YachtQuote::select(
            'yacht_quote_request.*',
            'quote_status.text as quote_status_text',
            'payment_status.text as payment_status_text',
            'customer.first_name as cust_f_name',
            'customer.last_name as cust_l_name'
        )
            ->leftjoin('quote_status', 'yacht_quote_request.quote_status_id', 'quote_status.id')
            ->leftjoin('payment_status', 'yacht_quote_request.payment_status_id', 'payment_status.id')
            ->leftjoin('customer', 'yacht_quote_request.customer_id', 'customer.id')
            ->where('yacht_quote_request.id', $id)
            ->first();
    }

    public static function getBusinessQuoteRequest($id)
    {
        return BusinessQuote::select(
            'business_quote_request.*',
            'quote_status.text as quote_status_text',
            'payment_status.text as payment_status_text',
            'customer.first_name as cust_f_name',
            'customer.last_name as cust_l_name',
            'business_type_of_insurance.text as business_type_text'
        )
            ->leftjoin('quote_status', 'business_quote_request.quote_status_id', 'quote_status.id')
            ->leftjoin('payment_status', 'business_quote_request.payment_status_id', 'payment_status.id')
            ->leftjoin('customer', 'business_quote_request.customer_id', 'customer.id')
            ->leftjoin('business_type_of_insurance', 'business_quote_request.business_type_of_insurance_id', 'business_type_of_insurance.id')
            ->where('business_quote_request.id', $id)
            ->first();
    }

    public static function getPetQuoteRequest($id)
    {
        return PetQuote::select(
            'pet_quote_request.*',
            'quote_status.text as quote_status_text',
            'payment_status.text as payment_status_text',
            'customer.first_name as cust_f_name',
            'customer.last_name as cust_l_name'
        )
            ->leftjoin('quote_status', 'pet_quote_request.quote_status_id', 'quote_status.id')
            ->leftjoin('payment_status', 'pet_quote_request.payment_status_id', 'payment_status.id')
            ->leftjoin('customer', 'pet_quote_request.customer_id', 'customer.id')
            ->where('pet_quote_request.id', $id)
            ->first();
    }
}

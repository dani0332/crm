<?php
namespace App\Transformers;

use App\Models\Customer;
use League\Fractal\TransformerAbstract;
use Illuminate\Support\Arr;

class CarQuoteInsuranceCoverageTransformer extends TransformerAbstract
{
	public function transform($data)
	{
        $carQuoteInsuranceCoverage  =   $data[0];
        $arr = $carQuoteInsuranceCoverage->toArray();
        $coverageData = collect($arr);
        $carQuote = $coverageData->get("car_quote_id");
        
        if($carQuote) {
            if(Arr::has($carQuote, 'plan_id') && $carQuote["plan_id"]) {
                $arr["plan_id"]     =  $carQuote["plan_id"];
                $arr["insurance_company"]  =  $carQuote["plan_id"]["provider_id"];
            }
        }
        return $arr ;
	}
}

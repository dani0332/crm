<?php

namespace App\Services;

use App\Models\LifeQuote;
use Illuminate\Http\Request;
use DB;
use Auth;

class LifeQuoteService extends BaseService
{
    protected $query;

    public function __construct()
    {
        $this->query =DB::table('life_quote_request as lqr')
                        ->select('lqr.id' ,'lqr.uuid' ,'lqr.first_name','lqr.last_name','lqr.email','lqr.mobile_no','lqr.gender','lqr.dob','lqr.is_smoker'
                        ,'lqr.others_info','lqr.sum_insured_value','lqr.sum_insured_currency_id','ct.TEXT AS sum_insured_currency_id_text'
                        ,'lqr.marital_status_id','ms.TEXT AS marital_status_id_text','lqr.purpose_of_insurance_id','lip.TEXT AS purpose_of_insurance_id_text'
                        ,'lqr.children_id','lc.TEXT AS children_id_text','lqr.tenure_of_insurance_id','lit.TEXT AS tenure_of_insurance_id_text'
                        ,'lqr.number_of_years_id','liy.TEXT AS number_of_years_id_text','lqr.nationality_id','n.TEXT AS nationality_id_text')
                        ->leftJoin('currency_type as ct', 'ct.id', '=', 'lqr.sum_insured_currency_id')
                        ->leftJoin('marital_status as ms', 'ms.id', '=', 'lqr.marital_status_id')
                        ->leftJoin('life_insurance_purpose as lip', 'lip.id', '=', 'lqr.purpose_of_insurance_id')
                        ->leftJoin('life_children as lc', 'lc.id', '=', 'lqr.children_id')
                        ->leftJoin('life_insurance_tenure as lit', 'lit.id', '=', 'lqr.tenure_of_insurance_id')
                        ->leftJoin('life_number_of_year as liy', 'liy.id', '=', 'lqr.number_of_years_id')
                        ->leftJoin('nationality as n', 'n.id', '=', 'lqr.nationality_id');
    }
    public function saveLifeQuote(Request $request)
    {
        $dataArr = array(
            "firstName" => $request->first_name,
            "lastName" => $request->last_name,
            "email" => $request->email,
            "address" => $request->address,
            "mobileNo" => $request->mobile_no,
            "dob" => $request->dob,
            "sumInsuredValue" => $request->sum_insured_value,
            "sumInsuredCurrencyId" => $request->sum_insured_currency_id,
            "maritalStatusId" => $request->marital_status_id,
            "purposeOfInsuranceId" => $request->purpose_of_insurance_id,
            "childrenId" => $request->children_id,
            "tenureOfInsuranceId" => $request->tenure_of_insurance_id,
            "numberOfYearsId" => $request->number_of_years_id,
            "isSmoker" => $request->is_smoker == 'Yes' ?  true : false,
            "gender" => $request->gender,
            "othersInfo" => $request->others_info,
        );
        if(Auth::user()->hasRole("LIFE_ADVISOR")) $dataArr['advisorId'] = Auth::users()->id;
        return CapiRequestService::sendCAPIRequest('/api/v1-save-home-quote', $dataArr);
    }

    public function getEntity($id)
    {
        return $this->query->where('lqr.uuid', $id)->first();
    }

    public function getEntityPlain($id)
    {
        return LifeQuote::where('id', $id)->first();
    }
    public function getLeadsForAssignment()
    {
        return LifeQuote::orderBy('created_at', 'desc')->get();
    }
    public function getGridData($searchProperties, $request)
    {
        if ($request->ajax()) {
            foreach ($searchProperties as $item) {
                if (!empty($request[$item])) {
                    $this->query->where($this->getQuerySuffix($item) . '.' . $item, $request[$item]);
                }
            }
        }
        $this->query->orderBy('lqr.created_at', 'DESC');
        return $this->query;
    }

    private function getQuerySuffix($item)
    {
        switch ($item) {
            case 'sum_insured_currency_id':
                return 'ct';
                break;
            case 'marital_status_id':
                return 'ms';
                break;
            case 'nationality_id':
                return 'n';
                break;
            case 'purpose_of_insurance_id':
                return 'lip';
                break;
            case 'children_id':
                return 'lc';
                break;
            case 'tenure_of_insurance_id':
                return 'lit';
                break;
            case 'number_of_years_id':
                return 'liy';
                break;
            default:
                return 'lqr';
                break;
        }
    }

    public function updateLifeQuote(Request $request, $id)
    {
        $updateArray = [
            'first_name'=>$request->first_name,
            'last_name'=>$request->last_name,
            'details'=>$request->details,
            'dob'=>$request->dob,
            'gender'=>$request->gender == 'Male' ? 'M' : 'F',
            'is_smoker'=>$request->is_smoker == 'Yes' ? '1' : '0',
        ];
        if(!Auth::user()->hasRole('LIFE_ADVISOR')){
            $updateArray['email'] = $request->email;
            $updateArray['mobile_no'] = $request->mobile_no;
        }
        LifeQuote::where('uuid',$id)->update($updateArray);

        if (isset($request->return_to_view))
            return redirect("quote/life/" . $lifeQuote->id)->with('success', 'Life Quote has been updated');
    }

    public function getLeads($CDBID, $email, $mobile_no, $lead_type)
    {
        $query = DB::table('life_quote_request as lqr')
                    ->select('lqr.id','lqr.uuid','lqr.first_name','lqr.last_name','lqr.created_at','u.name AS advisor_name',DB::raw("'Life' as lead_type")
                    ,'u.id as advisor_id','qs.text as lead_status')
                    ->Join('users as u', 'u.id', '=', 'lqr.advisor_id')
                    ->Join('quote_status as qs', 'qs.id', '=', 'lqr.quote_status_id');
        if (!empty($CDBID)) {
            $query->where('lqr.id', '=', $CDBID);
        }
        if (!empty($email)) {
            $query->where('lqr.email', '=', $email);
        }
        if (!empty($mobile_no)) {
            $query->where('lqr.mobile_no', '=', $mobile_no);
        }
        return $query;
    }

    public function fillModelProperties()
    {
        return array(
            "id" => "readonly|none",
            "first_name" => "input|text|required",
            "last_name" => "input|text|required",
            "mobile_no" => "input|number|title|required",
            "email" => "input|email|required",
            "dob" => "input|date|title|required",
            "sum_insured_value" => "input|number|title||required",
            "sum_insured_currency_id" => "select|title|required",
            "purpose_of_insurance_id" => "select|title|required",
            "marital_status_id" => "select|title|required",
            "children_id" => "select|title|required",
            "tenure_of_insurance_id" => "select|title|required",
            "number_of_years_id" => "select|title|required",
            "gender" => "|static|required|Male,Female",
            "is_smoker" => "|static|title|required|Yes,No",
            "others_info" => "textarea",
        );
    }

    public function getCustomTitleByProperty($propertyName)
    {
        $title = "";
        switch ($propertyName) {
            case 'purpose_of_insurance_id':
                $title = "Purpose of Insurance";
                break;
            case 'children_id':
                $title = "Children";
                break;
            case 'tenure_of_insurance_id':
                $title = "Tenure Of Insurance";
                break;
            case 'number_of_years_id':
                $title = "No. of Years";
                break;
            case 'sum_insured_currency_id':
                $title = "Currency";
                break;
            case 'dob':
                $title = "Date Of Birth";
                break;
            case 'mobile_no':
                $title = "Mobile Number";
                break;
            case 'is_smoker':
                $title = "Smoker";
                break;
            case 'mobile_no':
                $title = "Mobile Number";
                break;
            case 'sum_insured_value':
                $title = "Sum Insured Value";
                break;
            case 'marital_status_id':
                $title = "Marital Status";
                break;
            default:
                break;
        }
        return $title;
    }

    public function fillModelSkipProperties()
    {
        return [
            "create" => "id",
            "list" => "email,mobile_no,others_info",
        ];
    }

    public function fillModelSearchProperties()
    {
        return ["email", 'first_name', 'last_name', 'nationality_id', 'region_cover_for_id', 'travel_cover_for_id'];
    }
}

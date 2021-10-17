<?php

namespace App\Http\Controllers;

use App\Models\GenericQuoteModel;
use Illuminate\Http\Request;
use App\Models\HealthQuote;
use Config;
use DataTables;

class CRUDController extends Controller
{
    protected $genericQuote;
    public function __construct()
    {
        $this->genericQuote = new GenericQuoteModel();
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if(strpos($request->fullUrl(), 'health')) {
            $this->genericQuote->quoteType = 'Health';
            if ($request->ajax()) {
                $data = HealthQuote::select('*')->orderBy('created_at','desc');
                return Datatables::of($data)
                    ->addIndexColumn()
                    ->make(true);
            }
            return view('shared.add', compact('quote'));
        }
        if(strpos($request->fullUrl(), 'life')) $this->genericQuote->quoteType = 'Life';
        if(strpos($request->fullUrl(), 'bike')) $this->genericQuote->quoteType = 'Bike';



    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        if(strpos($request->fullUrl(), 'health')) $this->genericQuote->quoteType = 'Health';
        if(strpos($request->fullUrl(), 'life')) $this->genericQuote->quoteType = 'Life';
        if(strpos($request->fullUrl(), 'bike')) $this->genericQuote->quoteType = 'Bike';
        $this->fillQuoteModel($this->genericQuote->quoteType);
        $quote = $this->genericQuote;
        return view('shared.add', compact('quote'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $modelPropertiesList = json_decode($request->all()['model'], true);
        $validateArray = [];
        foreach($modelPropertiesList as $property => $value) {
            if(strpos($value, 'required')){
                $validateArray[$property] = 'required';
            }
        }
        $this->validate($request,$validateArray);

        if(json_decode($request->quoteType, true)){
            dd("All");
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    private function fillQuoteModel ($quoteType)
    {
        switch ($quoteType) {
            case 'Life':
                $this->genericQuote->properties = array (
                    "car_value" => "input|number|required",
                    "car_insurance" => "input|text",
                    "special_type" => "input|date"
                );
                break;
            case 'Health':
                $this->genericQuote->properties = array (
                    "Health_value" => "input|number|required",
                    "car_insurance" => "input|text",
                    "special_type" => "input|date"
                );
                break;
            case 'Bike':
                $this->genericQuote->properties = array (
                    "Bike_value" => "input|number|required",
                    "car_insurance" => "input|text",
                    "special_type" => "input|date"
                );
                break;
            default:
                # code...
                break;
        }
    }
}

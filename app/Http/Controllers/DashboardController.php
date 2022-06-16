<?php

namespace App\Http\Controllers;

use App\Models\CarQuote;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use DB;
use Config;

class DashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        dd(Config::get('constants.Log_channel'));
        return view('dashboard');
    }

    public function dashboardStats(Request $request)
    {
        $startDate = Carbon::now()->startOfDay()->toDateTimeString();
        $endDate = Carbon::now()->endOfDay()->toDateTimeString();
        if (
            isset($request->startDate) && !empty($request->startDate)
            && isset($request->endDate) && !empty($request->endDate)
        ) {
            $startDate = Carbon::parse($request->startDate)->startOfDay()->toDateTimeString();
            $endDate = Carbon::parse($request->endDate)->endOfDay()->toDateTimeString();
        }
        $customers = Customer::whereBetween('created_at', [$startDate, $endDate]);

        // $ecomLeadCount = DB::table('car_quote_request')->where('is_ecommerce', true)
        //                 ->whereBetween('created_at', [$startDate, $endDate]);

        $fakeLeads = DB::table('car_quote_request')
            ->join('quote_status', 'quote_status.id', '=', 'car_quote_request.quote_status_id')
            ->where('quote_status.text', 'New Lead')
            ->orWhere('quote_status.text', 'Fake')
            ->whereBetween('car_quote_request.created_at', [$startDate, $endDate]);

        $carQuotes = DB::table('car_quote_request')->whereBetween('created_at', [date($startDate), date($endDate)])
            ->GroupBy('customer_id');

        return response()->json([
            'totalCustomers' => $customers->count(),
            'totalCarQuotes' => $carQuotes->count(),
            'totalEcommerceLeads' => 0,
            'totalFakeLeads' => $fakeLeads->count(),
        ]);
    }
}

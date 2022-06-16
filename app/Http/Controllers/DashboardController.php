<?php

namespace App\Http\Controllers;

use App\Models\CarQuote;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use DB;

class DashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('dashboard');
    }

    public function dashboardStats(Request $request)
    {

        $lastWeekStart = Carbon::now()->startOfWeek()->subDays(1)->startOfWeek();
        $lastWeekEnd = $lastWeekStart->endOfWeek();

        $secondLastWeekStart = $lastWeekStart->subDays(1)->startOfWeek();
        $secondLastWeekEnd = $secondLastWeekStart->endOfWeek();

        $thirdLastWeekStart = $secondLastWeekStart->subDays(1)->startOfWeek();
        $thirdLastWeekEnd = $thirdLastWeekStart->endOfWeek();

        $fourthLastWeekStart = $thirdLastWeekStart->subDays(1)->startOfWeek();
        $fourthLastWeekEnd = $fourthLastWeekStart->endOfWeek();

        $stats = DB::select("
                    SELECT *
                    , (a.tran_approved_ecom / a.ecom_total * 100) ecom_conv
                    , (a.tran_approved_non_ecom / (a.total_assigned-a.ecom_total) * 100) non_ecom_conv
                    , (a.tran_approved_total / a.total_assigned * 100) overall_conv

                    FROM (
                    SELECT
                    count(q.id) total_assigned,
                    SUM(CASE WHEN q.paid_at is not NULL THEN 1 ELSE 0 END) paid_ecom,
                    SUM(CASE WHEN q.paid_at is not NULL AND q.payment_status_id = 4 THEN 1 ELSE 0 END) paid_ecom_auth,
                    SUM(CASE WHEN q.paid_at is not NULL AND q.payment_status_id = 6 THEN 1 ELSE 0 END) paid_ecom_captured,
                    SUM(CASE WHEN q.paid_at is not NULL AND q.payment_status_id = 3 THEN 1 ELSE 0 END) paid_ecom_cancelled,
                    SUM(CASE WHEN q.quote_status_id=15 AND q.is_ecommerce THEN 1 ELSE 0 END) tran_approved_ecom,
                    SUM(CASE WHEN q.quote_status_id=15 AND q.is_ecommerce= 0 THEN 1 ELSE 0 END) tran_approved_non_ecom,
                    SUM(CASE WHEN q.quote_status_id=15 THEN 1 ELSE 0 END) tran_approved_total,
                    SUM(CASE WHEN q.is_ecommerce THEN 1 ELSE 0 END) ecom_total,
                    u.email
                    FROM travel_quote_request q, users u
                    WHERE q.advisor_id=u.id AND q.quote_status_id NOT IN (9,35)
                    AND q.created_at BETWEEN '2021-05-30 00:00:00' and '2022-06-05 23:59:59'
                    AND q.renewal_import_code IS NULL
                    GROUP BY q.advisor_id) a;");
        dd($stats);


        // $startDate = Carbon::now()->startOfDay()->toDateTimeString();
        // $endDate = Carbon::now()->endOfDay()->toDateTimeString();
        // if (isset($request->startDate) && !empty($request->startDate)
        //     && isset($request->endDate) && !empty($request->endDate)) {
        //     $startDate = Carbon::parse($request->startDate)->startOfDay()->toDateTimeString();
        //     $endDate = Carbon::parse($request->endDate)->endOfDay()->toDateTimeString();
        // }


        // $customers = Customer::whereBetween('created_at', [$startDate, $endDate]);

        // // $ecomLeadCount = DB::table('car_quote_request')->where('is_ecommerce', true)
        // //                 ->whereBetween('created_at', [$startDate, $endDate]);

        // $fakeLeads = DB::table('car_quote_request')
        //                  ->join('quote_status', 'quote_status.id', '=', 'car_quote_request.quote_status_id')
        //                  ->where('quote_status.text', 'New Lead')
        //                  ->orWhere('quote_status.text', 'Fake')
        //                  ->whereBetween('car_quote_request.created_at', [$startDate, $endDate]);

        // $carQuotes = DB::table('car_quote_request')->whereBetween('created_at', [date($startDate), date($endDate)])
        //                 ->GroupBy('customer_id');

        return response()->json([
            'totalCustomers' => $customers->count(),
            'totalCarQuotes' => $carQuotes->count(),
            'totalEcommerceLeads' => 0,
            'totalFakeLeads' => $fakeLeads->count(),
        ]);
    }
}

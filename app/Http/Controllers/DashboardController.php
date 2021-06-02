<?php
namespace App\Http\Controllers;

use App\Models\CarQuote;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Http\Request;

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
        $startDate = Carbon::now()->startOfDay()->toDateTimeString();
        $endDate = Carbon::now()->endOfDay()->toDateTimeString();
        if (isset($request->startDate) && !empty($request->startDate)
            && isset($request->endDate) && !empty($request->endDate)) {
            $startDate = Carbon::parse($request->startDate)->startOfDay()->toDateTimeString();
            $endDate = Carbon::parse($request->endDate)->endOfDay()->toDateTimeString();
        }

        $customers = Customer::whereBetween('created_at', [$startDate, $endDate]);
        $carQuotes = CarQuote::whereBetween('created_at', [$startDate, $endDate])->GroupBy('customer_id');
        return response()->json([
            'totalCustomers' => $customers->count(),
            'totalCarQuotes' => $carQuotes->count(),
        ]);
    }
}

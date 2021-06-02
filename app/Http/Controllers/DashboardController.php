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
        $date = Carbon::now();

        $customers = Customer::whereDate('created_at', $date)->count();

        $carQuotes = CarQuote::whereDate('created_at', $date)->GroupBy('customer_id')->count();
        return view('dashboard', compact('customers', 'carQuotes'));
    }

    public function dashboardStats(Request $request)
    {
        // return $request->startDate.' '.$request->endDate;
        $customers = Customer::whereBetween('created_at', [$request->startDate, $request->endDate])->count();
        $carQuotes = CarQuote::whereBetween('created_at', [$request->startDate, $request->endDate])->GroupBy('customer_id')->count();
        return response()->json([
            'totalCustomers' => $customers,
            'totalCarQuotes' => $carQuotes,
        ]);
    }
}

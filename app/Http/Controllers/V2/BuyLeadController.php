<?php

namespace App\Http\Controllers\V2;

use App\Enums\PermissionsEnum;
use App\Enums\QuoteTypes;
use App\Exports\BuyLeadsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\BuyLeads\BuyLeadsRateFetchRequest;
use App\Http\Requests\BuyLeads\RequestBuyLeadsRequest;
use App\Services\BuyLeads\BuyLeadService;
use App\Services\Logger\LoggerService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BuyLeadController extends Controller
{
    public function __construct(public BuyLeadService $buyLeadService)
    {
        $this->middleware('permission:'.PermissionsEnum::BUY_LEADS, ['only' => ['show', 'tracking']]);
        $this->middleware('permission:'.PermissionsEnum::BUY_LEADS_EXPORT, ['only' => ['export', 'exportBuyLeadsData']]);
    }

    public function fetchRate(BuyLeadsRateFetchRequest $request)
    {
        $data['maxCapacity'] = $this->buyLeadService->getBlLeadRemainingLimit($request->getQuoteType());
        $data['isMaxCapReached'] = $data['maxCapacity'] === 0;
        $data['requestAlreadySubmitted'] = ! $data['isMaxCapReached'] && $this->buyLeadService->isRequestAlreadySubmitted($request->getQuoteType());
        $data['maxCapacity'] = $data['maxCapacity'] === 'DISABLED' ? 0 : $data['maxCapacity'];

        $config = $this->buyLeadService->findConfigCost($request->getQuoteType());
        if (is_string($config)) {
            $data['cost'] = 0;
        } else {
            $data['cost'] = $config[0];
        }

        return response()->json($data);
    }

    public function show()
    {
        $data['lobs'] = collect(QuoteTypes::withLabels())->filter(fn ($type) => in_array($type['value'], [QuoteTypes::CAR->value, QuoteTypes::HEALTH->value]))->values()->toArray();
        $data['requests'] = $this->buyLeadService->getActiveRequests();

        return inertia('BuyLeads/BuyLeadsRequest', $data);
    }

    public function submit(RequestBuyLeadsRequest $request)
    {
        if ($message = $this->buyLeadService->requestBuyLeads($request)) {
            return response()->json(['message' => $message], 422);
        }

        return response()->json(['message' => 'Buy leads requested successfully']);
    }

    public function tracking()
    {
        $quoteType = QuoteTypes::tryFrom(request()->get('quote_type'));
        $data['lobs'] = collect(QuoteTypes::withLabels())->filter(fn ($type) => in_array($type['value'], [QuoteTypes::CAR->value, QuoteTypes::HEALTH->value]))->values()->toArray();
        [$startDate, $endDate] = request('date');

        if ($quoteType && $startDate && $endDate) {
            if (request()->has('export')) {
                return $this->buyLeadService->exportTrackingReportPDF($quoteType, Carbon::parse($startDate), Carbon::parse($endDate));
            } else {
                $data['list'] = $this->buyLeadService->getTrackingData($quoteType, Carbon::parse($startDate), Carbon::parse($endDate));
            }
        }

        return inertia('BuyLeads/BuyLeadsTracking', $data);
    }

    public function export()
    {
        return inertia('BuyLeads/ExportBuyLeads');
    }

    public function exportBuyLeadsData(Request $request)
    {
        $startDate = Carbon::parse($request->input('start_date'))->startOfDay();
        $endDate = Carbon::parse($request->input('end_date'))->endOfDay();

        // Query 1
        $results1 = DB::table('buy_lead_requests as blr')
            ->selectRaw('
            users.name AS advisor,
            SUM(blr.requested_count) AS requested_count,
            SUM(blr.allocated_count) AS allocated_count,
            GROUP_CONCAT(DISTINCT t.name ORDER BY t.name SEPARATOR ", ") AS teams,
            blr.created_at,
            blr.quote_type_id
        ')
            ->join('users', 'users.id', '=', 'blr.user_id')
            ->join('user_team as ut', 'blr.user_id', '=', 'ut.user_id')
            ->join('teams as t', 'ut.team_id', '=', 't.id')
            ->whereBetween('blr.created_at', [$startDate, $endDate])
            ->whereIn('t.parent_team_id', [3, 8])
            ->groupBy('blr.user_id')
            ->get();

        // Query 2
        $results2 = DB::table('buy_lead_request_logs as blrl')
            ->selectRaw("
            CASE
                WHEN blr.quote_type_id = 1 THEN cqr.code
                WHEN blr.quote_type_id = 3 THEN hqr.code
                ELSE NULL
            END AS RefID,
            CASE
                WHEN blr.quote_type_id = 1 THEN 'NA'
                WHEN blr.quote_type_id = 3 THEN hqr.health_team_type
                ELSE NULL
            END AS TeamType,
            CASE
                WHEN blr.quote_type_id = 1 THEN (CASE WHEN cqr.sic_advisor_requested = 1 THEN 'Yes' ELSE 'No' END)
                WHEN blr.quote_type_id = 3 THEN (CASE WHEN hqr.sic_advisor_requested = 1 THEN 'Yes' ELSE 'No' END)
                ELSE NULL
            END AS advisor_requested,
            CASE
                WHEN blr.quote_type_id = 1 THEN (CASE WHEN cqr.assignment_type = 5 THEN 'Bought Lead' ELSE 'ReAssigned as Bought Lead' END)
                WHEN blr.quote_type_id = 3 THEN (CASE WHEN hqr.assignment_type = 5 THEN 'Bought Lead' ELSE 'ReAssigned as Bought Lead' END)
                ELSE NULL
            END AS assignment_type,
            CASE
                WHEN blr.quote_type_id = 1 THEN cqr.created_at
                WHEN blr.quote_type_id = 3 THEN hqr.created_at
                ELSE NULL
            END AS lead_created_at,
            CASE
                WHEN blr.quote_type_id = 1 THEN cqr.premium
                WHEN blr.quote_type_id = 3 THEN hqr.premium
                ELSE NULL
            END AS premium,
            users.name AS advisor,
            qs.text AS lead_status,
            blr.cost_per_lead AS cost,
            (
                SELECT GROUP_CONCAT(DISTINCT t2.name ORDER BY t2.name SEPARATOR ', ')
                FROM user_team ut2
                JOIN teams t2 ON ut2.team_id = t2.id
                WHERE ut2.user_id = blr.user_id AND t2.parent_team_id IN (3,8)
            ) AS teams,
            blrl.re_assigned_at,
            blrl.created_at
        ")
            ->join('buy_lead_requests as blr', 'blr.id', '=', 'blrl.buy_lead_request_id')
            ->join('users', 'users.id', '=', 'blr.user_id')
            ->leftJoin('car_quote_request as cqr', function ($join) {
                $join->on('cqr.id', '=', 'blrl.quote_id')
                    ->where('blr.quote_type_id', '=', 1);
            })
            ->leftJoin('health_quote_request as hqr', function ($join) {
                $join->on('hqr.id', '=', 'blrl.quote_id')
                    ->where('blr.quote_type_id', '=', 3);
            })
            ->leftJoin('quote_status as qs', function ($join) {
                $join->on('qs.id', '=', DB::raw('CASE WHEN blr.quote_type_id = 1 THEN cqr.quote_status_id WHEN blr.quote_type_id = 3 THEN hqr.quote_status_id ELSE NULL END'));
            })
            ->whereBetween('blrl.created_at', [$startDate, $endDate])
            ->groupBy('blrl.quote_id', 'blrl.quote_type_id')
            ->orderBy('blrl.created_at', 'asc')
            ->get();

        // File paths
        $start = $startDate->format('d-m-Y');
        $end = $endDate->format('d-m-Y');
        $file1 = "buy_leads_summary_{$start}_{$end}.xlsx";
        $file2 = "buy_leads_detailed_{$start}_{$end}.xlsx";
        // Create ZIP using robust logic (mirroring CentralController)
        $zipFileName = 'buy_leads_export_'.now()->format('Ymd_His').'.zip';
        $zipFilePath = storage_path('temp/'.$zipFileName);
        $zip = new \ZipArchive;

        if ($zip->open($zipFilePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return response()->json(['error' => 'Could not create ZIP file.'], 500);
        }

        try {
            $summaryExport = app(BuyLeadsExport::class, ['data' => $results1, 'type' => 'summary'])->download('buy_leads_summary.xlsx');
            $detailedExport = app(BuyLeadsExport::class, ['data' => $results2, 'type' => 'detailed'])->download('buy_leads_detailed.xlsx');
            $files = [
                ['path' => $summaryExport->getFile()->getRealPath(), 'name' => $file1],
                ['path' => $detailedExport->getFile()->getRealPath(), 'name' => $file2],
            ];

            foreach ($files as $file) {
                if (file_exists($file['path'])) {
                    $zip->addFile($file['path'], $file['name']);
                } else {
                    LoggerService::info("File does not exist: {$file['path']}");
                }
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error processing exports: '.$e->getMessage()], 500);
        }

        $zip->close();

        return response()->download($zipFilePath)->deleteFileAfterSend(true);
    }
}

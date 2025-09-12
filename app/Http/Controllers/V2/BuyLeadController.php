<?php

namespace App\Http\Controllers\V2;

use App\Enums\PermissionsEnum;
use App\Enums\QuoteTypes;
use App\Enums\TeamNameEnum;
use App\Exports\BuyLeadsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\BuyLeads\BuyLeadsRateFetchRequest;
use App\Http\Requests\BuyLeads\RequestBuyLeadsRequest;
use App\Services\BuyLeads\BuyLeadService;
use App\Services\Logger\LoggerService;
use App\Services\UserService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BuyLeadController extends Controller
{
    public function __construct(
        public BuyLeadService $buyLeadService,
        private readonly UserService $userService
    ) {
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
        // Step 1: Validate and parse dates
        $startDate = Carbon::parse($request->input('start_date'))->startOfDay();
        $endDate = Carbon::parse($request->input('end_date'))->endOfDay();

        // Step 2: Get eligible parent teams
        $eligibleParentTeamIds = [
            getTeamId(TeamNameEnum::CAR),
            getTeamId(TeamNameEnum::HEALTH),
        ];

        // Step 3: Run queries
        $summaryResults = $this->getSummaryResults($startDate, $endDate, $eligibleParentTeamIds);
        $detailResults = $this->getDetailResults($startDate, $endDate, $eligibleParentTeamIds);

        // Step 4: Export to Excel and zip files
        return $this->exportAsZip($summaryResults, $detailResults, $startDate, $endDate);
    }

    /**
     * Build and run summary query
     */
    private function getSummaryResults(Carbon $startDate, Carbon $endDate, array $teamIds)
    {
        return DB::table('buy_lead_requests as blr')
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
            ->whereIn('t.parent_team_id', $teamIds)
            ->groupBy('blr.user_id')
            ->get();
    }

    /**
     * Build and run detail query
     */
    private function getDetailResults(Carbon $startDate, Carbon $endDate, array $teamIds)
    {
        return DB::table('buy_lead_request_logs as blrl')
            ->selectRaw("
                CASE
                    WHEN blr.quote_type_id = 1 THEN cqr.code
                    WHEN blr.quote_type_id = 3 THEN hqr.code
                    ELSE NULL
                END AS RefID,
                CASE
                    WHEN blr.quote_type_id = 1 THEN IF(cqr.sic_advisor_requested = 1, 'Yes', 'No')
                    WHEN blr.quote_type_id = 3 THEN IF(hqr.sic_advisor_requested = 1, 'Yes', 'No')
                    ELSE NULL
                END AS advisor_requested,
                CASE
                    WHEN blr.quote_type_id = 1 THEN IF(cqr.assignment_type = 5, 'Bought Lead', 'ReAssigned as Bought Lead')
                    WHEN blr.quote_type_id = 3 THEN IF(hqr.assignment_type = 5, 'Bought Lead', 'ReAssigned as Bought Lead')
                    ELSE NULL
                END AS assignment_type,
                CASE
                    WHEN blr.quote_type_id = 1 THEN cqr.created_at
                    WHEN blr.quote_type_id = 3 THEN hqr.created_at
                    ELSE NULL
                END AS lead_created_at,
                users.name AS advisor,
                users.employee_code AS advisor_code,
                users.email AS advisor_email,
                departments.name AS department,
                qs.text AS lead_status,
                blr.cost_per_lead AS cost,
                (
                    SELECT GROUP_CONCAT(DISTINCT t2.name ORDER BY t2.name SEPARATOR ', ')
                    FROM user_team ut2
                    JOIN teams t2 ON ut2.team_id = t2.id
                    WHERE ut2.user_id = blr.user_id AND t2.parent_team_id IN (".implode(',', $teamIds).')
                ) AS teams
            ')
            ->join('buy_lead_requests as blr', 'blr.id', '=', 'blrl.buy_lead_request_id')
            ->join('users', 'users.id', '=', 'blr.user_id')
            ->leftJoin('departments', 'departments.id', '=', 'users.department_id')
            ->leftJoin('car_quote_request as cqr', function ($join) {
                $join->on('cqr.id', '=', 'blrl.quote_id')->where('blr.quote_type_id', '=', 1);
            })
            ->leftJoin('health_quote_request as hqr', function ($join) {
                $join->on('hqr.id', '=', 'blrl.quote_id')->where('blr.quote_type_id', '=', 3);
            })
            ->leftJoin('quote_status as qs', function ($join) {
                $join->on('qs.id', '=', DB::raw('CASE WHEN blr.quote_type_id = 1 THEN cqr.quote_status_id WHEN blr.quote_type_id = 3 THEN hqr.quote_status_id ELSE NULL END'));
            })
            ->whereBetween('blrl.created_at', [$startDate, $endDate])
            ->groupBy('blrl.quote_id', 'blrl.quote_type_id')
            ->orderBy('blrl.created_at', 'asc')
            ->get();
    }

    /**
     * Export summary + detail to Excel, zip them, and return response
     */
    private function exportAsZip($summaryResults, $detailResults, Carbon $startDate, Carbon $endDate)
    {
        $start = $startDate->format('d-m-Y');
        $end = $endDate->format('d-m-Y');

        $file1 = "buy_leads_summary_{$start}_{$end}.xlsx";
        $file2 = "buy_leads_detailed_{$start}_{$end}.xlsx";
        $zipFileName = 'buy_leads_export_'.now()->format('Ymd_His').'.zip';
        $zipFilePath = storage_path("temp/{$zipFileName}");

        $zip = new \ZipArchive;
        if ($zip->open($zipFilePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return response()->json(['error' => 'Could not create ZIP file.'], 500);
        }

        try {
            $summaryExport = app(BuyLeadsExport::class, ['data' => $summaryResults, 'type' => 'summary'])->download('summary.xlsx');
            $detailedExport = app(BuyLeadsExport::class, ['data' => $detailResults, 'type' => 'detailed'])->download('detailed.xlsx');

            $files = [
                ['path' => $summaryExport->getFile()->getRealPath(), 'name' => $file1],
                ['path' => $detailedExport->getFile()->getRealPath(), 'name' => $file2],
            ];

            foreach ($files as $file) {
                if (file_exists($file['path'])) {
                    $zip->addFile($file['path'], $file['name']);
                } else {
                    LoggerService::warning("File does not exist: {$file['path']}");
                }
            }
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Error processing exports: '.$e->getMessage()], 500);
        }

        $zip->close();

        return response()->download($zipFilePath)->deleteFileAfterSend(true);
    }

    /**
     * Update employee codes for advisors with null codes
     */
    public function updateEmployeeCodes(Request $request)
    {
        try {
            // Step 1: Validate and parse dates
            $startDate = Carbon::parse($request->input('start_date'))->startOfDay();
            $endDate = Carbon::parse($request->input('end_date'))->endOfDay();

            // Step 2: Get eligible parent teams
            $eligibleParentTeamIds = [
                getTeamId(TeamNameEnum::CAR),
                getTeamId(TeamNameEnum::HEALTH),
            ];

            // Step 3: Get advisor emails with null codes from EXPORT dataset
            $emailsWithNullCodes = $this->buyLeadService->getAdvisorEmailsWithNullCodes(
                $startDate,
                $endDate,
                $eligibleParentTeamIds
            );

            if (empty($emailsWithNullCodes)) {
                return response()->json([
                    'success' => true,
                    'message' => 'No employees with null codes found',
                    'emails_processed' => 0,
                ]);
            }

            LoggerService::info('UpdateEmployeeCodes: Processing emails with null codes', [
                'emails_count' => count($emailsWithNullCodes),
                'emails' => $emailsWithNullCodes,
                'date_range' => [
                    'start_date' => $startDate->format('Y-m-d H:i:s'),
                    'end_date' => $endDate->format('Y-m-d H:i:s'),
                ],
            ]);

            // Step 4: Fetch user codes from HRM service
            $hrmResponse = $this->userService->fetchUserCodes($emailsWithNullCodes);

            if (! ($hrmResponse['success'] ?? false) || empty($hrmResponse['results'])) {
                LoggerService::warning('UpdateEmployeeCodes: Failed to fetch HRM codes', [
                    'hrm_response' => $hrmResponse,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to fetch employee codes from HRM service',
                    'emails_processed' => count($emailsWithNullCodes),
                ], 422);
            }

            $updatedCodesCount = collect($hrmResponse['results'])
                ->filter(fn ($r) => $r['status'] === 'updated' && isset($r['new_employee_code']))
                ->count();

            LoggerService::info('UpdateEmployeeCodes: HRM codes fetched successfully', [
                'emails_processed' => count($emailsWithNullCodes),
                'codes_updated' => $updatedCodesCount,
                'hrm_response_summary' => [
                    'success' => $hrmResponse['success'] ?? false,
                    'results_count' => count($hrmResponse['results'] ?? []),
                ],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Employee codes updated successfully',
                'emails_processed' => count($emailsWithNullCodes),
                'codes_updated' => $updatedCodesCount,
            ]);

        } catch (\Exception $e) {
            LoggerService::error('UpdateEmployeeCodes: Exception occurred', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating employee codes',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

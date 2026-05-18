<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStorageEnums;
use App\Enums\FetchPlansStatuses;
use App\Enums\GenericRequestEnum;
use App\Enums\ProcessStatusCode;
use App\Enums\QuoteTypeShortCode;
use App\Enums\RenewalProcessStatuses;
use App\Enums\RenewalsUploadType;
use App\Enums\RolesEnum;
use App\Enums\SkipPlansEnum;
use App\Exports\RenewalFailedValidationExport;
use App\Exports\RenewalHealthUpdateFailedValidationExport;
use App\Exports\RenewalHomeFailedValidationExport;
use App\Http\Requests\RenewalsUploadRequest;
use App\Http\Requests\ScheduleRenewalsOcbRequest;
use App\Imports\RenewalsImport;
use App\Imports\RenewalsImportUpdate;
use App\Jobs\CQF\ProcessNonMotorCQFOrchestratorJob;
use App\Jobs\Renewals\FetchHomeRenewalsPlansJob;
use App\Jobs\Renewals\FetchRenewalsPlansJob;
use App\Jobs\ScheduleRenewalOcbEmails;
use App\Models\CarQuote;
use App\Models\HealthQuote;
use App\Models\HomeQuote;
use App\Models\QuoteType;
use App\Models\RenewalQuoteProcess;
use App\Models\RenewalsBatchEmails;
use App\Models\RenewalStatusProcess;
use App\Models\RenewalsUploadLeads;
use App\Models\User;
use App\Repositories\CarQuoteRepository;
use App\Services\Logger\LoggerService;
use App\Services\RenewalsUploadService;
use App\Traits\TeamHierarchyTrait;
use Carbon\Carbon;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;
use Laravel\SerializableClosure\Exceptions\PhpVersionNotSupportedException;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Traits\HasRoles;

class RenewalsUploadController extends Controller
{
    use TeamHierarchyTrait;

    private $renewalsUploadFileService;

    public function __construct(RenewalsUploadService $renewalsUploadFileService)
    {
        $this->renewalsUploadFileService = $renewalsUploadFileService;

    }

    /**
     * process upload and create import.
     *
     * @return Application|RedirectResponse|Redirector
     */
    public function renewalsUploadCreate(RenewalsUploadRequest $request)
    {
        return $this->renewalsUploadFileService->renewalsUploadCreate($request->validated());
    }

    /**
     * process upload and update import.
     *
     * @return Application|RedirectResponse|Redirector
     */
    public function renewalsUploadUpdate(RenewalsUploadRequest $request)
    {
        return $this->renewalsUploadFileService->renewalsUploadUpdate($request->validated());
    }

    /**
     * fetch plans batch wise.
     *
     * @param  $id
     * @return Application|RedirectResponse|Redirector
     */
    public function fetchPlans($batch)
    {
        if (! auth()->user()->hasAnyRole([RolesEnum::RenewalsManager, RolesEnum::Admin, RolesEnum::Engineering])) {
            return abort(403);
        }

        $totalPending = RenewalQuoteProcess::where([
            'quote_type' => QuoteTypeShortCode::CAR,
            'batch' => $batch,
            'status' => RenewalProcessStatuses::PROCESSED,
            'type' => RenewalsUploadType::UPDATE_LEADS,
            'fetch_plans_status' => FetchPlansStatuses::PENDING,
        ])->count();

        if ($totalPending > 0) {
            $renewalStatusProcess = RenewalStatusProcess::create([
                'batch' => $batch,
                'total_leads' => $totalPending,
                'status' => ProcessStatusCode::IN_PROGRESS,
                'user_id' => auth()->id(),
            ]);

            FetchRenewalsPlansJob::dispatch($renewalStatusProcess, $batch);

            return redirect()->route('batch-plans-processes', $batch)->with('success', 'Fetch plans is started for batch '.$batch);
        }

        return redirect()->route('batch-plans-processes', $batch)->with('error', 'No pending leads available to fetch plans');
    }

    public function fetchPlansNonMotor($batch, $quoteType)
    {

        LoggerService::info(message: 'FetchPlansNonMotor FN: fetchPlansNonMotor Fetch plans started', extra: [
            'batch' => $batch,
            'quoteType' => $quoteType,
        ]);

        $totalPending = RenewalQuoteProcess::where([
            'quote_type' => $quoteType,
            'renewal_batch_id' => $batch,
            'status' => RenewalProcessStatuses::PROCESSED,
            'type' => RenewalsUploadType::UPDATE_LEADS,
            'fetch_plans_status' => FetchPlansStatuses::PENDING,
        ])->count();

        if ($totalPending) {
            $renewalStatusProcess = RenewalStatusProcess::create([
                'renewal_batch_id' => $batch,
                'total_leads' => $totalPending,
                'status' => ProcessStatusCode::IN_PROGRESS,
                'user_id' => auth()->id(),
            ]);

            // Dispatch the job based on the quote type
            if ($quoteType == QuoteTypeShortCode::HOM) {
                FetchHomeRenewalsPlansJob::dispatch($renewalStatusProcess->id, $batch, $quoteType);
            }

            return redirect()->route('batch-plans-processes.non.motor', [$batch, $quoteType])->with('success', 'Fetch plans is started for batch '.$batch);
        }

        return redirect()->route('batch-plans-processes.non.motor', [$batch, $quoteType])->with('error', 'No pending leads available to fetch plans');

    }

    /**
     * renew the quote against the customer.
     */
    public function renewalsUploadProcess(Request $request)
    {
        $this->validate($request, [
            'file_name' => 'required|file|mimetypes:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/excel|max:2048',
        ]);

        if ($request->hasFile('file_name')) {
            // Check if file already uploaded
            $existingFile = RenewalsUploadLeads::where('file_name', $request->file_name->getClientOriginalName())->first();

            if ($existingFile) {
                // Returning with error message if file already uploaded
                return back()->withInput()->with('message', 'File already been uploaded. Please try again with different file.');
            }

            // Check upload type
            if ($request->renewals_upload_type == RenewalsUploadType::CREATE_LEADS) {
                // Generate unique code for file record
                $renewalImportCode = $this->renewalsUploadFileService->generateRandomString();
            } else {
                $renewalImportCode = $request->renewal_import_code;

                $this->validate($request, [
                    'renewal_import_code' => 'required',
                ]);

                // Check leads against renewal_import_code
                $carQuoteRequest = CarQuote::where('renewal_import_code', '=', $request->renewal_import_code)->get();
                $carQuoteRequestCount = $carQuoteRequest->count();
                if ($carQuoteRequestCount == 0) {
                    return back()->withInput()->with('message', 'No leads found for renewal import code: '.$request->renewal_import_code);
                }
            }

            // Getting file name only
            $fileNameOriginal = $request->file_name->getClientOriginalName();
            // Generating name for file for azure usage
            $fileNameAzure = get_guid().'_'.$fileNameOriginal;

            // Uploading file to Azure
            $filePathAzure = $request->file('file_name')->storeAs('renewals', $fileNameAzure, 'azureIMPrivate');

            // creating upload record in database before upload start
            $this->createRenewalUploadLeadRecord($fileNameOriginal, $filePathAzure);

            if ($request->renewals_upload_type == RenewalsUploadType::CREATE_LEADS) {
                $renewalsUpload = new RenewalsImport($this->renewalsUploadFileService, $request->file_name->getClientOriginalName(), $renewalImportCode, $request->renewals_upload_type); // Send the file name to the import class
            } else {
                $renewalsUpload = new RenewalsImportUpdate($this->renewalsUploadFileService, $request->file_name->getClientOriginalName(), $renewalImportCode, $request->renewals_upload_type); // Send the file name to the import class
            }

            $renewalsUpload->import(request()->file('file_name')); // Initiate the import

            $countRows = $renewalsUpload->getRowCount(); // Get the number of rows imported
            $countErrors = $renewalsUpload->failures()->count(); // Get the number of errors
            $totalRows = $countRows + $countErrors; // Get the total number of rows

            // update the record with the number of rows imported and errors
            $renewalsUploadLead = RenewalsUploadLeads::where('file_name', $fileNameOriginal)->first();
            $renewalsUploadLead->total_records = $totalRows;
            $renewalsUploadLead->cannot_upload = $countErrors;
            $renewalsUploadLead->renewal_import_code = $renewalImportCode;
            $renewalsUploadLead->renewal_import_type = $request->renewals_upload_type;
            $renewalsUploadLead->save();

            // Redirect back to the upload page if there are errors
            if ($renewalsUpload->failures()->isNotEmpty() || $countErrors > 30) {
                if ($request->renewals_upload_type == RenewalsUploadType::CREATE_LEADS) {
                    return redirect()->route('renewals-upload-create')->withFailures($renewalsUpload->failures());
                }
                if ($request->renewals_upload_type == RenewalsUploadType::UPDATE_LEADS) {
                    return redirect()->route('renewals-upload-update')->withFailures($renewalsUpload->failures());
                }
            }

            // Redirect back to the upload page if there are no errors
            if ($request->renewals_upload_type == RenewalsUploadType::CREATE_LEADS) {
                return redirect()->route('renewals-upload-create')->with('success', 'Uploaded renewals records has been stored');
            }
            if ($request->renewals_upload_type == RenewalsUploadType::UPDATE_LEADS) {
                return redirect()->route('renewals-upload-update')->with('success', 'Uploaded renewals records has been stored');
            }
        }
    }

    private function createRenewalUploadLeadRecord($fileName, $filePathAzure)
    {
        $azureStorageUrl = config('constants.AZURE_IM_STORAGE_URL');
        $azureStorageContainer = config('constants.AZURE_IM_STORAGE_CONTAINER');

        $renewalsUploadLead = new RenewalsUploadLeads;
        $renewalsUploadLead->file_name = $fileName;
        $renewalsUploadLead->file_path = $azureStorageUrl.$azureStorageContainer.'/'.$filePathAzure;
        $renewalsUploadLead->status = ProcessStatusCode::IN_PROGRESS;
        $renewalsUploadLead->good = 0;
        $renewalsUploadLead->created_by_id = auth()->id();
        $renewalsUploadLead->save();
    }

    public function uploadRenewals()
    {
        $azureStorageUrl = config('constants.AZURE_IM_STORAGE_URL');
        $azureStorageContainer = config('constants.AZURE_IM_STORAGE_CONTAINER');

        return inertia('Renewals/Upload', [
            'azureStorageUrl' => $azureStorageUrl,
            'azureStorageContainer' => $azureStorageContainer,
        ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request, RenewalsUploadLeads $renewalsUploadLeads)
    {

        $dataRenewalUpload = $renewalsUploadLeads::select(
            'renewals_upload_leads.id as id',
            'renewals_upload_leads.renewal_import_type as renewal_import_type',
            'renewals_upload_leads.renewal_import_code as renewal_import_code',
            'renewals_upload_leads.file_name as file_name',
            'renewals_upload_leads.total_records as total_records',
            'renewals_upload_leads.good as good',
            'renewals_upload_leads.is_sic as is_sic',
            'renewals_upload_leads.cannot_upload as cannot_upload',
            'renewals_upload_leads.status as status',
            'renewals_upload_leads.created_at as created_at',
            'renewals_upload_leads.updated_at as updated_at',
            'users.name as uploaded_by',
            'renewals_upload_leads.skip_plans'
        )
            ->leftjoin('users', 'users.id', 'renewals_upload_leads.created_by_id')
            ->orderBy('renewals_upload_leads.created_at', 'desc');
        $dataRenewalUpload = $dataRenewalUpload->simplePaginate();

        return inertia('Renewals/UploadedLeads', [
            'leads' => $dataRenewalUpload,
            'EnumGenericNo' => GenericRequestEnum::No,
            'EnumGenericYes' => GenericRequestEnum::Yes,
            'EnumSkipPlansNonGCC' => SkipPlansEnum::NON_GCC,
        ]);
    }

    public function updateRenewals()
    {
        $azureStorageUrl = config('constants.AZURE_IM_STORAGE_URL');
        $azureStorageContainer = config('constants.AZURE_IM_STORAGE_CONTAINER');
        $renewalsUploads = RenewalsUploadLeads::where('renewal_import_type', '=', RenewalsUploadType::CREATE_LEADS)
            ->where('renewal_import_code', '!=', '')
            ->orderBy('created_at', 'desc')->get();

        return inertia('Renewals/Index', [
            'azureStorageUrl' => $azureStorageUrl,
            'azureStorageContainer' => $azureStorageContainer,
        ]);
    }

    public function listRenewalBatches(Request $request)
    {
        if (! auth()->user()->hasAnyRole([RolesEnum::RenewalsManager, RolesEnum::Admin, RolesEnum::Engineering])) {
            return abort(403);
        }

        $query = RenewalQuoteProcess::query()
            ->select('batch as renewal_batch')
            ->where([
                'quote_type' => QuoteTypeShortCode::CAR,
                'type' => RenewalsUploadType::UPDATE_LEADS,
            ]);

        if (! empty($request->batch)) {
            $query->where('batch', $request->batch);
        }

        $renewalQuotes = $query->distinct()
            ->simplePaginate();

        return inertia('Renewals/Batches', [
            'batches' => $renewalQuotes,
        ]);
    }

    public function listRenewalBatchesNonMotor(Request $request)
    {
        $year = $request->year ?? Carbon::now()->year;
        $month = $request->month ?? Carbon::now()->month;

        $lob = $request->lob ?? QuoteTypeShortCode::HOM;
        $batch = $request->batch ?? null;

        $query = RenewalQuoteProcess::query()
            ->with('renewalBatch')
            ->whereHas('renewalBatch', callback: function ($query) use ($year, $month, $batch) {
                $query->when(! empty($batch), function ($query) use ($batch) {
                    return $query->where('name', $batch);
                }, function ($query) use ($year, $month) {
                    return $query->where('year', $year)
                        ->where('month', $month);
                });
            })
            ->where([
                'renewal_quote_processes.quote_type' => $lob,
                'renewal_quote_processes.type' => RenewalsUploadType::UPDATE_LEADS,
            ])->groupBy('renewal_batch_id');

        $renewalQuotes = $query->simplePaginate();

        $lobs = $this->renewalsUploadFileService->getNonMotorLobs();

        $years = array_combine(range((int) date('Y') + 1, 2010), range((int) date('Y') + 1, 2010));

        $months = $this->renewalsUploadFileService->getMonths();

        return inertia('Renewals/NonMotorBatches', [
            'lobs' => $lobs,
            'years' => $years,
            'months' => $months,
            'batches' => $renewalQuotes,
        ]);
    }

    /**
     * fetch plans for all pending quotes.
     *
     * @return Application|Factory|View|never
     */
    public function plansProcesses($batch)
    {
        if (! auth()->user()->hasAnyRole([RolesEnum::RenewalsManager, RolesEnum::Admin, RolesEnum::Engineering])) {
            return abort(403);
        }
        $process = RenewalStatusProcess::query()
            ->where([
                'batch' => $batch,
            ])->with('createdby');
        $process = $process->simplePaginate();

        return inertia('Renewals/PlanProcesses', [
            'process' => $process,
            'batch' => $batch,
        ]);
    }

    public function plansProcessesStatus($batch)
    {
        $query = RenewalQuoteProcess::where([
            'status' => RenewalProcessStatuses::PROCESSED,
            'quote_type' => QuoteTypeShortCode::CAR,
            'batch' => $batch,
            'type' => RenewalsUploadType::UPDATE_LEADS,
            'fetch_plans_status' => FetchPlansStatuses::PENDING,
        ])->whereNotNull('step_errors')->with(['renewalUploadLead', 'carQuote']);
        $process = $query->simplePaginate();

        return inertia('Renewals/PlanProcessesStatus', [
            'renewalLeads' => $process,
            'batch' => $batch,
        ]);
    }

    public function plansProcessesNonMotor($batch, $quoteType)
    {
        $quoteTypeId = QuoteTypeShortCode::getId($quoteType);
        $process = RenewalStatusProcess::with('createdby')
            ->whereHas('renewalBatch', function ($query) use ($batch, $quoteTypeId) {
                $query->where('id', $batch)
                    ->whereHas('personalQuotes', function ($q) use ($quoteTypeId) {
                        $q->where('quote_type_id', $quoteTypeId);
                    });
            })
            ->orderBy('id', 'desc');

        $process = $process->simplePaginate();

        return inertia('Renewals/PlanProcessesNonMotor', [
            'process' => $process,
            'batch' => $batch,
            'quoteType' => $quoteType,
        ]);
    }

    public function batchDetail($batch)
    {
        if (! auth()->user()->hasAnyRole([RolesEnum::RenewalsManager, RolesEnum::Admin, RolesEnum::Engineering])) {
            return abort(403);
        }

        $totalLeads = $this->renewalsUploadFileService->getProcessTotalLeads($batch);
        $totalLeadsCompleted = $this->renewalsUploadFileService->getProcessTotalLeadsWithPlans($batch);
        $hideSendEmailButton = $totalLeadsCompleted != $totalLeads ? 1 : 0;

        $emailBatches = RenewalsBatchEmails::query()
            ->where([
                'batch' => $batch,
            ])->with('createdby');
        $emailBatches = $emailBatches->simplePaginate();

        return inertia('Renewals/BatchDetail', [
            'emailBatches' => $emailBatches,
            'hideSendEmailButton' => $hideSendEmailButton,
            'batch' => $batch,
        ]);
    }

    /**
     * @return Application|RedirectResponse|Redirector
     */
    public function scheduleRenewalsOcb(ScheduleRenewalsOcbRequest $request, $batch)
    {
        $totalLeads = $this->renewalsUploadFileService->getPendingOcbLeadsTotal($batch);

        $renewalBatchEmail = RenewalsBatchEmails::create([
            'batch' => $batch,
            'status' => ProcessStatusCode::PENDING,
            'total_leads' => $totalLeads,
            'total_sent' => 0,
            'total_bounced' => 0,
            'total_failed' => 0,
            'created_by_id' => auth()->id(),
        ]);

        ScheduleRenewalOcbEmails::dispatch($batch, $renewalBatchEmail);

        return redirect('renewals/batches/'.$batch)->with('success', 'Batch has been created and emails are being sent');
    }

    public function validationFailed($id)
    {
        $renewalLeads = RenewalQuoteProcess::where('renewals_upload_lead_id', $id)
            ->with('renewalUploadLead')
            ->whereIn('status', [RenewalProcessStatuses::BAD_DATA, RenewalProcessStatuses::VALIDATION_FAILED])
            ->simplePaginate()->withQueryString();

        $batch_id = $id;

        return inertia('Renewals/ValidationFailed', [
            'renewalLeads' => $renewalLeads,
            'batchId' => $batch_id,
        ]);
    }

    public function downloadValidationFailed($id)
    {
        $renewaUploadLead = RenewalsUploadLeads::findOrFail($id);

        if ($renewaUploadLead->quote_type == QuoteTypeShortCode::HEA && $renewaUploadLead->renewal_import_type == RenewalsUploadType::UPDATE_LEADS) {
            return Excel::download(new RenewalHealthUpdateFailedValidationExport($renewaUploadLead), 'failed_'.$renewaUploadLead->file_name);
        }
        if ($renewaUploadLead->quote_type == QuoteTypeShortCode::HOM && $renewaUploadLead->renewal_import_type == RenewalsUploadType::UPDATE_LEADS) {
            return Excel::download(new RenewalHomeFailedValidationExport($renewaUploadLead), 'failed_'.$renewaUploadLead->file_name);
        }

        return Excel::download(new RenewalFailedValidationExport($renewaUploadLead), 'failed_'.$renewaUploadLead->file_name);
    }

    public function validationPassed($id)
    {
        $renewalLeads = RenewalQuoteProcess::where('renewals_upload_lead_id', $id)
            ->with('renewalUploadLead', 'renewalBatch')
            ->whereIn('status', [RenewalProcessStatuses::VALIDATED, RenewalProcessStatuses::PROCESSED, RenewalProcessStatuses::PLANS_FETCHED, RenewalProcessStatuses::EMAIL_SENT])
            ->simplePaginate()->withQueryString();

        $batch_id = $id;

        return inertia('Renewals/ValidationPassed', [
            'renewalLeads' => $renewalLeads,
            'batchId' => $batch_id,
        ]);
    }

    public function viewQuoteRedirect($renewalProcessId, $leadId)
    {
        $renewalLead = RenewalQuoteProcess::where('id', $leadId)->whereIn('status', [RenewalProcessStatuses::VALIDATED, RenewalProcessStatuses::PROCESSED, RenewalProcessStatuses::PLANS_FETCHED, RenewalProcessStatuses::EMAIL_SENT])->first();
        if (! $renewalLead) {
            return abort(404);
        }
        switch ($renewalLead->quote_type) {
            case QuoteTypeShortCode::CAR:
                $carQuote = CarQuote::where('previous_quote_policy_number', $renewalLead->policy_number)->orderBy('created_at', 'DESC')->first();
                if (! $carQuote) {
                    return abort(404);
                }

                return redirect(config('constants.ECOM_CAR_INSURANCE_QUOTE_URL').$carQuote->uuid);
                break;
            case QuoteTypeShortCode::HEA:
                $healthQuote = HealthQuote::where('previous_quote_policy_number', $renewalLead->policy_number)->orderBy('created_at', 'DESC')->first();
                if (! $healthQuote) {
                    return abort(404);
                }

                return redirect(config('constants.ECOM_HEALTH_INSURANCE_QUOTE_URL').$healthQuote->uuid);
            case QuoteTypeShortCode::HOM:
                $homeQuote = HomeQuote::where('previous_quote_policy_number', $renewalLead->policy_number)->orderBy('created_at', 'DESC')->first();
                if (! $homeQuote) {
                    return abort(404);
                }

                return redirect(config('constants.ECOM_HOME_INSURANCE_QUOTE_URL').$homeQuote->uuid);
                break;
            default:
                return abort(404);
                break;
        }
    }

    /**
     * schedule AML check for non-motor uploaded through renewals process
     *
     * @return void
     *
     * @throws PhpVersionNotSupportedException
     */
    public function search(Request $request)
    {
        $personalQuotes = [];
        $products = QuoteType::all();
        if ($request->page) {
            $personalQuotes = $this->renewalsUploadFileService->getSearch($request);
        }
        $advisors = CarQuoteRepository::getAdvisors();

        return inertia('Renewal/Index', [
            'quotes' => $personalQuotes,
            'advisors' => $advisors,
            'products' => $products,
        ]);
    }

    public function export(Request $request)
    {

        $quotes = $this->renewalsUploadFileService->getExport($request);

        return $quotes;
    }

    public function updateNonMotorRenewals()
    {
        $azureStorageUrl = config('constants.AZURE_IM_STORAGE_URL');
        $azureStorageContainer = config('constants.AZURE_IM_STORAGE_CONTAINER');
        $lobs = $this->renewalsUploadFileService->getNonMotorLobs();

        return inertia('Renewals/NonMotorUploadUpdate', [
            'lobs' => $lobs,
            'azureStorageUrl' => $azureStorageUrl,
            'azureStorageContainer' => $azureStorageContainer,
        ]);
    }

    /**
     * Retry all failed renewal processes for an upload batch
     *
     * @return JsonResponse
     */
    public function retryRenewalProcesses(RenewalsUploadLeads $renewalsUploadLead)
    {
        /** @var User|HasRoles $user */
        $user = Auth::user();
        if (! $user || ! $user->hasAnyRole([RolesEnum::RenewalsManager, RolesEnum::Admin, RolesEnum::Engineering])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $result = $this->renewalsUploadFileService->retryRenewalUploadLeadProcesses($renewalsUploadLead);

        if ($result) {
            return redirect()->route('renewals-uploaded-leads-list')->with('success', 'Renewal processes retry initiated successfully');
        }

        return redirect()->route('renewals-uploaded-leads-list')->with('error', 'Failed to retry renewal processes');
    }

    /**
     * Manually trigger the non-motor CQF renewal process (orchestrator job).
     */
    public function retriggerNonCQFProcess()
    {
        if (! getAppStorageValueByKey(ApplicationStorageEnums::NON_MOTOR_CQF_RENEWALS_SWITCH)) {
            return redirect()->route('renewals-upload-create')->with('error', 'Non-motor CQF renewals feature is currently disabled.');
        }

        ProcessNonMotorCQFOrchestratorJob::dispatch();

        return redirect()->route('renewals-upload-create')->with('success', 'Non-motor CQF renewal process has been queued.');
    }
}

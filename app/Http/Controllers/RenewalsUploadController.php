<?php

namespace App\Http\Controllers;

use App\Enums\FetchPlansStatuses;
use App\Enums\GenericRequestEnum;
use App\Enums\ProcessStatusCode;
use App\Enums\QuoteTypeShortCode;
use App\Enums\RenewalProcessStatuses;
use App\Enums\RenewalsUploadType;
use App\Enums\RolesEnum;
use App\Enums\SkipPlansEnum;
use App\Exports\RenewalFailedValidationExport;
use App\Http\Requests\RenewalsUploadRequest;
use App\Http\Requests\ScheduleRenewalsOcbRequest;
use App\Imports\RenewalsImport;
use App\Imports\RenewalsImportUpdate;
use App\Jobs\Renewals\FetchRenewalsPlansJob;
use App\Jobs\ScheduleRenewalOcbEmails;
use App\Models\CarQuote;
use App\Models\QuoteType;
use App\Models\RenewalQuoteProcess;
use App\Models\RenewalsBatchEmails;
use App\Models\RenewalStatusProcess;
use App\Models\RenewalsUploadLeads;
use App\Repositories\CarQuoteRepository;
use App\Services\RenewalsUploadService;
use App\Traits\TeamHierarchyTrait;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class RenewalsUploadController extends Controller
{
    private $renewalsUploadFileService;

    use TeamHierarchyTrait;

    public function __construct(RenewalsUploadService $renewalsUploadFileService)
    {
        $this->renewalsUploadFileService = $renewalsUploadFileService;
    }

    /**
     * process upload and create import.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function renewalsUploadCreate(RenewalsUploadRequest $request)
    {
        $result = $this->renewalsUploadFileService->renewalsUploadCreate($request->validated());

        return $result;
    }

    /**
     * process upload and update import.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function renewalsUploadUpdate(RenewalsUploadRequest $request)
    {
        $result = $this->renewalsUploadFileService->renewalsUploadUpdate($request->validated());

        return $result;
    }

    /**
     * fetch plans batch wise.
     *
     * @param  $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
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
            $filePathAzure = $request->file('file_name')->storeAs('renewals', $fileNameAzure, 'azureIM');

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
     * @return \Illuminate\Http\Response
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

    /**
     * fetch plans for all pending quotes.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|never
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
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
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

        return Excel::download(new RenewalFailedValidationExport($renewaUploadLead), 'failed_'.$renewaUploadLead->file_name);
    }

    public function validationPassed($id)
    {
        $renewalLeads = RenewalQuoteProcess::where('renewals_upload_lead_id', $id)
            ->with('renewalUploadLead')
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
     * @throws \Laravel\SerializableClosure\Exceptions\PhpVersionNotSupportedException
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
}

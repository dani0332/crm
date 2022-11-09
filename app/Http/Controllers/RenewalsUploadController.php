<?php

namespace App\Http\Controllers;

use App\Enums\ProcessStatusCode;
use App\Enums\QuoteTypeId;
use App\Enums\RenewalProcessStatuses;
use App\Enums\RenewalsUploadType;
use App\Exports\RenewalFailedValidationExport;
use App\Http\Requests\RenewalsUploadRequest;
use App\Imports\RenewalsImport;
use App\Imports\RenewalsImportUpdate;
use App\Jobs\RenewalBatchEmailJob;
use App\Models\CarQuote;
use App\Models\RenewalQuoteProcess;
use App\Models\RenewalsBatchEmails;
use App\Models\RenewalsUploadLeads;
use App\Services\RenewalsUploadService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\Datatables\Datatables;

class RenewalsUploadController extends Controller
{
    private $renewalsUploadFileService;

    public function __construct(RenewalsUploadService $renewalsUploadFileService)
    {
        $this->renewalsUploadFileService = $renewalsUploadFileService;
    }

    /**
     * process upload and create import.
     *
     * @param  RenewalsUploadRequest  $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function renewalsUploadCreate(RenewalsUploadRequest $request)
    {
        $result = $this->renewalsUploadFileService->renewalsUploadCreate($request->validated());

        return redirect('renewals/upload')->with('success', 'Uploaded renewals records has been stored');
    }

    /**
     * process upload and update import.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function renewalsUploadUpdate()
    {
        $result = $this->renewalsUploadFileService->renewalsUploadUpdate(request()->all());

        return redirect('renewals/update')->with('success', 'Uploaded renewals records has been updated');
    }

    /**
     * renew the quote against the customer.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function renewalsUploadProcess(Request $request)
    {
        // validate the file extension
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
                    return redirect('renewals/upload')->withFailures($renewalsUpload->failures());
                }
                if ($request->renewals_upload_type == RenewalsUploadType::UPDATE_LEADS) {
                    return redirect('renewals/update')->withFailures($renewalsUpload->failures());
                }
            }

            // Redirect back to the upload page if there are no errors
            if ($request->renewals_upload_type == RenewalsUploadType::CREATE_LEADS) {
                return redirect('renewals/upload')->with('success', 'Uploaded renewals records has been stored');
            }
            if ($request->renewals_upload_type == RenewalsUploadType::UPDATE_LEADS) {
                return redirect('renewals/update')->with('success', 'Uploaded renewals records has been stored');
            }
        }
    }

    private function createRenewalUploadLeadRecord($fileName, $filePathAzure)
    {
        $azureStorageUrl = config('constants.AZURE_IM_STORAGE_URL');
        $azureStorageContainer = config('constants.AZURE_IM_STORAGE_CONTAINER');

        $renewalsUploadLead = new RenewalsUploadLeads();
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

        return view('renewals.upload', compact('azureStorageUrl', 'azureStorageContainer'));
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, RenewalsUploadLeads $renewalsUploadLeads, Datatables $datatables)
    {
        if ($request->ajax()) {
            $dataRenewalUpload = $renewalsUploadLeads::select(
                'renewals_upload_leads.id as id',
                'renewals_upload_leads.renewal_import_type as renewal_import_type',
                'renewals_upload_leads.renewal_import_code as renewal_import_code',
                'renewals_upload_leads.file_name as file_name',
                'renewals_upload_leads.total_records as total_records',
                'renewals_upload_leads.good as good',
                'renewals_upload_leads.cannot_upload as cannot_upload',
                'renewals_upload_leads.status as status',
                'renewals_upload_leads.created_at as created_at',
                'renewals_upload_leads.updated_at as updated_at',
                'users.name as uploaded_by'
            )
            ->leftjoin('users', 'users.id', 'renewals_upload_leads.created_by_id')
            ->orderBy('renewals_upload_leads.created_at', 'desc');

            return $datatables::of($dataRenewalUpload)
                ->addIndexColumn()
                ->make(true);
        }

        return view('renewals.view');
    }

    public function updateRenewals()
    {
        $azureStorageUrl = config('constants.AZURE_IM_STORAGE_URL');
        $azureStorageContainer = config('constants.AZURE_IM_STORAGE_CONTAINER');

        $renewalsUploads = RenewalsUploadLeads::where('renewal_import_type', '=', RenewalsUploadType::CREATE_LEADS)
        ->where('renewal_import_code', '!=', '')
        ->orderBy('created_at', 'desc')->get();

        return view('renewals.update', compact('azureStorageUrl', 'azureStorageContainer', 'renewalsUploads'));
    }

    public function listRenewalBatches(Request $request, CarQuote $carQuote, Datatables $datatables)
    {
        if ($request->ajax()) {
            $datalRenewalsBatches = $carQuote::select('renewal_batch')
            ->whereNotNull(['renewal_batch', 'renewal_import_code'])
            ->groupBy('renewal_batch')
            ->orderBy('created_at', 'desc');

            return $datatables::of($datalRenewalsBatches)
                ->addIndexColumn()
                ->make(true);
        }

        return view('renewals.batches');
    }

    public function batchDetail($batch)
    {
        $batchEmails = RenewalsBatchEmails::select('id', 'batch', 'total_leads', 'total_sent', 'total_bounced', 'status', 'created_at', 'created_by_id')
        ->where('batch', $batch)
        ->orderBy('created_at', 'desc')
        ->get();

        return view('renewals.batch_detail', compact('batch', 'batchEmails'));
    }

    public function runBatchProcess($batch)
    {
        $batchLeads = CarQuote::select('car_quote_request.id as id')
        ->leftjoin('quote_status as qs', 'qs.id', 'car_quote_request.quote_status_id')
        ->whereNotNull('car_quote_request.previous_quote_policy_number')
        ->where(['car_quote_request.renewal_batch' => $batch])->get();

        $batchLeadsCount = $batchLeads->count();

        if ($batchLeadsCount == 0) {
            return redirect('renewals/batches/'.$batch)->with('message', 'No leads found for this batch');
        }

        $renewalsBatchStatus = new RenewalsBatchEmails();
        $renewalsBatchStatus->batch = $batch;
        $renewalsBatchStatus->status = ProcessStatusCode::IN_PROGRESS;
        $renewalsBatchStatus->total_leads = $batchLeadsCount;
        $renewalsBatchStatus->total_sent = 0;
        $renewalsBatchStatus->total_bounced = 0;
        $renewalsBatchStatus->created_by_id = auth()->id();
        $renewalsBatchStatus->save();

        foreach ($batchLeads as $batchLead) {
            dispatch(new RenewalBatchEmailJob($batchLead->id, $renewalsBatchStatus->id, QuoteTypeId::Car));
        }

        return redirect('renewals/batches/'.$batch)->with('success', 'Batch has been created and emails are being sent');
    }

    public function validationFailed($id)
    {
        $renewalLeads = RenewalQuoteProcess::where('renewals_upload_lead_id', $id)->whereIn('status', [RenewalProcessStatuses::BAD_DATA, RenewalProcessStatuses::VALIDATION_FAILED])->get();

        return view('renewals.validation_failed', compact('renewalLeads'));
    }

    public function downloadValidationFailed($id)
    {
        $renewaUploadLead = RenewalsUploadLeads::findOrFail($id);

        return Excel::download(new RenewalFailedValidationExport($renewaUploadLead), 'failed_'.$renewaUploadLead->file_name);
    }
}

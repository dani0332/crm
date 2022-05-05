<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RenewalsUploadLeads;
use App\Services\RenewalsUploadService;
use App\Imports\RenewalsImport;
use App\Imports\RenewalsImportUpdate;
use App\Models\CarQuote;
use DataTables;
use Auth;
use Config;

class RenewalsUploadController extends Controller
{
    private $renewalsUploadFileService;
    public function __construct(RenewalsUploadService $renewalsUploadFileService)
    {
        $this->renewalsUploadFileService = $renewalsUploadFileService;
    }

    /**
     * renew the quote against the customer
     *
     * @param \Illuminate\Http\Request $request
     */
    public function processRenewalsCSV(Request $request)
    {
        // validate the file extension
        $this->validate($request, [
            'file_name'=> 'required|file|mimetypes:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/excel|max:2048'
        ]);

        if ($request->hasFile('file_name')) {

            // Check if file already uploaded
            $existingFile = RenewalsUploadLeads::where('file_name', $request->file_name->getClientOriginalName())->first();

            if($existingFile) {
                // Returning with error message if file already uploaded
                return back()->withInput()->with('message', 'File already been uploaded. Please try again with different file.');
            }

            // Check upload type
            if($request->renewals_upload_type == 'create') {
                $uploadType = 'Create';
                // Generate unique code for file record
                $renewalImportCode = $this->renewalsUploadFileService->generateRandomString();
            } else {
                $uploadType = 'Update';
                $renewalImportCode = $request->renewal_import_code;

                $this->validate($request, [
                    'renewal_import_code' => 'required',
                ]);

                // Check leads against renewal_import_code
                $carQuoteRequest = CarQuote::where('renewal_import_code', '=', $request->renewal_import_code)->get();
                $carQuoteRequestCount = $carQuoteRequest->count();
                if($carQuoteRequestCount == 0) {
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

            if($request->renewals_upload_type == 'create') {
                $renewalsUpload = new RenewalsImport($this->renewalsUploadFileService, $request->file_name->getClientOriginalName(), $renewalImportCode, $uploadType); // Send the file name to the import class
            } else {
                $renewalsUpload = new RenewalsImportUpdate($this->renewalsUploadFileService, $request->file_name->getClientOriginalName(), $renewalImportCode, $uploadType); // Send the file name to the import class
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
            $renewalsUploadLead->renewal_import_type = $uploadType;
            $renewalsUploadLead->save();

            // Redirect back to the upload page if there are errors
            if ($renewalsUpload->failures()->isNotEmpty() || $countErrors > 30) {
                return redirect("renewals/upload")->withFailures($renewalsUpload->failures());
            }

            // Redirect back to the upload page if there are no errors
            return redirect('renewals/upload')->with('success', 'Uploaded renewals records has been stored');
        }
    }

    private function createRenewalUploadLeadRecord($fileName, $filePathAzure) 
    {
        $azureStorageUrl = Config::get('constants.AZURE_IM_STORAGE_URL');
        $azureStorageContainer = Config::get('constants.AZURE_IM_STORAGE_CONTAINER');

        $renewalsUploadLead = new RenewalsUploadLeads();
        $renewalsUploadLead->file_name = $fileName;
        $renewalsUploadLead->file_path = $azureStorageUrl.$azureStorageContainer.'/'.$filePathAzure;
        $renewalsUploadLead->status = 'Pending';
        $renewalsUploadLead->good = 0;
        $renewalsUploadLead->created_by_id = Auth::user()->id;
        $renewalsUploadLead->save();
    }

    public function uploadRenewals()
    {
        $azureStorageUrl = Config::get('constants.AZURE_IM_STORAGE_URL');
        $azureStorageContainer = Config::get('constants.AZURE_IM_STORAGE_CONTAINER');

        return view('renewals.upload',compact('azureStorageUrl','azureStorageContainer'));
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, RenewalsUploadLeads $renewalsUploadLeads, Datatables $datatables)
    {
        if($request->ajax()) {

            $dataRenewalUpload = $renewalsUploadLeads::select(
                'renewals_upload_leads.id as id',
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
            ->orderBy('renewals_upload_leads.created_at','desc');
            return $datatables::of($dataRenewalUpload)
                ->addIndexColumn()
                ->make(true);
        }

        return view('renewals.view');
    }

    public function updateRenewals()
    {
        $azureStorageUrl = Config::get('constants.AZURE_IM_STORAGE_URL');
        $azureStorageContainer = Config::get('constants.AZURE_IM_STORAGE_CONTAINER');

        $renewalsUploads = RenewalsUploadLeads::where('renewal_import_type', '=', 'Create')
        ->where('renewal_import_code', '!=', '')
        ->orderBy('created_at', 'desc')->get();

        return view('renewals.update',compact('azureStorageUrl','azureStorageContainer','renewalsUploads'));
    }
}

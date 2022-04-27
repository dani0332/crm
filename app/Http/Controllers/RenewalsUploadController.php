<?php

namespace App\Http\Controllers;

use DataTables;
use Illuminate\Http\Request;
use App\Services\RenewalsUploadService;
use App\Imports\RenewalsImport;
use Auth;
use App\Models\RenewalsUploadLeads;
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
    public function processRenewalsCSV(Request $request) {
        // validate the file extension
        $this->validate($request, [
            'file_name' => 'required|mimetypes:text/csv,text/plain,application/csv,text/comma-separated-values,text/anytext,application/octet-stream,application/txt,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet|max:2048',
        ]);
        
        if ($request->hasFile('file_name')) {
            // Check if file already uploaded
            $existingFile = RenewalsUploadLeads::where('file_name', $request->file_name->getClientOriginalName())->first();
            if($existingFile){
                // Returning with error message if file already uploaded
                return redirect("renewals-upload")->with('message', 'File already been uploaded. Please try again with different file.');
            }

            // Getting file name only
            $fileNameOriginal = $request->file_name->getClientOriginalName();
            // Generating name for file for azure usage
            $fileNameAzure = get_guid().'_'.$fileNameOriginal;

            // Uploading file to Azure
            $filePathAzure = $request->file('file_name')->storeAs('renewals', $fileNameAzure, 'azureIM');

            // creating upload record in database before upload start
            $this->createRenewalUploadLeadRecord($fileNameOriginal, $filePathAzure);

            $renewalsUpload = new RenewalsImport($this->renewalsUploadFileService, $request->file_name->getClientOriginalName()); // Send the file name to the import class
            $renewalsUpload->import(request()->file('file_name')); // Initiate the import

            $countRows = $renewalsUpload->getRowCount(); // Get the number of rows imported
            $countErrors = $renewalsUpload->failures()->count(); // Get the number of errors
            $totalRows = $countRows + $countErrors; // Get the total number of rows

            // update the record with the number of rows imported and errors
            $renewalsUploadLead = RenewalsUploadLeads::where('file_name', $fileNameOriginal)->first();
            $renewalsUploadLead->total_records = $totalRows;
            $renewalsUploadLead->cannot_upload = $countErrors;
            $renewalsUploadLead->save();
            
            // Redirect back to the upload page if there are errors
            if ($renewalsUpload->failures()->isNotEmpty() || $countErrors > 50) {
                return redirect("renewals-upload")->withFailures($renewalsUpload->failures());
            }
            // Redirect back to the upload page if there are no errors
            return redirect("renewals-upload")->with('success', 'Uploaded renewals records has been stored');
        }
    }

    private function createRenewalUploadLeadRecord($fileName, $filePathAzure) {

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

    public function uploadRenewals() {
        return view('renewals.upload');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $dataRenewalFiles = [];
        $dataRenewalFiles = RenewalsUploadLeads::select(
            'id', 'file_name', 'file_path', 'total_records', 'good', 'cannot_upload', 'created_at', 'updated_at', 'status',
            )->orderBy('created_at','desc')->get();

        if ($request->ajax()) {
            return DataTables::of($dataRenewalFiles)
                ->addIndexColumn()
                ->make(true);
        }

        return view('renewals.view');
    }
}

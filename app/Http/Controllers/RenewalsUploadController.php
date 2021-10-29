<?php

namespace App\Http\Controllers;

use DataTables;
use Illuminate\Http\Request;
use App\Services\RenewalsUploadService;
use App\Imports\RenewalsImport;
use Auth;
use App\Models\RenewalsUploadLeads;

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

        $this->validate($request, [
            'file_name' => 'required|mimetypes:text/csv,text/plain,application/csv,text/comma-separated-values,text/anytext,application/octet-stream,application/txt,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet|max:2048',
        ]);

        if ($request->hasFile('file_name')) {
            $renewalsUpload = new RenewalsImport($this->renewalsUploadFileService);
            $renewalsUpload->import(request()->file('file_name'));

            $fileNameOriginal = $request->file_name->getClientOriginalName();
            $fileNameAzure = get_guid().'_'.$fileNameOriginal;
            $filePathAzure = $request->file('file_name')->storeAs('/', $fileNameAzure, 'azure');

            $countRows = $renewalsUpload->getRowCount();
            $countErrors = $renewalsUpload->failures()->count();
            $totalRows = $countRows + $countErrors;

            $renewalsUploadLead = new RenewalsUploadLeads();
            $renewalsUploadLead->file_name = $fileNameOriginal;
            $renewalsUploadLead->file_path = $filePathAzure;
            $renewalsUploadLead->good = $countRows;
            $renewalsUploadLead->total_records = $totalRows;
            $renewalsUploadLead->cannot_upload = $countErrors;
            $renewalsUploadLead->created_by_id = Auth::user()->id;
            $renewalsUploadLead->save();

            if ($renewalsUpload->failures()->isNotEmpty()) {
                return redirect("renewals-upload")->withFailures($renewalsUpload->failures());
            }

            return redirect("renewals-upload")->with('success', 'Uploaded renewals records has been stored');
        }
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
        $data =[];
        $data = RenewalsUploadLeads::select(
            'id', 'file_name', 'file_path', 'total_records', 'good', 'cannot_upload', 'created_at', 'updated_at'
            )->orderBy('created_at','desc')->get();
        
        if ($request->ajax()) {
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    return view('renewals.actions', compact('row'))->render();
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        return view('renewals.view');
    }
}

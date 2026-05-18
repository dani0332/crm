<?php

namespace App\Http\Controllers;

use App\Imports\TMLeadsImport;
use App\Models\TmUploadLead;
use App\Services\TMUploadLeadsService;
use Auth;
use Config;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TmUploadLeadController extends Controller
{
    private $teleMarketingUploadLeadsService;

    public function __construct(TMUploadLeadsService $tmUploadLeadsCreateUpdateService)
    {
        $this->teleMarketingUploadLeadsService = $tmUploadLeadsCreateUpdateService;
        $this->middleware('permission:tm-upload-leads-list|tm-upload-leads-create|tm-upload-leads-edit|tm-upload-leads-delete', ['only' => ['index', 'store']]);
        $this->middleware('permission:tm-upload-leads-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:tm-upload-leads-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:tm-upload-leads-delete', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request, TmUploadLead $tmUploadLead)
    {
        $dataTmLeads = $tmUploadLead::select('tm_upload_leads.*', 'users.name as user_name')
            ->leftjoin('users', 'tm_upload_leads.created_by_id', 'users.id')
            ->where('tm_upload_leads.is_deleted', 0)
            ->orderBy('tm_upload_leads.created_at', 'desc')
            ->paginate();

        return inertia('Telemarketing/UploadTmLead/Index', [
            'dataTmLeads' => $dataTmLeads,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        return inertia('Telemarketing/UploadTmLead/Form');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(Request $request)
    {
        // Only storing the file in DB, no operation is performed
        $this->validate($request, [
            'file_name' => 'required|mimetypes:text/csv,text/plain,application/csv,text/comma-separated-values,text/anytext,application/octet-stream,application/txt|max:2048',
        ]);

        if ($request->hasFile('file_name')) {
            $tmLeadsImport = new TMLeadsImport;
            $fileNameOriginal = $request->file_name->getClientOriginalName();
            $fileNameAzure = get_guid().'_'.$fileNameOriginal;
            $filePathAzure = $request->file('file_name')->storeAs('tmleads', $fileNameAzure, 'azureIMPrivate');

            $tmLeadsImport->import(request()->file('file_name'));
            $countRows = $tmLeadsImport->getRowCount();

            if ($tmLeadsImport->failures()->count() > 100) {
                return redirect('telemarketing/tmuploadlead')->with('message', 'Data is not valid in csv file, kindly follow the import instructions, correct the data and import it again.');
            }

            // Disucss with Shahji we are just storing this file in DB, no operation is performed
            $azureStorageUrl = Config::get('constants.AZURE_IM_STORAGE_URL');
            $azureStorageContainer = Config::get('constants.AZURE_IM_STORAGE_CONTAINER');

            $tmUploadLead = new TmUploadLead;
            $tmUploadLead->file_name = $fileNameOriginal;
            $tmUploadLead->file_path = $azureStorageUrl.$azureStorageContainer.'/'.$filePathAzure;
            $tmUploadLead->good = $countRows;
            $tmUploadLead->created_by_id = Auth::user()->id;
            $tmUploadLead->save();

            if ($tmLeadsImport->failures()->isNotEmpty()) {
                return redirect('telemarketing/tmuploadlead/'.$tmUploadLead->id)->withFailures($tmLeadsImport->failures());
            }
        }

        return redirect('telemarketing/tmuploadlead/'.$tmUploadLead->id)->with('success', 'Upload TM Leads file has been stored');
    }

    /**
     * Display the specified resource.
     *
     * @return Response
     */
    public function show(TmUploadLead $tmuploadlead)
    {
        return inertia('Telemarketing/UploadTmLead/Show', [
            'tmuploadlead' => $tmuploadlead,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit(TmUploadLead $tmuploadlead)
    {
        return view('tmuploadlead.edit', compact('tmuploadlead'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @return Response
     */
    public function update(Request $request, TmUploadLead $tmuploadlead)
    {
        $this->validate($request, [
            'file_name' => 'required|mimetypes:text/csv,text/plain,application/csv,text/comma-separated-values,text/anytext,application/octet-stream,application/txt|max:2048',
        ]);

        $tmUploadLeadID = $this->teleMarketingUploadLeadsService->tmUploadLeadsCreateUpdate($request, 'update', $tmuploadlead->id);

        return redirect('telemarketing/tmuploadlead/'.$tmUploadLeadID)->with('success', 'Upload TM Leads file has been updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return Response
     */
    public function destroy(TmUploadLead $tmuploadlead)
    {
        $tmuploadlead->is_deleted = 1;
        $tmuploadlead->save();

        return redirect()->route('tmuploadlead.index')->with('message', 'Upload TM Leads file has been deleted');
    }
}

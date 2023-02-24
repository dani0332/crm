<?php

namespace App\Http\Controllers;

use App\Models\Claim;
use App\Models\ClaimsAttachments;
use Auth;
use Config;
use Illuminate\Http\Request;

class ClaimsAttachmentsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Claim $claim)
    {
        $claims = Claim::orderBy('created_at', 'desc')->get();

        return view('claim.view', compact('claims'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Claim $claim)
    {
        return view('claimsattachments.add', compact('claim'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Claim $claim)
    {
        $CLAIMS_UPLOAD_MIME_TYPES = Config::get('constants.CLAIMS_UPLOAD_MIME_TYPES');
        $this->validate($request, [
            'file_name' => 'mimes:'.$CLAIMS_UPLOAD_MIME_TYPES.'|max:5120',
        ]);

        $claimsAttachment = new ClaimsAttachments();
        $claimsAttachment->claims_id = $claim->id;

        if ($request->hasFile('file_name')) {
            $fileName = get_guid().'_'.$request->file_name->getClientOriginalName();
            $filePath = $request->file('file_name')->storeAs('/', $fileName, 'azure');
            $claimsAttachment->file_name = $fileName;
            $claimsAttachment->file_original_name = $request->file_name->getClientOriginalName();
            $claimsAttachment->file_path = $filePath;
            $claimsAttachment->file_type = $request->file('file_name')->getMimeType();
        }
        $claimsAttachment->created_by_id = Auth::user()->id;
        $claimsAttachment->modified_by_id = Auth::user()->id;
        $claimsAttachment->save();

        if (isset($request->return_to_view)) {
            return redirect('claim/claims/'.$claim->id.'/'.'claim-attachment/'.$claimsAttachment->id)->with('success', 'Claim Attachment has been stored');
        }

        return redirect()->back()->with('success', 'Claim Attachment has been stored');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ClaimsAttachments  $claimsAttachments
     * @return \Illuminate\Http\Response
     */
    public function show(Claim $claim, ClaimsAttachments $claimAttachment)
    {
        return view('claimsattachments.show', compact('claim', 'claimAttachment'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\ClaimsAttachments  $claimsAttachments
     * @return \Illuminate\Http\Response
     */
    public function edit(Claim $claim, ClaimsAttachments $claimAttachment)
    {
        return view('claimsattachments.edit', compact('claim', 'claimAttachment'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ClaimsAttachments  $claimsAttachments
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Claim $claim, ClaimsAttachments $claimAttachment)
    {
        $CLAIMS_UPLOAD_MIME_TYPES = Config::get('constants.CLAIMS_UPLOAD_MIME_TYPES');
        $this->validate($request, [
            'file_name' => 'mimes:'.$CLAIMS_UPLOAD_MIME_TYPES.'|max:5120',
        ]);

        if ($request->file('file_name')) {
            $fileName = get_guid().'_'.$request->file_name->getClientOriginalName();
            $filePath = $request->file('file_name')->storeAs('/', $fileName, 'azure');
            $claimAttachment->file_name = $fileName;
            $claimAttachment->file_original_name = $request->file_name->getClientOriginalName();
            $claimAttachment->file_path = $filePath;
            $claimAttachment->file_type = $request->file('file_name')->getMimeType();
        }
        $claimAttachment->modified_by_id = Auth::user()->id;
        $claimAttachment->save();

        if (isset($request->return_to_view)) {
            return redirect('claim/claims/'.$claim->id.'/'.'claim-attachment/'.$claimAttachment->id)->with('success', 'Claim Attachment has been updated');
        }

        return redirect()->back()->with('success', 'Claim Attachment has been updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ClaimsAttachments  $claimsAttachments
     * @return \Illuminate\Http\Response
     */
    public function destroy(Claim $claim, ClaimsAttachments $claimAttachment)
    {
        $claimAttachment->delete();

        return redirect('claim/claims/'.$claim->id)->with('message', 'Claim Attachment has been deleted');
    }
}

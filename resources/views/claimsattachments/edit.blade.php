@extends('layouts.app')
@section('title','Edit Claim Attachment')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>Edit Claim Attachment</h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a href="{{ route('claims.index') }}/{{$claim->id}}" class="btn btn-warning btn-sm">Got back to Claim</a></li>
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <br />
                @if(session()->has('success'))
                    <div class="alert alert-success">{{ session()->get('success') }}</div>
                @endif
                <form id="demo-form2" method='post' action="{{ route('claims.claim-attachment.update',['claim'=>$claim->id,'claim_attachment'=>$claimAttachment->id]) }}" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left" autocomplete="off">
                    {{csrf_field()}}
                    @method('PUT')
                    <div class="item form-group">
                        <div class="col">
                            <span class="col-form-label col-md-6 col-sm-6">File Name </span>
                            <input type="file" id="file_name" name="file_name" class="form-control form-control-sm" accept='.jpg, .jpeg, .png, .bmp, .webp, .pdf, .doc, .docx, application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document' />
                            @if($claimAttachment->file_name != '')
                                <div class="col-form-label col-md-6 col-sm-6"><a href="{{ \Config::get('constants.azure_storage_url').'myrewards/'.$claimAttachment->file_name }}" target="_blank">{{$claimAttachment->file_original_name}}</a></div>
                            @else
                                <div class="col-form-label col-md-6 col-sm-6">File not uploaded!</div>
                            @endif
                            @if ($errors->has('file_name'))
                                <span class="text-danger">{{ $errors->first('file_name') }}</span>
                            @endif
                        </div>
                        <div class="col">

                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <b>Important Note: Max upload size is 5 MB, allowed formats: jpg, jpeg, png, bmp, webp, pdf, doc, docx.</b>
                        </div>
                        <div class="col">
                            
                        </div>
                    </div>
                    <div id='redirect_to_view_div'></div>
                    <div class="ln_solid"></div>
                    <div class="row">
                        <div class="col-auto mr-auto"></div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-warning btn-sm">Update & Continue Updating</button> <button type="submit" class="btn btn-warning btn-sm" id="return_to_view">Update</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

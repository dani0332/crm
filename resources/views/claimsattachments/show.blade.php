@extends('layouts.app')
@section('title','Claim Attachment Detail')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>Claim Attachment Detail</h2>
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
                    <form id="demo-form2" method='post' enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left" autocomplete="off">
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="file_name"><b> File Name </b></label>
                            <div class="col-md-6 col-sm-6 ">
                                @if($claimAttachment->file_name != '')
                                <p class="label-align-center"><a href="{{ \Config::get('constants.azure_storage_url').'myrewards/'.$claimAttachment->file_name }}" target="_blank">{{$claimAttachment->file_original_name}}</a></p>
                                @else
                                <p class="label-align-center">File not uploaded!</p>
                                @endif
                            </div>
                        </div>
                        <div class="col">

                        </div>
                    </div>
                    <div class="ln_solid"></div>
                    <div class="row">
                    <div class="col-auto mr-auto"></div>
                        <div class="col-auto">
                            {{--<a href="{{ route('claims.claim-attachment.edit', ['claim'=>$claim->id,'claim_attachment' => $claimAttachment->id]) }}" class='btn btn-warning btn-sm'>Edit </a>--}}
                            <a href="#" date-route="{{ route('claims.claim-attachment.destroy', ['claim'=>$claim->id,'claim_attachment' => $claimAttachment->id]) }}" class='btn btn-warning btn-sm delete'>Delete</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@can('auditable')
    <div id="auditable">
        <button id='auditablebtn' class="btn btn-warning btn-sm auditablebtn" data-id="{{ $claimAttachment->id }}" data-model="App\Models\ClaimsAttachments">
            View Audit Logs
        </button>
    </div>
@endcan
@endsection


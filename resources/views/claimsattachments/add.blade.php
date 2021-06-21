@extends('layouts.app')
@section('title','Add Claim Attachment')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>Create Claim Attachment</h2>
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
                <form id="demo-form2" method='post' action="{{ route('claims.claim-attachment.store',['claim'=>$claim->id]) }}" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left" autocomplete="off">
                {{csrf_field()}}
                    <div class="item form-group">
                        <div class="col">
                            <span class="col-form-label col-md-6 col-sm-6">File Name </span>
                            <input type="file" id="file_name" name="file_name" class="form-control form-control-sm" /> <br/>
                            @if ($errors->has('file_name'))
                                <span class="text-danger">{{ $errors->first('file_name') }}</span>
                            @endif
                        </div>
                        <div class="col">
                            
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <b>Important Note: Max upload size is 5 MB, allowed formats: jpg, jpeg, png, bmp, webp, pdf, docx.</b>
                        </div>
                        <div class="col">
                            
                        </div>
                    </div>
                    <div id='redirect_to_view_div'></div>
                    <div class="ln_solid"></div>
                    <div class="row">
                    <div class="col-auto mr-auto"></div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-warning btn-sm" id="return_to_view" >Create</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

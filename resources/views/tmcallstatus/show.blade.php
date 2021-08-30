@extends('layouts.app')
@section('title','TM Call Status')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 admin-detail">
        <div class="x_panel">
            <div class="x_title">
                <h2>TM Call Status Detail</h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a href="{{ route('tmcallstatus.index') }}" class="btn btn-warning btn-sm">TM Call Status List</a></li>
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <br />
                @if(session()->has('success'))
                    <div class="alert alert-success">{{ session()->get('success') }}</div>
                @endif
                <form id="demo-form2" method='post' action="{{ route('tmcallstatus.update', ['tmcallstatus' => $tmcallstatus->id]) }}" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left">
                {{csrf_field()}}
                @method('PUT')
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="code"><b>Code</b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $tmcallstatus->code }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="text"><b>Text</b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $tmcallstatus->text }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="text_ar"><b>Text Ar</b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $tmcallstatus->text_ar }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="sort_order"><b>Sort Order</b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $tmcallstatus->sort_order }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="is_active"><b>Is Active</b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $tmcallstatus->is_active ? 'True' : 'False' }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="created_at"><b>Created At</b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $tmcallstatus->created_at }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="updated_at"><b>Updated At</b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $tmcallstatus->updated_at }}</p>
                        </div>
                    </div>
                    <div class="ln_solid"></div>
                    <div class="row">
                    <div class="col-auto mr-auto"></div>
                        <div class="col-auto">
                            @can('tm-call-status-edit')
                            <a id="texta" href="{{ route('tmcallstatus.edit', ['tmcallstatus' => $tmcallstatus->id]) }}" class='btn btn-warning btn-sm'>Edit </a>
                            @endcan
                            @can('tm-call-status-delete')
                            <a href="#" date-route="{{ route('tmcallstatus.destroy', ['tmcallstatus' => $tmcallstatus->id]) }}" class='btn btn-warning btn-sm delete'>Delete</a>
                            @endcan
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@can('auditable')
    <div id="auditable">
        <button id='auditablebtn' class="btn btn-warning btn-sm auditablebtn" data-id="{{ $tmcallstatus->id }}" data-model="App\Models\TmCallStatus">
            View Audit Logs
        </button>
    </div>
@endcan
@endsection

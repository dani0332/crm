@extends('layouts.app')
@section('title','Reason Detail')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>Reason Detail</h2>
                 <ul class="nav navbar-right panel_toolbox">
                    <li><a href="{{ route('reason.index') }}" class="btn btn-warning btn-sm">Reasons List</a></li>
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <br />
                @if(session()->has('success'))
                    <div class="alert alert-success">{{ session()->get('success') }}</div>
                @endif
                    <form id="demo-form2" method='post' enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left">
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Name"><b>Name</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $reason->name }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Is Active"><b>Is Active</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center"> {{ $reason->is_active ? 'True' : 'False' }} </p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Created By"><b>Created By</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $reason->created_by }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Updated By"><b>Updated By</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $reason->updated_by }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Created At"><b>Created At</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $reason->created_at }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Updated At"><b>Updated At</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center"> {{ $reason->updated_at }} </p>
                        </div>
                    </div>
                    <div class="ln_solid"></div>
                    <div class="row">
                    <div class="col-auto mr-auto"></div>
                        <div class="col-auto">
                            <a href="{{ route('reason.edit', ['reason' => $reason->id]) }}" class='btn btn-warning btn-sm'>Edit</a>
                            <a href="#" date-route="{{ route('reason.destroy', ['reason' => $reason->id]) }}" class='btn btn-warning btn-sm delete'>Delete</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@can('auditable')
    <div id="auditable">
        <button id='auditablebtn' class="btn btn-warning btn-sm auditablebtn" data-id="{{ $reason->id }}" data-model="App\Models\Reason">
            View Audit Logs
        </button>
    </div>
@endcan
@endsection

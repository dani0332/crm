@extends('layouts.app')
@section('title','Claim Status')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 admin-detail">
        <div class="x_panel">
            <div class="x_title">
                <h2>Claim Status Detail</h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a href="{{ route('claimsstatus.index') }}" class="btn btn-warning btn-sm">Claim Status List</a></li>
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <br />
                @if(session()->has('success'))
                    <div class="alert alert-success">{{ session()->get('success') }}</div>
                @endif
                <form id="demo-form2" method='post' action="{{ route('claimsstatus.update', ['claimsstatus' => $claimsstatus->id]) }}" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left">
                {{csrf_field()}}
                @method('PUT')
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="text"><b>Text</b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $claimsstatus->text }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="text_ar"><b>Text Ar</b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $claimsstatus->text_ar }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="sort_order"><b>Sort Order</b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $claimsstatus->sort_order }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="is_active"><b>Is Active</b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $claimsstatus->is_active ? 'True' : 'False' }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="created_at"><b>Created At</b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $claimsstatus->created_at }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="updated_at"><b>Updated At</b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $claimsstatus->updated_at }}</p>
                        </div>
                    </div>
                    <div class="ln_solid"></div>
                    <div class="row">
                    <div class="col-auto mr-auto"></div>
                        <div class="col-auto">
                            @can('claims-status-edit')
                            <a id="texta" href="{{ route('claimsstatus.edit', ['claimsstatus' => $claimsstatus->id]) }}" class='btn btn-warning btn-sm'>Edit </a>
                            @endcan
                            {{--@can('claims-status-delete')
                            <a href="#" date-route="{{ route('claimsstatus.destroy', ['claimsstatus' => $claimsstatus->id]) }}" class='btn btn-warning btn-sm delete'>Delete</a>
                            @endcan--}}
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@can('auditable')
    <div id="auditable">
        <button id='auditablebtn' class="btn btn-warning btn-sm auditablebtn" data-id="{{ $claimsstatus->id }}" data-model="App\Models\ClaimsStatus">
            View Audit Logs
        </button>
    </div>
@endcan
@endsection

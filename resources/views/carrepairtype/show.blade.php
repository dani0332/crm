@extends('layouts.app')
@section('title','Car Repair Type')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 admin-detail">
        <div class="x_panel">
            <div class="x_title">
                <h2>Car Repair Type Detail</h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a href="{{ route('carrepairtype.index') }}" class="btn btn-warning btn-sm">Car Repair Types List</a></li>
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <br />
                @if(session()->has('success'))
                    <div class="alert alert-success">{{ session()->get('success') }}</div>
                @endif
                <form id="demo-form2" method='post' action="{{ route('carrepairtype.update', ['carrepairtype' => $carrepairtype->id]) }}" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left">
                {{csrf_field()}}
                @method('PUT')
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="text"><b> Text </b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $carrepairtype->text }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="text_ar"><b> Text Ar </b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $carrepairtype->text_ar }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="sort_order"><b> Sort Order </b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $carrepairtype->sort_order }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="is_active"><b>Is Active</b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center"> {{ $carrepairtype->is_active ? 'True' : 'False' }} </p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="created_at"><b> Created At </b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $carrepairtype->created_at }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="updated_at"><b> Updated At </b></label>
                        <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $carrepairtype->updated_at }}</p>
                        </div>
                    </div>
                    <div class="ln_solid"></div>
                    <div class="row">
                    <div class="col-auto mr-auto"></div>
                        <div class="col-auto">
                            @can('car-repair-type-edit')
                            <a id="texta" href="{{ route('carrepairtype.edit', ['carrepairtype' => $carrepairtype->id]) }}" class='btn btn-warning btn-sm'>Edit </a>
                            @endcan
                            @can('car-repair-type-delete')
                            <a href="#" date-route="{{ route('carrepairtype.destroy', ['carrepairtype' => $carrepairtype->id]) }}" class='btn btn-warning btn-sm delete'>Delete</a>
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
        <button id='auditablebtn' class="btn btn-warning btn-sm auditablebtn" data-id="{{ $carrepairtype->id }}" data-model="App\Models\CarRepairType">
            View Audit Logs
        </button>
    </div>
@endcan
@endsection

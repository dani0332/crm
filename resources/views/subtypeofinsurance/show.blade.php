@extends('layouts.app')
@section('title','Sub Type of Insurance')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 admin-detail">
        <div class="x_panel">
            <div class="x_title">
                <h2>Sub Type of Insurance Detail</h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a href="{{ route('subtypeofinsurance.index') }}" class="btn btn-warning btn-sm">Sub Type of Insurance List</a></li>
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <br />
                @if(session()->has('success'))
                    <div class="alert alert-success">
                        {{ session()->get('success') }}
                    </div>
                @endif
                <form id="demo-form2" method='post' action="{{ route('subtypeofinsurance.update', ['subtypeofinsurance' => $subtypeofinsurance->id]) }}" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left">
                {{csrf_field()}}
                @method('PUT')
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="text">
                            <b> Text </b>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $subtypeofinsurance->text }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="text_ar">
                            <b> Text Ar </b>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $subtypeofinsurance->text_ar }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="sort_order">
                            <b> Sort Order </b>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $subtypeofinsurance->sort_order }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="is_active"><b>Is Active</b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center"> {{ $subtypeofinsurance->is_active ? 'True' : 'False' }} </p>
                        </div>
                    </div>
                    <div class="ln_solid"></div>
                    <div class="item form-group">
                        <div class="col-md-6 col-sm-6 offset-md-3">
                                @can('sub-type-of-insurance-edit')
                                    <a id="texta" href="{{ route('subtypeofinsurance.edit', ['subtypeofinsurance' => $subtypeofinsurance->id]) }}" class='btn btn-warning btn-sm'>Edit </a>
                                @endcan
                                @can('sub-type-of-insurance-delete')
                                    <a href="#" date-route="{{ route('subtypeofinsurance.destroy', ['subtypeofinsurance' => $subtypeofinsurance->id]) }}"   class='btn btn-warning btn-sm delete'>Delete</a>
                                
                                    {{-- <form action="" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-warning btn-sm">Delete</button>
                                    </form> --}}
                                @endcan
                            </tr>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@can('auditable')
    <div id="auditable">
        <button id='auditablebtn' class="btn btn-warning btn-sm" data-id="{{ $subtypeofinsurance->id }}" data-model="App\Models\SubTypeOfInsuranceController">
            View Audit Logs
        </button>
    </div>
@endcan
@endsection
@extends('layouts.app')
@section('title','Rent a Car')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 admin-detail">
        <div class="x_panel">
            <div class="x_title">
                <h2>Rent a Car Detail</h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a href="{{ route('rentacar.index') }}" class="btn btn-warning btn-sm">Rent a Car List</a></li>
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
                <form id="demo-form2" method='post' action="{{ route('rentacar.update', ['rentacar' => $rentacar->id]) }}" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left">
                {{csrf_field()}}
                @method('PUT')
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="text">
                            <b> Text </b>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $rentacar->text }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="text_ar">
                            <b> Text Ar </b>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $rentacar->text_ar }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="sort_order">
                            <b> Sort Order </b>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $rentacar->sort_order }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="is_active"><b>Is Active</b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center"> {{ $rentacar->is_active ? 'True' : 'False' }} </p>
                        </div>
                    </div>
                    <div class="ln_solid"></div>
                    <div class="item form-group">
                        <div class="col-md-6 col-sm-6 offset-md-3">
                                @can('rent-a-car-edit')
                                    <a id="texta" href="{{ route('rentacar.edit', ['rentacar' => $rentacar->id]) }}" class='btn btn-warning btn-sm'>Edit </a>
                                @endcan
                                @can('rent-a-car-delete')
                                    <form action="{{ route('rentacar.destroy', ['rentacar' => $rentacar->id]) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-warning btn-sm">Delete</button>
                                    </form>
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
        <button id='auditablebtn' class="btn btn-warning btn-sm" data-id="{{ $rentacar->id }}" data-model="App\Models\RentACarController">
            View Audit Logs
        </button>
    </div>
@endcan
@endsection
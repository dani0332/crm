@extends('layouts.app')
@section('title','Partner Detail ')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 admin-detail">
        <div class="x_panel">
            <div class="x_title">
                <h2>Partner Detail</h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <br />
                @if(session()->has('success'))
                    <div class="alert alert-success">
                        {{ session()->get('success') }}
                    </div>
                @endif
                <form id="demo-form2" method='post' action="{{ route('partner.update', ['partner' => $partner->id]) }}" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left">
                {{csrf_field()}}
                @method('PUT')
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="name">
                            <b> Name En : </b>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $partner->name }}</p>
                        </div>
                       
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="name_ar">
                            <b> Name Ar : </b>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $partner->name_ar }}</p>
                        </div>
                        
                    </div>
                    <div class="item form-group">
                        
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="logo_image">
                            <b> Logo Image </b> 
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center"><img src="{{ \Config::get('constants.azure_storage_url').'myrewards/'.$partner->logo_image }}" style='width:100px;' /></p>

                        </div>
                       
                    </div>
                    <div class="item form-group">
                        <label for="middle-name" class="col-form-label col-md-3 col-sm-3 label-align">Is Active</label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center"> {{ $partner->is_active ? 'True' : 'False' }} </p>
                        </div>
                    </div>
                    <div class="ln_solid"></div>
                    <div class="item form-group">
                        <div class="col-md-6 col-sm-6 offset-md-3">
                                @can('partners-edit')
                                    <a href="{{ route('partner.edit', ['partner' => $partner->id]) }}" class='btn btn-warning btn-sm'>Edit </a>
                                
                                @endcan
                                @can('partners-delete')
                                    <form action="{{ route('partner.destroy', ['partner' => $partner->id]) }}" method="POST">  
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
@endsection
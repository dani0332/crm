@extends('layouts.app')
@section('title','Edit Partner')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 admin-edit">
        <div class="x_panel">
            <div class="x_title">
                <h2>Edit Partner</h2>
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
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="name">Name En <span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <input type="text" id="name" name="name" value="{{ $partner->name }}"  class="form-control ">
                            @if ($errors->has('name'))
                                <span class="text-danger">{{ $errors->first('name') }}</span>
                            @endif
                        </div>
                       
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="name_ar">Name Ar <span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <input type="text" id="name_ar" name="name_ar" value="{{ $partner->name_ar }}"  class="form-control">
                            @if ($errors->has('name_ar'))
                                <span class="text-danger">{{ $errors->first('name_ar') }}</span>
                            @endif
                        </div>
                        
                    </div>
                    <div class="item form-group">
                        
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="logo_image">Logo Image 
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <img src="{{ \Config::get('constants.azure_storage_url').'myrewards/'.$partner->logo_image }}" style='width:100px;' /> <br /><br />
                            <input type="file" id="logo_image"  name="logo_image"  >
                        </div>
                       
                    </div>
                    <div class="item form-group">
                        <label for="middle-name" class="col-form-label col-md-3 col-sm-3 label-align">Is Active</label>
                        <div class="col-md-6 col-sm-6 ">
                            <input {{ $partner->is_active ? 'checked' : '' }} type="checkbox" class="flat" name='is_active'>
                        </div>
                    </div>
                    <div class="ln_solid"></div>
                    <div class="item form-group">
                        <div class="col-md-6 col-sm-6 offset-md-3">
                          <button type="submit" class="btn btn-warning">Update</button>
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>
@endsection
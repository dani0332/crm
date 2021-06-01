@extends('layouts.app')
@section('title','Edit Claim Status')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 ">
        <div class="x_panel">
            <div class="x_title">
                <h2>Edit Claim Status</h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <br />
                @if(session()->has('success'))
                    <div class="alert alert-success">
                        {{ session()->get('success') }}
                    </div>
                @endif
                <form id="demo-form2" method='post' action="{{ route('claimsstatus.update', ['claimsstatus' => $claimsstatus->id]) }}" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left">
                {{csrf_field()}}
                @method('PUT')
                <div class="item form-group">
                    <label class="col-form-label col-md-3 col-sm-3 label-align" for="text">Text En <span class="required">*</span>
                    </label>
                    <div class="col-md-6 col-sm-6 ">
                        <input type="text" id="text" name="text" value="{{ $claimsstatus->text }}"  class="form-control ">
                        @if ($errors->has('text'))
                            <span class="text-danger">{{ $errors->first('text') }}</span>
                        @endif
                    </div>
                    
                </div>
                <div class="item form-group">
                    <label class="col-form-label col-md-3 col-sm-3 label-align" for="text_ar">Text Ar <span class="required">*</span>
                    </label>
                    <div class="col-md-6 col-sm-6 ">
                        <input type="text" id="text_ar" name="text_ar" value="{{ $claimsstatus->text_ar }}"  class="form-control">
                        @if ($errors->has('text_ar'))
                            <span class="text-danger">{{ $errors->first('text_ar') }}</span>
                        @endif
                    </div>
                    
                </div>
                <div class="item form-group">
                    <label class="col-form-label col-md-3 col-sm-3 label-align" for="sort_order">Sort Order <span class="required">*</span>
                    </label>
                    <div class="col-md-6 col-sm-6 ">
                        <input type="number" id="text_ar" name="sort_order" value="{{ $claimsstatus->sort_order }}"  class="form-control">
                        @if ($errors->has('sort_order'))
                            <span class="text-danger">{{ $errors->first('sort_order') }}</span>
                        @endif
                    </div>
                    
                </div>
                <div class="item form-group">
                    <label for="middle-name" class="col-form-label col-md-3 col-sm-3 label-align">Is Active</label>
                    <div class="col-md-6 col-sm-6 ">
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" {{ $claimsstatus->is_active ? 'checked' : '' }} class="flat" name='is_active'>
                        </label>
                    </div>
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
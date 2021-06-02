@extends('layouts.app')
@section('title','Add Tranlsation')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 ">
        <div class="x_panel">
            <div class="x_title">
                <h2>Create Reward Translation</h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <br />
                @if(session()->has('success'))
                    <div class="alert alert-success">
                        {{ session()->get('success') }}
                    </div>
                @endif
                <form id="demo-form2" method='post' action="{{ route('reward.reward-translation.store',['reward'=>$reward->id]) }}" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left">
                {{csrf_field()}}
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="title">Title <span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <input type="text" id="title" name="title" value="{{ old('title') }}"  class="form-control ">
                            @if ($errors->has('title'))
                                <span class="text-danger">{{ $errors->first('title') }}</span>
                            @endif
                        </div>
                        
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="description1">Description 1 <span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <textarea id='editor1' name="description1">{{ old('description1') }}</textarea>
                            @if ($errors->has('description1'))
                                <span class="text-danger">{{ $errors->first('description1') }}</span>
                            @endif
                        </div>
                        
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="description2">Description 2 <span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <textarea  id='editor2' name="description2">{{ old('description2') }}</textarea>
                            @if ($errors->has('description2'))
                                <span class="text-danger">{{ $errors->first('description2') }}</span>
                            @endif
                        </div>
                        
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="instructions">Instructions<span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <textarea  id='editor3' name="instructions">{{ old('instructions') }}</textarea>
                            @if ($errors->has('instructions'))
                                <span class="text-danger">{{ $errors->first('instructions') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="product_image">Product Image <span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <input type="file" id="product_image"  name="product_image" /> <br/>
                            @if ($errors->has('product_image'))
                                <span class="text-danger">{{ $errors->first('product_image') }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="full_width_banner_image">Full Width Banner <span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <input type="file" id="full_width_banner_image"  name="full_width_banner_image" /> <br/>
                            @if ($errors->has('full_width_banner_image'))
                                <span class="text-danger">{{ $errors->first('full_width_banner_image') }}</span>
                            @endif
                        </div>
                        
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="generic_banner_image">Generic Banner <span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <input type="file" id="generic_banner_image"  name="generic_banner_image" /> <br/>
                            @if ($errors->has('generic_banner_image'))
                                <span class="text-danger">{{ $errors->first('generic_banner_image') }}</span>
                            @endif
                        </div>
                        
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="terms_and_conditions">Terms And Conditions<span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <textarea id='editor4'  name="terms_and_conditions">{{ old('terms_and_conditions') }}</textarea>
                            
                            @if ($errors->has('terms_and_conditions'))
                                <span class="text-danger">{{ $errors->first('terms_and_conditions') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="lang">Language <span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6 ">
                            <select class="form-control" name='lang'>
                                <option value=''>Choose Language</option>
                                <option value='en'>English</option>
                                <option value='ar'>Arabic</option>
                            </select>
                            @if ($errors->has('lang'))
                                <span class="text-danger">{{ $errors->first('lang') }}</span>
                            @endif
                        </div>
                    </div>
                    <div id='redirect_to_view_div'></div>
                    <div class="ln_solid"></div>
                    <div class="item form-group">
                        <div class="col-md-6 col-sm-6 offset-md-3">
                          <button type="submit" class="btn btn-warning">Create & Add New</button> <button type="submit" class="btn btn-warning" id="return_to_view" >Cretae</button>
                        </div>
                    </div>


                </form>
            </div>
        </div>
    </div>
</div>
@endsection
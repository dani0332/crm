@extends('layouts.app')
@section('title','Edit Reward')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 admin-edit">
        <div class="x_panel">
            <div class="x_title">
                <h2>Edit Reward</h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a href="{{ route('reward.index') }}" class="btn btn-warning btn-sm">Rewards List</a></li>
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <br />
                @if(session()->has('success'))
                    <div class="alert alert-success">{{ session()->get('success') }}</div>
                @endif
                <form id="demo-form2" method='post' action="{{ route('reward.update', ['reward' => $reward->id]) }}" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left" autocomplete="off">
                {{csrf_field()}}
                @method('PUT')
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="coupon_code">Coupon Code <span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6">
                            <input type="text" id="coupon_code" name="coupon_code" value="{{ $reward->coupon_code }}" class="form-control" maxlength="25">
                            @if ($errors->has('coupon_code'))
                                <span class="text-danger">{{ $errors->first('coupon_code') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="name">Partner <span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6">
                            <select class="form-control" name='partner_id'>
                                <option value=''>Choose Partner</option>
                                @foreach($partners as $partner)
                                    <option {{ $reward->partner_id == $partner->id ? 'selected' : '' }} value="{{ $partner->id }}">{{ $partner->name }}</option>
                                @endforeach
                            </select>
                            @if ($errors->has('partner_id'))
                                <span class="text-danger">{{ $errors->first('partner_id') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="discount">Discount <span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6">
                            <input type="text" id="discount" name="discount" value="{{ $reward->discount }}" class="form-control">
                            @if ($errors->has('discount'))
                                <span class="text-danger">{{ $errors->first('discount') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="start_date">Start Date<span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6">
                            <input type="text" id="datepicker" name="start_date" value="{{ $reward->start_date  }}" class="form-control">
                            @if ($errors->has('start_date'))
                                <span class="text-danger">{{ $errors->first('start_date') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="end_date">End Date <span class="required">*</span>
                        </label>
                        <div class="col-md-6 col-sm-6">
                            <input type="text" id="datepicker_2" name="end_date" value="{{ $reward->end_date }}" class="form-control">
                            @if ($errors->has('end_date'))
                                <span class="text-danger">{{ $errors->first('end_date') }}</span>
                            @endif
                        </div>
                    </div>
                    {{-- <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="if_flat_discount">Is Flat Discount</label>
                        <div class="col-md-6 col-sm-6">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" {{ $reward->is_flat_discount ? 'checked' : '' }} class="flat" name='is_flat_discount'>
                            </label>
                        </div>
                        </div>
                    </div> --}}
                    <div class="item form-group">
                    <label class="col-form-label col-md-3 col-sm-3 label-align" for="reward_category">Reward Category <span class="required">*</span>
                    </label>
                        <div class="col-md-6 col-sm-6">
                            <select class="select2_multiple form-control" name='reward_categories[]' multiple data-live-search="true">
                                @foreach($rewardCategories as $rewardCategory)
                                    <option  {{ in_array($rewardCategory->id ,$reward->rewardCategories->pluck('id')->toArray() ) ? 'selected' : '' }} value="{{ $rewardCategory->id }}">{{ $rewardCategory->text }}</option>
                                @endforeach
                            </select>
                            @if ($errors->has('reward_categories'))
                                <span class="text-danger">{{ $errors->first('reward_categories') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                    <label class="col-form-label col-md-3 col-sm-3 label-align" for="reward_tag">Reward Tag <span class="required">*</span>
                    </label>
                        <div class="col-md-6 col-sm-6">
                            <select class="select2_multiple form-control" name='reward_tags[]' multiple data-live-search="true">
                                @foreach($rewardTags as $rewardTag)
                                    <option {{ in_array($rewardTag->id ,$reward->rewardTags->pluck('id')->toArray() ) ? 'selected' : '' }} value="{{ $rewardTag->id }}">{{ $rewardTag->text }}</option>
                                @endforeach
                            </select>
                            @if ($errors->has('reward_tags'))
                                <span class="text-danger">{{ $errors->first('reward_tags') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="is_active">Is Active</label>
                        <div class="col-md-6 col-sm-6">
                        <div class="checkbox">
                             <input type="checkbox" {{ $reward->is_active ? 'checked' : '' }} class="flat" id='is_active' name='is_active'>
                             <br />
                            @if ($errors->has('is_active'))
                                <span class="text-danger">{{ $errors->first('is_active') }}</span>
                            @endif
                        </div>
                        </div>
                    </div>
                    <div id='redirect_to_view_div'></div>
                    <div class="ln_solid"></div>
                    <div class="row">
                        <div class="col-auto mr-auto"></div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-warning btn-sm">Update & Continue Updating</button> <button type="submit" class="btn btn-warning btn-sm" id="return_to_view" >Update</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@extends('layouts.app')
@section('title','Add Team')
@section('content')
@php
use App\Enums\TeamTypeEnum;
@endphp

<script src="{{ asset('vendors/jquery/dist/jquery.min.js') }}"></script>
<script>
    function renderParentOptions(products)
    {
        var selectedType = $('#type option:selected').val();
        $('#parent_team_id').empty();
        if( selectedType == 1) {
            $('#parent_team_id').prop('disabled', true);
        }else{
            $('#parent_team_id').prop('disabled', false);
            for (let index = 0; index < products.length; index++) {
            const element = products[index];
                if(element.type == '{{TeamTypeEnum::PRODUCT_STR}}' && selectedType == '{{TeamTypeEnum::TEAM}}') {
                    $('#parent_team_id').append('<option value="'+ element.id +'" >'+ element.name +'</option>');
                }
                if(element.type == '{{TeamTypeEnum::TEAM_STR}}' && selectedType == '{{TeamTypeEnum::SUB_TEAM}}') {
                    $('#parent_team_id').append('<option value="'+ element.id +'" >'+ element.name +'</option>');
                }
            }
        }
    }

    $(document).ready(function(){
        var products  = JSON.parse('<?php echo json_encode($products); ?>');
        $('#type').on('change', function (item, index){
            renderParentOptions(products);
        });
        renderParentOptions(products);
    });


</script>
<div class="row">
    <div class="col-md-12 col-sm-12 ">
        <div class="x_panel">
            <div class="x_title">
                <h2>Create Team</h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a href="{{ route('team.index') }}" class="btn btn-warning btn-sm">Teams List</a></li>
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <br />
                @if(session()->has('success'))
                    <div class="alert alert-success">{{ session()->get('success') }}</div>
                @endif
                <form id="demo-form2" method='post' action="{{ route('team.store') }}" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left" autocomplete="off">
                {{csrf_field()}}
                    <div class="item form-group">
                        <div class="col">
                            <span class="col-form-label col-md-6 col-sm-6">Name <span class="required">*</span></span>
                            <input type="text" id="name" name="name" value="{{ old('name') }}" class="form-control">
                            @if ($errors->has('name'))
                                <span class="text-danger">{{ $errors->first('name') }}</span>
                            @endif
                        </div>
                        <div class="col">
                            <span class="col-form-label col-md-6 col-sm-6">Record Type <span class="required">*</span></span>

                            <select class="form-control" id='type' name='type'>
                            <option value='1'>Product</option>
                            <option value='2'>Team</option>
                            <option value='3'>Subteam</option>
                            </select>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <span class="col-form-label col-md-6 col-sm-6">Parent</span>
                            <select class="form-control" id='parent_team_id' name='parent_team_id'>
                            </select>
                            @if ($errors->has('parent_team_id'))
                                <span class="text-danger">{{ $errors->first('parent_team_id') }}</span>
                            @endif
                        </div>
                        <div class="col">
                            <span class="col-form-label col-md-3 col-sm-3">Slabs Count <span class="required">*</span></span>
                            <input type="number" id="slabs_count" placeholder="Allowed slabs count" name="slabs_count" value="{{ old('allowed_slabs') }}" class="form-control">
                            @if ($errors->has('allowed_slabs'))
                                <span class="text-danger">{{ $errors->first('allowed_slabs') }}</span>
                            @endif
                        </div>
                        <div class="col">
                            <span class="col-form-label col-md-3 col-sm-3">Is Active
                                <br />
                                <input type="checkbox"  style="margin-top:12px" checked id='is_active' name='is_active'>
                            </span>
                        </div>
                    </div>
                    <div id='redirect_to_view_div'></div>
                    <div class="ln_solid"></div>
                    <div class="row">
                    <div class="col-auto mr-auto"></div>
                        <div class="col-auto">
                        <button type="submit" class="btn btn-warning btn-sm" >Create</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

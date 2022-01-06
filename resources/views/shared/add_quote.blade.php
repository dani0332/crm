@extends('layouts.app')
@section('title','Create Car Quote')
@section('content')
<script src="{{ asset('vendors/jquery/dist/jquery.min.js') }}"></script>
<script>
$(document).ready(function() {
    $('.extra-fields-customer').click(function() {
        $('.customer_records').clone().appendTo('.customer_records_dynamic');
        $('.customer_records_dynamic .customer_records').addClass('single remove');
        $('.single .extra-fields-customer').remove();
        $('.single').append('<a href="#" class="remove-field btn-remove-customer" style="text-align: right;"><span class="fa fa-remove"></span></a>');
        $('.customer_records_dynamic > .single').attr("class", "remove");

        $('.customer_records_dynamic input').each(function() {
            var count = 0;
            var fieldname = $(this).attr("name");
            $(this).attr('name', fieldname + count);
            count++;
        });
    });
    $(document).on('click', '.remove-field', function(e) {
        $(this).parent('.remove').remove();
        e.preventDefault();
    });
});
</script>
<div class="row">
    <div class="col-md-12 col-sm-12 ">
        <div class="x_panel">
            <div class="x_title">
                <h2>Create Car Quote</h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a href="{{ url('quotes/car') }}" class="btn btn-warning btn-sm">Car List</a></li>
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <br />
                @if(session()->has('success'))
                    <div class="alert alert-success">{{ session()->get('success') }}</div>
                @endif
                <form id="demo-form2" method='post' action="" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left" autocomplete="off">
                    {{csrf_field()}}

                    <div class="item form-group">
                        <div class="col-md-6 col-sm-6">
                            <div class="row">
                                <div class="customer_records">
                                    <table cellspacing="5" cellpadding="5">
                                        <tr>
                                            <td>
                                                <select class="form-control" id='insurance_provider_id' name='insurance_provider_id' data-toggle="tooltip" data-placement="top" title="Please select insurance provider" style="width: auto;">
                                                    <option value=''>Select Provider</option>
                                                    @foreach($insuranceproviders as $insuranceprovider)
                                                        @if (old('insurance_provider_id') == $insuranceprovider->id)
                                                            <option value="{{ $insuranceprovider->id }}" data-id="{{ $insuranceprovider->code }}" selected>{{ $insuranceprovider->text }}</option>
                                                        @else
                                                            <option value="{{ $insuranceprovider->id }}" data-id="{{ $insuranceprovider->code }}">{{ $insuranceprovider->text }}</option>
                                                        @endif
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <select class="form-control" id="car_plan_id" name="car_plan_id" data-toggle="tooltip" data-placement="top" title="Please select plan" style="width: auto;">
                                                    <option value="">Select Plan</option>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="number" id="sss" name="sss" placeholder="Enter Premium" onfocus="this.type='number';" class="form-control" data-toggle="tooltip" data-placement="top" title="Please enter premium" onKeyDown="if(this.value.length==11)return false;" style="width: 150px;">
                                            </td>
                                            <td>
                                                <a class="extra-fields-customer" href="#"><span class="fa fa-plus-square"></span></a>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="customer_records_dynamic"></div>
                            </div>
                        </div>
                    </div>

                   <div id='redirect_to_view_div'></div>
                    <div class="ln_solid"></div>
                    <div class="row">
                    <div class="col-auto mr-auto"></div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-warning btn-sm" id="return_to_view">Create Quote</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

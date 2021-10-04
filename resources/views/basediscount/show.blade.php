@extends('layouts.app')
@section('title', 'Base Discount Detail')
@section('content')
    <div class="row">
        <div class="col-md-12 col-sm-12 admin-detail">
            <div class="x_panel">
                <div class="x_title">
                    <h2>Base Discount Detail</h2>
                    <ul class="nav navbar-right panel_toolbox">
                        <li><a href="{{ url('discount/base') }}" class="btn btn-warning btn-sm">Base Discount List</a></li>
                    </ul>
                    <div class="clearfix"></div>
                </div>
                <div class="x_content">
                    <br />
                    @if (session()->has('success'))
                        <div class="alert alert-success">{{ session()->get('success') }}</div>
                    @endif
                    @if (session()->has('message'))
                        <div class="alert alert-danger">{{ session()->get('message') }}</div>
                    @endif
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="first_name"><b> Start Value
                                </b></label>
                            <div class="col-md-6 col-sm-6 ">
                                <p class="label-align-center">{{ $basediscount->value_start }}</p>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="last_name"><b> End Value
                                </b></label>
                            <div class="col-md-6 col-sm-6 ">
                                <p class="label-align-center">{{ $basediscount->value_end }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="email_address"><b> Vehicle
                                    Type </b></label>
                            <div class="col-md-6 col-sm-6 ">
                                <p class="label-align-center">
                                    {{ $basediscount->vehicle_type_id ? $basediscount->vehicle_type_id : '' }}
                                </p>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="phone_number"><b>
                                    Comprehensive NON Agency Discount </b></label>
                            <div class="col-md-6 col-sm-6 ">
                                <p class="label-align-center">{{ $basediscount->comprehensive_discount }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="agency"><b> Agency Discount
                                </b></label>
                            <div class="col-md-6 col-sm-6 ">
                                <p class="label-align-center">{{ $basediscount->agency_discount }}</p>
                            </div>
                        </div>
                        <div class="col">
                        </div>
                    </div>
                    <div class="ln_solid"></div>
                    <div class="row">
                        <div class="col-auto mr-auto"></div>
                        <div class="col-auto">
                            @can('base-discount-edit')
                                <a id="texta" href="{{ route('base.edit', $basediscount) }}"
                                    class='btn btn-warning btn-sm'>Edit</a>
                            @endcan
                            @can('base-discount-delete')
                                <a href="#" date-route="{{ route('base.destroy', $basediscount) }}"
                                    class='btn btn-warning btn-sm delete'>Delete</a>
                            @endcan
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @can('auditable')
        <div id="auditable">
            <button id='auditablebtn' class="btn btn-warning btn-sm auditablebtn" data-id="{{ $basediscount->id }}"
                data-model="App\Models\BaseDiscount">
                View Audit Logs
            </button>
        </div>
    @endcan
@endsection

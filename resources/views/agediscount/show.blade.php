@extends('layouts.app')
@section('title', 'Age Discount Detail')
@section('content')
    <div class="row">
        <div class="col-md-12 col-sm-12 admin-detail">
            <div class="x_panel">
                <div class="x_title">
                    <h2>Age Discount Detail</h2>
                    <ul class="nav navbar-right panel_toolbox">
                        <li><a href="{{ url('discount/age') }}" class="btn btn-warning btn-sm">Age Discount List</a></li>
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
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="first_name"><b> Age Start
                                </b></label>
                            <div class="col-md-6 col-sm-6 ">
                                <p class="label-align-center">{{ $agediscount->age_start }}</p>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="last_name"><b>Age End
                                </b></label>
                            <div class="col-md-6 col-sm-6 ">
                                <p class="label-align-center">{{ $agediscount->age_end }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="item form-group">

                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="phone_number"><b>
                                    Discount </b></label>
                            <div class="col-md-6 col-sm-6 ">
                                <p class="label-align-center">{{ $agediscount->discount }}</p>
                            </div>
                        </div>
                        <div class="col">
                        </div>
                    </div>
                    <div class="ln_solid"></div>
                    <div class="row">
                        <div class="col-auto mr-auto"></div>
                        <div class="col-auto">
                            @can('age-discount-edit')
                                <a id="texta" href="{{ route('age.edit', $agediscount) }}"
                                    class='btn btn-warning btn-sm'>Edit</a>
                            @endcan
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @can('auditable')
        <div id="auditable">
            <button id='auditablebtn' class="btn btn-warning btn-sm auditablebtn" data-id="{{ $agediscount->id }}"
                data-model="App\Models\agediscount">
                View Audit Logs
            </button>
        </div>
    @endcan
@endsection

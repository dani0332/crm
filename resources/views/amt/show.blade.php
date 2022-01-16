@extends('layouts.app')
@section('title','GM Lead  Detail')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 admin-detail">
        <div class="x_panel">
            <div class="x_title">
                <h2>Group Medical Lead Detail</h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a href="{{ url('medical/amt') }}" class="btn btn-warning btn-sm">Group Medical List</a></li>
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <br />
                @if(session()->has('success'))
                    <div class="alert alert-success">{{ session()->get('success') }}</div>
                @endif
                @if(session()->has('message'))
                    <div class="alert alert-danger">{{ session()->get('message') }}</div>
                @endif
                <form id="demo-form2" method='post'  action="{{ url('medical/amt') }}"  enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left">
                {{csrf_field()}}
                @method('PUT')
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="created_at"><b> ID</b></label>
                        <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $record->id }}</p>
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="updated_at"><b> CDB ID</b></label>
                        <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $record->code }}</p>
                        </div>
                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="first_name"><b> First Name </b></label>
                        <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $record->first_name }}</p>
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="last_name"><b> Last Name </b></label>
                        <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $record->last_name }}</p>
                        </div>
                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="email_address"><b> Email Address </b></label>
                        <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $record->email }}</p>
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="phone_number"><b> Mobile Number </b></label>
                        <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $record->mobile_no }}</p>
                        </div>
                    </div>
                </div>

                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="created_at"><b> Created At </b></label>
                        <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $record->created_at }}</p>
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="updated_at"><b> Updated At </b></label>
                        <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $record->updated_at }}</p>
                        </div>
                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="created_at"><b> Company Name</b></label>
                        <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $record->company_name }}</p>
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="updated_at"><b> Number Of Employees</b></label>
                        <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $record->number_of_employees }}</p>
                        </div>
                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="created_at"><b> Business Type Of Insurance</b></label>
                        <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">Group Medical</p>
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="updated_at"><b>Lead Status</b></label>
                        <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $selectedLeadStatus }}</p>
                        </div>
                    </div>
                </div>
                <div class="item form-group">
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="created_at"><b> Premium</b></label>
                        <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{$record->premium}}</p>
                        </div>
                    </div>
                    <div class="col">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="updated_at"><b>Brief Details</b></label>
                        <div class="col-md-6 col-sm-6 ">
                        <p class="label-align-center">{{ $record->brief_details }}</p>
                        </div>
                    </div>
                </div>
                <div class="ln_solid"></div>
                <div class="row">
                <div class="col-auto mr-auto"></div>
                    <div class="col-auto">
                        @can('gm-quotes-edit')
                        <a id="texta" href="{{ url('medical/amt/'. $record->uuid. '/edit') }}" class='btn btn-warning btn-sm'>Edit</a>
                        @endcan
                    </div>
                </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection


@extends('layouts.app')
@section('title','Home')
@section('content')

<div class="row">
    <div class="col-md-12 col-sm-12 ">
        <div class="x_panel">
            <div class="x_title">
                <h2>Search Leads</h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                @if(session()->has('message'))
                    <div class="alert alert-danger">{{ session()->get('message') }}</div>
                @endif
                @if(session()->has('success'))
                    <div class="alert alert-success">{{ session()->get('success') }}</div>
                @endif

                <form method="POST" id="search-leads" class="form-horizontal form-label-left" role="form" data-parsley-validate="" novalidate="" autocomplete="off">
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-2 col-sm-2" for="Search By">CDB ID </label>
                            <div class="col-md-6 col-sm-6">
                                <input type="text" class="form-control" id="cdbID" name="cdbID">
                            </div>
                        </div>
                        <div class="col">
                            <div>
                                <label class="col-form-label col-md-2 col-sm-2" for="Search Value">Customer Email</label>
                                <div class="col-md-6 col-sm-6">
                                    <input type="text" class="form-control" id="email" name="email">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-2 col-sm-2" for="Start Date">Customer Phone Number</label>
                            <div class="col-md-6 col-sm-6">
                                <input class="form-control" type="number" max="10" id="phnNumber" name="phnNumber">
                            </div>
                        </div>
                        <div class="col">

                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">

                        </div>
                            <div class="col">
                                <ul class="nav navbar-right panel_toolbox">
                                <li><input type="submit" class="btn btn-warning btn-sm" value="Search"></li>
                                <li><input type="reset" class="btn btn-warning btn-sm"></li>
                                </ul>
                            </div>
                        </div>
                    </form>
            </div>
        </div>
    </div>
</div>
@endsection


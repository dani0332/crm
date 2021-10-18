@extends('layouts.app')
@section('title','AML')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 admin-view">
        <div class="x_panel">
            <div class="x_title">
                <h2>AML</h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <form method="POST" id="searchAML" class="form-horizontal form-label-left" role="form" data-parsley-validate="" novalidate="" autocomplete="off">
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-2 col-sm-2" for="Quote Type">Quote Type</label>
                            <div class="col-md-6 col-sm-6">
                                <div class="input-group">
                                    <select class="form-control" id="quoteTypeValue" name="quoteType">
                                        <option value="">Select</option>
                                        @foreach ($quoteTypes as $quoteType)
                                            <option value="{{ $quoteType->id }}">{{ $quoteType->text }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col">

                        </div>
                    </div>
                    <div id="aml-search-fields">
                        <div class="item form-group">
                            <div class="col">
                                <label class="col-form-label col-md-2 col-sm-2" for="Search By">Search By</label>
                                <div class="col-md-6 col-sm-6">
                                    <select class="form-control" id="searchType" name="searchType">
                                        <option value=""></option>
                                        <option value="cdbId">CDB ID</option>
                                        <option value="customerEmail">Customer Email</option>
                                        <option value="id">AML ID</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col">
                                <label class="col-form-label col-md-2 col-sm-2" for="Search Value">Search Value</label>
                                <div class="col-md-6 col-sm-6">
                                    <div class="input-group">
                                        <div><input type="text" class="form-control" id="searchField" name="searchField" placeholder="Type here..."></div>
                                        <div id="aml-search-filter-result" class="required"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="item form-group">
                            <div class="col">
                                <label class="col-form-label col-md-2 col-sm-2" for="Match found">Match found</label>
                                <div class="col-md-6 col-sm-6">
                                    <select class="form-control" id="matchFound" name="matchFound">
                                        <option value=""></option>
                                        <option value="False">False</option>
                                        <option value="True">True</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col">

                            </div>
                        </div>
                        <div class="item form-group">
                            <div class="col">
                                <label class="col-form-label col-md-2 col-sm-2" for="Start Date">Start Date</label>
                                <div class="col-md-6 col-sm-6">
                                    <input type="text" class="form-control" id="amlCreatedStartDate" name="amlCreatedStartDate">
                                </div>
                                <span id="amlCreatedStartDateMsg" style="color:red;"> </span>
                            </div>
                            <div class="col">
                                <label class="col-form-label col-md-2 col-sm-2" for="End Date">End Date</label>
                                <div class="col-md-6 col-sm-6">
                                    <input type="text" class="form-control" id="amlCreatedEndDate" name="amlCreatedEndDate">
                                </div>
                                <span id="amlCreatedEndDateMsg" style="color:red;"> </span>
                            </div>
                        </div>
                    </div>
                    <div id="aml-search-submit">
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
                    </div>
                </form>
                @if(session()->has('message'))
                    <div class="alert alert-danger">{{ session()->get('message') }}</div>
                @endif
                <table class="table table-striped jambo_table aml-data-table" style="width:100%">
                    <thead>
                        <tr>
                        <th>AML Id</th>
                        <th>Quote Type</th>
                        <th>CDB Id</th>
                        <th>Input</th>
                        <th>Screenshot</th>
                        <th>Created At</th>
                        <th>Updated At</th>
                        </tr>
                    </thead>

                    <tbody>

                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

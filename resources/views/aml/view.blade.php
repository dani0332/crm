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
                            <label class="col-form-label col-md-2 col-sm-2" for="Search By">Search By</label>
                            <div class="col-md-6 col-sm-6">
                                <select class="form-control" id="searchType" name="searchType">
                                    <option value="quoteRequestId">Quote Request ID</option>
                                    <option value="id">ID</option>
                                </select>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-2 col-sm-2" for="Search Value">Search Value</label>
                            <div class="col-md-6 col-sm-6">
                                <div class="input-group">
                                    <input type="text" class="form-control" id="searchField" name="searchField" placeholder="Type here...">
                                </div>
                            </div>
                        </div>
                    </div>
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
                            <ul class="nav navbar-right panel_toolbox">
                            <li><input type="submit" class="btn btn-warning btn-sm" value="Search"></li>
                            <li><input type="reset" class="btn btn-warning btn-sm"></li>
                            </ul>
                        </div>
                    </div>
                </form>
                @if(session()->has('message'))
                    <div class="alert alert-danger">{{ session()->get('message') }}</div>
                @endif
                <table class="table table-striped jambo_table aml-data-table" style="width:100%">
                    <thead>
                        <tr>
                        <th>ID</th>
                        <th>Quote Type</th>
                        <th>Quote Request ID</th>
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

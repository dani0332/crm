@extends('layouts.app')
@section('title','AML Detail')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 admin-detail">
        <div class="x_panel">
            <div class="x_title">
                <h2>AML Detail</h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a href="{{ route('aml.index') }}" class="btn btn-warning btn-sm">All Quotes</a></li>
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <br />
                @if(session()->has('success'))
                    <div class="alert alert-success">{{ session()->get('success') }}</div>
                @endif
                <form id="demo-form2" method="POST" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left">
                @method('POST')
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="ID"><b>AML Id</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $aml->id }}</p>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Quote Type"><b>Quote Type</b></label>
                            <div class="col-md-6 col-sm-6">
                                <p class="label-align-center">{{ $aml->quotetype ? $aml->quotetype->text : '' }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Quote Request ID"><b>Quote Request ID</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center"><a href="{{ $aml->quote_type_id }}/details/{{ $aml->quote_request_id }}" style="text-decoration: underline;font-weight: bold;">{{ $aml->quote_request_id }}</a></p>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Input"><b>Input</b></label>
                            <div class="col-md-6 col-sm-6">
                                <p class="label-align-center">{{ $aml->input }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Created At"><b>Created At</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $aml->created_at }}</p>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Updated At"><b>Updated At</b></label>
                            <div class="col-md-6 col-sm-6">
                                <p class="label-align-center">{{ $aml->updated_at }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Results Found"><b>Results Found</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $aml->results_found }}</p>
                            </div>
                        </div>
                        <div class="col">
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Screenshot"><b style="width: 143.4px;">Screenshot</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center" style="width: 286.8px;word-wrap: break-word;"><img class="img-responsive" src="{{ $aml->screenshot }}" alt="screenshot" width="800px" ></p>
                            </div>
                        </div>
                        <div class="col">
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12 col-sm-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>Results</h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <br />
                <table id="datatable" class="table table-striped jambo_table" style="width:100%">
                    <thead>
                      <tr>
                        <th>ID</th>
                        <th>First Name</th>
                        <th>Middle Name</th>
                        <th>Last Name</th>
                        <th>Alias</th>
                        <th>Gender</th>
                        <th>DOB</th>
                        <th>YOB Match</th>
                        <th>POB</th>
                        <th>Nationality</th>
                        <th>Nationality Match</th>
                        <th>Source</th>
                        <th>Source ID</th>
                        <th>Created At</th>
                        <th>Updated At</th>
                        <th>ObjectID</th>
                      </tr>
                    </thead>

                    <tbody>
                        @foreach($amlResults as $key => $amlResult)
                      <tr>
                        <td>{{ $amlResult->id }}</td>
                        <td>{{ $amlResult->firstName }}</td>
                        <td>{{ $amlResult->middleName }}</td>
                        <td>{{ $amlResult->lastName }}</td>
                        <td>{{ $amlResult->alias }}</td>
                        <td>{{ $amlResult->gender }}</td>
                        <td>{{ $amlResult->dob }}</td>
                        @if ($amlResult->yobMatch)
                            @if ($amlResult->yobMatch == 1)
                                <td>True</td>
                            @else 
                                <td>False</td>
                            @endif
                        @else 
                            <td>No match found</td>
                        @endif
                        <td>{{ $amlResult->pob }}</td>
                        <td>{{ $amlResult->nationality }}</td>
                        @if ($amlResult->nationalityMatch)
                            @if ($amlResult->nationalityMatch == 1)
                                <td>True</td>
                            @else 
                                <td>False</td>
                            @endif
                        @else 
                            <td>No match found</td>
                        @endif
                        <td>{{ $amlResult->source }}</td>
                        <td>{{ $amlResult->sourceId }}</td>
                        <td>{{ date('d-M-Y h:ia', strtotime($amlResult->createdAt)) }}</td>
                        <td>{{ date('d-M-Y h:ia', strtotime($amlResult->updatedAt)) }}</td>
                        <td>{{ $amlResult->objectID }}</td>
                      </tr>
                      @endforeach
                    </tbody>
                  </table>
            </div>
        </div>
    </div>
</div>
@endsection

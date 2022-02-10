@extends('layouts.app')
@section('title','AML Detail')
@section('content')
<?php
    use App\Enums\AmlSearchType;
?>
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
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Search Type"><b>Search Type</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center">{{ $aml->search_type }}</p>
                            </div>
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
                    @if ($aml->search_type == AmlSearchType::ENTITY)

                    <thead>
                        <tr>
                          <th style="width: 100px !important">ID</th>
                          <th style="width: 100px !important">Alias</th>
                          <th style="width: 100px !important">Source</th>
                          <th style="width: 100px !important">Created At</th>
                        </tr>
                      </thead>

                      <tbody>
                          @foreach($amlResults as $key => $amlResult)
                        <tr>
                          <td>{{ $amlResult->id }}</td>
                          <td>{{ $amlResult->alias }}</td>
                          <td>{{ $amlResult->source }}</td>
                          <td>{{ date('d-M-Y h:ia', strtotime($amlResult->createdAt)) }}</td>
                        </tr>
                        @endforeach
                      </tbody>

                    @else

                    <thead>
                        <tr>
                          <th style="width: 100px !important">ID</th>
                          <th style="width: 100px !important">First Name</th>
                          <th style="width: 100px !important">Middle Name</th>
                          <th style="width: 100px !important">Last Name</th>
                          <th style="width: 100px !important">Alias</th>
                          <th style="width: 100px !important">Gender</th>
                          <th style="width: 100px !important">DOB</th>
                          <th style="width: 100px !important">YOB Match</th>
                          <th style="width: 100px !important">POB</th>
                          <th style="width: 100px !important">Nationality</th>
                          <th style="width: 100px !important">Nationality Match</th>
                          <th style="width: 100px !important">Source</th>
                          <th style="width: 100px !important">Created At</th>
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
                          @if (isset($amlResult->yobMatch))
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
                          @if (isset($amlResult->nationalityMatch))
                              @if ($amlResult->nationalityMatch == 1)
                                  <td>True</td>
                              @else
                                  <td>False</td>
                              @endif
                          @else
                              <td>No match found</td>
                          @endif
                          <td>{{ $amlResult->source }}</td>
                          <td>{{ date('d-M-Y h:ia', strtotime($amlResult->createdAt)) }}</td>
                        </tr>
                        @endforeach
                      </tbody>
                    @endif
                  </table>
            </div>
        </div>
    </div>
</div>
@endsection

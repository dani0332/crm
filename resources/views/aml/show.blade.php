@extends('layouts.app')
@section('title','AML Detail')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 admin-detail">
        <div class="x_panel">
            <div class="x_title">
                <h2>AML Detail</h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a href="{{ route('aml.index') }}" class="btn btn-warning btn-sm">Home</a></li>
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
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="ID"><b> ID</b></label>
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
                            <p class="label-align-center">{{ $aml->quote_request_id }}</p>
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
                    {{-- <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="Results"><b style="width: 143.4px;">Results</b></label>
                            <div class="col-md-6 col-sm-6">
                            <p class="label-align-center" style="width: 286.8px;word-wrap: break-word;">
                            @foreach($aml_results as $key => $aml_result)
                                {{ $aml_result->id }}
                            @endforeach
                            </p>
                            </div>
                        </div>
                        <div class="col">

                        </div>
                    </div> --}}
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
                    <div class="ln_solid"></div>
                    <div class="row">
                    <div class="col-auto mr-auto"></div>
                        <div class="col-auto">
                            {{--<button type="submit" class="btn btn-warning btn-sm">Resubmit</button>--}}
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
                        <th>POB</th>
                        <th>Nationality</th>
                        <th>Source</th>
                        <th>Source ID</th>
                        <th>Created At</th>
                        <th>Updated At</th>
                        <th>ObjectID</th>
                      </tr>
                    </thead>

                    <tbody>
                        @foreach($aml_results as $key => $aml_result)
                      <tr>
                        <td>{{ $aml_result->id }}</td>
                        <td>{{ $aml_result->firstName }}</td>
                        <td>{{ $aml_result->middleName }}</td>
                        <td>{{ $aml_result->lastName }}</td>
                        <td>{{ $aml_result->alias }}</td>
                        <td>{{ $aml_result->gender }}</td>
                        <td>{{ $aml_result->dob }}</td>
                        <td>{{ $aml_result->pob }}</td>
                        <td>{{ $aml_result->nationality }}</td>
                        <td>{{ $aml_result->source }}</td>
                        <td>{{ $aml_result->sourceId }}</td>
                        <td>{{ $aml_result->createdAt }}</td>
                        <td>{{ $aml_result->updatedAt }}</td>
                        <td>{{ $aml_result->objectID }}</td>
                      </tr>
                      @endforeach
                    </tbody>
                  </table>
            </div>
        </div>
    </div>
</div>
@endsection

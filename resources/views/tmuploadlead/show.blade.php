@extends('layouts.app')
@section('title','Upload TM Lead')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 admin-detail">
        <div class="x_panel">
            <div class="x_title">
                <h2>Upload TM Lead Detail</h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a href="{{ route('tmuploadlead.index') }}" class="btn btn-warning btn-sm">Upload TM Lead List</a></li>
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <br />
                @if(session()->has('success'))
                    <div class="alert alert-success">{{ session()->get('success') }}</div>
                @endif

                @if (session()->has('failures'))
                <div class="required"><b>Please correct below data and import it again separately, other data already imported.</b></div>
                <br />
                <table class="table table-danger">
                    <tr>
                        <th>Row</th>
                        <th>Column</th>
                        <th>Errors</th>
                        <th>Value</th>
                    </tr>
                    @foreach (session()->get('failures') as $validation)
                        <tr>
                            <td>{{ $validation->row() }}</td>
                            <td>{{ $validation->attribute()+1 }}</td>
                            <td>
                                <ul>
                                    @foreach ($validation->errors() as $e)
                                        <li>{{ $e }}</li>
                                    @endforeach
                                </ul>
                            </td>
                            <td>
                                {{ $validation->values()[$validation->attribute()] }}
                            </td>
                        </tr>
                    @endforeach
                </table>
                @endif

                <form id="demo-form2" method='post' action="{{ route('tmuploadlead.update', ['tmuploadlead' => $tmuploadlead->id]) }}" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left">
                {{csrf_field()}}
                @method('PUT')
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="File"><b>File</b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{$tmuploadlead->file_name}}</p>
                            {{-- <p class="label-align-center"><a href="{{ \Config::get('constants.azure_storage_url').'myrewards/'.$tmuploadlead->file_path }}" target="_blank">{{$tmuploadlead->file_name}}</a></p> --}}
                        </div>
                    </div>
                    {{-- <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Total Records"><b>Total Records</b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $tmuploadlead->total_records }}</p>
                        </div>
                    </div> --}}
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Good"><b>Good</b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $tmuploadlead->good }}</p>
                        </div>
                    </div>
                    {{-- <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Cannot Upload"><b>Cannot Upload</b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $tmuploadlead->cannot_upload }}</p>
                        </div>
                    </div> --}}
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Created At"><b>Created At</b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $tmuploadlead->created_at }}</p>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="Submitted By"><b>Submitted By</b></label>
                        <div class="col-md-6 col-sm-6 ">
                            <p class="label-align-center">{{ $tmuploadlead->createdby->name }}</p>
                        </div>
                    </div>
                    <div class="ln_solid"></div>
                    <div class="row">
                    <div class="col-auto mr-auto"></div>
                        <div class="col-auto">
                           {{-- @can('tm-upload-leads-edit')
                            <a id="texta" href="{{ route('tmuploadlead.edit', ['tmuploadlead' => $tmuploadlead->id]) }}" class='btn btn-warning btn-sm'>Edit </a>
                            @endcan
                            @can('tm-upload-leads-delete')
                            <a href="#" date-route="{{ route('tmuploadlead.destroy', ['tmuploadlead' => $tmuploadlead->id]) }}" class='btn btn-warning btn-sm delete'>Delete</a>
                            @endcan --}}
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@can('auditable')
    <div id="auditable">
        <button id='auditablebtn' class="btn btn-warning btn-sm auditablebtn" data-id="{{ $tmuploadlead->id }}" data-model="App\Models\TmUploadLead">
            View Audit Logs
        </button>
    </div>
@endcan
@endsection

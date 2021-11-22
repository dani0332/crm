@extends('layouts.app')
@section('title','AML Download History')
@section('content')
<script src="{{ asset('vendors/jquery/dist/jquery.min.js') }}"></script>
<script>
    var url = JSON.parse('<?php echo json_encode($url); ?>');
    $(document).ready(function() {
        var sanctionListDownloads = $(".sanction-list-downloads-table").DataTable({
            ordering: false,
            info: false,
            searching: false,
            bLengthChange: false,
            ajax: config.routes.sanction_list_downloads_datatable_route,
            columns: [
                { data: "id", name: "id" },
                { data: "file_name", name: "file_name",
                  render: function(data, type, row) {
                    if (row.file_name === null) {
                      return "null";
                    }
                    return "<a href='" + url + "/" + row.file_name + "' target='_blank'>" + row.file_name + "</a>";
                  }
                },
                { data: "file_path", name: "file_path" },
                { data: "source", name: "source" },
                { data: "total_records", name: "total_records" },
                { data: "created_at", name: "created_at" },
                { data: "updated_at", name: "updated_at" },
            ],
        });
      });
  </script>
<div class="row">
        <div class="x_panel">
            <div class="x_title">
                <h2>Downloaded Sanction Lists</h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                {{-- <div id="success_message" class="alert alert-success" style="display:none"></div>
                <div id="error_message" class="alert alert-danger" style="display:none"></div> --}}
                <br />
                <br/>
                <table  class="table table-striped jambo_table sanction-list-downloads-table" style="width:100%">
                      <thead>
                        <tr>
                          <th>id</th>
                          <th>File Name</th>
                          <th>File Path</th>
                          <th>Source</th>
                          <th>Total Records</th>
                          <th>Created At</th>
                          <th>Updated At</th>
                        </tr>
                      </thead>
                      <tbody>
                        @foreach($data as $key => $amlHistory)
                        <tr>
                          <td>{{ $amlHistory->id }}</td>
                          <td>
                            <a href="{{ \Config::get('constants.azure_ryu_storage_url').'aml-uae'.$amlHistory->file_name }}" target="_blank">
                              {{ $amlHistory->file_name }}
                            </a>
                          </td>
                          <td>{{ $amlHistory->file_path }}</td>
                          <td>{{ $amlHistory->source }}</td>
                          <td>{{ $amlHistory->total_records }}</td>
                          <td>{{ $amlHistory->created_at }}</td>
                          <td>{{ $amlHistory->updated_at }}</td>
                        </tr>
                        @endforeach
                      </tbody>
                    </table>
            </div>
        </div>
    </div>
</div>
@endsection

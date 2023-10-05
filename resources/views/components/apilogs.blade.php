@php
use Carbon\Carbon;
@endphp
<div class="row">
        <div class="col-md-12 col-sm-12 ">
            <div class="x_panel">
                <div class="x_title">
                    <h2>API Logs</h2>
                    <div class="clearfix"></div>

                </div>
                <div class="x_content">
                    <br />
                    <div class="table-responsive">
                    <table id="datatable" style="width:100%; table-layout: fixed;" class="table table-striped jambo_table">
                          <thead>
                            <tr>
                              <th>ID</th>
                              <th>REF-ID</th>
                              <th>Call Type</th>
                              <th>Status</th>
                              <th>Provider Name</th>
                              <th>Created At</th>
                              <th>Action</th>
                            </tr>
                          </thead>
                          <tbody>
                            @foreach($apilogs as $key => $apilog)
                            <tr>
                              <td>{{ $apilog->id }}</td>
                              <td>{{ $apilog->quote_uuid }}</td>
                              <td>{{ $apilog->call_type }}</td>
                              <td style="color: {{ ($apilog->status === 'passed' || $apilog->status === 'N/A') ? 'green' : 'red' }}">{{ strtoupper($apilog->status) }}</td>
                              <td>{{ $apilog->insuranceProvider->text }}</td>
                              <td>{{ date('d/m/Y H:i:s',strtotime($apilog->created_at)) }}</td>
                              <td>
                              <button class="btn btn-warning btn-sm view-button"
                                    data-id="{{ $apilog->id }}"
                                    data-ref-id="{{ $apilog->quote_uuid }}"
                                    data-call-type="{{ $apilog->call_type }}"
                                    data-status="{{ strtoupper($apilog->status) }}"
                                    data-provider-name="{{ $apilog->insuranceProvider->text }}"
                                    data-request="{{ $apilog->request }}"
                                    data-response="{{ $apilog->response }}"
                                    data-created-at="{{ date('d/m/Y H:i:s',strtotime($apilog->created_at)) }}"
                                    data-updated-at="{{ date('d/m/Y H:i:s',strtotime($apilog->updated_at)) }}"
                              >
                                View
                            </button>
                              </td>
                            </tr>
                            @endforeach

                          </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>         
    </div>
    <!-- Add Modal -->    
    <div class="modal fade" id="viewModalLog" tabindex="-1" role="dialog" aria-labelledby="viewModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #4183bd;">
                <h4 class="modal-title" id="viewModalLabel" style="color: white; margin-right: 10px;">Insurance Request Response Details: <span id="modalId"></span></h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>           
            
    <div class="modal-body">
        <table class="table">
            <tr>
                <td><strong>REF-ID:</strong></td>
                <td><span id="modalRefId"></span></td>
            </tr>
            <tr>
                <td><strong>Call Type:</strong></td>
                <td><span id="callType"></span></td>
            </tr>
            <tr>
                <td><strong>Status:</strong></td>
                <td><span id="status"></span></td>
            </tr>
            <tr>
                <td><strong>Provider Name:</strong></td>
                <td><span id="providerName"></span></td>
            </tr>
            <tr>
                <td><strong>Request:</strong></td>
                <td>
                    <div style="background-color: #d5edfd; color: white; height: 150px; width: 600px; overflow-y: auto; padding:10px;">
                        <pre id="request"></pre>
                    </div>
                </td>
            </tr>
            <tr>
                <td><strong>Response:</strong></td>
                <td>
                    <div style="background-color: #d5edfd; color: white; height: 150px; width: 600px; overflow-y: auto;">
                        <pre id="response"></pre>
                    </div>
                </td>
            </tr>
            <tr>
                <td><strong>Created At:</strong></td>
                <td><span id="createdAt"></span></td>
            </tr>
            <tr>
                <td><strong>Updated At:</strong></td>
                <td><span id="updatedAt"></span></td>
            </tr>
        </table>
    </div>
  </div>
    </div>
</div>

    <script>
      $(document).ready(function () {
          // Attach a click event handler to the "View" buttons
          $('.view-button').click(function () {
              // Get the data from the row
              var id = $(this).data('id');
              var refId = $(this).data('ref-id');
              
              var callType = $(this).data('call-type');
              var status = $(this).data('status');
              var providerName = $(this).data('provider-name');
              var request = $(this).data('request');
              var response = $(this).data('response');
              var createdAt = $(this).data('created-at');
              var updatedAt = $(this).data('updated-at');

              // Set the data in the modal
              $('#modalId').text(id);
              $('#modalRefId').text(refId);
              $('#callType').text(callType);
              $('#status').text(status);
              $('#providerName').text(providerName);
              $('#request').text(JSON.stringify(request, null, 2));
              $('#response').text(JSON.stringify(response, null, 2));
              $('#createdAt').text(createdAt);
              $('#updatedAt').text(updatedAt);

              // Show the modal
              $('#viewModalLog').modal('show');
          });
      });
  </script>
  
    

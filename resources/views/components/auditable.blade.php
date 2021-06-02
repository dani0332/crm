<div class="row">
        <div class="col-md-12 col-sm-12 ">
            <div class="x_panel">
                <div class="x_title">
                    <h2>Audit Logs</h2>
                    <div class="clearfix"></div>

                </div>
                <div class="x_content">
                    <br />
                    <div class="table-responsive">
                    <table id="datatable" class="table table-striped jambo_table">
                          <thead>
                            <tr>
                              <th>Id</th>
                              <th>User</th>
                              <th>Event</th>
                              <th>Old Values</th>
                              <th>New Values</th>
                              <th>Ip Address</th>
                              <th>Created_at</th>
                            </tr>
                          </thead>


                          <tbody>

                            @foreach($audits as $key => $audit)
                            <tr>
                              <td>{{ $audit->id }}</td>
                              <td>{{ $audit->name }}</td>
                              <td>{{ $audit->event }}</td>
                              <td>{{ $audit->old_values }}</td>
                              <td>{{ $audit->new_values }}</td>
                              <td>{{ $audit->ip_address }}</td>
                              <td>{{ $audit->created_at }}</td>
                            </tr>
                            @endforeach

                          </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

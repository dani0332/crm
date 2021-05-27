<div class="row">
        <div class="col-md-12 col-sm-12 ">
            <div class="x_panel">
                <div class="x_title">
                    <h2>Audits</h2>
                    <div class="clearfix"></div>
                    
                </div>
                <div class="x_content">
                    <br />
                    <table id="datatable" class="table table-striped jambo_table table-responsive">
                          <thead>
                            <tr>
                              <th>Id</th>
                              <th>User</th>
                              <th>Event</th>
                              <th>Auditable type</th>
                              <th>Auditable Id</th>
                              <th>Old Values</th>
                              <th>New Values</th>
                              <th>Url</th>
                              <th>Ip Address</th>
                            </tr>
                          </thead>
    
    
                          <tbody>
                            
                            @foreach($audits as $key => $audit)
                            <tr>
                              <td>{{ $audit->id }}</td>
                              <td>{{ $audit->name }}</td>
                              <td>{{ $audit->event }}</td>
                              <td>{{ $audit->auditable_type }}</td>
                              <td>{{ $audit->auditable_id }}</td>
                              <td>{{ $audit->old_values }}</td>
                              <td>{{ $audit->new_values }}</td>
                              <td>{{ $audit->url }}</td>
                              <td>{{ $audit->ip_address }}</td>
                            </tr>
                            @endforeach
                            
                          </tbody>
                        </table>
                </div>
            </div>
        </div>
    </div>
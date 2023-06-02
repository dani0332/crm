@php
use Carbon\Carbon;
@endphp
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
                    <table id="datatable" style="width:100%; table-layout: fixed;" class="table table-striped jambo_table">
                          <thead>
                            <tr>
                              <th>Id</th>
                              <th>User</th>
                              <th>Event</th>
                              <th>Old Values</th>
                              <th>New Values</th>
                              <th>Ip Address</th>
                              <th>Logged At</th>
                            </tr>
                          </thead>


                          <tbody>
                            @foreach($audits as $key => $audit)
                            <tr>
                              <td style="word-wrap:break-word;">{{ $audit->id }}</td>
                              <td style="word-wrap:break-word;">{{ $audit->name }}</td>
                              <td style="word-wrap:break-word;">{{ $audit->event }}</td>
                              <td style="word-wrap:break-word;">{{ $audit->old_values }}</td>
                              <td style="word-wrap:break-word;">
                                  @if($audit->auditable_type == \App\Models\CarQuotePlanDetail::class)
                                      Plan Name : {{$audit->plan_name}} <br/>
                                      Provider Name : {{$audit->provider_name}} <br/>
                                      {{ $audit->new_values }}
                                  @else
                                      {{ $audit->new_values }}
                                  @endif
                              </td>
                              <td style="word-wrap:break-word;">{{ $audit->ip_address }}</td>
                              <td style="word-wrap:break-word;">{{ isset($audit->created_at) ? Carbon::parse($audit->created_at)->format(config('constants.DATETIME_DISPLAY_FORMAT')) : '' }}</td>
                            </tr>
                            @endforeach

                          </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

<div class="row">
        <div class="col-md-12 col-sm-12">
            <div class="x_panel">
                <div class="x_title">
                    <h2>Available Plans Addons</h2>
                    <ul class="nav navbar-right panel_toolbox">
                        <li><a href="{{ url('generic/carplanaddon/create?id='.$record->id) }}" class="btn btn-warning btn-sm">Create Plan Addon</a></li>
                    </ul>
                    <div class="clearfix"></div>
                </div>
                <div class="x_content">
                    <div id="quote-plans">
                        @if($plansList)
                                <table id="addon-datatable" class="table table-striped jambo_table" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>Id</th>
                                            <th>CarPlan Addon Name</th>
                                            <th>CarPlan Addon Name (Arabic)</th>
                                            <th>Code</th>
                                            <th>Type</th>
                                            <th>Created At</th>
                                            <th>Updated At</th>
                                            <!-- <th>Action</th> -->
                                        </tr>
                                    </thead>
                                    <tbody>
                                    @foreach ($plansList as $key => $plan)
                                            <tr>
                                                <td><a href="{{ url('generic/carplanaddon/'.$plan->id) }}">{{$plan->id}}</a></td>
                                                <td>{{ $plan->text }}</td>
                                                <td>{{ $plan->text_ar }}</td>
                                                <td>{{ $plan->code }}</td>
                                                <td>{{ $plan->type }}</td>
                                                <td>{{ $plan->created_at }}</td>
                                                <td>{{ $plan->updated_at }}</td>
                                               
                                            </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            @else
                            <table id="addon-datatable" class="table table-striped jambo_table" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Id</th>
                                        <th>CarPlan Addon Name</th>
                                        <th>CarPlan Addon Name (Arabic)</th>
                                        <th>Code</th>
                                        <th>Type</th>
                                        <th>Created At</th>
                                        <th>Updated At</th>
                                    </tr>
                                </thead>
                            <tbody>
                                <tr class="odd">
                                    <td valign="top" colspan="11" class="dataTables_empty">{{ ucfirst($plansList) }}</td>
                                </tr>
                            </tbody>
                            </table>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

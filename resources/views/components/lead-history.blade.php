    <div class="row">
        <div class="col-md-12 col-sm-12">
            <div class="x_panel">
                <div class="x_title">
                    <h2>Lead History</h2>
                    <div class="clearfix"></div>
                </div>
                <div class="x_content">
                    <div id="lead-history-div">
                        <table id="datatabless" class="table table-striped jambo_table" style="width:100%">
                            <thead>
                                <tr>
                                    <th style="width: 10%;">Modified At</th>
                                    <th style="width: 10%;">Modified By</th>
                                    <th style="width: 10%;">Lead Status</th>
                                    <th style="width: 10%;">Advisor</th>
                                    <th style="width: 60%;">Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($audits as $auditLog)
                                    <tr>
                                        <td>{{ $auditLog->ModifiedAt }}</td>
                                        <td>{{ $auditLog->ModifiedBy }}</td>
                                        <td>{{ $auditLog->NewStatus }}</td>
                                        <td>{{ $auditLog->NewAdvisor }}</td>
                                        <td>{{ $auditLog->NewNotes }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

@extends('layouts.app')
@section('title', 'My Leads')
@section('content')
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <script src="{{ asset('vendors/jquery/dist/jquery.min.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.2/moment.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment-timezone/0.5.34/moment-timezone.min.js"></script>

    <script>
        function formatedDate(date) {
            var newDate = new Date(date);
            var offset = newDate.getTimezoneOffset();
            newDate = new Date(newDate.getTime() - (offset * 60 * 1000));
            newDate = newDate.toISOString().split('T')[0];
            return newDate;
        }
        function changeIcon(item) {
            $(item).find('i').toggleClass("fa-angle-double-down fa-angle-double-up");
        }
        var userId = JSON.parse('<?php echo json_encode(Auth::user()->id); ?>');
        var isAdmin = JSON.parse('<?php echo json_encode(Auth::user()->hasRole('ADMIN')); ?>');
        var teamUserIds = JSON.parse('<?php echo json_encode(Auth::user()->getTeamUserIds()); ?>');
        var isRenewalUser = JSON.parse('<?php echo json_encode(Auth::user()->isRenewalAdvisor()); ?>');
        var isNewUser = JSON.parse('<?php echo json_encode(Auth::user()->isNewBusinessAdvisor()); ?>');
        $(document).ready(function() {
            $('#handler').find('i').toggleClass("fa-angle-double-down fa-angle-double-up");
            $('#collapseOne').collapse();
            $('#mylead-search-submit-btn').on('click', function(e) {
                e.preventDefault();
                if ($('#startedAt').val() != '' && $('#endAt').val() == '') {
                    $('#endAt').next().html('Please select assigned to end date');
                    return false;
                }
                if ($('#startedAt').val() == '' && $('#endAt').val() != '') {
                    $('#startedAt').next().html('Please select assigned to start date');
                    return false;
                }
                if ($('#nfdSart').val() != '' && $('#nfdEnd').val() == '') {
                    $('#nfdEnd').next().html('Please select next followup end date');
                    return false;
                }
                if ($('#nfdSart').val() == '' && $('#nfdEnd').val() != '') {
                    $('#nfdSart').next().html('Please select next followup start date');
                    return false;
                }
                $("span").each(function(k, v) {
                    if ($(v).hasClass('text-danger')) {
                        $(v).html('');
                    }
                });
                $('#my-leads-form').submit();
            });
            var Columns = [];
            if (isRenewalUser && $("#modelType").val() != "Business") {
                Columns.push({
                    data: 'id',
                    name: 'id',
                    render: function(data, type, row) {
                        return "<a target='_blank' href='/quotes/" + $("#modelType").val()
                        .toLowerCase() + '/' + row.uuid + "'>" + row.code + "</a>"
                    }
                }, {
                    data: "clientName",
                    name: "clientName"
                }, {
                    data: "leadStatus",
                    name: "leadStatus"
                }, {
                    data: "createdAt",
                    name: "createdAt"
                }, {
                    data: "vehicleType",
                    name: "vehicleType"
                }, {
                    data: 'previousPolicyPremium',
                    name: 'previousPolicyPremium'
                }, {
                    data: 'renewalBatch',
                    name: 'renewalBatch'
                }, {
                    data: 'previousPolicyExpiryDate',
                    name: 'previousPolicyExpiryDate'
                }, {
                    data: 'previousPolicyNumber',
                    name: 'previousPolicyNumber'
                }, {
                    data: 'carMake',
                    name: 'carMake'
                }, {
                    data: 'carModel',
                    name: 'carModel'
                }, {
                    data: 'yearOfManufacture',
                    name: 'yearOfManufacture'
                }, {
                    data: 'typeOfCarInsurance',
                    name: 'typeOfCarInsurance'
                }, {
                    data: 'currentlyInsuredWith',
                    name: 'currentlyInsuredWith'
                });
            } else if (isRenewalUser && $("#modelType").val() == "Business") {
                Columns.push({
                    data: 'id',
                    name: 'id',
                    render: function(data, type, row) {
                        return "<a target='_blank' href='/quotes/" + $("#modelType").val()
                        .toLowerCase() + '/' + row.uuid + "'>" + row.code + "</a>"
                    }
                }, {
                    data: "clientName",
                    name: "clientName"
                }, {
                    data: "leadStatus",
                    name: "leadStatus"
                }, {
                    data: "createdAt",
                    name: "createdAt"
                }, {
                    data: "updatedAt",
                    name: "updatedAt"
                }, {
                    data: "assignedBy",
                    name: "assignedBy"
                }, {
                    data: 'previous_policy_number',
                    name: 'previous_policy_number'
                }, {
                    data: 'renewal_batch',
                    name: 'renewal_batch'
                }, {
                    data: 'premium',
                    name: 'premium',
                }, {
                    data: 'previous_policy_expiry_date',
                    name: 'previous_policy_expiry_date'
                },{
                    data: 'previous_policy_expiry_date',
                    name: 'previous_policy_expiry_date'
                }, {
                    data: 'company_name',
                    name: 'company_name',
                });
            } else {
                Columns.push({
                    data: 'id',
                    name: 'id',
                    render: function(data, type, row) {
                        return "<a target='_blank' href='/quotes/" + $("#modelType").val()
                        .toLowerCase() + '/' + row.uuid + "'>" + row.code + "</a>"
                    }
                }, {
                    data: "clientName",
                    name: "clientName"
                }, {
                    data: "leadStatus",
                    name: "leadStatus"
                }, {
                    data: "createdAt",
                    name: "createdAt"
                }, {
                    data: "assignedDate",
                    name: "assignedDate"
                }, {
                    data: "assignedBy",
                    name: "assignedBy"
                }, {
                    data: 'nextFollowupDate',
                    name: 'nextFollowupDate',
                }, {
                    data: 'paymentStatus',
                    name: 'paymentStatus',
                });
            }
            var myleadsTable = $("#dtBasicExample-leadsearch").DataTable({
                ordering: false,
                info: false,
                searching: false,
                bLengthChange: false,
                serverSide: true,
                scrollX: true,
                ajax: {
                    url: config.routes.myleadsDataTable,
                    data: function(d) {
                        d.leadType = $("#modelType").val();
                        d.cdbId = $("#cdbId").val();
                        d.leadStatus = $("#leadStatus").val();
                        d.startedAt = $("#startedAt").val();
                        d.endAt = $("#endAt").val();
                        d.nfdSart = $('#nfdSart').val();
                        d.nfdEnd = $('#nfdEnd').val();
                        d.email = $('#email').val();
                        d.teamType = $('#teamType').val();
                        d.paymentStatus = $('#paymentStatus').val();
                        d.renewal_batch = $('#renewal_batch').val();
                        d.previous_policy_number = $('#previous_policy_number').val();
                        d.previous_policy_expiry_date = $('#previous_policy_expiry_date').val();
                        d.previous_policy_expiry_date_end = $('#previous_policy_expiry_date_end').val();
                        d.isEcommerce = $('#isEcommerce').val();
                    },
                },
                columnDefs: [
                    // { orderable: false, targets: [1,2,5,6,] }
                ],
                columns: Columns
            });
            myleadsTable.on('draw', function() {
                var rows = $('#dtBasicExample-leadsearch tr');
                var headerRowColumns = $(rows[0]).children();
                var nextFollowupDateColumn = 0;
                for (let i = 0; i < headerRowColumns.length; i++) {
                    const element = headerRowColumns[i];
                    if (element.outerText == "Next FollowUp Date") {
                        nextFollowupDateColumn = i;
                    }
                    for (let index = 1; index < rows.length; index++) {}
                }
            });
            $('#mylead-reset-btn').on('click', function() {
                $("span").each(function(k, v) {
                    if ($(v).hasClass('text-danger')) {
                        $(v).html('');
                    }
                });
                $(':input', '#my-leads-form')
                    .not(':button, :submit, :reset, :hidden')
                    .val('')
                    .prop('checked', false)
                    .prop('selected', false);
                    window.location.reload();
                $(".loader").show();
                myleadsTable.draw();
                setTimeout(() => {
                    $(".loader").hide();
                }, 1000);
            });
            $("#my-leads-form").submit(function(e) {
                e.preventDefault();
                $(".loader").show();
                myleadsTable.draw();
                //followupLeadsTable.draw();
                $(".loader").hide();
            });
            $(".toggle-btn-2").on("click", function() {
                $(".show-visual-cards").addClass("hideme");
                $(".show-visual-cards").removeClass("showme");
                $(".show-container").addClass("showme");
                $(".show-container").removeClass("hideme");
                $(this).addClass("active");
                $(".toggle-btn").removeClass("active");
            });
            $(".toggle-btn").on("click", function() {
                $(".show-visual-cards").addClass("showme");
                $(".show-visual-cards").removeClass("hideme");
                $(".show-container").removeClass("showme");
                $(".show-container").addClass("hideme");
                $(this).addClass("active");
                $(".toggle-btn-2").removeClass("active");
            });
        });
        var ENDPOINT = "{{ url('/') }}";
        var page;
        var temp_status = '';
        function loadMore(status) {
            if (localStorage.getItem('page' + status) == null)
                page = 2;
            else
                page = localStorage.getItem('page' + status);
            infinteLoadMore(page, status);
        }
        function infinteLoadMore(page, status) {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.ajax({
                    url: ENDPOINT + "/quotes/records?page=" + page + "&modelType=" + "Business" + "&status=" + status +
                        "&myleads=myleads",
                    datatype: "html",
                    type: "post",
                    beforeSend: function() {
                        $('.loader').show();
                    }
                })
                .done(function(response) {
                    $('.loader').hide();
                    if (response.length == 0) {
                        localStorage.removeItem('page' + status, page);
                        $("#load_more_btn" + status).hide();
                        alert("Nothing to Show");
                        return;
                    }
                    $(".status_list" + status + " li:last").append(response);
                    temp_status = status;
                    page = parseInt(page) + 1;
                    localStorage.setItem('page' + status, page);
                })
                .fail(function(jqXHR, ajaxOptions, thrownError) {
                    console.log('Server error occured');
                });
        }
        function searchTerm(element) {
            var term = $(element).val();
            var status = $(element).attr('name');
            if (term) {
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });
                $.ajax({
                        url: ENDPOINT + "/quotes/records/search?term=" + term + "&status=" + status + "&modelType=" +
                            "Business" + "&myleads=myleads",
                        datatype: "html",
                        type: "post",
                        beforeSend: function() {
                            $('.loader').show();
                        }
                    })
                    .done(function(response) {
                        $("#load_more_btn" + status).hide();
                        $(element).val('');
                        $('.loader').hide();
                        if (response.length == 0) {
                            alert("Nothing to Show");
                            return;
                        }
                        $(".status_list" + status).empty();
                        $(".status_list" + status).append(response);
                    })
                    .fail(function(jqXHR, ajaxOptions, thrownError) {
                        console.log('Server error occured');
                    });
            }
        }
        window.onload = function() {
            window.localStorage.clear();
        }
    </script>
    <div class="row">
        <div class="col-md-12 col-sm-12 ">
            <div class="x_panel" style="overflow:hidden">
                <div class="x_title">
                    <h2>My Leads</h2>
                    <div class="clearfix"></div>
                </div>
                <div class="x_content">
                    <button type="button" class="showme btn btn-warning btn-sm toggle-btn  float-right change-layout">Cards
                        View</button>
                    <button type="button"
                        class="showme btn btn-warning btn-sm toggle-btn-2 active float-right change-layout">List
                        View</button>
                    <div class="show-visual-cards hideme">
                        <x-my-leads-visual-card :teamName="$teamName" :leadStatuses="$leadStatusList" />
                    </div>
                    <div class="show-container showme">
                        @if (session()->has('message'))
                            <div class="alert alert-danger">{{ session()->get('message') }}</div>
                        @endif
                        @if (session()->has('success'))
                            <div class="alert alert-success">{{ session()->get('success') }}</div>
                        @endif

                        <form method="POST" id="my-leads-form" class="form-horizontal form-label-left" role="form"
                            data-parsley-validate="" novalidate="" autocomplete="off" style="margin-top:80px">
                            {{ csrf_field() }}
                            @method('POST')
                            <input type="hidden" name="modelType" id="modelType" value="{{ $teamName }}">
                            <div class="item form-group">
                                <div class="col">
                                    <label class="col-form-label col-md-4 col-sm-4" for="Start Date">Assigned Date
                                        Start</label>
                                    <div class="col-md-6 col-sm-6">
                                        <div class="input-group">
                                            <input type="date" name="startedAt" id="startedAt" class="form-control">
                                            <span class="text-danger"></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col">
                                    <label class="col-form-label col-md-4 col-sm-4" for="Start Date">Assigned Date
                                        End</label>
                                    <div class="col-md-6 col-sm-6">
                                        <div class="input-group">
                                            <input type="date" name="endAt" id="endAt" class="form-control">
                                            <span class="text-danger"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @if (!Auth::user()->isRenewalAdvisor())
                                <div class="item form-group">
                                    <div class="col">
                                        <label class="col-form-label col-md-4 col-sm-4" for="Start Date">NextFollowup Date
                                            Start</label>
                                        <div class="col-md-6 col-sm-6">
                                            <div class="input-group">
                                                <input type="date" name="nfdSart" id="nfdSart" class="form-control">
                                                <span class="text-danger"></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <label class="col-form-label col-md-4 col-sm-4" for="End Date">NextFollowup Date
                                            End</label>
                                        <div class="col-md-6 col-sm-6">
                                            <div class="input-group">
                                                <input type="date" name="nfdEnd" id="nfdEnd" class="form-control">
                                                <span class="text-danger"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                            <div class="item form-group">
                                <div class="col">
                                    <label class="col-form-label col-md-4 col-sm-4" for="Start Date">CDB ID</label>
                                    <div class="col-md-6 col-sm-6">
                                        <div class="input-group">
                                            <input type="text" name="cdbId" id="cdbId" class="form-control">
                                        </div>
                                    </div>
                                </div>
                                <div class="col">
                                    <label class="col-form-label col-md-4 col-sm-4" for="Start Date">Lead Status</label>
                                    <div class="col-md-6 col-sm-6">
                                        <div class="input-group">
                                            <select class="form-control" id="leadStatus" name="leadStatus">
                                                <option value="" selected="selected">Select Lead Status</option>
                                                @foreach ($leadStatusList as $item)
                                                    <option value="{{ $item->id }}">{{ $item->text }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @if(!Auth::user()->isRenewalAdvisor())
                                <div class="item form-group">
                                    <div class="col">
                                        <label class="col-form-label col-md-4 col-sm-4" for="policy_number">Policy
                                            Number</label>
                                        <div class="col-md-6 col-sm-6">
                                            <div class="input-group">
                                                <input type="text" name="policy_number" id="policy_number"
                                                    class="form-control">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <label class="col-form-label col-md-4 col-sm-4" for="policy_number"></label>
                                        <div class="col-md-6 col-sm-6">
                                            <div class="input-group">
                                                
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                            @if (Auth::user()->isRenewalAdvisor())
                                <div class="item form-group">
                                    <div class="col">
                                        <label class="col-form-label col-md-4 col-sm-4" for="clientName">Name</label>
                                        <div class="col-md-6 col-sm-6">
                                            <div class="input-group">
                                                <input type="text" name="clientName" id="clientName" class="form-control">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <label class="col-form-label col-md-4 col-sm-4" for="mobile_no">Phone</label>
                                        <div class="col-md-6 col-sm-6">
                                            <div class="input-group">
                                                <input type="text" name="mobile_no" id="mobile_no" class="form-control">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="item form-group">
                                    @if (Auth::user()->isRenewalAdvisor())
                                        <div class="col">
                                            <label class="col-form-label col-md-4 col-sm-4" for="previous_policy_number">Previous Policy Number</label>
                                            <div class="col-md-6 col-sm-6">
                                                <div class="input-group">
                                                    <input type="text" name="previous_policy_number" id="previous_policy_number" class="form-control">
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                    <div class="col">
                                        @if (Auth::user()->isRenewalAdvisor())
                                            <label class="col-form-label col-md-4 col-sm-4" for="Ecommerce">Ecommerce</label>
                                            <div class="col-md-6 col-sm-6">
                                                <div class="input-group">
                                                    <select class="form-control" id="isEcommerce" name="isEcommerce">
                                                        <option value="" selected="selected">Please select is ecommerce</option>
                                                        <option value="Yes">Yes</option>
                                                        <option value="No">No</option>
                                                    </select>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                @if (Auth::user()->isRenewalAdvisor())
                                    <div class="item form-group">
                                        <div class="col">
                                            <label class="col-form-label col-md-4 col-sm-4" for="previous_policy_expiry_date">Prev. Policy Expiry Start</label>
                                            <div class="col-md-6 col-sm-6">
                                                <div class="input-group">
                                                    <input type="date" name="previous_policy_expiry_date" id="previous_policy_expiry_date" class="form-control">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <label class="col-form-label col-md-4 col-sm-4" for="previous_policy_expiry_date_end">Prev. Policy Expiry End</label>
                                            <div class="col-md-6 col-sm-6">
                                                <div class="input-group">
                                                    <input type="date" name="previous_policy_expiry_date_end" id="previous_policy_expiry_date_end" class="form-control">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                                <div class="item form-group">
                                     @if (Auth::user()->isRenewalAdvisor() && $teamName == 'Business')
                                        <div class="col">
                                            <label class="col-form-label col-md-4 col-sm-4" for="company">Company
                                                Name</label>
                                            <div class="col-md-6 col-sm-6">
                                                <div class="input-group">
                                                    <input type="text" name="company" id="company" class="form-control">
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                    <div class="col">
                                            <label class="col-form-label col-md-4 col-sm-4" for="company"></label>
                                            <div class="col-md-6 col-sm-6">
                                                <div class="input-group">
                                                   
                                                </div>
                                            </div>
                                        </div>
                                </div>
                                <div class="item form-group">
                                    <div class="col">
                                        <label class="col-form-label col-md-4 col-sm-4" for="email">Email</label>
                                        <div class="col-md-6 col-sm-6">
                                            <div class="input-group">
                                                <input type="text" name="email" id="email" class="form-control">
                                            </div>
                                        </div>
                                    </div>
                                    @if (Auth::user()->isRenewalAdvisor())
                                        <div class="col">
                                            <label class="col-form-label col-md-4 col-sm-4" for="batch_no">Renewal Batch #</label>
                                            <div class="col-md-6 col-sm-6">
                                                <div class="input-group">
                                                    <input type="text" name="renewal_batch" id="renewal_batch" class="form-control">
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                    @if (!Auth::user()->isRenewalAdvisor())
                                        <div class="col">
                                            <label class="col-form-label col-md-4 col-sm-4" for="is_renewal">Is
                                                Renewal</label>
                                            <div class="col-md-6 col-sm-6">
                                                <div class="input-group">
                                                    <select class="form-control" id="is_renewal" name="is_renewal">
                                                        <option>Please Select</option>
                                                        <option value="1">Yes</option>
                                                        <option value="0">No</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>

                            @endif
                            @if (!Auth::user()->isRenewalAdvisor())
                                <div class="item form-group">

                                    <div class="col">
                                        <label class="col-form-label col-md-4 col-sm-4">Email</label>
                                        <div class="col-md-6 col-sm-6">
                                            <div class="input-group">
                                                <input type="text" name="email" id="email" class="form-control">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col">
                                        <label class="col-form-label col-md-4 col-sm-4" for="Start Date">Lead Type</label>
                                        <div class="col-md-6 col-sm-6">
                                            <div class="input-group">
                                                <select class="form-control" id="teamType" name="teamType">
                                                    @foreach ($allowedTeamTypes as $item)
                                                        <option @if ($item['id'] == $parentTeamId) selected @endif
                                                            value="{{ $item['name'] }}">{{ $item['name'] }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                            <div class="item form-group">
                                <div class="col">
                                    <label class="col-form-label col-md-4 col-sm-4" for="Payment Status">Payment
                                        Status</label>
                                    <div class="col-md-6 col-sm-6">
                                        <div class="input-group">
                                            <select class="form-control" id="paymentStatus" name="paymentStatus">
                                                <option value="" selected="selected">Select Payment Status</option>
                                                @foreach ($paymentStatusList as $item)
                                                    <option value="{{ $item->id }}">{{ $item->text }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col">

                                </div>
                            </div>
                            <div class="item form-group">
                                <div class="col">
                                </div>
                                <div class="col">
                                    <ul class="nav navbar-right panel_toolbox">
                                        <li><input type="submit" id="mylead-search-submit-btn"
                                                class="btn btn-warning btn-sm" value="Search"></li>
                                        <li><input type="reset" id="mylead-reset-btn" class="btn btn-warning btn-sm"></li>
                                    </ul>
                                </div>
                            </div>
                        </form>
                        <table id="dtBasicExample-leadsearch" class="table table-striped jambo_table leadSearch-data-table"
                         style="table-layout: fixed;" width="100%">
                            <thead>
                                <tr>
                                    <th style="width: 100px !important">CDB ID</th>
                                    <th style="width: 100px !important">Client Name</th>
                                    <th style="width: 100px !important">Lead Status</th>
                                    <th style="width: 100px !important">Created Date</th>
                                    @if (Auth::user()->isRenewalAdvisor())
                                        <th style="width: 100px !important">Vehicle Type</th>
                                    @endif
                                    @if (Auth::user()->isRenewalAdvisor())
                                        <th style="width: 100px !important">Previous Policy Premium</th>
                                    @endif
                                    @if (Auth::user()->isRenewalAdvisor())
                                        <th style="width: 100px !important">Renewal Batch #</th>
                                    @endif
                                    @if (Auth::user()->isRenewalAdvisor())
                                        <th style="width: 100px !important">Previous Policy Expiry Date</th>
                                    @endif
                                    @if (Auth::user()->isRenewalAdvisor())
                                        <th style="width: 100px !important">Previous Policy Number</th>
                                    @endif
                                    @if (Auth::user()->isRenewalAdvisor())
                                        <th style="width: 100px !important">Car Make</th>
                                    @endif
                                    @if (Auth::user()->isRenewalAdvisor())
                                        <th style="width: 100px !important">Car Model</th>
                                    @endif
                                    @if (Auth::user()->isRenewalAdvisor())
                                        <th style="width: 100px !important">Year Of Manufacture</th>
                                    @endif
                                    @if (Auth::user()->isRenewalAdvisor())
                                        <th style="width: 100px !important">Type of Car Insurance</th>
                                    @endif
                                    @if (Auth::user()->isRenewalAdvisor())
                                        <th style="width: 100px !important">Current Insurer</th>
                                    @endif
                                    @if (!Auth::user()->isRenewalAdvisor())
                                        <th style="width: 100px !important">Assigned Date</th>
                                    @endif
                                    @if (!Auth::user()->isRenewalAdvisor())
                                        <th style="width: 100px !important">Assigned By</th>
                                    @endif
                                    @if (!Auth::user()->isRenewalAdvisor())
                                        <th style="width: 100px !important">Next FollowUp Date</th>
                                    @endif
                                    @if (!Auth::user()->isRenewalAdvisor())
                                        <th style="width: 100px !important">Payment Status</th>
                                    @endif
                                    @if (Auth::user()->isRenewalAdvisor() && $teamName == 'Business')
                                        <th style="width: 100px !important">Company Name</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="odd">
                                    <td valign="top" colspan="9" class="dataTables_empty">No data available in table</td>
                                </tr>
                            </tbody>
                        </table>

                        <div id="accordion" style="width: 98%;margin-left: 20px;margin-top: 50px;display:none">
                            <div class="card">
                                <div class="card-header" id="headingOne" style="background-color: #4183BD;">
                                    <h5 class="mb-0">
                                        <a style="background-color: transparent;color: white;border: 0px;"
                                            onclick="javascript:changeIcon(this)" id="handler" data-toggle="collapse"
                                            data-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                            <i class="fa fa-angle-double-up" aria-hidden="true"></i> Over Due Leads
                                        </a>

                                    </h5>
                                    <h5><a style="color: floralwhite;float: right;margin-top: -24px;font-size: 25px;">Search
                                            filters doesn't apply on this grid.</a></h5>
                                </div>
                            </div>
                            <div id="collapseOne" class="collapse show" style="display:none" aria-labelledby="headingOne"
                                data-parent="#accordion">
                                <div class="card-body" style="border: 1px solid #ced4da;margin-bottom: 25px;">
                                    <table id="overDueFollowups" class="table table-striped jambo_table" 
                                    style="table-layout: fixed;" width="100%">
                                        <thead>
                                            <tr>
                                                <th style="width: 100px !important">CDB ID</th>
                                                <th style="width: 100px !important">Client Name</th>
                                                <th style="width: 100px !important">Lead Status</th>
                                                <th style="width: 100px !important">Created Date</th>
                                                <th style="width: 100px !important">Assigned Date</th>
                                                <th style="width: 100px !important">Assigned By</th>
                                                <th style="width: 100px !important">Next FollowUp Date</th>
                                                <th style="width: 100px !important">Premium</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr class="odd">
                                                <td valign="top" colspan="9" class="dataTables_empty">No data available in
                                                    table</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@extends('layouts.app')
@section('title', 'My Leads')
@section('content')
@php
    use App\Enums\quoteTypeCode;
    $isRenewalAdvisor = Auth::user()->isRenewalAdvisor();
@endphp
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
        var isRenewalUser = JSON.parse('<?php echo json_encode($isRenewalAdvisor); ?>');
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
                $("span").each(function(k, v) {
                    if ($(v).hasClass('text-danger')) {
                        $(v).html('');
                    }
                });
                $('#my-leads-form').submit();
            });
            var Columns = [];
            if (isRenewalUser && $("#modelType").val() != "Business") {
                if($("#modelType").val() == <?php echo json_encode(quoteTypeCode::Car); ?>) {
                        Columns.push({
                        data: 'id',
                        name: 'id',
                        render: function(data, type, row) {
                            return "<a target='_blank' href='/quotes/" + $("#modelType").val()
                            .toLowerCase() + '/' + row.uuid + "'>" + row.code + "</a>"
                        }
                    }, {
                        data: 'renewalBatch',
                        name: 'renewalBatch'
                    }, {
                        data: 'assignedTo',
                        name: 'assignedTo'
                    }, {
                        data: "firstName",
                        name: "firstName"
                    }, {
                        data: "lastName",
                        name: "lastName"
                    }, {
                        data: 'previousPolicyNumber',
                        name: 'previousPolicyNumber'
                    }, {
                        data: 'previousPolicyExpiryDate',
                        name: 'previousPolicyExpiryDate'
                    }, {
                        data: 'currentlyInsuredWith',
                        name: 'currentlyInsuredWith'
                    }, {
                        data: 'typeOfCarInsurance',
                        name: 'typeOfCarInsurance'
                    }, {
                        data: "leadStatus",
                        name: "leadStatus"
                    }, {
                        data: 'carMake',
                        name: 'carMake'
                    }, {
                        data: 'carModel',
                        name: 'carModel'
                    }, {
                        data: "vehicleType",
                        name: "vehicleType"
                    }, {
                        data: "previousPolicyPremium",
                        name: "previousPolicyPremium"
                    }, {
                        data: "updatedAt",
                        name: "updatedAt"
                    }, {
                        data: 'createdAt',
                        name: 'createdAt'
                    }, {
                        data: 'lostReason',
                        name: 'lostReason'
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
                        data: 'renewalBatch',
                        name: 'renewalBatch'
                    }, {
                        data: "firstName",
                        name: "firstName"
                    }, {
                        data: "lastName",
                        name: "lastName"
                    }, {
                        data: 'previousPolicyNumber',
                        name: 'previousPolicyNumber'
                    }, {
                        data: 'previousPolicyExpiryDate',
                        name: 'previousPolicyExpiryDate'
                    }, {
                        data: "leadStatus",
                        name: "leadStatus"
                    }, {
                        data: 'previousPolicyPremium',
                        name: 'previousPolicyPremium'
                    }, {
                        data: "createdAt",
                        name: "createdAt"
                    });
                }
            } else if (isRenewalUser && $("#modelType").val() == <?php echo json_encode(quoteTypeCode::Business); ?>) {
                Columns.push({
                    data: 'id',
                    name: 'id',
                    render: function(data, type, row) {
                        return "<a target='_blank' href='/quotes/" + $("#modelType").val()
                        .toLowerCase() + '/' + row.uuid + "'>" + row.code + "</a>"
                    }
                }, {
                    data: "firstName",
                    name: "firstName"
                }, {
                    data: "lastName",
                    name: "lastName"
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
                    data: 'previousPolicyNumber',
                    name: 'previousPolicyNumber'
                }, {
                    data: 'renewalBatch',
                    name: 'renewalBatch'
                }, {
                    data: 'previousPolicyPremium',
                    name: 'previousPolicyPremium',
                }, {
                    data: 'previousPolicyExpiryDate',
                    name: 'previousPolicyExpiryDate'
                }, {
                    data: 'companyName',
                    name: 'companyName',
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
                    data: "firstName",
                    name: "firstName"
                }, {
                    data: "lastName",
                    name: "lastName"
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
                        d.nfdEnd = $('#nfdEnd').val();
                        d.email = $('#email').val();
                        d.teamType = $('#teamType').val();
                        d.paymentStatus = $('#paymentStatus').val();
                        d.renewal_batch = $('#renewal_batch').val();
                        d.previous_policy_number = $('#previous_policy_number').val();
                        d.previous_policy_expiry_date = $('#previous_policy_expiry_date').val();
                        d.previous_policy_expiry_date_end = $('#previous_policy_expiry_date_end').val();
                        d.isEcommerce = $('#isEcommerce').val();
                        d.createdAtStart = $('#createdAtStart').val();
                        d.createdAtEnd = $('#createdAtEnd').val();
                        d.vehicleType = $('#vehicleType').val();
                        d.typeOfCarInsurance = $('#typeOfCarInsurance').val();
                        d.currentlyInsuredWith = $('#currentlyInsuredWith').val();

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
    <style>
    div.dataTables_wrapper div.dataTables_processing{
        font-size: 30px !important;
        border: none !important;
        background-color: transparent !important;
        color: #4183BD !important;
        padding: 0px  !important;
        height: 110px !important;
        width: 250px !important;
    }

    .dataTables_paginate .paginate_button.active {
        background: blue !important;
    }
    .pagination{
        margin-top: 12px !important;
    }
    .dataTables_paginate .paginate_button.active a {
        background: #71A1CC !important;
        border-radius: 3px;
        color: white;
    }
    .select2-results__option--highlighted {
        background: #4183BD !important;
        color: #fff;
        cursor: pointer !important;
    }
    </style>
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
                            @if ($isRenewalAdvisor)
                            <div class="item form-group">
                                <div class="col">
                                    <label class="col-form-label col-md-4 col-sm-4" for="Created Date Start">Created Date
                                        Start</label>
                                    <div class="col-md-6 col-sm-6">
                                        <div class="input-group">
                                            <input type="date" name="createdAtStart" id="createdAtStart" class="form-control">
                                            <span class="text-danger"></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col">
                                    <label class="col-form-label col-md-4 col-sm-4" for="Created Date End">Created Date
                                        End</label>
                                    <div class="col-md-6 col-sm-6">
                                        <div class="input-group">
                                            <input type="date" name="createdAtEnd" id="createdAtEnd" class="form-control">
                                            <span class="text-danger"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
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
                            @if(!$isRenewalAdvisor)
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
                            @if ($isRenewalAdvisor)
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
                                    @if ($isRenewalAdvisor)
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
                                        @if ($isRenewalAdvisor)
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
                                <div class="item form-group">
                                     @if ($isRenewalAdvisor && $teamName == quoteTypeCode::Business)
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
                                    @if ($isRenewalAdvisor)
                                        <div class="col">
                                            <label class="col-form-label col-md-4 col-sm-4" for="batch_no">Renewal Batch #</label>
                                            <div class="col-md-6 col-sm-6">
                                                <div class="input-group">
                                                    <input type="text" name="renewal_batch" id="renewal_batch" class="form-control">
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                    @if (!$isRenewalAdvisor)
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
                            @if (!$isRenewalAdvisor)
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
                                    @if ($isRenewalAdvisor && $teamName == quoteTypeCode::Car)
                                    <label class="col-form-label col-md-4 col-sm-4" for="Vehicle Type">Vehicle Type</label>
                                    <div class="col-md-6 col-sm-6">
                                        <div class="input-group">
                                            <select class="form-control" id="vehicleType" name="vehicleType">
                                                <option value="" selected="selected">Select Vehicle Type</option>
                                                @foreach ($vehicleTypeList as $item)
                                                    <option value="{{ $item->id }}">{{ $item->text }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            </div>
                            @if ($isRenewalAdvisor && $teamName == quoteTypeCode::Car)
                            <div class="item form-group">
                                <div class="col">
                                    <label class="col-form-label col-md-4 col-sm-4" for="Type of Car Insurance">Type of Car Insurance</label>
                                    <div class="col-md-6 col-sm-6">
                                        <div class="input-group">
                                            <select class="form-control" id="typeOfCarInsurance" name="typeOfCarInsurance">
                                                <option value="" selected="selected">Select Type of Car Insurance</option>
                                                @foreach ($carTypeInsuranceList as $item)
                                                    <option value="{{ $item->id }}">{{ $item->text }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col">
                                    <label class="col-form-label col-md-4 col-sm-4" for="Currently Insured With">Currently Insured With</label>
                                    <div class="col-md-6 col-sm-6">
                                        <div class="input-group">
                                            <select class="form-control" id="currentlyInsuredWith" name="currentlyInsuredWith">
                                                <option value="" selected="selected">Select Currently Insured With</option>
                                                @foreach ($insuranceProviderList as $item)
                                                    <option value="{{ $item->text }}">{{ $item->text }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif
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

                                    @if ($isRenewalAdvisor)
                                        <th style="width: 100px !important">Renewal Batch #</th>
                                    @endif
                                    @if ($isRenewalAdvisor && $teamName == quoteTypeCode::Car)
                                        <th style="width: 100px !important">Assigned To</th>
                                    @endif

                                    <th style="width: 100px !important">First Name</th>
                                    <th style="width: 100px !important">Last Name</th>

                                    @if ($isRenewalAdvisor)
                                        <th style="width: 100px !important">Previous Policy Number</th>
                                    @endif
                                    @if ($isRenewalAdvisor)
                                        <th style="width: 100px !important">Previous Policy Expiry Date</th>
                                    @endif
                                    @if ($isRenewalAdvisor && $teamName == quoteTypeCode::Car)
                                        <th style="width: 100px !important">Currently Insured with</th>
                                    @endif
                                    @if ($isRenewalAdvisor && $teamName == quoteTypeCode::Car)
                                        <th style="width: 100px !important">Type of Car Insurance</th>
                                    @endif

                                    <th style="width: 100px !important">Lead Status</th>

                                    @if ($isRenewalAdvisor && $teamName == quoteTypeCode::Car)
                                        <th style="width: 100px !important">Car Make</th>
                                    @endif
                                    @if ($isRenewalAdvisor && $teamName == quoteTypeCode::Car)
                                        <th style="width: 100px !important">Car Model</th>
                                    @endif
                                    @if ($isRenewalAdvisor && $teamName == quoteTypeCode::Car)
                                        <th style="width: 100px !important">Vehicle Type</th>
                                    @endif

                                    @if ($isRenewalAdvisor)
                                        <th style="width: 100px !important">Previous Policy Premium</th>
                                    @endif
                                    @if ($isRenewalAdvisor && $teamName == quoteTypeCode::Car)
                                        <th style="width: 100px !important">Last Modified Date</th>
                                    @endif

                                    <th style="width: 100px !important">Created Date</th>

                                    @if ($isRenewalAdvisor && $teamName == quoteTypeCode::Car)
                                        <th style="width: 100px !important">Lost Reason</th>
                                    @endif

                                    @if (!$isRenewalAdvisor)
                                        <th style="width: 100px !important">Assigned Date</th>
                                    @endif
                                    @if (!$isRenewalAdvisor)
                                        <th style="width: 100px !important">Assigned By</th>
                                    @endif

                                    @if (!$isRenewalAdvisor)
                                        <th style="width: 100px !important">Payment Status</th>
                                    @endif
                                    @if ($isRenewalAdvisor && $teamName == quoteTypeCode::Business)
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

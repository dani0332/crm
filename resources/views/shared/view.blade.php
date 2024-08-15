@extends('layouts.app')
@section('title', 'View ' . $model->modelType)
@section('content')
    @inject('crudService', 'App\Services\CRUDService')
    @php
        use App\Enums\quoteTypeCode;
        use App\Enums\RolesEnum;
        use App\Enums\QuoteStatusEnum;
        use App\Enums\PermissionsEnum;
        use App\Enums\GenericRequestEnum;
        use App\Enums\CarTeamType;
        use App\Repositories\UserRepository;
    @endphp
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <script src="{{ asset('vendors/jquery/dist/jquery.min.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script>
        function convertObjectToArray(obj) {
            return Object.keys(obj).map(key => ({
                name: key,
                value: obj[key],
            }));
        }
        String.prototype.replaceAll = function(search, replacement) {
            var target = this;
            return target.replace(new RegExp(search, 'g'), replacement);
        };

        function formatedDate(date) {
            var newDate = new Date(date);
            var offset = newDate.getTimezoneOffset();
            newDate = new Date(newDate.getTime() - (offset * 60 * 1000));
            newDate = newDate.toISOString().split('T')[0];
            return newDate;
        }
        $(document).ready(function() {
            // Getting the required objects from laravel into javascript for checks and handling of data based on roles
            var model = JSON.parse('<?php echo json_encode(get_object_vars($model)); ?>');
            var isAdmin = JSON.parse('<?php echo json_encode(Auth::user()->hasRole('ADMIN')); ?>');
            var isManagerOrDeputy = $("#isManagerOrDeputy").val();
            let canExtractData = '{{ auth()->user()->can(PermissionsEnum::DATA_EXTRACTION) }}';
            var isLeadPool = $("#isLeadPool").val();
            var isNewBusinessUser = JSON.parse('<?php echo json_encode($isNewBusinessUser); ?>');
            var isManualAllocationAllowed = JSON.parse('<?php echo json_encode($isManualAllocationAllowed); ?>');
            var isCarAdvisor = JSON.parse('<?php echo json_encode(Auth::user()->hasRole('CAR_ADVISOR')); ?>');
            // Adding custom search fields for admin role
            if (isAdmin) {
                model.searchProperties.push('is_ecommerce');
                model.searchProperties.push('payment_status_id');
            }
            // validation before form submit usually for date fields
            $('#searchGenericSubmit').on('click', function(e) {
                e.preventDefault();
                if ($('#advisor_assigned_date').val() != '' && $('#advisor_assigned_date_end').val() ==
                    '') {
                    $('#assigned_to_date_end').next().html('Please select assigned to end date');
                    return false;
                }
                if ($('#advisor_assigned_date').val() == '' && $('#advisor_assigned_date_end').val() !=
                    '') {
                    $('#advisor_assigned_date').next().html('Please select assigned start date');
                    return false;
                }
                if ($('#policy_expiry_date').val() == '' && $('#policy_expiry_date_end').val() != '') {
                    $('#policy_expiry_date').next().html('Please select renewal start date');
                    return false;
                }
                if ($('#policy_expiry_date').val() != '' && $('#policy_expiry_date_end').val() == '') {
                    $('#policy_expiry_date_end').next().html('Please select renewal to end date');
                    return false;
                }
                if ($('#previous_policy_expiry_date').val() != '' && $('#previous_policy_expiry_date_end')
                    .val() == '') {
                    $('#previous_policy_expiry_date_end').next().html(
                        'Please select previous policy expiry date to end date');
                    return false;
                }
                if ($('#created_at').val() != '' && $('#created_at_end').val() == '') {
                    $('#created_at_end').next().html('Please select created end date');
                    return false;
                }
                if ($('#created_at').val() == '' && $('#created_at_end').val() != '') {
                    $('#created_at').next().html('Please select created start date');
                    return false;
                }
                if ($('#next_followup_date').val() != '' && $('#next_followup_date_end').val() == '') {
                    $('#next_followup_date_end').next().html('Please select next followup end date');
                    return false;
                }
                if ($('#next_followup_date').val() == '' && $('#next_followup_date_end').val() != '') {
                    $('#next_followup_date').next().html('Please select next followup start date');
                    return false;
                }
                $("span").each(function(k, v) {
                    if ($(v).hasClass('text-danger')) {
                        $(v).html('');
                    }
                });
                $('#searchTable').submit();
            });
            var allowedModelTypes = ['home', 'health', 'life', 'business', 'travel', 'car', 'pet'];
            var skipPropertiesArray = [];
            // Getting the skip properties based on loggedin user role
            skipPropertiesArray = model.skipProperties['list'].split(',');

            // MS: Hide columns for car_advisor - part1
            if (model.modelType == '{{ quoteTypeCode::Car }}' &&
                '{{ Auth::user()->hasRole(RolesEnum::CarAdvisor) }}') {
                skipPropertiesArray.push('source');
                skipPropertiesArray.push('lost_reason');
                skipPropertiesArray.push('assignment_type');
            }
            if (model.modelType == '{{ quoteTypeCode::Car }}' &&
                '{{ Auth::user()->hasRole(RolesEnum::CarManager) }}') {
                model.searchProperties.push('show_renewal_upload_leads');
            }
            var modelPropertiesArray = convertObjectToArray(model.properties);
            $('#modelType').val(model.modelType);
            var dataTableColumns = [];
            for (var i = 0; i < modelPropertiesArray.length; i++) {
                if (!skipPropertiesArray.includes(modelPropertiesArray[i].name)) {
                    // checking if the model type is either leadstatus or teams because it needs to be handled differently
                    if (model.modelType == 'LeadStatus' || model.modelType == 'Team') {
                        // checking if the property is id field to add link on id field
                        if (modelPropertiesArray[i].name == 'id') {
                            dataTableColumns.push({
                                data: "id",
                                name: "id",
                                render: function(data, type, row) {
                                    var url = '/quotes/' + model.modelType.toLowerCase();
                                    return "<a target='_blank' href='" + url + '/' + row.uuid + "'>" +
                                        row.id + "</a>";
                                },
                            });
                        } else {
                            // adding all columns except id field
                            dataTableColumns.push({
                                data: modelPropertiesArray[i].name,
                                name: modelPropertiesArray[i].name
                            });
                        }
                    } else {
                        // adding properties for all types except leadstatus and teams
                        if (modelPropertiesArray[i].name == 'id') {
                            // Handling id field
                            if (isManualAllocationAllowed && allowedModelTypes.includes(model.modelType
                                    .toLocaleLowerCase())) {
                                // Checkboxes should be available if the user is Manager Or deputy also the model type is allowed
                                dataTableColumns.push({
                                    data: "id",
                                    name: "id",
                                    render: function(data, type, row, meta) {
                                        if (row.quote_status_id !=
                                            '{{ QuoteStatusEnum::TransactionApproved }}' || isLeadPool
                                            ) {
                                            return (
                                                '<input type="checkbox" id="tmLeadID" class="tmleadCheckbox" name="tmLeadID" value="' +
                                                data + '">'
                                            );
                                        } else {
                                            return '';
                                        }

                                    },
                                });
                            }
                            // Adding link field for id field
                            var isAllowedModel = allowedModelTypes.includes(model.modelType.toLocaleLowerCase());
                            dataTableColumns.push({
                                data: 'code',
                                name: 'code',
                                render: function(data, type, row) {
                                    var url = '/quotes/' + model.modelType.toLowerCase();
                                    var href = "<a target='_blank' href='" + url + '/' + row.uuid +
                                        "'>" + (isAllowedModel ? row.code : row.id) + "</a>";
                                    return href;
                                }
                            });
                        } else {
                            // Adding all columns except id field
                            if (modelPropertiesArray[i].name !== 'code') {
                                // Handling select field separately because there data is selected in query as field name with suffix of text
                                if (modelPropertiesArray[i].value.indexOf('select') > -1) {
                                    dataTableColumns.push({
                                        data: modelPropertiesArray[i].name + '_text',
                                        name: modelPropertiesArray[i].name
                                    });
                                } else {
                                    dataTableColumns.push({
                                        data: modelPropertiesArray[i].name,
                                        name: modelPropertiesArray[i].name
                                    });
                                }
                            }
                        }
                    }
                }
            }
            var totalRecords = 0;
            // Initializing the datatable
            var vehicleTypeDataTable = $("#dtBasicExample").DataTable({
                ordering: false,
                info: true,
                searching: false,
                dom: 'rBfrtip',
                bLengthChange: false,
                stateSave: true,
                serverSide: true,
                paging: true,
                processing: true,
                scrollX: true,
                ajax: {
                    url: '/quotes/' + model.modelType.toLowerCase(),
                    data: function(d) {
                        var carProps = model.searchProperties;
                        carProps = [...new Set(carProps)];
                        carProps.forEach(element => {
                            d[element] = $('#' + element).val();
                        });
                        if (!isCarAdvisor) {
                            d.advisor_id = $('#advisor_id').val();
                        }
                        d.advisor_assigned_date = $('#advisor_assigned_date').val();
                        d.advisor_assigned_date_end = $('#advisor_assigned_date_end').val();
                        d.policy_expiry_date = $('#policy_expiry_date').val();
                        d.policy_expiry_date_end = $('#policy_expiry_date_end').val();
                        d.policy_number = $('#policy_number').val();
                        if (isManagerOrDeputy == '1') d.show_renewal_upload_leads = $(
                            '#show_renewal_upload_leads').val();
                        if (model.properties['created_at'] && model.properties['created_at'].indexOf(
                                'range') > -1) {
                            d['created_at_end'] = $('#created_at_end').val();
                        }
                        if (model.properties['previous_policy_expiry_date'] && model.properties[
                                'previous_policy_expiry_date'].indexOf(
                                'range') > -1) {
                            d['previous_policy_expiry_date_end'] = $('#previous_policy_expiry_date_end')
                                .val();
                        }
                        if (model.properties['next_followup_date'] && model.properties[
                                'next_followup_date'].indexOf('range') > -1) {
                            d['next_followup_date_end'] = $('#next_followup_date_end').val();
                        }
                    }
                },
                language: {
                    "processing": "<span class='fa-stack fa-lg'>\n\
                                                <i class='fa fa-spinner fa-spin fa-stack-2x fa-fw'></i>\n\
                                        </span>&emsp;Processing ...",
                },
                columns: dataTableColumns,
                buttons: canExtractData ? [{
                    extend: 'excel',
                    text: '<i class="fa fa-file-excel-o" style="color:green;" ></i><div style="font-weight:bold;">Export</div>',
                    title: model.modelType + ' Listing',
                    action: newexportaction
                }] : []
            });
            vehicleTypeDataTable.on('draw', function() {
                var rows = $('#dtBasicExample tr');
                var headerRowColumns = $(rows[0]).children();
                var nextFollowupDateColumn = -10;
                var checkboxIndexes = [];
                for (let i = 0; i < headerRowColumns.length; i++) {
                    const element = headerRowColumns[i];
                    if ($(element).data('type') == 'checkbox' || $(element).data('type') == 'static') {
                        checkboxIndexes.push(i);
                    }
                    if (element.outerText == "NEXT FOLLOWUP DATE") {
                        nextFollowupDateColumn = i;
                    }
                }
                for (let index = 1; index < rows.length; index++) {
                    var columns = $(rows[index]).children();
                    for (let i = 0; i < columns.length; i++) {
                        if (checkboxIndexes.includes(i)) {
                            const element = columns[i];
                            if ($(element).text() == '1') {
                                $(element).text('Yes');
                            } else if ($(element).text() == '0') {
                                $(element).text('No');
                            }
                        }
                    }
                }
                totalRecords = vehicleTypeDataTable.page.info().recordsTotal;
            });
            $("#searchTable").submit(function(e) {
                e.preventDefault();
                $(".loader").show();
                vehicleTypeDataTable.draw();
                setTimeout(() => {
                    $(".loader").hide();
                }, 1000);
            });
            $('#reset-btn-generic').click(function(e) {
                $("span").each(function(k, v) {
                    if ($(v).hasClass('text-danger')) {
                        $(v).html('');
                    }
                });
                $(':input', '#searchTable')
                    .not(':button, :submit, :reset, :hidden')
                    .val('')
                    .prop('checked', false)
                    .prop('selected', false);
                $(".loader").show();
                vehicleTypeDataTable.draw();
                setTimeout(() => {
                    $(".loader").hide();
                }, 1000);
                location.reload();
            });

            function showErrorMessage(msg) {
                $(".errorMsg").empty().hide();
                $(".errorMsg").append('<div class="alert alert-danger">' + msg + '</div>');
                $(".errorMsg").fadeIn(300);
                setTimeout(function() {
                    $(".errorMsg").fadeOut(300, function() {
                        $(".errorMsg").empty().hide();
                    });
                }, 3200);
            }

            function validateDateRange(startDateStr, endDateStr) {

                if (!startDateStr && !endDateStr) {
                    showErrorMessage(
                        'For Export : date range must be selected either created date or advisor assigned date.'
                        );
                    return false;
                }

                if (startDateStr && endDateStr) {
                    var startDateParts = startDateStr.split("-");
                    var endDateParts = endDateStr.split("-");

                    var startDate = new Date(startDateParts[2], startDateParts[1] - 1, startDateParts[0]);
                    var endDate = new Date(endDateParts[2], endDateParts[1] - 1, endDateParts[0]);


                    if (endDate <= startDate) {
                        showErrorMessage('Note : End date must be greater than start date.');
                        return false;
                    }

                    var differenceInMilliseconds = endDate - startDate;
                    var differenceInDays = differenceInMilliseconds / (1000 * 60 * 60 * 24);

                    if (differenceInDays > 31) {
                        showErrorMessage('Note : Date range cannot exceed 31 days.');
                        return false;
                    }

                    return true;
                } else {
                    return false;
                }
            }

            function newexportaction(e, dt, button, config) {
                var advisorStartDate = $('#advisor_assigned_date').val();
                var advisorEndDate = $('#advisor_assigned_date_end').val();
                var createdStartDate = $('#created_at').val();
                var createdEndDate = $('#created_at_end').val();

                if (!validateDateRange(advisorStartDate, advisorEndDate) && !validateDateRange(createdStartDate,
                        createdEndDate)) {
                    return;
                }

                var self = this;
                var oldStart = dt.settings()[0]._iDisplayStart;

                dt.one('preXhr', function(e, s, data) {
                    data.start = 0;
                    data.length = totalRecords;
                    dt.one('preDraw', function(e, settings) {
                        var btnClass = button[0].className;

                        if (btnClass.indexOf('buttons-copy') >= 0) {
                            $.fn.dataTable.ext.buttons.copyHtml5.action.call(self, e, dt, button,
                                config);
                        } else if (btnClass.indexOf('buttons-excel') >= 0) {
                            ($.fn.dataTable.ext.buttons.excelHtml5.available(dt, config) ?
                                $.fn.dataTable.ext.buttons.excelHtml5.action : $.fn.dataTable.ext
                                .buttons.excelFlash.action)
                            .call(self, e, dt, button, config);
                        } else if (btnClass.indexOf('buttons-csv') >= 0) {
                            ($.fn.dataTable.ext.buttons.csvHtml5.available(dt, config) ?
                                $.fn.dataTable.ext.buttons.csvHtml5.action : $.fn.dataTable.ext
                                .buttons.csvFlash.action)
                            .call(self, e, dt, button, config);
                        } else if (btnClass.indexOf('buttons-pdf') >= 0) {
                            ($.fn.dataTable.ext.buttons.pdfHtml5.available(dt, config) ?
                                $.fn.dataTable.ext.buttons.pdfHtml5.action : $.fn.dataTable.ext
                                .buttons.pdfFlash.action)
                            .call(self, e, dt, button, config);
                        } else if (btnClass.indexOf('buttons-print') >= 0) {
                            $.fn.dataTable.ext.buttons.print.action(e, dt, button, config);
                        }

                        dt.one('preXhr', function(e, s, data) {
                            settings._iDisplayStart = oldStart;
                            data.start = oldStart;
                        });

                        setTimeout(dt.ajax.reload, 0);
                        return false;
                    });
                });

                dt.ajax.reload();
            }


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
                $(".toggle-btn").removeClass("active");
            });

            $('.date-search-field').datepicker({
                dateFormat: "dd-mm-yy",
                changeMonth: true,
                changeYear: true,
            });
            $('.date-search-field').prop('readonly', true);
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
                    url: ENDPOINT + "/quotes/records?page=" + page + "&modelType=" + "{{ $model->modelType }}" +
                        "&status=" + status,
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
                            "{{ $model->modelType }}",
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
        $(document).on("click", ".const-create-car-lead", function(e) {
            e.preventDefault();
            $('#createCarLeadModal').modal('show');
            $('.const-car-lead-cnfrm-btn').on('click', function(e) {
                var confirm = $("input[name='reason-manual-lead']:checked").attr('data-confirmation');
                if (confirm) {
                    window.location.href =
                        "{{ url('quotes/' . strtolower($model->modelType) . '/create') }}";
                    localStorage.setItem('throughConfirmation', true);
                    $('#createCarLeadModal').modal('hide');
                } else {
                    $('#createCarLeadModal').modal('hide');
                }
            });
        });

        window.onload = function() {
            window.localStorage.clear();
        }
    </script>
    <div class="row">
        <div class="col-md-12 col-sm-12">
            <div class="x_panel" style="overflow:hidden">
                <div class="x_title">
                    <div class="errorMsg"></div>
                    <h2>{{ str_contains(strtolower($model->modelType), 'team')
                        ? 'Team'
                        : (str_contains(strtolower($model->modelType), 'leadstatus')
                            ? 'Lead Status'
                            : 'Lead') }}
                        List</h2>
                    @if (auth()->user()->hasRole(RolesEnum::CarAdvisor))
                        <h2 style="margin-left: 28%;font-size: 23px;font-weight: 900;color:black;">
                            Auto / Manual Assigned Leads ( {{ $todayAssignmentCount }} ) , Max Cap ( {{ $userMaxCap }} )
                        </h2>
                    @endif
                    @cannot(PermissionsEnum::ApprovePayments)
                        <ul class="nav navbar-right panel_toolbox">
                            @if (str_contains(strtolower($model->modelType), 'team') || str_contains(strtolower($model->modelType), 'leadstatus'))
                                <li><a href="{{ url('quotes/' . strtolower($model->modelType) . '/create') }}"
                                        class="btn btn-warning btn-sm">Create
                                        {{ str_contains(strtolower($model->modelType), 'team')
                                            ? 'Team'
                                            : (str_contains(strtolower($model->modelType), 'leadstatus')
                                                ? 'Lead Status'
                                                : 'Lead') }}</a>
                                </li>
                            @endif
                            @if (strtolower($model->modelType) == strtolower(quoteTypeCode::Business))
                                @can('corpline-quotes-create')
                                    <li><a href="{{ url('quotes/' . strtolower($model->modelType) . '/create') }}"
                                            class="btn btn-warning btn-sm">Create
                                            {{ str_contains(strtolower($model->modelType), 'team')
                                                ? 'Team'
                                                : (str_contains(strtolower($model->modelType), 'leadstatus')
                                                    ? 'Lead Status'
                                                    : 'Lead') }}</a>
                                    </li>
                                @endcan
                            @endif
                            @if (strtolower($model->modelType) != strtolower(quoteTypeCode::Business))
                                @can(strtolower($model->modelType) . '-quotes-create')
                                    <li><a href="{{ url('quotes/' . strtolower($model->modelType) . '/create') }}"
                                            @class([
                                                'btn',
                                                'btn-warning',
                                                'btn-sm',
                                                'const-create-car-lead' => collect([
                                                    strtolower(quoteTypeCode::Car),
                                                ])->contains(strtolower($model->modelType)),
                                            ])>Create
                                            {{ str_contains(strtolower($model->modelType), 'team')
                                                ? 'Team'
                                                : (str_contains(strtolower($model->modelType), 'leadstatus')
                                                    ? 'Lead Status'
                                                    : 'Lead') }}</a>
                                    </li>
                                @endcan
                            @endif
                        </ul>
                    @endcannot
                    <div class="clearfix"></div>
                </div>
                <div class="x_content">
                    @if (
                        $model->modelType == quoteTypeCode::Travel ||
                            $model->modelType == quoteTypeCode::Home ||
                            $model->modelType == quoteTypeCode::Health ||
                            $model->modelType == quoteTypeCode::Business ||
                            $model->modelType == quoteTypeCode::Life)
                        <button type="button" class="btn btn-warning btn-sm toggle-btn change-layout float-right">Cards
                            View</button>
                        <button type="button"
                            class="btn btn-warning btn-sm toggle-btn-2 active change-layout float-right">List
                            View</button>
                        <div class="show-visual-cards hideme">
                            <x-leads-visual-card :model="$model" :dropdownSource="$dropdownSource" />
                        </div>
                    @endif
                    <div class="show-container">
                        @if (session()->has('message'))
                            <div class="alert alert-danger">{!! session()->get('message') !!}</div>
                        @endif
                        @if (session()->has('success'))
                            <div class="alert alert-success">{{ session()->get('success') }}</div>
                        @endif
                        @php
                            $searchProperties = $model->searchProperties;
                            if (
                                auth()
                                    ->user()
                                    ->hasRole(RolesEnum::CarAdvisor) &&
                                array_search('advisor_id', $searchProperties)
                            ) {
                                unset($searchProperties[array_search('advisor_id', $searchProperties)]);
                            }
                            if (
                                $model->modelType == quoteTypeCode::Car &&
                                !Auth::user()->hasRole(RolesEnum::CarManager)
                            ) {
                                unset($searchProperties[array_search('show_renewal_upload_leads', $searchProperties)]);
                            }
                            $sourcePropertiesArry = $crudService->sortMetaArray($model->properties, 'ss:');
                            $skipProperties = $model->skipProperties;
                            if (
                                $model->modelType == quoteTypeCode::Car &&
                                Auth::user()->hasRole(RolesEnum::CarAdvisor)
                            ) {
                                $skipProperties[] = 'source';
                                $skipProperties[] = 'lost_reason';
                                $skipProperties[] = 'assignment_type';
                            }
                        @endphp

                        @if (count($searchProperties) > 0)
                            <form method="POST" id="searchTable" class="form-horizontal form-label-left" role="form"
                                data-parsley-validate="" novalidate="" autocomplete="off">
                                @foreach ($sourcePropertiesArry as $property => $value)
                                    @foreach ($searchProperties as $searchProperty)
                                        @if ($searchProperty == $property)
                                            @if (strpos($value, 'input') !== false && !str_contains($value, 'range'))
                                                <x-input-filter :customtitles="$customTitles" :property="$property" :errors="$errors"
                                                    :value="$value" />
                                            @endif
                                            @if (strpos($value, 'select') !== false && str_contains($value, 'multiple'))
                                                <x-multi-select-filter :customtitles="$customTitles" :property="$property" :errors="$errors"
                                                    :model="$model" :value="$value" :dropdownSource="$dropdownSource" />
                                            @endif
                                            @if (str_contains($value, 'range'))
                                                <x-date-range-filter :customtitles="$customTitles" :property="$property"
                                                    :errors="$errors" />
                                            @else
                                                @if ((strpos($value, 'select') !== false || strpos($value, 'static') !== false) && !str_contains($value, 'multiple'))
                                                    <x-select-filter :customtitles="$customTitles" :property="$property"
                                                        :errors="$errors" :model="$model" :value="$value"
                                                        :dropdownSource="$dropdownSource" />
                                                @endif
                                            @endif
                                        @endif
                                    @endforeach
                                @endforeach
                                <div class="col-md-12" style="margin-top: 25px;">
                                    <div class="col">
                                        <ul class="nav navbar-right panel_toolbox">
                                            <li><input type="submit" value="Search" id="searchGenericSubmit"
                                                    class="btn btn-warning btn-sm"></li>
                                            <li><input type="reset" id="reset-btn-generic" class="btn btn-warning btn-sm">
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </form>
                        @endif

                        <form method="post"
                            @if (strtolower($model->modelType) == strtolower(quoteTypeCode::Health)) action="wcuAssign" @else action={{ strtolower($model->modelType) . '/manualLeadAssign' }} @endif
                            class="form-horizontal form-label-left" role="form" data-parsley-validate="" novalidate=""
                            autocomplete="off">
                            {{ csrf_field() }}
                            <input type="hidden" value="{{ strtolower($model->modelType) }}" name="modelType">
                            <div class="row" id="tm-leads-assign-div" style="display: none;">
                                <div class="col-md-12 col-sm-12">
                                    <div class="x_panel">
                                        <div class="x_title">
                                            <h2>Assign Leads</h2>
                                            <div class="clearfix"></div>
                                        </div>
                                        <div class="x_content" id="form-to-show">
                                            <div class="item form-group">
                                                @if (strtolower($model->modelType) == strtolower(quoteTypeCode::Health))
                                                    <div class="col-md-4 col-sm-4" id="healthTeamTypeAssignDiv">
                                                        <label
                                                            style="margin-left: 8px;font-size: 16px;font-weight: bolder;">Assign
                                                            Health Team Type</label>
                                                        <span class='required' style="margin-left:10px;">*</span>
                                                        @php
                                                            $updatedAdvisors = $advisors;
                                                        @endphp
                                                        <select class="form-control" id="assign_team" name="assign_team"
                                                            readonly="readonly" style="margin-bottom: 10px;">
                                                            <option value="">Select Health Team Type</option>
                                                            <option selected="selected" value="Wow-Call">Wow Call
                                                            </option>
                                                        </select>
                                                        <span class="text-danger" id="teamErrorSpan"
                                                            style="display: none;font-size: 15px;margin-left: 6px;font-weight: bolder;">Health
                                                            Team Type must be selected</span>
                                                    </div>
                                                @endif
                                                <div class="col-md-4 col-sm-4" style="float:left; margin-left: 10px;">
                                                    <label
                                                        style="margin-left: 8px;font-size: 16px;font-weight: bolder;">Assign
                                                        Advisor</label>
                                                    <span class='required' style="margin-left:10px;">*</span>
                                                    @php
                                                        $updatedAdvisors = $advisors;
                                                        if (
                                                            strtolower($model->modelType) !=
                                                            strtolower(quoteTypeCode::Health)
                                                        ) {
                                                            if (!empty($renewalAdvisors)) {
                                                                $updatedAdvisors = $renewalAdvisors;
                                                            }
                                                        }
                                                    @endphp
                                                    <select class="form-control" id="assigned_to_id_new"
                                                        name="assigned_to_id_new">
                                                        @foreach ($updatedAdvisors as $handler)
                                                            @if (Auth::user()->isHealthWCUAdvisor() && strtolower($model->modelType) == strtolower(quoteTypeCode::Health))
                                                            @else
                                                                <option value="{{ $handler->id }}">{{ $handler->name }}
                                                                </option>
                                                            @endif
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-md-2 col-sm-2">
                                                    <button type="submit" id="tmLeadsAssignToUser"
                                                        name="tmLeadsAssignToUser" style="margin-top:34px;"
                                                        class="btn btn-warning btn-sm">Assign</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" id="modelType" name="modelType"
                                value={{ strtolower($model->modelType) }}>
                            <input type="hidden" id="displayTmLeadsDownloadCsvIcon" name="displayTmLeadsDownloadCsvIcon"
                                value="">
                            <input type="hidden" id="selectTmLeadId" name="selectTmLeadId" value="">
                            <input type="hidden" id="isManagerOrDeputy" name="isManagerOrDeputy"
                                value="{{ $isManagerORDeputy }}">
                            <input type="hidden" id="isLeadPool" name="isLeadPool" value="{{ $isLeadPool }}">
                            <input type="hidden" id="isManualAllocationAllowed" name="isManualAllocationAllowed"
                                value="{{ $isManualAllocationAllowed }}">
                        </form>


                        @if (isset($showDeadlineAlert) && $showDeadlineAlert)
                            <div class="row">
                                <div class="col-sm-12 text-center">
                                    <span class="text-danger">Alert! Deadline for submission for Batch
                                        {{ $upcomingBatch->name }} uncontactable is on
                                        {{ $upcomingBatch->deadline->deadline_date }}</span>
                                </div>
                            </div>
                        @endif


                        @php
                            $updatedTitles = ['premium' => 'price'];
                        @endphp
                        <table id="dtBasicExample" class="table-striped jambo_table table" style="table-layout: fixed;"
                            width="100%">
                            <thead>
                                <tr>
                                    @if (
                                        $isManualAllocationAllowed &&
                                            str_contains('home,health,life,business,travel,car,pet', strtolower($model->modelType)))
                                        <th style="width: 15px;"><input type="checkbox" id="checkAllTmLeads"
                                                name="checkAllTmLeads" value=""></th>
                                    @endif
                                    @if ($model->modelType == quoteTypeCode::Car && Auth::user()->hasRole(RolesEnum::CarAdvisor))
                                        @php
                                            // MS: Hide columns for car_advisor - part3
                                            $skipProperties['list'] = $skipProperties['list'] . ',source';
                                            $skipProperties['list'] = $skipProperties['list'] . ',lost_reason';
                                            $skipProperties['list'] = $skipProperties['list'] . ',assignment_type';
                                        @endphp
                                    @endif
                                    @foreach ($model->properties as $property => $value)
                                        @if ($model->modelType != 'LeadStatus' && $model->modelType != 'Team')
                                            @if ($property != 'id')
                                                @if (!in_array($property, explode(',', $skipProperties['list'])))
                                                    @php
                                                        $textDecorations = $tooltip = '';
                                                        if (
                                                            strpos($value, 'title') &&
                                                            strtoupper($customTitles[$property]) == 'REF-ID'
                                                        ) {
                                                            $textDecorations =
                                                                'text-decoration: underline; text-decoration-style: dotted;';
                                                            $tooltip = 'Reference ID';
                                                        }
                                                    @endphp
                                                    <th data-type="{{ explode('|', $value)[1] }}" data-toggle="tooltip"
                                                        data-placement="top" title="{{ $tooltip }}"
                                                        style="width: 180px !important; {{ $textDecorations }}">
                                                        @if (strpos($value, 'title'))
                                                            {{ strtoupper(array_key_exists(strtolower($customTitles[$property]), $updatedTitles) ? $updatedTitles[strtolower($customTitles[$property])] : $customTitles[$property]) }}
                                                        @else
                                                            {{ str_replace('_', ' ', strtoupper(array_key_exists(strtolower($property), $updatedTitles) ? $updatedTitles[strtolower($property)] : $property)) }}
                                                        @endif
                                                    </th>
                                                @endif
                                            @endif
                                        @else
                                            @if (!in_array($property, explode(',', $skipProperties['list'])))
                                                <th data-type="{{ explode('|', $value)[1] }}"
                                                    style="width: 180px !important">
                                                    @if (strpos($value, 'title'))
                                                        {{ strtoupper(array_key_exists(strtolower($customTitles[$property]), $updatedTitles) ? $updatedTitles[strtolower($customTitles[$property])] : $customTitles[$property]) }}
                                                    @else
                                                        {{ str_replace('_', ' ', strtoupper(array_key_exists($property, $updatedTitles) ? $updatedTitles[strtolower($property)] : $property)) }}
                                                    @endif
                                                </th>
                                            @endif
                                        @endif
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>

    @if (collect([strtolower(quoteTypeCode::Car)])->contains(strtolower($model->modelType)))
        @include('components.car-create-lead-modal')
    @endif

@endsection

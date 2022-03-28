@extends('layouts.app')
@section('title', 'View ' . $model->modelType)
@section('content')
<?php
use App\Enums\quoteTypeCode;
?>
<meta name="csrf-token" content="{{ csrf_token() }}" />
    <script src="{{ asset('vendors/jquery/dist/jquery.min.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <style>
        div.dt-buttons {
            position: relative;
            float: left;
        }

        td {
            word-wrap: break-word;
        }

    </style>
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
            newDate = new Date(newDate.getTime() - (offset*60*1000));
            newDate = newDate.toISOString().split('T')[0];
            return newDate;
        }
        $(document).ready(function() {
            // Getting the required objects from laravel into javascript for checks and handling of data based on roles
            var isRenewalUser = JSON.parse('<?php echo json_encode($isRenewalUser); ?>');
            var model = JSON.parse('<?php echo json_encode(get_object_vars($model)); ?>');
            var isAdmin = JSON.parse('<?php echo json_encode(Auth::user()->hasRole("ADMIN")); ?>');
            var isManagerOrDeputy = $("#isManagerOrDeputy").val();
            // Adding custom search fields for admin role
            if(isAdmin && !isRenewalUser) {
                model.searchProperties.push('is_ecommerce');
                model.searchProperties.push('payment_status_id');
            }
            // validation before form submit usually for date fields
            $('#searchGenericSubmit').on('click', function(e){
                e.preventDefault();
                if($('#assigned_to_date_start').val() != '' && $('#assigned_to_date_end').val() == '')  {
                    $('#assigned_to_date_end').next().html('Please select assigned to end date');
                    return false;
                }
                if($('#assigned_to_date_start').val() == '' && $('#assigned_to_date_end').val() != '')  {
                    $('#assigned_to_date_start').next().html('Please select assigned start date');
                    return false;
                }
                if($('#renewal_expiry_date').val() == '' && $('#renewal_expiry_date_end').val() != '')  {
                    $('#renewal_expiry_date').next().html('Please select renewal start date');
                    return false;
                }

                if($('#renewal_expiry_date').val() != '' && $('#renewal_expiry_date_end').val() == '')  {
                    $('#renewal_expiry_date_end').next().html('Please select renewal to end date');
                    return false;
                }

                if($('#created_at').val() != '' && $('#created_at_end').val() == '')  {
                    $('#created_at_end').next().html('Please select created end date');
                    return false;
                }
                if($('#created_at').val() == '' && $('#created_at_end').val() != '')  {
                    $('#created_at').next().html('Please select created start date');
                    return false;
                }
                if($('#next_followup_date').val() != '' && $('#next_followup_date_end').val() == '') {
                    $('#next_followup_date_end').next().html('Please select next followup end date');
                    return false;
                }
                if($('#next_followup_date').val() == '' && $('#next_followup_date_end').val() != '') {
                    $('#next_followup_date').next().html('Please select next followup start date');
                    return false;
                }
                $("span").each(function (k, v) {
                    if($(v).hasClass('text-danger')){
                        $(v).html('');
                    }
                });
                $('#searchTable').submit();
            });
            var allowedModelTypes = ['home', 'health', 'life', 'business', 'travel', 'car'];
            var skipPropertiesArray = [];
            // Getting the skip properties based on loggedin user role
            if(isRenewalUser && model.modelType.toLowerCase() == 'car'){
                skipPropertiesArray = model.renewalSkipProperties['list'].split(',');
            } else {
                skipPropertiesArray = model.skipProperties['list'].split(',');
            }

            var modelPropertiesArray = convertObjectToArray(model.properties);
            $('#modelType').val(model.modelType);
            var dataTableColumns = [];
            for (var i = 0; i < modelPropertiesArray.length; i++) {
                if (!skipPropertiesArray.includes(modelPropertiesArray[i].name)) {
                    // checking if the model type is either leadstatus or teams because it needs to be handled differently
                    if(model.modelType == 'LeadStatus' || model.modelType == 'Teams') {
                        // checking if the property is id field to add link on id field
                            if(modelPropertiesArray[i].name == 'id'){
                                dataTableColumns.push({
                                    data: "id",
                                    name: "id",
                                    render: function(data, type, row) {
                                        var url = '/quotes/' + model.modelType.toLowerCase();
                                        return "<a target='_blank' href='" + url + '/' + row.uuid + "'>" + row.id + "</a>";
                                    },
                                });
                            }else {
                                // adding all columns except id field
                                dataTableColumns.push({
                                    data: modelPropertiesArray[i].name,
                                    name: modelPropertiesArray[i].name
                                });
                            }

                    }else{
                        // adding properties for all types except leadstatus and teams
                        if (modelPropertiesArray[i].name == 'id' ) {
                            // Handling id field
                            if (isManagerOrDeputy === "1" && allowedModelTypes.includes(model.modelType.toLocaleLowerCase())) {
                                // Checkboxes should be available if the user is Manager Or deputy also the model type is allowed
                                dataTableColumns.push({
                                    data: "id",
                                    name: "id",
                                    render: function(data, type, row, meta) {

                                        return (
                                            '<input type="checkbox" id="tmLeadID" class="tmleadCheckbox" name="tmLeadID" value="' +
                                            data + '">'
                                        );

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
                                    var href = "<a target='_blank' href='" + url + '/' + row.uuid + "'>" + (isAllowedModel ? row.code : row.id) + "</a>";
                                    return href;
                                }
                            });
                        } else {
                            // Adding all columns except id field
                            if(modelPropertiesArray[i].name !== 'code'){
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
            // Handling Sorting on Grid Column, need switch case because Manager,Deputy and Admin have different set of columns then normal user
            var disableSortColumns = [];
            switch(model.modelType.toLowerCase()) {

                case 'car':
                    if(isRenewalUser){
                        if(isManagerOrDeputy){
                        disableSortColumns = [-1,1,2,3,4,5,6,7,8,9,10,14,15,16,17,18];
                        }else{
                            disableSortColumns = [-1,1,2,3,4,5,6,7,8,9,13,14,15,16,17];
                        }
                    }
                    else if(isManagerOrDeputy){
                        disableSortColumns = [-1,1,2,3,4,5,6,7,8,9,10,11,15];
                    }
                    else if (isAdmin){
                        disableSortColumns = [-1,1,2,3,4,5,6,7,8,9,10,14,15];
                    }
                    else {
                        disableSortColumns = [-1,1,2,3,4,5,6,7,8,9,10,14,15];
                    }
                    break;
                case 'home':
                    if(isManagerOrDeputy == '1' || isAdmin)
                        disableSortColumns = [-1,1,2,3,4,5,9,10];
                    else
                        disableSortColumns = [-1,0,1,2,3,4,8,9,10];
                    break;
                    break;
                case 'health':
                    if(isManagerOrDeputy == '1' || isAdmin)
                        disableSortColumns = [-1,1,2,3,4,5,9,10];
                    else
                        disableSortColumns = [-1,0,1,2,3,4,8,9,10];
                    break;
                case 'life':
                    if(isManagerOrDeputy == '1' || isAdmin)
                        disableSortColumns = [-1,1,2,3,4,5,9,10];
                    else
                        disableSortColumns = [-1,0,1,2,3,4,8,9,10];
                    break;
                case 'business':
                    if(isManagerOrDeputy == '1' || isAdmin)
                        disableSortColumns = [-1,1,2,3,4,6,7,8,9,12,13];
                    else
                        disableSortColumns = [-1,0,1,2,3,5,6,7,8,11,12,13];
                    break;
                case 'travel':
                    if(isManagerOrDeputy == '1' || isAdmin)
                        disableSortColumns = [-1,1,2,3,4,5,9,10];
                    else
                        disableSortColumns = [-1,0,1,2,3,4,8,9,10];
                    break;
                default:
                disableSortColumns = [];
                    break;
            }
            // Initializing the datatable
            var vehicleTypeDataTable = $("#dtBasicExample").DataTable({
                ordering: true,
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
                        var carProps = isRenewalUser ? model.renewalSearchProperties : model.searchProperties;
                        carProps = [...new Set(carProps)];
                        carProps.forEach(element => {
                            d[element] = $('#' + element).val();
                        });
                        d.assigned_to_date_start = $('#assigned_to_date_start').val();
                        d.assigned_to_date_end = $('#assigned_to_date_end').val();
                        d.renewal_expiry_date = $('#renewal_expiry_date').val();
                        d.renewal_expiry_date_end = $('#renewal_expiry_date_end').val();
                        if (model.properties['created_at'] && model.properties['created_at'].indexOf('range') > -1) {
                            d['created_at_end'] = $('#created_at_end').val();
                        }
                    }
                },
                columnDefs: [
                    { orderable: false, targets: disableSortColumns }
                    ],
                columns: dataTableColumns,
                buttons: [{
                    extend: 'excel',
                    text: '<i class="fa fa-file-excel-o" style="color:green;" ></i><div style="font-weight:bold;">Export</div>',
                    title: model.modelType + ' Listing',
                    action: newexportaction
                }]
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
                    if(element.outerText == "NEXT FOLLOWUP DATE"){
                        nextFollowupDateColumn = i;
                    }
                }

                for (let index = 1; index < rows.length; index++) {
                    var columns = $(rows[index]).children();
                    for (let i = 0; i < columns.length; i++) {
                        if(i == nextFollowupDateColumn && $(columns[i]).text() != ""){
                            if(formatedDate(new Date()) > formatedDate(new Date($(columns[i]).text()))){
                                $(rows[index]).children().eq(i).css({'color': 'white', 'background-color': 'red', 'font-weight': 'bold', 'font-size': '12px'});
                            }
                        }
                        if (checkboxIndexes.includes(i)) {
                            const element = columns[i];
                            if ($(element).text() == '1') {
                                $(element).text('True');
                            } else if ($(element).text() == '0') {
                                $(element).text('False');
                            }
                        }
                    }

                }
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
                $("span").each(function (k, v) {
                    if($(v).hasClass('text-danger')){
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
            // Custom export function to export all the available rows in grid not just the visible ones
            function newexportaction(e, dt, button, config) {
                var self = this;
                var oldStart = dt.settings()[0]._iDisplayStart;
                dt.one('preXhr', function(e, s, data) {
                    data.start = 0;
                    data.length = 2147483647;
                    dt.one('preDraw', function(e, settings) {
                        if (button[0].className.indexOf('buttons-copy') >= 0) {
                            $.fn.dataTable.ext.buttons.copyHtml5.action.call(self, e, dt, button,
                                config);
                        } else if (button[0].className.indexOf('buttons-excel') >= 0) {
                            $.fn.dataTable.ext.buttons.excelHtml5.available(dt, config) ?
                                $.fn.dataTable.ext.buttons.excelHtml5.action.call(self, e, dt,
                                    button, config) :
                                $.fn.dataTable.ext.buttons.excelFlash.action.call(self, e, dt,
                                    button, config);
                        } else if (button[0].className.indexOf('buttons-csv') >= 0) {
                            $.fn.dataTable.ext.buttons.csvHtml5.available(dt, config) ?
                                $.fn.dataTable.ext.buttons.csvHtml5.action.call(self, e, dt, button,
                                    config) :
                                $.fn.dataTable.ext.buttons.csvFlash.action.call(self, e, dt, button,
                                    config);
                        } else if (button[0].className.indexOf('buttons-pdf') >= 0) {
                            $.fn.dataTable.ext.buttons.pdfHtml5.available(dt, config) ?
                                $.fn.dataTable.ext.buttons.pdfHtml5.action.call(self, e, dt, button,
                                    config) :
                                $.fn.dataTable.ext.buttons.pdfFlash.action.call(self, e, dt, button,
                                    config);
                        } else if (button[0].className.indexOf('buttons-print') >= 0) {
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
            $(".toggle-btn").on("click", function() {
                $(".show-visual-cards").addClass("showme");
                $(".show-container").removeClass("showme");
                $(".show-container").addClass("hideme");
                $(this).addClass("active");
                $(".toggle-btn-2").removeClass("active");
            });
            $(".toggle-btn-2").on("click", function() {
                $(".show-visual-cards").addClass("hideme");
                $(".show-visual-cards").removeClass("showme");
                $(".show-container").addClass("showme");
                $(this).addClass("active");
                $(".toggle-btn").removeClass("active");
            });

        });

        var ENDPOINT = "{{ url('/') }}";
        var page;
        var temp_status = '';
        function loadMore(status) {
            if(localStorage.getItem('page'+status) == null)
                page = 2;
            else
                page = localStorage.getItem('page'+status);
            infinteLoadMore(page,status);
        }
        function infinteLoadMore(page,status) {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.ajax({
                    url: ENDPOINT + "/quotes/records?page=" + page +"&modelType=" + "{{ $model->modelType}}" + "&status=" + status,
                    datatype: "html",
                    type: "post",
                    beforeSend: function () {
                        $('.loader').show();
                    }
                })
                .done(function (response) {
                    $('.loader').hide();
                    if (response.length == 0) {
                        localStorage.removeItem('page'+status, page);
                        $("#load_more_btn"+status).hide();
                        alert("Nothing to Show");
                        return;
                    }
                    $(".status_list"+status+" li:last").append(response);
                    temp_status = status;
                    page = parseInt(page) + 1;
                    localStorage.setItem('page'+status, page);
                })
                .fail(function (jqXHR, ajaxOptions, thrownError) {
                    console.log('Server error occured');
                });
        }

        function searchTerm(element) {
            var term = $(element).val();
            var status = $(element).attr('name');
            if(term) {
                $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.ajax({
                    url: ENDPOINT + "/quotes/records/search?term=" + term + "&status=" + status +"&modelType=" + "{{ $model->modelType}}",
                    datatype: "html",
                    type: "post",
                    beforeSend: function () {
                        $('.loader').show();
                    }
                })
                .done(function (response) {
                    $("#load_more_btn"+status).hide();
                    $(element).val('');
                    $('.loader').hide();
                    if (response.length == 0) {
                        alert("Nothing to Show");
                        return;
                    }
                    $(".status_list"+status).empty();
                    $(".status_list"+status).append(response);
                })
                .fail(function (jqXHR, ajaxOptions, thrownError) {
                    console.log('Server error occured');
                });
            }

        }
        window.onload = function () {
            window.localStorage.clear();
        }
    </script>
    <div class="row">
        <div class="col-md-12 col-sm-12 ">
            <div class="x_panel" style="overflow:hidden">
                <div class="x_title">
                    <h2>{{ str_contains(strtolower($model->modelType), 'teams') ? 'Teams' : (str_contains(strtolower($model->modelType), 'leadstatus') ? 'Lead Status' : $model->modelType) }}
                        List</h2>
                    <ul class="nav navbar-right panel_toolbox">
                        @if(str_contains(strtolower($model->modelType), 'teams') || str_contains(strtolower($model->modelType), 'leadstatus'))
                            <li><a href="{{ url('quotes/' . strtolower($model->modelType) . '/create') }}"
                                class="btn btn-warning btn-sm">Create
                                    {{ str_contains(strtolower($model->modelType), 'teams') ? 'Team' : (str_contains(strtolower($model->modelType), 'leadstatus') ? 'Lead Status' : 'Lead') }}</a>
                            </li>
                        @endif
                        @if(strtolower($model->modelType) == 'business')
                        @can('corpline-quotes-create')
                            <li><a href="{{ url('quotes/' . strtolower($model->modelType) . '/create') }}"
                                    class="btn btn-warning btn-sm">Create
                                    {{ str_contains(strtolower($model->modelType), 'teams') ? 'Team' : (str_contains(strtolower($model->modelType), 'leadstatus') ? 'Lead Status' : 'Lead') }}</a>
                            </li>
                        @endcan
                        @endif
                        @can(strtolower($model->modelType) . '-quotes-create')
                            <li><a href="{{ url('quotes/' . strtolower($model->modelType) . '/create') }}"
                                    class="btn btn-warning btn-sm">Create
                                    {{ str_contains(strtolower($model->modelType), 'teams') ? 'Team' : (str_contains(strtolower($model->modelType), 'leadstatus') ? 'Lead Status' : 'Lead') }}</a>
                            </li>
                        @endcan

                    </ul>
                    <div class="clearfix"></div>
                </div>
                <div class="x_content">
                @php $dynamicClass = "showme"; @endphp
                @if($model->modelType == quoteTypeCode::Travel || $model->modelType == quoteTypeCode::Home || $model->modelType == quoteTypeCode::Health || $model->modelType == quoteTypeCode::Business || $model->modelType == quoteTypeCode::Life)
                @php $dynamicClass = "hideme"; @endphp
                <button type="button" class="btn btn-warning btn-sm toggle-btn active float-right change-layout">Cards View</button>
                <button type="button" class="btn btn-warning btn-sm toggle-btn-2 float-right change-layout">List View</button>
                <div class="show-visual-cards showme">
                        <x-leads-visual-card
                            :model="$model"
                            :dropdownSource="$dropdownSource"
                        />
                    </div>
                @endif
                    <div class="show-container {{$dynamicClass}}">
                    @if (session()->has('message'))
                        <div class="alert alert-danger">{{ session()->get('message') }}</div>
                    @endif
                    @if (session()->has('success'))
                        <div class="alert alert-success">{{ session()->get('success') }}</div>
                    @endif
                    @php
                        $searchProperties = [];
                        $skipProperties = [];
                        if($isRenewalUser && strtolower($model->modelType) == 'car'){
                            $searchProperties = $model->renewalSearchProperties;
                            $skipProperties = $model->renewalSkipProperties;
                        }
                        else{
                            $searchProperties = $model->searchProperties;
                            $skipProperties = $model->skipProperties;
                        }
                    @endphp
                    @if (count($searchProperties) > 0)
                        <form method="POST" id="searchTable" class="form-horizontal form-label-left" role="form"
                            data-parsley-validate="" novalidate="" autocomplete="off">

                            @if(strtolower($model->modelType) != 'teams' && strtolower($model->modelType) != 'leadstatus')
                                <div class="col-md-6">
                                    <span style="font-size: 11px;" class="col-form-label col-md-6 col-sm-6" >ASSIGNED START DATE</span>
                                    <input type="date" class="form-control" id="assigned_to_date_start" name="assigned_to_date_start" />
                                    <span class="text-danger"></span>
                                </div>
                                <div class="col-md-6">
                                    <span style="font-size: 11px;" class="col-form-label col-md-6 col-sm-6" >ASSIGNED END DATE</span>
                                    <input type="date" class="form-control" id="assigned_to_date_end" name="assigned_to_date_end" />
                                    <span class="text-danger"></span>
                                </div>
                            @endif
                            @if (Auth::user()->hasRole('ADMIN') && $model->modelType == 'Car')
                                @php
                                    array_push($searchProperties, 'is_ecommerce');
                                    array_push($searchProperties, 'payment_status_id');
                                    $searchProperties = array_unique($searchProperties);
                                @endphp
                            @endif
                            @foreach ($model->properties as $property => $value)
                                @foreach ($searchProperties as $searchProperty)
                                    @if ($searchProperty == $property)
                                        @if (str_contains($value, 'range'))
                                            <div class="col-md-6">
                                                <span style="font-size: 11px;" class="col-form-label col-md-6 col-sm-6"
                                                    for="name">
                                                    {{ strtoupper($customTitles[$property]) . ' START' }}
                                                </span>
                                                <input type="date" id="{{ $property }}" name="{{ $property }}"
                                                    class="form-control">
                                                @if ($errors->has($property))
                                                    <span class="text-danger">{{ $errors->first($property) }}</span>
                                                @endif
                                            </div>
                                            <div class="col-md-6">
                                                <span style="font-size: 11px;" class="col-form-label col-md-6 col-sm-6"
                                                    for="name">
                                                    {{ strtoupper($customTitles[$property]) . ' END' }}
                                                </span>
                                                <input type="date" id="{{ $property . '_end' }}"
                                                    name="{{ $property . '_end' }}" class="form-control">
                                                @if ($errors->has($property . '_end'))
                                                    <span
                                                        class="text-danger">{{ $errors->first($property . '_end') }}</span>
                                                @endif
                                            </div>
                                        @endif
                                    @endif
                                @endforeach
                            @endforeach
                            @foreach ($model->properties as $property => $value)
                                @foreach ($searchProperties as $searchProperty)
                                    @if ($searchProperty == $property && !str_contains($value, 'range'))
                                        <div @if (count($model->properties) < 6) class="col-md-12 show-less" @else class="col-md-6 additional-filters" @endif>
                                            @if (strpos($value, 'input') !== false && !str_contains($value, 'range'))
                                                <span style="font-size: 11px;" class="col-form-label col-md-6 col-sm-6"
                                                    for="name">
                                                    @if (strpos($value, 'title'))
                                                        {{ strtoupper($customTitles[$property]) }}
                                                    @else
                                                        {{ str_replace('_', ' ', strtoupper($property)) }}
                                                    @endif
                                                </span>
                                                <input type={{ explode('|', $value)[1] }} id={{ $property }}
                                                    name={{ $property }} class="form-control">
                                                @if ($errors->has($property))
                                                    <span class="text-danger">{{ $errors->first($property) }}</span>
                                                @endif
                                            @endif
                                            @if (strpos($value, 'select') !== false)
                                                <span style="font-size: 11px;" class="col-form-label col-md-6 col-sm-6"
                                                    for="name">
                                                    @if (strpos($value, 'title'))
                                                        {{ strtoupper($customTitles[$property]) }}
                                                    @else
                                                        {{ str_replace('_', ' ', strtoupper($property)) }}
                                                    @endif
                                                    @if (strpos($value, 'required') == true)
                                                        <span class='required'>*</span>
                                                    @endif
                                                </span>
                                                <select @if (strpos($value, 'multiple')) multiple="multiple" class="form-control select2 select-roles" @else class="form-control" @endif id="{{ $property }}"
                                                    name="{{ $property }}">
                                                    @if (strpos($value, 'title'))
                                                        <option value="">
                                                            {{ 'Please select ' . $customTitles[$property] }}
                                                        </option>
                                                    @else
                                                        <option value="">
                                                            {{ 'Please select ' . str_replace('id', ' ', str_replace('_', ' ', $property)) }}
                                                        </option>
                                                    @endif
                                                    @if($property == 'advisor_id')
                                                        <option value="null">UnAssigned</option>
                                                    @endif
                                                    @foreach ($dropdownSource[$property] as $item)
                                                        <option value="{{ $item->id }}">
                                                            {{ $item->text ?? $item->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @if ($errors->has($property))
                                                    <span class="text-danger">{{ $errors->first($property) }}</span>
                                                @endif
                                            @endif
                                            @if (strpos($value, 'static') !== false)
                                                <span style="font-size: 11px;" class="col-form-label col-md-6 col-sm-6"
                                                    for="name">
                                                    @if (strpos($value, 'title'))
                                                        {{ strtoupper($customTitles[$property]) }}
                                                    @else
                                                        {{ str_replace('_', ' ', strtoupper($property)) }}
                                                    @endif
                                                    @if (strpos($value, 'required') == true)
                                                        <span class='required'>*</span>
                                                    @endif
                                                </span>
                                                @php
                                                    $propertyLastIndex = explode('|', $model->properties[$property]);
                                                    $staticOptionString = end($propertyLastIndex);
                                                    $staticOptions = explode(',', $staticOptionString);
                                                @endphp
                                                <select @if (strpos($value, 'multiple')) name="{{ $property . '[]' }}" multiple="multiple" class="form-control select2 select-roles" @else class="form-control" name="{{ $property }}" @endif id="{{ $property }}">
                                                    <option value="">
                                                        {{ 'Please select ' . str_replace('id', ' ', str_replace('_', ' ', $property)) }}
                                                    </option>

                                                    @foreach ($staticOptions as $item)
                                                        @if (str_contains($model->properties[$property], 'default') && $item == explode('|', explode('default:', $model->properties[$property])[1])[0])
                                                            <option value="" selected>
                                                                {{ $item }}
                                                            </option>
                                                        @else
                                                            @if($item == 'No-Type')
                                                                <option value="null">No-Type</option>
                                                            @else
                                                            <option value={{$item}}>
                                                                {{ $item }}
                                                            </option>
                                                            @endif
                                                        @endif
                                                    @endforeach
                                                </select>
                                                @if ($errors->has($property))
                                                    <span class="text-danger">{{ $errors->first($property) }}</span>
                                                @endif
                                            @endif
                                        </div>
                                    @endif
                                @endforeach
                            @endforeach
                            <div class="col-md-12" style="margin-top: 25px;">
                                <div class="col">
                                    <ul class="nav navbar-right panel_toolbox">
                                        <li><input type="submit" value="Search" id="searchGenericSubmit" class="btn btn-warning btn-sm"></li>
                                        <li><input type="reset" id="reset-btn-generic" class="btn btn-warning btn-sm">
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </form>
                    @endif

                    <form method="post" action="manualLeadAssign" class="form-horizontal form-label-left" role="form"
                        data-parsley-validate="" novalidate="" autocomplete="off">
                        {{ csrf_field() }}
                        <input type="hidden" value="{{ strtolower($model->modelType) }}" name="modelType">
                        <div class="row" id="tm-leads-assign-div">
                            <div class="col-md-12 col-sm-12">
                                <div class="x_panel">
                                    <div class="x_title">
                                        <h2>Assign Leads</h2>
                                        <div class="clearfix"></div>
                                    </div>
                                    <div class="x_content" id="form-to-show">
                                        <div class="item form-group">
                                            <label class="col-form-label col-md-2 col-sm-2" for="Assign To">Assign
                                                To</label>
                                            <div class="col-md-6 col-sm-6">
                                                @php
                                                    $updatedAdvisors = $isRenewalUser ? $renewalAdvisors : $advisors;
                                                @endphp
                                                <select class="form-control" id="assigned_to_id_new"
                                                    name="assigned_to_id_new">
                                                    @foreach ($updatedAdvisors as $handler)
                                                        <option value="{{ $handler->id }}">{{ $handler->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="item form-group">
                                            <label class="col-form-label col-md-2 col-sm-2" for="first-name"> </label>
                                            <div class="col-md-6 col-sm-6">
                                                <div class="input-group">
                                                    <button type="submit" id="tmLeadsAssignToUser"
                                                        name="tmLeadsAssignToUser"
                                                        class="btn btn-warning btn-sm">Assign</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" id="modelType" name="modelType" value={{ strtolower($model->modelType) }}>
                        <input type="hidden" id="displayTmLeadsDownloadCsvIcon" name="displayTmLeadsDownloadCsvIcon"
                            value="">
                        <input type="hidden" id="selectTmLeadId" name="selectTmLeadId" value="">
                        <input type="hidden" id="isManagerOrDeputy" name="isManagerOrDeputy"
                            value="{{ $isManagerORDeputy }}">
                        <table id="dtBasicExample" class="table table-striped jambo_table" style="table-layout: fixed;"
                            width="100%">
                            <thead>
                                <tr>
                                    @if ($isManagerORDeputy == '1' && str_contains('home,health,life,business,travel,car', strtolower($model->modelType)))
                                        <th style="width: 15px;"><input type="checkbox" id="checkAllTmLeads"
                                                name="checkAllTmLeads" value=""></th>
                                    @endif
                                    @foreach ($model->properties as $property => $value)
                                        @if ($model->modelType != 'LeadStatus' && $model->modelType != 'Teams')
                                            @if ($property != 'id' )
                                                @if (!in_array($property, explode(',', $skipProperties['list'])))
                                                    <th data-type="{{ explode('|', $value)[1] }}" style="width: 100px !important">
                                                        @if (strpos($value, 'title'))
                                                            {{ strtoupper($customTitles[$property]) }}
                                                        @else
                                                            {{ str_replace('_', ' ', strtoupper($property)) }}
                                                        @endif
                                                    </th>
                                                @endif
                                            @endif

                                        @else
                                            @if (!in_array($property, explode(',', $skipProperties['list'])))
                                                <th data-type="{{ explode('|', $value)[1] }}" style="width: 100px !important">
                                                    @if (strpos($value, 'title'))
                                                        {{ strtoupper($customTitles[$property]) }}
                                                    @else
                                                        {{ str_replace('_', ' ', strtoupper($property)) }}
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
                    </form>
                </div>
                </div>
            </div>
        </div>
    </div>
@endsection

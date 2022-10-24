@extends('layouts.app')
@section('title', 'View ' . $model->modelType)
@section('content')
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
        $(document).ready(function() {

            $('#searchGenericSubmit').on('click', function(e){
                e.preventDefault();
                if($('#created_at').val() != '' && $('#created_at_end').val() == '')  {
                    $('#created_at_end').next().html('Please select created end date');
                    return false;
                }
                if($('#created_at').val() == '' && $('#created_at_end').val() != '')  {
                    $('#created_at').next().html('Please select created start date');
                    return false;
                }
                $("span").each(function (k, v) {
                    if($(v).hasClass('text-danger')){
                        $(v).html('');
                    }
                });
                $('#searchTable').submit();
            });
            String.prototype.replaceAll = function(search, replacement) {
                var target = this;
                return target.replace(new RegExp(search, 'g'), replacement);
            };

            function convertObjectToArray(obj) {
                return Object.keys(obj).map(key => ({
                    name: key,
                    value: obj[key],
                }));
            }
            var model = JSON.parse('<?php echo json_encode(get_object_vars($model)); ?>');
            var isAdmin = JSON.parse('<?php echo json_encode(Auth::user()->hasRole("ADMIN")); ?>');
            var modelPropertiesArray = convertObjectToArray(model.properties);
            $('#modelType').val(model.modelType);
            var dataTableColumns = [];
            var skipPropertiesArray = model.skipProperties['list'].split(',');
            for (var i = 0; i < modelPropertiesArray.length; i++) {
                if (!skipPropertiesArray.includes(modelPropertiesArray[i].name)) {
                        if (modelPropertiesArray[i].name == 'id' ) {
                            dataTableColumns.push({
                                data: 'id',
                                name: 'id',
                                render: function(data, type, row) {
                                    var url = '/generic/' + model.modelType.toLowerCase();
                                    var href = "<a href='" + url + '/' + row.id + "'>" + row.id + "</a>";
                                    return href;
                                }
                            });
                        } else {
                                    dataTableColumns.push({
                                        data: modelPropertiesArray[i].name,
                                        name: modelPropertiesArray[i].name
                                    });
                                }
                        }
                }

            var disableSortColumns = [];
            var genericDataTable = $("#dtBasicExample").DataTable({
                ordering: true,
                info: true,
                searching: false,
                dom: 'rBfrtip',
                bLengthChange: false,
                stateSave: false,
                serverSide: true,
                paging: true,
                processing: true,

                ajax: {
                    url: '/generic/' + model.modelType.toLowerCase(),
                    data: function(d) {
                        model.searchProperties.forEach(element => {
                            d[element] = $('#' + element).val();
                        });
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
            genericDataTable.on('draw', function() {
                var rows = $('#dtBasicExample tr');
                var headerRowColumns = $(rows[0]).children();
                var checkboxIndexes = [];
                for (let i = 0; i < headerRowColumns.length; i++) {
                    const element = headerRowColumns[i];
                    if ($(element).data('type') == 'checkbox' || $(element).data('type') == 'static') {
                        checkboxIndexes.push(i);
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
            });
            $("#searchTable").submit(function(e) {
                e.preventDefault();
                $(".loader").show();
                genericDataTable.draw();
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
                genericDataTable.draw();
                setTimeout(() => {
                    $(".loader").hide();
                }, 1000);
            });

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

        });
    </script>
    <div class="row">
        <div class="col-md-12 col-sm-12 ">
            <div class="x_panel">
                <div class="x_title">
                    <h2>{{ $model->modelType }}
                        List</h2>
                    <ul class="nav navbar-right panel_toolbox">
                        <li><a href="{{ url('generic/' . strtolower($model->modelType) . '/create') }}"
                                class="btn btn-warning btn-sm">Create {{ $model->modelType }}</a>
                        </li>
                    </ul>
                    <div class="clearfix"></div>
                </div>
                <div class="x_content">
                    @if (session()->has('message'))
                        <div class="alert alert-danger">{{ session()->get('message') }}</div>
                    @endif
                    @if (session()->has('success'))
                        <div class="alert alert-success">{{ session()->get('success') }}</div>
                    @endif
                    @if (count($model->searchProperties) > 0)
                        <form method="POST" id="searchTable" class="form-horizontal form-label-left" role="form"
                            data-parsley-validate="" novalidate="" autocomplete="off">

                            @foreach ($model->properties as $property => $value)
                                @foreach ($model->searchProperties as $searchProperty)
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
                                @foreach ($model->searchProperties as $searchProperty)
                                    @if ($searchProperty == $property && !str_contains($value, 'range'))
                                        <div @if (count($model->properties) < 6) class="col-md-12" @else class="col-md-6" @endif>
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
                                                            <option value={{$item}}>
                                                                {{ $item }}
                                                            </option>
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

                            <table id="dtBasicExample" class="table table-striped jambo_table" style="table-layout: fixed;"
                            width="100%">
                            <thead>
                                <tr>

                                    @foreach ($model->properties as $property => $value)
                                        @if (!in_array($property, explode(',', $model->skipProperties['list'])))
                                            <th data-type="{{ explode('|', $value)[1] }}">
                                                @if (strpos($value, 'title'))
                                                    {{ strtoupper($customTitles[$property]) }}
                                                @else
                                                    {{ str_replace('_', ' ', strtoupper($property)) }}
                                                @endif
                                            </th>
                                        @endif
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                        </form>
                    @endif

                </div>
            </div>
        </div>
    </div>
@endsection

@extends('layouts.app')
@section('title','View '.$model->modelType )
@section('content')
<script src="{{ asset('vendors/jquery/dist/jquery.min.js') }}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<style>
    div.dt-buttons {
    position: relative;
    float: left;
}
td{
    word-wrap: break-word;
}
</style>
<script>
    document.addEventListener('DOMContentLoaded',function () {

    });
$(document).ready(function() {
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
    var model = JSON.parse('<?php echo json_encode(get_object_vars($model)) ?>');
    var modelPropertiesArray = convertObjectToArray(model.properties);

    var dataTableColumns = [];
    var skipPropertiesArray = model.skipProperties['list'].split(',');
    for(var i=0; i < modelPropertiesArray.length ;i++){

        if(!skipPropertiesArray.includes(modelPropertiesArray[i].name)){
            console.log(modelPropertiesArray[i].name);
            if(modelPropertiesArray[i].name == 'id'){
                dataTableColumns.push({
                    data: modelPropertiesArray[i].name,
                    name: 'id',
                    render: function(data, type, row) {
                        var url = '/quotes/'+ model.modelType.toLowerCase();
                        return "<a href='" + url + '/' + row.id + "'>" + row.id + "</a>"
                    }
                });
            }
            else{
                if(modelPropertiesArray[i].value.indexOf('select') > -1){
                    dataTableColumns.push({ data: modelPropertiesArray[i].name + '_text', name: modelPropertiesArray[i].name});
                }else{
                    dataTableColumns.push({ data: modelPropertiesArray[i].name, name: modelPropertiesArray[i].name});
                }
            }
        }
    }
    var vehicleTypeDataTable = $("#dtBasicExample").DataTable({
        ordering: false,
        info: false,
        searching: false,
        dom: 'rBfrtip',
        bLengthChange: false,
        serverSide: true,
        ajax: {
            url: '/quotes/'+ model.modelType.toLowerCase(),
            data: function(d) {
                model.searchProperties.forEach(element => {
                    d[element] = $('#' + element).val();
                });
            }
        },
        columns: dataTableColumns,
        buttons: [{
            extend: 'excel',
            text: '<i class="fa fa-file-excel-o" style="color:green;" ></i><div style="font-weight:bold;">Export to Excel</div>',
            title: model.modelType+ ' Listing',
            action: newexportaction
        }]
    });
    vehicleTypeDataTable.on( 'draw', function () {
        var rows = $('#dtBasicExample tr');
        var headerRowColumns = $(rows[0]).children();
        var checkboxIndexes = [];
        for (let i = 0; i < headerRowColumns.length; i++) {
            const element = headerRowColumns[i];
            if($(element).data('type') == 'checkbox' || $(element).data('type') == 'static'){
                checkboxIndexes.push(i);
            }
        }
        for (let index = 1; index < rows.length; index++) {
            var columns = $(rows[index]).children();
            for (let i = 0; i < columns.length; i++) {
                if(checkboxIndexes.includes(i)){
                    const element = columns[i];
                    if($(element).text() == '1'){
                        $(element).text('True');
                    }
                    else if ($(element).text() == '0'){
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
    $('#reset-btn-generic').click(function(e){
        $(':input','#searchTable')
        .not(':button, :submit, :reset, :hidden')
        .val('')
        .prop('checked', false)
        .prop('selected', false);
        $(".loader").show();
        vehicleTypeDataTable.draw();
        setTimeout(() => {
            $(".loader").hide();
        }, 1000);
    });
    function newexportaction(e, dt, button, config) {
         var self = this;
         var oldStart = dt.settings()[0]._iDisplayStart;
         dt.one('preXhr', function (e, s, data) {
             // Just this once, load all data from the server...
             data.start = 0;
             data.length = 2147483647;
             dt.one('preDraw', function (e, settings) {
                 // Call the original action function
                 if (button[0].className.indexOf('buttons-copy') >= 0) {
                     $.fn.dataTable.ext.buttons.copyHtml5.action.call(self, e, dt, button, config);
                 } else if (button[0].className.indexOf('buttons-excel') >= 0) {
                     $.fn.dataTable.ext.buttons.excelHtml5.available(dt, config) ?
                         $.fn.dataTable.ext.buttons.excelHtml5.action.call(self, e, dt, button, config) :
                         $.fn.dataTable.ext.buttons.excelFlash.action.call(self, e, dt, button, config);
                 } else if (button[0].className.indexOf('buttons-csv') >= 0) {
                     $.fn.dataTable.ext.buttons.csvHtml5.available(dt, config) ?
                         $.fn.dataTable.ext.buttons.csvHtml5.action.call(self, e, dt, button, config) :
                         $.fn.dataTable.ext.buttons.csvFlash.action.call(self, e, dt, button, config);
                 } else if (button[0].className.indexOf('buttons-pdf') >= 0) {
                     $.fn.dataTable.ext.buttons.pdfHtml5.available(dt, config) ?
                         $.fn.dataTable.ext.buttons.pdfHtml5.action.call(self, e, dt, button, config) :
                         $.fn.dataTable.ext.buttons.pdfFlash.action.call(self, e, dt, button, config);
                 } else if (button[0].className.indexOf('buttons-print') >= 0) {
                     $.fn.dataTable.ext.buttons.print.action(e, dt, button, config);
                 }
                 dt.one('preXhr', function (e, s, data) {
                     // DataTables thinks the first item displayed is index 0, but we're not drawing that.
                     // Set the property to what it was before exporting.
                     settings._iDisplayStart = oldStart;
                     data.start = oldStart;
                 });
                 // Reload the grid with the original page. Otherwise, API functions like table.cell(this) don't work properly.
                 setTimeout(dt.ajax.reload, 0);
                 // Prevent rendering of the full data to the DOM
                 return false;
             });
         });
         // Requery the server with the new one-time export settings
         dt.ajax.reload();
     }

});
</script>
<div class="row">
    <div class="col-md-12 col-sm-12 ">
        <div class="x_panel">
            <div class="x_title">
                <h2>{{ $model->modelType }} List</h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a href="{{ url('quotes/'. strtolower($model->modelType).'/create') }}" class="btn btn-warning btn-sm">Create {{ $model->modelType }}</a></li>
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                @if (count($model->searchProperties) > 0)
                <form method="POST" id="searchTable" class="form-horizontal form-label-left" role="form" data-parsley-validate="" novalidate="" autocomplete="off">
                    @foreach($model->properties as $property => $value)
                        @foreach ($model->searchProperties as $searchProperty)
                            @if ($searchProperty == $property)
                                <div @if(count($model->properties) <6) class="col-md-12" @else class="col-md-6" @endif>
                                    @if(strpos($value, 'input') !== false )
                                        <span style="font-size: 11px;" class="col-form-label col-md-6 col-sm-6" for="name">
                                            @if(strpos($value, 'title'))
                                                {{ strtoupper($customTitles[$property])}}
                                            @else
                                                {{str_replace("_"," ",strtoupper($property))}}
                                            @endif
                                        </span>
                                        <input
                                            @if(explode("|", $value)[1] != 'date')
                                                type={{ explode("|", $value)[1]  }}
                                                @endif id={{$property}}
                                            name={{$property}}
                                        class="form-control">
                                        @if ($errors->has($property))
                                            <span class="text-danger">{{ $errors->first($property) }}</span>
                                        @endif
                                    @endif
                                    @if(strpos($value, 'select') !== false)
                                        <span style="font-size: 11px;" class="col-form-label col-md-6 col-sm-6" for="name">
                                            @if(strpos($value, 'title'))
                                                {{ strtoupper($customTitles[$property]) }}
                                            @else
                                                {{str_replace("_"," ",strtoupper($property))}}
                                            @endif
                                            @if(strpos($value, "required") == true)
                                            <span class='required'>*</span>
                                            @endif
                                        </span>
                                        <select @if(strpos($value, 'multiple')) multiple="multiple" class="form-control select2 select-roles" @else class="form-control" @endif id="{{$property}}" name="{{$property}}">
                                            <option value="">{{"Please select ".str_replace("_"," ",$property) }}</option>
                                            @foreach($dropdownSource[$property] as $item)
                                                <option value="{{$item->id}}">
                                                {{ $item->text ?? $item->name }}
                                                </option>
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
                        <li><input type="submit" class="btn btn-warning btn-sm"></li>
                        <li><input type="reset" id="reset-btn-generic" class="btn btn-warning btn-sm"></li>
                        </ul>
                    </div>
                </div>
                 </form>
                @endif

                <table id="dtBasicExample" class="table table-striped jambo_table" style="table-layout: fixed;" width="100%">
                    <thead>
                        <tr>
                            @foreach($model->properties as $property => $value)
                                @if(!in_array($property, explode(',', $model->skipProperties['list'])))
                                    @if(str_contains('checkbox', $value))
                                        <th data-type="checkbox" >{{str_replace("_"," ",strtoupper($property))}}</th>
                                    @endif
                                    @if(str_contains('static', $value))
                                        <th data-type="static" >{{str_replace("_"," ",strtoupper($property))}}</th>
                                    @else
                                        <th data-type="{{explode('|',$value)[1]}}" >{{str_replace("_"," ",strtoupper($property))}}</th>
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
@endsection

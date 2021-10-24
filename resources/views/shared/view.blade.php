@extends('layouts.app')
@section('title','View '.$model->modelType )
@section('content')
<script src="{{ asset('vendors/jquery/dist/jquery.min.js') }}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<style>
    div.dt-buttons {
    position: relative;
    float: right;
}
td{
    word-wrap: break-word;
}
</style>
<script>
$(document).ready(function() {
    function convertObjectToArray(obj) {
    return Object.keys(obj).map(key => ({
        name: key,
        value: obj[key],
        }));
    }
    var model = JSON.parse('<?php echo json_encode(get_object_vars($model)) ?>');
    var modelPropertiesArray = convertObjectToArray(model.properties);
    var dataTableColumns = [];
    for(var i=0; i < modelPropertiesArray.length ;i++){
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
            dataTableColumns.push({ data: modelPropertiesArray[i].name, name: modelPropertiesArray[i].name});
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
            url: '/quotes/'+ model.modelType.toLowerCase()
        },
        columns: dataTableColumns,
        buttons: [{
            extend: 'excel',
            text: '<i class="fa fa-file-excel-o" style="color:green;" ></i><div style="font-weight:bold;">Export to Excel</div>',
            title: model.modelType+ ' Listing',
            action: newexportaction
        }],

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
                <table id="dtBasicExample" class="table table-striped jambo_table" style="table-layout: fixed;" width="100%">
                    <thead>
                        <tr>
                            @foreach($model->properties as $property => $value)
                                <th>{{str_replace("_"," ",strtoupper($property))}}</th>
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

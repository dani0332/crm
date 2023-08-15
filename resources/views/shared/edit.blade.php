@extends('layouts.app')
@section('title', 'Edit '.$model->modelType)
@section('content')
<script src="{{ asset('vendors/jquery/dist/jquery.min.js') }}"></script>
<style>
    .form-control:disabled, .form-control[readonly]{
        background-color: white !important;
    }
    .select2-results__option--selected {
            display: none;
        }
        .select2-results__option[aria-selected=true] {
            display: none;
        }
</style>
@php
    use App\Enums\quoteTypeCode;
    use App\Enums\GenericRequestEnum;
    use App\Enums\RolesEnum;
    use App\Enums\DatabaseColumnsString;
@endphp
<script>
    function getCarMakes(id)
    {
        $.get('/car-make', function (data) {
            var carMake = $('#car_make_id').empty();
            $.each(data, function (create, carmodelObj) {
                if(carmodelObj.id == id){
                    carMake.append('<option data-id="' + carmodelObj.code + '"value="' + carmodelObj.id + '" selected>' + carmodelObj.text + '</option>');
                }else{
                    carMake.append('<option data-id="' + carmodelObj.code + '" value="' + carmodelObj.id + '">' + carmodelObj.text + '</option>');
                }
            });
        });
    }
    function getCarModels(makeId, modelId)
    {
        $.get('/car-model-by-id?id=' + makeId, function (data) {
            var carmodel = $('#car_model_id').empty();
            $.each(data, function (create, carmodelObj) {
                if(carmodelObj.id == modelId){
                    carmodel.append('<option data-id="' + carmodelObj.code + '" value="' + carmodelObj.id + '" selected>' + carmodelObj.text + '</option>');
                }else{
                    carmodel.append('<option data-id="' + carmodelObj.code + '" value="' + carmodelObj.id + '">' + carmodelObj.text + '</option>');
                }
            });
        });
    }
    $(document).ready(function() {
        var model = JSON.parse('<?php echo json_encode(get_object_vars($model)) ?>');
        var modelPropertiesArray = convertObjectToArray(model.properties);
        var modelSkipProperties = convertObjectToArray(model.skipProperties);
        if(model.modelType.toLowerCase() == '<?php echo strtolower(quoteTypeCode::Car); ?>'){
            var oldCarModelId = JSON.parse('<?php echo json_encode(isset($record->car_model_id) ? $record->car_model_id : 0) ?>');
            var oldCarMakeId = JSON.parse('<?php echo json_encode(isset($record->car_make_id) ? $record->car_make_id : 0) ?>');
            var carModelDetailId = JSON.parse('<?php echo json_encode(isset($record->car_model_detail_id) ? $record->car_model_detail_id : 0) ?>');
            getCarMakes(oldCarMakeId);
            getCarModels(oldCarMakeId, oldCarModelId);
            ajaxCallScript(oldCarModelId);
        }
        var result = modelSkipProperties.filter(obj => {
            return obj.name === 'update'
            });
        $('#skip').val(result[0].value);
        var record = JSON.parse(`<?php echo json_encode($record) ?>`);
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

        if(model.modelType == "Home") {
            $('#has_personal_belongings_div,#personal_belongings_aed_div,#has_building_div,#building_aed_div,#contents_aed_div').each(function(){
                if($(this).find('input').length > 0 && $(this).find('input').val() == ''){
                    $(this).hide();
                }
                if($(this).find('input').attr('type') == 'checkbox' > 0 && $(this).find('input').val() == 'off'){
                    $(this).hide();
                }

            });
            $('#iam_possesion_type_id').on('change',function(){
                if($("#iam_possesion_type_id option:selected").text() == 'A landlord'){
                    $('#has_building_div').show();
                }
                else{
                    $('#has_building_div').hide();
                }
            });

            $('#has_personal_belongings').on('change',function(){
                this.checked ? $('#personal_belongings_aed_div').show() : $('#personal_belongings_aed_div').hide();
            });
            $('#has_building').on('change',function(){
                this.checked ? $('#building_aed_div').show() : $('#building_aed_div').hide();
            });
            $('#has_contents').on('change',function(){
                if(this.checked){
                    if($("#iam_possesion_type_id option:selected").text() == 'A landlord'){
                        $('#has_building_div').show();
                    }
                    $('#contents_aed_div').show();
                    $('#has_personal_belongings_div').show();
                }
                else{
                    $('#contents_aed_div').hide();
                    $('#has_personal_belongings_div').hide();
                }
            });
        }
        $('#is_microchipped').on("change", function (e) {
            pet_field_microchip_visibility();
        });

        function pet_field_microchip_visibility() {
            var is_microchipped_value = $("#is_microchipped").val();
            if (is_microchipped_value == "Yes") {
                $("#microchip_no_div").show(300);
            } else {
                $("#microchip_no_div").hide(300);
            }
        }
        pet_field_microchip_visibility();

        $('#car_model_id').on('change',function(){
            var car_model_id = $('#car_model_id').val();
            ajaxCallScript(car_model_id)
        });

        function ajaxCallScript(car_model_id){
            $.ajax({
                url: "{{ url('/getCarModelDetails') }}",
                type: "GET",
                data: {
                    car_model_id: car_model_id
                },
                success: function(data) {
                    if(data.length > 0) {
                        var trim = $('#trim').empty();
                        $.each(data, function (create, carmodelObj) {
                            if(carmodelObj.is_default != undefined) {
                                if(carmodelObj.is_default == 1 && carModelDetailId == 0) {
                                    populateCarValues(carmodelObj)
                                    trim.append('<option selected value="' + carmodelObj.id + '">' + carmodelObj.text + '</option>');
                                } else {
                                    if(carModelDetailId != 0 && carmodelObj.id == carModelDetailId) {
                                        trim.append('<option selected value="' + carmodelObj.id + '">' + carmodelObj.text + '</option>');
                                    } else if(create == 0) {
                                        populateCarValues(carmodelObj)
                                    }
                                    trim.append('<option value="' + carmodelObj.id + '">' + carmodelObj.text + '</option>');
                                }
                            } else {
                                populateCarValues(carmodelObj)
                                trim.append('<option value="">I dont know</option>');
                            }
                        });
                        if(car_model_id != oldCarModelId) {
                            $("#vehicle_assumptions_error_msg").hide(300);
						    $("#vehicle_assumptions_success_msg").show(300);
                        }
                    } else {
                        $("#vehicle_assumptions_error_msg").show(300);
						$("#vehicle_assumptions_success_msg").hide(300);
						$('#cylinder').val('');
						$('#seat_capacity').val('');
						$('#vehicle_type_id').val('');
						$('#trim').empty();
                    }
                },
            });
        }

        $('#trim').on('change',function(){
            loadTrimValues($('#trim').val())
        });
        function populateCarValues(carmodelObj) {
            $('#cylinder').val(carmodelObj.cylinder);
            $('#seat_capacity').val(carmodelObj.seat_capacity);
            if(carmodelObj.vehicle_type_id)
                $('#vehicle_type_id').val(carmodelObj.vehicle_type_id);
        }
        function loadTrimValues(trimId) {
            $.get('/getCarModelTrimValues?id=' + trimId, function (data) {
                populateCarValues(data);
            });
        }

        $('#previous_policy_expiry_date, #dob').datepicker({
            changeMonth: true,
            changeYear: true,
            dateFormat: 'yy-mm-dd',
            yearRange: '-80:+00',
        });
});
</script>
    <div class="row">
        <div class="col-md-12 col-sm-12">
            <div class="x_panel">
                <div class="x_title">

                    @if($model->modelType == quoteTypeCode::Car && \Illuminate\Support\Facades\Auth::user()->hasRole(RolesEnum::CarManager))
                        <div class="alert alert-info"> Only Renewal Batch # field will be updated </div>
                    @endif

                    <h2>{{'Edit '.(str_contains(strtolower($model->modelType), 'team') ? 'Team' : (str_contains(strtolower($model->modelType), 'leadstatus') ? 'Lead Status' : $model->modelType))}}</h2>
                    <ul class="nav navbar-right panel_toolbox">
                        <li><a href="{{ url('quotes/'.strtolower($model->modelType)) }}" class="btn btn-warning btn-sm">{{(str_contains(strtolower($model->modelType), 'team') ? 'Team' : (str_contains(strtolower($model->modelType), 'leadstatus') ? 'Lead Status' : $model->modelType)).' List'}}</a></li>
                    </ul>
                    <div class="clearfix"></div>
                </div>
                <div class="x_content">
                    <br />
                    @if (session()->has('success'))
                        <div class="alert alert-success">{{ session()->get('success') }}</div>
                    @endif
                    @if (session()->has('message'))
                        <div class="alert alert-danger">{{ session()->get('message') }}</div>
                    @endif
                    <div id="vehicle_assumptions_success_msg" class="alert alert-success" style="display:none;">Vehicle Assumptions Data Found</div>
                    <div id="vehicle_assumptions_error_msg" class="alert alert-danger" style="display:none;">No Vehicle Assumptions Data Found</div>
                    <form id="demo-form2" method='post'
                        action="{{ route(strtolower($model->modelType).'.update', $record->uuid) }}"
                        enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left"
                        autocomplete="off">
                        {{ csrf_field() }}
                        @method('PUT')
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

                        <input type="hidden" name="model" value={{ json_encode($model->properties) }} />
                        <input type="hidden" name="modelType" value={{ json_encode($model->modelType) }} />
                        <input type="hidden" name="modelSkipProperties" id="skip" value={{ json_encode($skipProperties) }} />
                        @php
                            $index = 0;
                            $skipPropertiesArray = array_filter(explode(",", $skipProperties['update']));
                        @endphp
                        @foreach($model->properties as $property => $value)
                            @if(!in_array($property, $skipPropertiesArray))
                                @if($index == 0 || strpos($value, 'checkbox'))
                                @else
                                    <div @if(count($model->properties) <6) class="col-md-12" @else class="col-md-6" @endif id={{$property.'_div'}}>
                                        @if(strpos($value, 'input') !== false )
                                            <span class="col-form-label col-md-6 col-sm-6" for="name">
                                                @if(strpos($value, 'title'))
                                                    {{ strtoupper($customTitles[$property])}}
                                                @else
                                                    {{str_replace("_"," ",strtoupper($property))}}
                                                @endif
                                                @if(strpos($value, "required") == true)
                                                <span class='required'>*</span>
                                                @endif
                                            </span>
                                            <input
                                                @if(explode("|", $value)[1] == "date") readonly="readonly" @endif
                                                @if(explode("|", $value)[1] != 'date') type={{ explode("|", $value)[1] }} @endif
                                                id={{$property}} name={{$property}}
                                                @if($property == DatabaseColumnsString::EMAIL || $property == DatabaseColumnsString::MOBILE || ($property == DatabaseColumnsString::CAR_VALUE_TIER && !Auth::user()->hasRole(RolesEnum::LeadPool))) style="background-color: #e9ecef !important;" disabled="disabled" @endif
                                                value="{{ old($property, $record->$property) }}" class="form-control"
                                                value="{{ old($property, $record->$property) }}" class="form-control"
                                                @if(strpos($value, 'max') !== false) maxlength="{{explode(":", $value)[1]}}" @endif
                                                @if(!Auth::user()->hasAnyRole([RolesEnum::CarManager, RolesEnum::Admin, RolesEnum::LeadPool]))
                                                    @if($property == DatabaseColumnsString::RENEWAL_BATCH || $property == DatabaseColumnsString::PREVIOUS_QUOTE_POLICY_NUMBER || $property == DatabaseColumnsString::PREVIOUS_POLICY_EXPIRY_DATE)
                                                        readonly="readonly" style="background-color: #e9ecef !important;pointer-events: none;"
                                                    @endif
                                                @else
                                                    @if($property == DatabaseColumnsString::PREVIOUS_QUOTE_POLICY_NUMBER && $record->$property)
                                                        readonly="readonly" style="background-color: #e9ecef !important;"
                                                    @endif
                                                    @if($property == DatabaseColumnsString::PREVIOUS_POLICY_EXPIRY_DATE && $record->$property)
                                                        readonly="readonly" style="background-color: #e9ecef !important;pointer-events: none;"
                                                    @endif
                                                    @if($property == DatabaseColumnsString::RENEWAL_BATCH && !empty($record->$property) && !auth()->user()->can(\App\Enums\PermissionsEnum::RenewalBatchUpdate))
                                                        readonly="readonly" style="background-color: #e9ecef !important;"
                                                    @endif
                                                @endif

                                            @if($property == DatabaseColumnsString::DATE_OF_BIRTH) readonly="readonly" @endif>
                                            @if ($errors->has($property))
                                                <span class="text-danger">{{ $errors->first($property) }}</span>
                                            @endif
                                        @endif
                                        @if(strpos($value, 'select') !== false)
                                            <span class="col-form-label col-md-6 col-sm-6" for="name">
                                                @if(strpos($value, 'title'))
                                                    {{ strtoupper($customTitles[$property]) }}
                                                @else
                                                {{str_replace("_"," ",strtoupper($property))}}
                                                @endif
                                                @if(strpos($value, "required") == true)
                                                <span class='required'>*</span>
                                                @endif
                                            </span>
                                            <select @if(strpos($value, 'multiple')) multiple="multiple" name="{{$property.'[]'}}" class="form-control select2 select-roles" @else name="{{$property}}" class="form-control" @endif id="{{$property}}" >
                                                @if(strpos($value, 'title'))
                                                    <option value="">{{"Please select ".$customTitles[$property] }}</option>
                                                @else
                                                    <option value="">{{"Please select ".str_replace("id"," ",str_replace("_"," ",$property)) }}</option>
                                                @endif
                                                @if (strpos($value, 'customTable') !== false)
                                                    @foreach($customLists[$property] as $selectedItem)
                                                        @foreach($dropdownSource[$property] as $item)
                                                            @if ($selectedItem->id == $item->id)
                                                                <option value="{{$item->id}}" selected="selected">
                                                                {{ $item->text ?? $item->name }}
                                                                </option>
                                                            @else
                                                                <option value="{{$item->id}}">
                                                                {{ $item->text ?? $item->name }}
                                                                </option>
                                                            @endif
                                                        @endforeach
                                                    @endforeach
                                                @else
                                                @foreach($dropdownSource[$property] as $item)
                                                    @if($property == 'currently_insured_with' || $property == 'year_of_manufacture')
                                                        <option value="{{$item->text}}" {{ $item->text == old($item->id, $record->$property) ? 'selected' : ''}}>{{ $item->text ?? $item->name }}</option>
                                                    @else
                                                        <option value="{{$item->id}}" {{ $item->id == old($item->id, $record->$property) ? 'selected' : ''}}>{{ $item->text ?? $item->name }}</option>
                                                    @endif
                                                @endforeach

                                                @endif

                                            </select>
                                            @if ($errors->has($property))
                                            <span class="text-danger">{{ $errors->first($property) }}</span>
                                            @endif
                                        @endif
                                        @if(strpos($value, 'textarea') !== false )
                                            <span class="col-form-label col-md-6 col-sm-6" for="name">
                                                @if(strpos($value, 'title'))
                                                    {{ strtoupper($customTitles[$property])}}
                                                @else
                                                    {{str_replace("_"," ",strtoupper($property))}}
                                                @endif
                                                @if(strpos($value, "required") == true)
                                                <span class='required'>*</span>
                                                @endif
                                            </span>
                                            <textarea
                                                id={{$property}}
                                                name={{$property}}
                                            class="form-control">{{ old($property, $record->$property) }}</textarea>
                                            @if ($errors->has($property))
                                                <span class="text-danger">{{ $errors->first($property) }}</span>
                                            @endif
                                        @endif
                                        @if(strpos($value, 'static') !== false )
                                        <span class="col-form-label col-md-6 col-sm-6" for="name">
                                            @if(strpos($value, 'title'))
                                                {{ strtoupper($customTitles[$property])}}
                                            @else
                                                {{str_replace("_"," ",strtoupper($property))}}
                                            @endif
                                            @if(strpos($value, "required") == true)
                                            <span class='required'>*</span>
                                            @endif
                                        </span>
                                        @php
                                            $propertyLastIndex = explode('|', $model->properties[$property]);
                                            $staticOptionString = end($propertyLastIndex);
                                            $staticOptions = explode(',', $staticOptionString);
                                        @endphp
                                        @if ($errors->has($property))
                                        <span class="text-danger">{{ $errors->first($property) }}</span>
                                        @endif
                                        <select @if(strpos($value, 'multiple')) name="{{$property.'[]'}}" multiple="multiple" class="form-control select2 select-roles" @else class="form-control" name="{{$property}}" @endif id="{{$property}}" >
                                            @foreach($staticOptions as $item)
                                                @if($item == quoteTypeCode::yesText && $record->$property == 1 || $item == quoteTypeCode::noText && $record->$property == 0)
                                                    <option value="{{ $item }}" {{ $item == old($item, $item) ? 'selected' : ''}}>{{ $item }}</option>
                                                @elseif($item == GenericRequestEnum::FEMALE_SINGLE_VALUE || $item == GenericRequestEnum::FEMALE_SINGLE)
                                                    <option value="{{ GenericRequestEnum::FEMALE_SINGLE_VALUE }}" {{ GenericRequestEnum::FEMALE_SINGLE_VALUE == old($item, $record->$property) ? 'selected' : ''}}>{{ $item }}</option>
                                                @elseif($item == GenericRequestEnum::FEMALE_MARRIED_VALUE || $item == GenericRequestEnum::FEMALE_MARRIED)
                                                    <option value="{{ GenericRequestEnum::FEMALE_MARRIED_VALUE }}" {{ GenericRequestEnum::FEMALE_MARRIED_VALUE  == old($item, $record->$property) ? 'selected' : ''}}>{{ $item }}</option>
                                                @elseif(($item == GenericRequestEnum::MALE_SINGLE_VALUE && $model->modelType == quoteTypeCode::Health) || ($item == GenericRequestEnum::MALE_SINGLE && $model->modelType == quoteTypeCode::Health))
                                                    <option value="{{ GenericRequestEnum::MALE_SINGLE_VALUE }}" {{ GenericRequestEnum::MALE_SINGLE_VALUE == old($item, $record->$property) ? 'selected' : ''}}>{{ $item }}</option>
                                                @else
                                                    <option value="{{ $item }}" {{ $item == old($item, $record->$property) ? 'selected' : ''}}>{{ $item }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                    @endif
                                    </div>
                                @endif
                            @endif
                            @php
                            $index++
                            @endphp

                        @endforeach
                        @foreach ($model->properties as $property => $value)
                            @if(!str_contains($skipProperties['update'], $property))
                                @if (strpos($value, 'checkbox'))
                                    <div class="col-md-2" id={{$property.'_div'}}>
                                        <div class="col-md-9">
                                            <label for="middle-name" style="margin-top: 8px;">
                                                <b>
                                                    @if(strpos($value, 'title'))
                                                        {{ strtoupper($customTitles[$property])}}
                                                    @else
                                                        {{str_replace("_"," ",strtoupper($property))}}
                                                    @endif
                                                </b>
                                            </label>
                                        </div>
                                        <div class="col-md-3">
                                            <input type="checkbox" {{ $record->$property ? 'checked' : '' }} style="float: right;" id={{$property}} name={{$property}}>                                        </div>
                                    </div>
                                    <br />
                                    @if ($errors->has($property))
                                        <span class="text-danger">{{ $errors->first($property) }}</span>
                                    @endif
                                @endif
                            @endif
                        @endforeach
                        <div style="clear: both;"></div>
                        <div id='redirect_to_view_div'></div>
                        <div class="ln_solid"></div>
                        <div class="row">
                            <div class="col-auto mr-auto"></div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-warning btn-sm" id="return_to_view" onClick="this.form.submit(); this.disabled=true; this.innerHTML='Updating…';">Update</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

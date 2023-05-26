@extends('layouts.app')
@section('title', 'Add '.$model->modelType )
@section('content')
<style>
	.form-control:disabled,
	.form-control[readonly] {
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
@endphp
<script src="{{ asset('vendors/jquery/dist/jquery.min.js') }}"></script>
<script>
	$(document).ready(function() {
		String.prototype.replaceAll = function(search, replacement) {
			var target = this;
			return target.replace(new RegExp(search, 'g'), replacement);
		};

		var oldCarModelId = '';
		var oldCarMakeId = '';
		var model = JSON.parse(
			'<?php echo json_encode(get_object_vars($model)) ?>');
		var modelPropertiesArray = convertObjectToArray(model.properties);
		if (model.modelType.toLowerCase() ==
			'<?php echo strtolower(quoteTypeCode::Car); ?>') {
			oldCarModelId = JSON.parse(
				'<?php echo json_encode(old('car_model_id')) ?>'
			);
			oldCarMakeId = JSON.parse(
				'<?php echo json_encode(old('car_make_id')) ?>'
			);
			if (oldCarMakeId != '' && oldCarMakeId != null) {
				getCarModels(oldCarMakeId, oldCarModelId);
			}
		}

		$('#car_model_id').on('change', function() {
			var car_model_id = $('#car_model_id').val();
			ajaxCallScript(car_model_id)
		});

		function ajaxCallScript(car_model_id) {
			$.ajax({
				url: "{{ url('/getCarModelDetails') }}",
				type: "GET",
				data: {
					car_model_id: car_model_id
				},
				success: function(data) {
					if (data.length > 0) {
						var trim = $('#trim').empty();
						$.each(data, function(create, carmodelObj) {
							if (carmodelObj.is_default != undefined) {
								if (carmodelObj.is_default == 1) {
									populateCarValues(carmodelObj)
									trim.append('<option selected value="' + carmodelObj.id +
										'">' + carmodelObj.text + '</option>');
								} else {
									if (create == 0) {
										populateCarValues(carmodelObj)
									}
									trim.append('<option value="' + carmodelObj.id + '">' +
										carmodelObj.text + '</option>');
								}
							} else {
								populateCarValues(carmodelObj)
								trim.append('<option value="">I dont know</option>');
							}

						});

						$("#vehicle_assumptions_error_msg").hide(300);
						$("#vehicle_assumptions_success_msg").show(300);
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

		$('#trim').on('change', function() {
			loadTrimValues($('#trim').val())
		});

		function populateCarValues(carmodelObj) {
			$('#cylinder').val(carmodelObj.cylinder);
			$('#seat_capacity').val(carmodelObj.seat_capacity);
			$('#vehicle_type_id').val(carmodelObj.vehicle_type_id);
		}

		function loadTrimValues(trimId) {
			$.get('/getCarModelTrimValues?id=' + trimId, function(data) {
				populateCarValues(data);
			});
		}

		function convertObjectToArray(obj) {
			return Object.keys(obj).map(key => ({
				name: key,
				value: obj[key],
			}));
		}

		if (model.modelType == "Home") {
			$('#has_personal_belongings_div,#personal_belongings_aed_div,#has_building_div,#building_aed_div,#contents_aed_div')
				.each(function() {
					if ($('#' + $(this).attr('id').replace('_div', '')).attr('type') == 'checkbox') {
						if (!$('#' + $(this).attr('id').replace('_div', '')).is(':checked')) {
							$(this).hide();
						}
					} else {
						if ($('#' + $(this).attr('id').replace('_div', '')).val() == '') {
							$(this).hide();
						}
					}
				});
			$('#iam_possesion_type_id').on('change', function() {
				if ($("#iam_possesion_type_id option:selected").text() == 'A landlord') {
					$('#has_building_div').show();
				} else {
					$('#has_building_div').hide();
				}
			});
			$('#has_personal_belongings').on('change', function() {
				this.checked ? $('#personal_belongings_aed_div').show() : $('#personal_belongings_aed_div')
					.hide();
			});
			$('#has_building').on('change', function() {
				this.checked ? $('#building_aed_div').show() : $('#building_aed_div').hide();
			});
			$('#has_contents').on('change', function() {
				if (this.checked) {
					if ($("#iam_possesion_type_id option:selected").text() == 'A landlord') {
						$('#has_building_div').show();
					}
					$('#contents_aed_div').show();
					$('#has_personal_belongings_div').show();
				} else {
					$('#contents_aed_div').hide();
					$('#has_personal_belongings_div').hide();
				}
			});
		}
        checkLeadRedirection();
	});

	function getCarModels(makeId, modelId) {
		$.get('/car-model-by-id?id=' + makeId, function(data) {
			var carmodel = $('#car_model_id').empty();
			$.each(data, function(create, carmodelObj) {
				if (carmodelObj.id == modelId) {
					carmodel.append('<option selected data-id="' + carmodelObj.code + '" value="' +
						carmodelObj.id + '">' + carmodelObj.text + '</option>');
				} else {
					carmodel.append('<option data-id="' + carmodelObj.code + '" value="' + carmodelObj.id +
						'">' + carmodelObj.text + '</option>');
				}

			});
		});
	}

    function checkLeadRedirection()
    {
        let model = JSON.parse('<?php echo json_encode(get_object_vars($model)) ?>');

        if(!localStorage.getItem('throughConfirmation') && model.modelType.toLowerCase() == '<?php echo strtolower(quoteTypeCode::Car); ?>')
        {
            localStorage.removeItem("throughConfirmation");
            window.location.href = "{{ url('quotes/' . strtolower($model->modelType) ) }}";
        }
    }
</script>
<div class="row">
	<div class="col-md-12 col-sm-12">
		<div class="x_panel">
			<div class="x_title">
				<h2>Create {{str_contains(strtolower($model->modelType), 'team') ? 'Team' :
					(str_contains(strtolower($model->modelType), 'leadstatus') ? 'Lead Status' : $model->modelType)}}
				</h2>
				<ul class="nav navbar-right panel_toolbox">
					<li><a href="{{ url('quotes/'.strtolower($model->modelType)) }}"
							class="btn btn-warning btn-sm">{{(str_contains(strtolower($model->modelType), 'team') ?
							'Team' : (str_contains(strtolower($model->modelType), 'leadstatus') ? 'Lead Status' :
							$model->modelType)). ' List' }}</a></li>
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
				<div id="vehicle_assumptions_success_msg" class="alert alert-success" style="display:none;">Vehicle
					Assumptions Data Found</div>
				<div id="vehicle_assumptions_error_msg" class="alert alert-danger" style="display:none;">No Vehicle
					Assumptions Data Found</div>
				<form id="demo-form2" autocomplete="off" action="{{ route('saveQuote') }}" method='post'
					enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left"
					autocomplete="off">
					@php
					$index = 0;
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

					{{ csrf_field() }}
					<input type="hidden" name="model" value={{ json_encode($model->properties) }} />
					<input type="hidden" name="modelSkipProperties" value={{ json_encode($skipProperties) }} />
					<input type="hidden" name="modelType" value={{ json_encode($model->modelType) }} />


					@foreach($model->properties as $property => $value)
					@if(strpos($value, 'checkbox'))
					@else
					@php
					$skipPropertiesArray = array_filter(explode(",", $skipProperties['create']));
					@endphp
					@if(!in_array($property, $skipPropertiesArray))
					@if(strpos($value, 'input') !== false )
					<div @if(count($model->properties) < 6) class="col-md-12" @else class="col-md-6" @endif
							id={{$property.'_div'}}>
							<div class="col">
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
								$title = '';
								if(str_contains($value, 'title')){
								$title = 'Please confirm '. strtolower($customTitles[$property]);
								}else{
								$title = 'Please confirm '. str_replace("_"," ",strtolower($property));
								}
								@endphp
								<input @if(explode("|", $value)[1]=="date" || $property=='dob' ) readonly="readonly"
									@endif type={{ explode("|", $value)[1] }} id={{$property}} name={{$property}}
									value="{{ old($property) }}" @if($property=='seat_capacity' || $property=='cylinder'
									) data-toggle="tooltip" data-placement="top" title="{{$title}}" @endif
									class="form-control" @if(strpos($value, 'max') !== false) maxlength="{{explode(":", $value)[1]}}" @endif
                                >
								@if ($errors->has($property))
								<span class="text-danger">{{ $errors->first($property) }}</span>
								@endif
							</div>
					</div>
					@endif
					@if(strpos($value, 'select') !== false)
					<div @if(count($model->properties) <6) class="col-md-12" @else class="col-md-6" @endif
							id={{$property.'_div'}}>
							<div class="col">
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
								@php
								$title = '';
								if(str_contains($value, 'title')){
								$title = 'Please select '. strtolower($customTitles[$property]);
								}else{
								$title = 'Please select '. str_replace("_"," ",strtolower($property));
								}
								@endphp
								<select @if(strpos($value, 'multiple' )) name="{{$property.'[]'}}" multiple="multiple"
									class="form-control select2 select-roles" @else class="form-control"
									name="{{$property}}" @endif @if($property=='vehicle_type_id' ) data-toggle="tooltip"
									data-placement="top" title="{{$title}}" @endif id="{{$property}}">
									@if(strpos($value, 'title'))
									<option value="">{{"Please confirm ".$customTitles[$property] }}</option>
									@else
									<option value="">{{"Please confirm ".str_replace("id"," ",str_replace("_","
										",$property)) }}</option>
									@endif
									@if (strpos($value, 'customTable') !== false)
									@foreach($dropdownSource[$property] as $item)
									<option value="{{ $item->id }}" @if(old($property)==$item->id) selected @endif>{{
										$item->text ?? $item->name }}</option>
									@endforeach
									@else
									@foreach($dropdownSource[$property] as $item)
									@if($property == 'currently_insured_with' || $property == 'year_of_manufacture')
									<option value="{{$item->text}}" {{ $item->text == old($property) ? 'selected' :
										''}}>{{ $item->text ?? $item->name }}</option>
									@else
									<option value="{{$item->id}}" {{ $item->id == old($property) ? 'selected' : ''}}>{{
										$item->text ?? $item->name }}</option>
									@endif
									@endforeach
									@endif
								</select>
								@if ($errors->has($property))
								<span class="text-danger">{{ $errors->first($property) }}</span>
								@endif
							</div>
					</div>
					@endif
					@if(strpos($value, 'textarea') !== false)
					<div @if(count($model->properties) <6) class="col-md-12" @else class="col-md-6" @endif>
							<div class="col">
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
								<textarea id={{$property}} name={{$property}}
									class="form-control">{{ old($property) }}</textarea>
								@if ($errors->has($property))
								<span class="text-danger">{{ $errors->first($property) }}</span>
								@endif
							</div>
					</div>
					@endif
					@if(strpos($value, 'static') !== false )
					<div @if(count($model->properties) < 6) class="col-md-12" @else class="col-md-6" @endif
							id={{$property.'_div'}}>
							<div class="col">
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
								<select @if(strpos($value, 'multiple' )) name="{{$property.'[]'}}" multiple="multiple"
									class="form-control select2 select-roles" @else class="form-control"
									name="{{$property}}" @endif id="{{$property}}">
									@foreach($staticOptions as $item)
									@if($item == GenericRequestEnum::FEMALE_SINGLE)
										<option value="{{ GenericRequestEnum::FEMALE_SINGLE_VALUE }}" {{ $item == old(GenericRequestEnum::FEMALE_SINGLE_VALUE) ? 'selected' : ''}}>{{ $item }}</option>
									@elseif($item == GenericRequestEnum::FEMALE_MARRIED)
										<option value="{{ GenericRequestEnum::FEMALE_MARRIED_VALUE }}" {{ $item == old(GenericRequestEnum::FEMALE_MARRIED_VALUE) ? 'selected' : ''}}>{{ $item }}</option>
									@elseif($item == GenericRequestEnum::MALE_SINGLE && $model->modelType == quoteTypeCode::Health)
										<option value="{{ GenericRequestEnum::MALE_SINGLE_VALUE }}" {{ $item == old(GenericRequestEnum::MALE_SINGLE_VALUE) ? 'selected' : ''}}>{{ $item }}</option>
									@else
										<option value="{{$item}}" {{ $item==old($property) ? 'selected' : '' }}>{{ $item }}
									@endif
									</option>
									@endforeach
								</select>

							</div>
					</div>
					@endif
					@endif
					@endif
					@php
					$index++
					@endphp
					@endforeach
					@if(count($skipProperties) != 0)
			</div>
			@endif
			@foreach ($model->properties as $property => $value)
			@if (strpos($value, 'checkbox'))

			<div class="col-md-3" id={{$property.'_div'}}>
				<div class="col-md-8">
					<label for="middle-name" style="margin-top: 8px;float: left">
						<b>
							@if(strpos($value, 'title'))
							{{ strtoupper($customTitles[$property])}}
							@else
							{{str_replace("_"," ",strtoupper($property))}}
							@endif
						</b>

					</label>
					@if(strpos($value, "required") == true)
					<span class='required' style="float: left;margin-top: 8px;margin-left: 1px;">*</span>
					@endif
					@if ($errors->has($property))
					<span style="float: left" class="text-danger">{{ $errors->first($property) }}</span>
					@endif
				</div>
				<div class="col-md-2">
					<input type={{ explode("|", $value)[1] }} {{ old($property) ? 'checked' : '' }}
						style="float: right;" id={{$property}} name={{$property}}>
				</div>
			</div>
			<br />

			@endif
			@endforeach

			<div style="clear: both;"></div>
			<div id='redirect_to_view_div'></div>
			<div class="ln_solid"></div>
			<div class="row">
				<div class="col-auto mr-auto"></div>
				<div class="col-auto">
					<button type="submit" class="btn btn-warning btn-sm"
						onClick="this.form.submit(); this.disabled=true; this.innerHTML='Creating…';">Create</button>
				</div>
			</div>
			</form>
		</div>
	</div>
</div>
</div>
@endsection

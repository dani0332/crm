@extends('layouts.app')
@section('title', 'Retention Configs')
@section('content')
    <script src="{{ asset('vendors/jquery/dist/jquery.min.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.date-search-field').datepicker({
                dateFormat: "yy-mm-dd",
                changeMonth: true,
                changeYear: true,
            });
            $('.date-search-field').prop('readonly', true);
        });
    </script>
    <div class="row">
        <div class="col-md-12 col-sm-12">
            <div class="x_panel">
                <div class="x_title">
                    <h2>Renewal Batch Configs</h2>
                    <div class="clearfix"></div>
                </div>

                <div class="x_content">
                    <form method="POST" action="{{ route('renewal-batches-store') }}">
                        @csrf
                        <div class="card mb-3">
                            <h5 class="card-header">Batch Details</h5>
                            <div class="card-body">
                                <div class="form-row">
                                    <div class="col">
                                        <label for="formGroupExampleInput">Batch Name <span
                                                class="required">*</span></label>
                                        <input type="text" class="form-control" name="name"
                                            value="{{ old('name') }}" placeholder="Batch Name">
                                    </div>
                                    <div class="col">
                                        <label for="formGroupExampleInput">Batch Start <span
                                                class="required">*</span></label>
                                        <input type="text" class="form-control date-search-field" name="start_date"
                                            value="{{ old('start_date') }}" min="{{ date('Y-m-d') }}"
                                            placeholder="Batch Start Date">
                                    </div>
                                    <div class="col">
                                        <label for="formGroupExampleInput">Batch End <span class="required">*</span></label>
                                        <input type="text" class="form-control date-search-field" name="end_date"
                                            value="{{ old('end_date') }}" min="{{ date('Y-m-d') }}"
                                            placeholder="Batch End Date">
                                    </div>
                                    <div class="col">
                                        <label for="formGroupExampleInput">Batch Month <span class="required">*</span></label>
                                        <select class="form-control" id="batch-month" name="month">
                                            <option selected disabled>Select Batch Month here</option>
                                            <option value="1">January</option>
                                            <option value="2">Feburary</option>
                                            <option value="3">March</option>
                                            <option value="4">April</option>
                                            <option value="5">May</option>
                                            <option value="6">June</option>
                                            <option value="7">July</option>
                                            <option value="8">August</option>
                                            <option value="9">September</option>
                                            <option value="10">October</option>
                                            <option value="11">November</option>
                                            <option value="12">December</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        {{-- =================== DEADLINES =========================== --}}

                        <div class="card mb-3">
                            <h5 class="card-header">Renewal Batch Deadlines</h5>
                            <div class="card-body">
                                <div class="form-row">
                                    <div class="col">
                                        <label for="formGroupExampleInput"> <b> {{ \App\Enums\quoteStatusCode::CAR_SOLD}} Deadline </b> <span
                                                class="required">*</span></label>
                                        <input type="text" class="form-control date-search-field" name="deadline_date[{{\App\Enums\QuoteStatusEnum::CarSold}}]"
                                            value="{{ old('deadline_date[]') }}" min="{{ date('Y-m-d') }}"
                                            placeholder="Car Sold Deadline">
                                        <input type="hidden" name="quote_status_id[]" value="{{ \App\Enums\QuoteStatusEnum::CarSold}}">
                                    </div>
                                    {{-- <div class="col">
                                        <label for="formGroupExampleInput"> <b>{{ \App\Enums\quoteStatusCode::UNCONTACTABLE}} Deadline </b> <span
                                                class="required">*</span></label>
                                        <input type="text" class="form-control date-search-field" name="deadline_date[{{\App\Enums\QuoteStatusEnum::Uncontactable}}]"
                                            value="{{ old('deadline_date[]') }}" min="{{ date('Y-m-d') }}"
                                            placeholder="Car Uncontactable Deadline">
                                        <input type="hidden" name="quote_status_id[]" value="{{ \App\Enums\QuoteStatusEnum::Uncontactable}}">
                                    </div> --}}
                                </div>
                            </div>
                        </div>

                        {{-- ========================================================= --}}

                        <div class="card mb-3">
                            <h5 class="card-header">Teamswise Slabs</h5>
                            <div class="card-body">
                                @if ($teams)
                                    <table class="table table-bordered text-center">
                                        <thead>
                                            <tr>
                                                <th scope="col">Team</th>
                                                @if ($slabs)
                                                    @foreach ($slabs as $slab)
                                                        <th scope="col">{{ $slab->title }}</th>
                                                    @endforeach
                                                @endif
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($teams as $team)
                                                <tr>
                                                    <th scope="row">{{ $team->name }} <span class="required">*</span>
                                                    </th>
                                                    @if ($slabs)
                                                        @php
                                                            $slabsCount = count($slabs);
                                                        @endphp
                                                        @foreach ($slabs as $key => $slab)
                                                            @if ($key < ($team->slabs_count ?: 0))
                                                                <td>
                                                                    <div class="form-row">
                                                                        <div class="col">
                                                                            <input type="number"
                                                                                name="slab[{{ $slab->id }}][{{ $team->id }}][Min]"
                                                                                class="form-control" placeholder="min"
                                                                                step="0.01"
                                                                                min="0" max="100"
                                                                                value="{{ !empty($lastBatchSlabs) && isset($lastBatchSlabs[$slab->id][$team->id]) ? $lastBatchSlabs[$slab->id][$team->id]['pivot']['min'] : old("slab.$slab->id.$team->id.Min") }}"
                                                                                required>
                                                                        </div>
                                                                        <div class="col">
                                                                            <input type="number"
                                                                                name="slab[{{ $slab->id }}][{{ $team->id }}][Max]"
                                                                                class="form-control" placeholder="max"
                                                                                step="0.01"
                                                                                min="0" max="100"
                                                                                value="{{ !empty($lastBatchSlabs) && isset($lastBatchSlabs[$slab->id][$team->id]) ? $lastBatchSlabs[$slab->id][$team->id]['pivot']['max'] : old("slab.$slab->id.$team->id.Max") }}"
                                                                                required>
                                                                        </div>
                                                                    </div>
                                                                </td>
                                                            @else
                                                                <td>
                                                                    <h4>Not Applicable</h4>
                                                                </td>
                                                                <input type="hidden" name="optional_slabs[]"
                                                                    value="{{ $slab->id }}">
                                                                <input type="hidden" name="optional_teams[]"
                                                                    value="{{ $team->id }}">
                                                            @endif
                                                            @php
                                                                $slabsCount--;
                                                            @endphp
                                                        @endforeach
                                                    @endif
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                @else
                                    <h3>No Team found</h3>
                                @endif
                            </div>
                        </div>

                        <div class="card mb-3">
                            <h5 class="card-header">Segments</h5>
                            <div class="card-body">
                                <table class="table table-bordered text-center">
                                    <thead>
                                        <tr>
                                            <th scope="col" class="w-25">Segment Type</th>
                                            <th scope="col">Advisors</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <th scope="row">Segment Volume <span class="required">*</span></th>
                                            <td>
                                                <div class="form-row">
                                                    <div class="col text-left">
                                                        @if ($carAdvisors)
                                                            <select id="segment-volume" name="segment_volume[]"
                                                                multiple="multiple" style="margin-bottom:15px;"
                                                                class="form-control select2 select-roles">
                                                                @foreach ($carAdvisors as $carAdvisor)
                                                                    <option value="{{ $carAdvisor->id }}"
                                                                        {{ !empty($volumeSegmentAdvisorsId) && in_array($carAdvisor->id, $volumeSegmentAdvisorsId) ? 'selected' : '' }}>
                                                                        {{ $carAdvisor->name }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row">Segment Value <span class="required">*</span></th>
                                            <td>
                                                <div class="form-row">
                                                    <div class="col text-left">
                                                        @if ($carAdvisors)
                                                            <select id="segment-value" name="segment_value[]"
                                                                multiple="multiple" style="margin-bottom:15px;"
                                                                class="form-control select2 select-roles">
                                                                @foreach ($carAdvisors as $carAdvisor)
                                                                    <option value="{{ $carAdvisor->id }}"
                                                                        {{ !empty($valueSegmentAdvisorsId) && in_array($carAdvisor->id, $valueSegmentAdvisorsId) ? 'selected' : '' }}>
                                                                        {{ $carAdvisor->name }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary pull-right">Submit</button>
                    </form>
                </div>
            </div>

        </div>
    </div>
@endsection

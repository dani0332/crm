@extends('layouts.app')
@section('title', 'Retention Configs | Edit')
@section('content')
    <div class="row">
        <div class="col-md-12 col-sm-12">
            <div class="x_panel">
                <div class="x_title">
                    <h2>Edit Renewal Batch Configs</h2>
                    <div class="clearfix"></div>
                </div>

                <div class="x_content">
                    <form method="post" action="{{ route('renewal-batch.update' , [$renewalBatch]) }}">
                        @csrf
                        @method('PUT')
                        <div class="card mb-3">
                            <h5 class="card-header">Batch Details</h5>
                            <div class="card-body">
                                <div class="form-row">
                                    <div class="col">
                                        <label for="formGroupExampleInput">Batch Name <span
                                                class="required">*</span></label>
                                        <input type="text" class="form-control" name="name"
                                            value="{{ $renewalBatch->name }}" placeholder="Batch Name">
                                    </div>
                                    <div class="col">
                                        <label for="formGroupExampleInput">Batch Start <span
                                                class="required">*</span></label>
                                        <input type="date" class="form-control" name="start_date"
                                            value="{{ $renewalBatch->start_date }}"
                                            placeholder="Batch Start Date">
                                    </div>
                                    <div class="col">
                                        <label for="formGroupExampleInput">Batch End <span class="required">*</span></label>
                                        <input type="date" class="form-control" name="end_date"
                                            value="{{ $renewalBatch->end_date }}"
                                            placeholder="Batch Start Date">
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
                                        <input type="date" class="form-control" name="deadline_date[{{\App\Enums\QuoteStatusEnum::CarSold}}]"
                                            value="{{ $carSoldDeadline ?: old('deadline_date[]') }}"
                                            placeholder="Batch Start Date">
                                        <input type="hidden" name="quote_status_id[]" value="{{ \App\Enums\QuoteStatusEnum::CarSold}}">
                                    </div>
                                    <div class="col">
                                        <label for="formGroupExampleInput"> <b>{{ \App\Enums\quoteStatusCode::UNCONTACTABLE}} Deadline </b> <span
                                                class="required">*</span></label>
                                        <input type="date" class="form-control" name="deadline_date[{{\App\Enums\QuoteStatusEnum::Uncontactable}}]"
                                            value="{{ $uncontactableDeadline ?: old('deadline_date[]') }}"
                                            placeholder="Batch Start Date">
                                        <input type="hidden" name="quote_status_id[]" value="{{ \App\Enums\QuoteStatusEnum::Uncontactable}}">
                                    </div>
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
                                                                                min="0" max="100"
                                                                                value="{{ !empty($lastBatchSlabs) && isset($lastBatchSlabs[$slab->id][$team->id]) ? $lastBatchSlabs[$slab->id][$team->id]['pivot']['min'] : old("slab.$slab->id.$team->id.Min") }}"
                                                                                required>
                                                                        </div>
                                                                        <div class="col">
                                                                            <input type="number"
                                                                                name="slab[{{ $slab->id }}][{{ $team->id }}][Max]"
                                                                                class="form-control" placeholder="max"
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
                        <button type="submit" class="btn btn-primary pull-right">Update</button>
                    </form>
                </div>
            </div>

        </div>
    </div>
@endsection

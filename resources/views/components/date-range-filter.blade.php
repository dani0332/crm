<div class="col-md-3">
    <span style="font-size: 11px;" class="col-form-label col-md-8 col-sm-8" for="name">
        {{ strtoupper($customtitles[$property]) . ' START' }}
    </span>
    <input type="text" id="{{ $property }}" style="width:90%;" name="{{ $property }}"
        @if ($property == 'created_at') value="{{ \Carbon\Carbon::today()->subdays(30)->format('d-m-Y') }}" @endif
        class="form-control date-search-field">
    @if ($errors->has($property))
        <span class="text-danger">{{ $errors->first($property) }}</span>
    @endif
</div>

<div class="col-md-3">
    <span style="font-size: 11px;" class="col-form-label col-md-8 col-sm-8" for="name">
        {{ strtoupper($customtitles[$property]) . ' END' }}
    </span>
    <input type="text" id="{{ $property . '_end' }}" style="width:90%;" name="{{ $property . '_end' }}"
        @if ($property == 'created_at') value="{{ \Carbon\Carbon::today()->format('d-m-Y') }}" @endif
        class="form-control date-search-field">
    @if ($errors->has($property . '_end'))
        <span class="text-danger">{{ $errors->first($property . '_end') }}</span>
    @endif
</div>

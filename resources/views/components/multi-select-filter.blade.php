<div class="col-md-3 ms" >
    <span style="font-size: 11px;" class="col-form-label col-md-8 col-sm-8"
        for="name">
        @if (strpos($value, 'title'))
            {{ strtoupper($customtitles[$property]) }}
        @else
            {{ str_replace('_', ' ', strtoupper($property)) }}
        @endif
        @if (strpos($value, 'required') == true)
            <span class='required'>*</span>
        @endif
    </span>
    <select
        multiple="multiple" class="form-control select2 select-roles 1" id="{{ $property }}" name="{{ $property }}" style="width:90%;">
        @if (strpos($value, 'title'))
            <option value="">
                {{ 'Please select ' . $customtitles[$property] }}
            </option>
        @else
            <option value="">
                {{ 'Please select ' . str_replace('id', ' ', str_replace('_', ' ', $property)) }}
            </option>
        @endif
        @if ($property == 'advisor_id')
            <option value="null">UnAssigned</option>
        @endif
        @foreach ($dropdownSource[$property] as $item)
            <option
                value="{{ str_contains($model->properties[$property], 'idAsText') ? $item->text : $item->id }}">
                {{ $item->text ?? $item->name }}
            </option>
        @endforeach
    </select>
    @if ($errors->has($property))
        <span class="text-danger">{{ $errors->first($property) }}</span>
    @endif
</div>

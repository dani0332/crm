<div class="col-md-3" >
    @if (strpos($value, 'input') !== false && !str_contains($value, 'range'))
        <span style="font-size: 11px;"
            class="col-form-label col-md-12 col-sm-12" for="name">
            @if (strpos($value, 'title'))
                {{ strtoupper($customtitles[$property]) }}
            @else
                {{ str_replace('_', ' ', strtoupper($property)) }}
            @endif
        </span>
        <input type={{ explode('|', $value)[1] }} style="width:90%;"
            id={{ $property }} name={{ $property }}
            class="form-control">
        @if ($errors->has($property))
            <span
                class="text-danger">{{ $errors->first($property) }}</span>
        @endif
    @endif
</div>

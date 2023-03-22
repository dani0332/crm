<div class="col-md-3 sf"  >
    @if (strpos($value, 'select') !== false)
        <span style="font-size: 11px;"
            class="col-form-label col-md-6 col-sm-6" for="name">
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
            class="form-control" style="width:90%;"
            id="{{ $property }}" name="{{ $property }}">
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
            <span
                class="text-danger">{{ $errors->first($property) }}</span>
        @endif
    @endif
    @if (strpos($value, 'static') !== false)
        <span style="font-size: 11px;"
            class="col-form-label col-md-6 col-sm-6" for="name">
            @if (strpos($value, 'title'))
                {{ strtoupper($customtitles[$property]) }}
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
        <select style="width:90%;"
            class="form-control"  name="{{ $property }}"
            id="{{ $property }}">
            <option value="">
                {{ 'Please select ' . str_replace('id', ' ', str_replace('_', ' ', $property)) }}
            </option>

            @foreach ($staticOptions as $item)
                @if (str_contains($model->properties[$property], 'default') &&
                        $item == explode('|', explode('default:', $model->properties[$property])[1])[0]
                )
                    <option value="" selected>
                        {{ $item }}
                    </option>
                @else
                    @if ($item == 'No-Type')
                        <option value="null">No-Type</option>
                    @else
                        <option value={{ $item }}>
                            {{ $item }}
                        </option>
                    @endif
                @endif
            @endforeach
        </select>
        @if ($errors->has($property))
            <span
                class="text-danger">{{ $errors->first($property) }}</span>
        @endif
    @endif
</div>

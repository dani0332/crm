@php
$theme = $component->getTheme();
@endphp

@if ($theme === 'tailwind')

<div x-data="{show: false}" x-on:click.away="show = false" class="relative">
    <button x-on:click="show = !show;" x-on:keydown.escape.stop="show = false" type="button" class="pl-3 pr-10 py-2 border border-gray-300 block w-full rounded-md shadow-sm transition duration-150 ease-in-out focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-800 dark:text-white dark:border-gray-600" aria-haspopup="listbox" aria-expanded="true" aria-labelledby="listbox-label">
        <span class="flex items-center truncate py-0">
            {{count($component->getAppliedFilterWithValue($filter->getKey()) ?? [])}} Selected @if($filter->hasConfig('max')) (max {{ $filter->getConfig('max') }}) @endif
        </span>
        <span class="ml-3 absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
            <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M10 3a1 1 0 01.707.293l3 3a1 1 0 01-1.414 1.414L10 5.414 7.707 7.707a1 1 0 01-1.414-1.414l3-3A1 1 0 0110 3zm-3.707 9.293a1 1 0 011.414 0L10 14.586l2.293-2.293a1 1 0 011.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
        </span>
    </button>
    <ul x-show="show" style="display: none" class="absolute z-10 mt-1 w-full bg-white shadow-lg max-h-56 rounded-md p-1.5 text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none sm:text-sm divide-y" tabindex="-1" role="listbox" aria-labelledby="listbox-label" aria-activedescendant="listbox-option-3">
        @if($filter->hasConfig('placeholder'))
        <li>
            <input
                type="checkbox"
                id="{{ $component->getTableName() }}-filter-{{ $filter->getKey() }}-select-all"
                wire:input="selectAllFilterOptions('{{ $filter->getKey() }}')"
                {{ count($component->getAppliedFilterWithValue($filter->getKey()) ?? []) === count($filter->getOptions()) ? 'checked' : ''}}

                class="text-indigo-600 mb-1 rounded border-gray-300 shadow-sm transition duration-150 ease-in-out focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 disabled:opacity-50 disabled:cursor-wait"
            >
            <label for="{{ $component->getTableName() }}-filter-{{ $filter->getKey() }}-select-all" class="dark:text-white">Select All</label>
        </li>
        
        @endif
        @foreach($filter->getOptions() as $key => $value)
        <li wire:key="{{ $component->getTableName() }}-filter-{{ $filter->getKey() }}-multiselect-{{ $key }}" class="flex gap-1.5 py-1.5">
            <input type="checkbox" id="{{ $component->getTableName() }}-filter-{{ $filter->getKey() }}-{{ $loop->index }}" value="{{ $key }}" wire:key="{{ $component->getTableName() }}-filter-{{ $filter->getKey() }}-{{ $loop->index }}" wire:model.lazy="{{ $component->getTableName() }}.filters.{{ $filter->getKey() }}" @if($filter->hasConfig('max'))
            {{ in_array($key, $component->getAppliedFilterWithValue($filter->getKey()) ?? []) ? 'checked' : (count($component->getAppliedFilterWithValue($filter->getKey()) ?? []) !== $filter->getConfig('max') ? '' : 'disabled') }}
            @endif
            :class="{'disabled:bg-gray-400 disabled:hover:bg-gray-400' : {{ count($component->getAppliedFilterWithValue($filter->getKey()) ?? []) === count($filter->getOptions()) ? 'true' : 'false' }}}"
            class="text-indigo-600 rounded border-gray-300 shadow-sm transition duration-150 ease-in-out focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-900 dark:text-white dark:border-gray-600 dark:hover:bg-gray-600 dark:focus:bg-gray-600 disabled:opacity-50 disabled:cursor-wait">
            <label for="{{ $component->getTableName() }}-filter-{{ $filter->getKey() }}-{{ $loop->index }}" class="dark:text-white text-xs hover:text-sky-700 cursor-pointer">{{ $value }}</label>
        </li>
        @endforeach
    </ul>
</div>
@elseif ($theme === 'bootstrap-4' || $theme === 'bootstrap-5')
<div class="form-check">
    <input type="checkbox" id="{{ $component->getTableName() }}-filter-{{ $filter->getKey() }}-select-all" wire:input="selectAllFilterOptions('{{ $filter->getKey() }}')" {{ count($component->getAppliedFilterWithValue($filter->getKey()) ?? []) === count($filter->getOptions()) ? 'checked' : ''}} class="form-check-input">
    <label class="form-check-label" for="{{ $component->getTableName() }}-filter-{{ $filter->getKey() }}-select-all">@lang('All')</label>
</div>

@foreach($filter->getOptions() as $key => $value)
<div class="form-check" wire:key="{{ $component->getTableName() }}-filter-{{ $filter->getKey() }}-multiselect-{{ $key }}">
    <input class="form-check-input" type="checkbox" id="{{ $component->getTableName() }}-filter-{{ $filter->getKey() }}-{{ $loop->index }}" value="{{ $key }}" wire:key="{{ $component->getTableName() }}-filter-{{ $filter->getKey() }}-{{ $loop->index }}" wire:model.stop="{{ $component->getTableName() }}.filters.{{ $filter->getKey() }}">
    <label class="form-check-label" for="{{ $component->getTableName() }}-filter-{{ $filter->getKey() }}-{{ $loop->index }}">{{ $value }}</label>
</div>
@endforeach
@endif

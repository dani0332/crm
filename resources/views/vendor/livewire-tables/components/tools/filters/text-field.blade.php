@if ($filter->hasConfig('range'))

<style>
  span.easepick-wrapper {
    position: relative !important;
    z-index: 10;
  }
</style>

<div class="rounded-md shadow-sm">
  <input wire:model.stop="{{ $component->getTableName() }}.filters.{{ $filter->getKey() }}" wire:key="{{ $component->getTableName() }}-filter-{{ $filter->getKey() }}" id="{{ $component->getTableName() }}-filter-{{ $filter->getKey() }}" type="text" class="block w-full border-gray-300 rounded-md shadow-sm transition duration-150 ease-in-out focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-800 dark:text-white dark:border-gray-600" @if($filter->hasConfig('placeholder')) placeholder="{{ $filter->getConfig('placeholder') }}" @endif
  />
</div>
<script src="https://cdn.jsdelivr.net/npm/@easepick/bundle@1.2.0/dist/index.umd.min.js"></script>
<script>
  const picker = new easepick.create({
    element: document.getElementById('{{ $component->getTableName() }}-filter-{{ $filter->getKey() }}'),
    css: [
        "https://cdn.jsdelivr.net/npm/@easepick/bundle@1.2.0/dist/index.css"
    ],
    zIndex: 10,
    RangePlugin: {
        tooltip: true
    },
    LockPlugin: {
        minDays: 0,
        maxDays: @js($filter->getConfig('max_days')) ?? 365,
    },
    plugins: [
        "RangePlugin",
        "LockPlugin",
        "PresetPlugin"
    ]
  });
</script>
@else
<div class="rounded-md shadow-sm">
  <input wire:model.stop="{{ $component->getTableName() }}.filters.{{ $filter->getKey() }}" wire:key="{{ $component->getTableName() }}-filter-{{ $filter->getKey() }}" id="{{ $component->getTableName() }}-filter-{{ $filter->getKey() }}" type="text" @if($filter->hasConfig('placeholder')) placeholder="{{ $filter->getConfig('placeholder') }}" @endif
  @if($filter->hasConfig('maxlength')) maxlength="{{ $filter->getConfig('maxlength') }}" @endif
  class="block w-full border-gray-300 rounded-md shadow-sm transition duration-150 ease-in-out focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-800 dark:text-white dark:border-gray-600"
  />
</div>
@endif
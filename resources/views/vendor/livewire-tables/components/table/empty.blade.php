@aware(['component'])

@php
    $attributes = $attributes->merge(['wire:key' => 'empty-message-'.$component->id]);
    $theme = $component->getTheme();
@endphp

@if ($theme === 'tailwind')
    <tr {{ $attributes }}>
        <td colspan="{{ $component->getColspanCount() }}">
            <div class="flex items-center space-x-2 dark:bg-gray-800">
                <span class="font-medium p-8 text-gray-400 text-lg dark:text-white">{{ $component->getEmptyMessage() }}</span>
            </div>
        </td>
    </tr>
@elseif ($theme === 'bootstrap-4' || $theme === 'bootstrap-5')
     <tr {{ $attributes }}>
        <td colspan="{{ $component->getColspanCount() }}">
            {{ $component->getEmptyMessage() }}
        </td>
    </tr>
@endif
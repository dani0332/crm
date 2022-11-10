<div class="flex flex-wrap gap-4 justify-between items-center py-4">
  <div class="text-sm">
    Page # <span class="lining-nums font-bold">{{ $this->getCurrentPage() }}</span>
  </div>

  <div class="flex items-center gap-4">
    <button wire:click="previousPage()" wire:loading.attr="disabled" dusk="previousPage" rel="prev" class="relative inline-flex gap-2 items-center px-5 py-2 text-sm font-semibold text-white bg-sky-700 border border-sky-800 rounded-md hover:bg-sky-900 focus:ring focus:ring-sky-700 focus:ring-offset-2 active:bg-gray-800 transition ease-in-out duration-150 disabled:bg-gray-500" {{ $this->getCurrentPage() == 1 ? 'disabled' : '' }}>
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5">
        <path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" />
      </svg>
      Prev
    </button>

    <button wire:click="nextPage()" wire:loading.attr="disabled" dusk="nextPage" rel="next" class="relative inline-flex gap-2 items-center px-5 py-2 text-sm font-semibold text-white bg-sky-700 border border-sky-800 rounded-md hover:bg-sky-900 focus:ring focus:ring-sky-700 focus:ring-offset-2 active:bg-gray-800 transition ease-in-out duration-150 disabled:bg-gray-500" {{ $rows->count() < 10 ? 'disabled' : '' }}>
      Next
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5">
        <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
      </svg>
    </button>
  </div>
</div>

<div x-data="{ showModal: $wire.entangle('show') }" x-on:keydown.window.escape="showModal = false" class="relative z-10" aria-labelledby="modal-title" role="dialog" aria-modal="true">

    <div x-cloak x-show="showModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

    <div x-cloak x-show="showModal" x-transition class="fixed inset-0 z-50 flex items-center justify-center px-4">
        <div x-on:click.away="showModal = false" class="relative w-full max-w-6xl overflow-y-auto max-h-[88vh] rounded-lg bg-white p-4 shadow-lg">
            <div class="sticky top-0">
                <button x-on:click="showModal = false" class="absolute -right-2 -top-2">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-8 h-8 hover:text-orange-600">
                        <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zm-1.72 6.97a.75.75 0 10-1.06 1.06L10.94 12l-1.72 1.72a.75.75 0 101.06 1.06L12 13.06l1.72 1.72a.75.75 0 101.06-1.06L13.06 12l1.72-1.72a.75.75 0 10-1.06-1.06L12 10.94l-1.72-1.72z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>

            @if ($show)
                <h2 class="text-lg font-semibold mb-4 text-center pb-2 border-b">Advisor Leads</h2>
                <div>
                    @livewire('advisor-assigned-car-quotes-table', ['advisorId' => $this->advisorId, 'leadType' => $this->leadType, 'startDate' => $startDate, 'endDate' => $endDate,
                    'excludeCreatedLeadsFilter' => $excludeCreatedLeadsFilter, 'createdAtFilter' => $createdAtFilter, 'ecommerceFilter' => $ecommerceFilter, 'batchNumberFilter' => $batchNumberFilter, 'tiersFilter' => $tiersFilter
                    , 'leadSourceFilter' => $leadSourceFilter, 'teamsFilter' => $teamsFilter,'advisorsFilter' => $advisorsFilter])
                </div>
            @endif

        </div>
    </div>
</div>

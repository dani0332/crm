<script setup>

const isSendUpdateListView = ref(false);
const modals = reactive({
    filterLoading: false,
    filters: false,
});

const filtersModal = () => {
    modals.filterLoading = true;
    modals.filters = true;
};

const resetFilter = () => {
    modals.filterLoading = false;
    modals.filters = false;
};

const searchRecords = () => {
    modals.filterLoading = false;
    modals.filters = false;
};

const leadsListTable = [
    { text: 'Ref-ID', value: '' },
    { text: 'First Name', value: '' },
    { text: 'Last Name', value: '' },
    { text: 'Company Name', value: '' },
    { text: 'Line of Business', value: '' },
    { text: 'Business Insurance Type', value: '' },
    { text: 'Created Date', value: '' },
    { text: 'Policy Expiry Date ', value: '' },
    { text: 'Policy Number', value: '', width: 60, align: 'center' },
    { text: 'Status', value: '' },
];

const sendUpdateListTable = [
    { text: 'SU Ref-ID', value: '' },
    { text: 'First Name', value: '' },
    { text: 'Last Name', value: '' },
    { text: 'Company Name', value: '' },
    { text: 'Line of Business', value: '' },
    { text: 'Business Insurance Type', value: '' },
    { text: 'Created Date', value: '' },
    { text: 'Policy Expiry Date ', value: '' },
    { text: 'Policy Number', value: '', width: 60, align: 'center' },
    { text: 'Type', value: '' },
    { text: 'Sub type', value: '' },
    { text: 'Notes', value: '' },
    { text: 'Status', value: '' },
];


</script>

<template>
    <div>
        <Head title="Search" />
        <div class="flex justify-between items-center">
            <h2 class="text-xl font-semibold">{{ isSendUpdateListView ? 'Send Update' : 'Lead'}} List</h2>
            <div class="space-x-3">
                <x-button size="sm" color="emerald">
                    Export to Excel
                </x-button>
                <x-button size="sm" color="primary" @click="isSendUpdateListView=!isSendUpdateListView">
                    {{ isSendUpdateListView ? 'Lead' : 'Send Update'}} List
                </x-button>
                <x-button size="sm" color="orange" @click.prevent="filtersModal" :loading="modals.filterLoading">
                    <x-icon
                        icon="magnifyingGlass"
                        size="sm"
                        class="transition transform duration-300"
                    />
                    Search Filter
                </x-button>
            </div>
        </div>
        <x-divider class="my-4" />
        <DataTable
            table-class-name="tablefixed"
            :headers="isSendUpdateListView ? sendUpdateListTable : leadsListTable"
            :items="[]"
            border-cell
            hide-rows-per-page
            hide-footer
        >
        </DataTable>
        <x-modal
            v-model="modals.filters"
            size="lg"
            title="Search Filters"
            show-close
            backdrop
        >
            <template #title>
                <h2 class="text-lg font-semibold">Search Filters</h2>
            </template>
            <template #body>
                <div class="space-y-4">
                    <x-input label="Subject" />
                    <x-textarea label="Message" />
                </div>
            </template>
            <template #footer>
                <div class="flex justify-end space-x-3">
                    <x-button size="sm" color="orange" @click="searchRecords">Search</x-button>
                    <x-button size="sm" color="primary" @click="resetFilter">Reset</x-button>
                </div>
            </template>
        </x-modal>
    </div>
</template>

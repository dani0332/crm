<script setup>
import { ref } from 'vue';

defineProps({
    quotes: Object,
});

const page = usePage();
const loader = reactive({
    table: false,
    export: false,
});

let availableFilters = {
    advisor_id: '',
    renewal_batch: '',
    approval_status: '',
    page: 1,
};

const filters = reactive(availableFilters);

function onSubmit(isValid) {
    if (isValid) {
        filters.page = 1;

        Object.keys(filters).forEach(
            key =>
                (filters[key] === '' || filters[key].length === 0) &&
                delete filters[key],
        );

        router.visit('/quotes/car-sold', {
            method: 'get',
            data: filters,
            preserveState: true,
            preserveScroll: true,
            onBefore: () => (loader.table = true),
            onSuccess: () => (loader.table = false),
        });
    } else {
        console.log('Invalid');
    }
}

function onReset() {
    router.visit('/quotes/car-sold', {
        method: 'get',
        data: { page: 1 },
        preserveScroll: true,
        onBefore: () => (loader.table = true),
        onSuccess: () => (loader.table = false),
    });
}

function setQueryStringFilters() {
    let queryString = window.location.search;
    let urlParams = new URLSearchParams(queryString);

    for (const [key] of Object.entries(availableFilters)) {
        if (urlParams.has(key)) {
            filters[key] = urlParams.get(key);
        }
    }
}

onMounted(() => {
    setQueryStringFilters();
});

const tableHeader = [
    { text: 'CDB ID', value: 'uuid' },
    { text: 'Renewal Batch', value: 'renewal_batch' },
    { text: 'FIRST NAME', value: 'first_name' },
    { text: 'LAST NAME', value: 'last_name' },
    { text: 'ADVISOR', value: 'advisor' },
    { text: 'Approval Status STATUS', value: 'quote_status' },
    { text: 'Notes', value: 'notes' },
];

const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;

const quotesSelected = ref([]),
    assignAdvisor = ref(null),
    assignmentType = ref(null),
    isDisabled = ref(false);
</script>

<template>
    <div>
        <Head title="Car Sold Quotes" />

        <div class="flex justify-between items-center">
            <h2 class="text-xl font-semibold">Car Sold Quotes List</h2>
        </div>
        <x-divider class="my-4" />

        <!--   filters     -->
        <x-form @submit="onSubmit" :auto-focus="false">
            <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-4">

                <x-input
                    v-model="filters.advisor_id"
                    type="search"
                    name="renewal_batch"
                    label="Renewal Batch"
                    class="w-full"
                    placeholder="Search by Renewal Batch"
                />

                <x-input
                    v-model="filters.renewal_batch"
                    type="search"
                    name="renewal_batch"
                    label="Renewal Batch"
                    class="w-full"
                    placeholder="Search by Renewal Batch"
                />

                <x-select
                    v-model="filters.approval_status"
                    label="Approval Status"
                    placeholder="Search by approval status"
                    :options="[
            { value: 'Pending', label: 'Pending' },
            { value: 'Approved', label: 'Approved' },
            { value: 'Rejected', label: 'Rejected' },
          ]"
                    class="w-full"
                />


            </div>
            <div class="flex justify-end gap-3 mb-4">
                <x-button size="sm" color="#ff5e00" type="submit">Search</x-button>
                <x-button size="sm" color="primary" @click.prevent="onReset">
                    Reset
                </x-button>
            </div>
        </x-form>



        <DataTable
            v-model:items-selected="quotesSelected"
            table-class-name="tablefixed"
            :headers="tableHeader"
            :loading="loader.table"
            :items="quotes.data || []"
            border-cell
            hide-rows-per-page
            hide-footer
            fixed-checkbox
        >
            <template #item-uuid="{ code, uuid }">
                {{ code }}
            </template>

            <template #item-advisor="{ advisor }">
                {{ advisor?.email }}
            </template>

            <template #item-is_ecommerce="{ is_ecommerce }">
                <div class="text-center">
                    <x-tag size="sm" :color="is_ecommerce ? 'success' : 'error'">
                        {{ is_ecommerce ? 'Yes' : 'No' }}
                    </x-tag>
                </div>
            </template>

        </DataTable>

        <Pagination
            :links="{
        next: quotes.next_page_url,
        prev: quotes.prev_page_url,
        current: quotes.current_page,
        from: quotes.from,
        to: quotes.to,
      }"
        />
    </div>
</template>

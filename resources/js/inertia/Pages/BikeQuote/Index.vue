<script setup>
import { reactive, computed, onMounted, ref } from 'vue';
import { Head, router, usePage, Link } from '@inertiajs/vue3';
import Pagination from '@/inertia/Components/Pagination.vue';
import ExportExcel from '@/inertia/Components/ExportExcel.vue';
import ComboBox from '@/inertia/Components/ComboBox.vue';

defineProps({
    quotes: Object,
});

const page = usePage();
const loader = reactive({
    table: false,
    export: false,
});

let availableFilters = {
    code: '',
    first_name: '',
    last_name: '',
    email: '',
    mobile_no: '',
    created_at_start: '',
    created_at_end: '',
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
        router.visit('/personal-quotes/bike', {
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
    router.visit('/personal-quotes/bike', {
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
        if(urlParams.has(key)) {
            filters[key] = urlParams.get(key);
        }
    }
}

onMounted(() => {
    setQueryStringFilters();
});

const tableHeader = [
    { text: 'CDB ID', value: 'uuid' },
    { text: 'FIRST NAME', value: 'first_name' },
    { text: 'LAST NAME', value: 'last_name' },
    { text: 'Email', value: 'email' },
    { text: 'Mobile No', value: 'mobile_no' },
    { text: 'CREATED DATE', value: 'created_at' },
    { text: 'LAST MODIFIED DATE', value: 'updated_at' },
];


</script>

<template>
    <div>
        <Head title="Bike Quotes" />
        <div class="flex justify-between items-center">
            <h2 class="text-xl font-semibold">Bike Quotes List</h2>
            <x-button size="sm" color="#ff5e00" href="/personal-quotes/bike/create">
                Create Lead
            </x-button>
        </div>
        <x-divider class="my-4" />

        <!--   filters     -->
        <x-form @submit="onSubmit" :auto-focus="false">
            <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-4">
                <x-input
                    v-model="filters.uuid"
                    type="search"
                    name="code"
                    label="CDB ID"
                    class="w-full"
                    placeholder="Search by CDB ID"
                />
                <x-input
                    v-model="filters.first_name"
                    type="search"
                    name="first_name"
                    label="First Name"
                    class="w-full"
                    placeholder="Search by First Name"
                />
                <x-input
                    v-model="filters.last_name"
                    type="search"
                    name="last_name"
                    label="Last Name"
                    class="w-full"
                    placeholder="Search by Last Name"
                />
                <x-input
                    v-model="filters.email"
                    type="search"
                    name="email"
                    label="Email"
                    class="w-full"
                    placeholder="Search by Email"
                />
                <x-input
                    v-model="filters.mobile_no"
                    type="search"
                    name="mobile_no"
                    label="Mobile Number"
                    class="w-full"
                    placeholder="Search by Mobile Number"
                />
                <x-input
                    v-model="filters.created_at_start"
                    type="date"
                    name="created_at_start"
                    label="Created Date"
                    class="w-full"
                />
                   <x-input
                    v-model="filters.created_at_end"
                    type="date"
                    name="created_at_end"
                    label="Created Date End"
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

            table-class-name="tablefixed"
            :headers="tableHeader"
            :loading="loader.table"
            :items="quotes.data || []"
            border-cell
            hide-rows-per-page
            hide-footer
            fixed-checkbox
        >

            <template #item-uuid="{ uuid }">
                <Link
                    :href="`/personal-quotes/bike/${uuid}`"
                    class="text-primary-500 hover:underline"
                >
                    {{ uuid }}
                </Link>
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


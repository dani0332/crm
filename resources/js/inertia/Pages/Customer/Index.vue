<script setup>

defineProps({
    customers : Object
});

let availableFilters = {
    search_type : '',
    search_value : '',
    page : 1
}

const filters = reactive(availableFilters);
const loader = reactive({
    table: false,
    export: false,
});

const tableHeader = [
    { text: 'CUSTOMER ID', value: 'uuid' },
    { text: 'NAME', value: 'first_name' },
    { text: 'INSURED NAME', value: 'insured_first_name' },
    { text: 'CREATED AT', value: 'created_at' },
    { text: 'UPDATED AT', value: 'updated_at' },
    { text: 'POLICY NUMBER', value: 'mobile_no' },
    { text: 'POLICY START DATE', value: 'gender' },
    { text: 'POLICY END DATE', value: 'has_alfred_access' },
    { text: 'TYPE OF POLICY', value: 'dob' },
    { text: 'ADVISOR', value: 'dob' },
];

function onSubmit(isValid){
    if (isValid) {
        filters.page = 1;
        Object.keys(filters).forEach(
            key =>
                (filters[key] === '' || filters[key].length === 0) &&
                delete filters[key],
        );

        router.visit('/customer', {
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

function onReset(){
    router.visit('/customer', {
        method: 'get',
        data: { page: 1 },
        preserveScroll: true,
        onBefore: () => (loader.table = true),
        onSuccess: () => (loader.table = false),
    });
}

</script>

<template>
    <div>
        <Head title="Customer" />
        <div class="flex justify-between items-center">
            <h2 class="text-xl font-semibold">Customer List</h2>
        </div>
        <x-divider class="my-4" />

        <!-- Filters -->
        <x-form @submit="onSubmit" :auto-focus="false">
            <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-4">
                <x-select
                    v-model="filters.search_type"
                    label="Search By"
                    :options="[
                        { value: 'email', label: 'Email Address' },
                        { value: 'first_name', label: 'Customer Name' },
                        { value: 'entity_name', label: 'Entity Name' },
                        { value: 'insured_first_name', label: 'Insured Name' },
                        { value: 'mobile_no', label: 'Mobile Number' },
                        { value: 'uuid', label: 'Customer ID' },
                      ]"
                    placeholder="Search By"
                    class="w-full"
                />
                <x-input
                    v-model="filters.search_value"
                    type="search"
                    name="search_value"
                    label="Search Value"
                    placeholder="Search Value"
                    class="w-full"
                />
            </div>
            <div class="flex justify-end gap-2 mb-4 mt-1">
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
            :items="customers.data || []"
            border-cell
            hide-rows-per-page
            hide-footer
            fixed-checkbox>

            <template #item-id="{id, uuid}">
                <Link :href="`/customer/${uuid}`" class="text-primary-500 hover:underline">
                    {{ id }}
                </Link>
            </template>

            <template #item-has_alfred_access="{has_alfred_access}">
                <div class="text-center">
                    <x-tag size="sm" :color="has_alfred_access ? 'success' : 'error'">
                        {{ has_alfred_access ? 'Yes' : 'No' }}
                    </x-tag>
                </div>
            </template>

        </DataTable>
        <Pagination
            :links="{
                next: customers.next_page_url,
                prev: customers.prev_page_url,
                current: customers.current_page,
                from: customers.from,
                to: customers.to,
          }"
        />
    </div>
</template>

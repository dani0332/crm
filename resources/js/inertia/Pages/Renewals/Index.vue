<script setup>
import {computed, reactive} from "vue";
import {Head, router, usePage, Link, useForm} from '@inertiajs/vue3';

defineProps({
    quoteStatuses: Array,
    renewalBatches: Array,
});

const page = usePage();

const filters = reactive({
    deadline_date: '',
    quote_status: '',
    page: 1,
});

const loader = reactive({
    table: false,
    export: false,
});

const quoteStatusOptions = computed(() => {
    return page.props.quoteStatuses.map(status => ({
        value: status.id,
        label: status.text,
    }));
});

const tableHeader = [
    { text: 'BATCH', value: 'batch' },
    { text: 'LEAD STATUS', value: 'quote_status' },
    { text: 'DEADLINE DATE', value: 'deadline_date' },
];

</script>

<template>
    <div>
        <Head title="Renewal Batches"/>
        <div class="flex justify-between items-center">
            <h2 class="text-xl font-semibold">Renewal Batches</h2>
            <div class="space-x-3">
                <Link href="renewal-batches/create">
                    <x-button size="sm" color="#ff5e00" tag="div"> Create Renewal Batch</x-button>
                </Link>
            </div>
        </div>
        <x-divider class="my-4"/>
        <x-form @submit="onSubmit" :auto-focus="false">
            <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-4">
                <x-select
                    v-model="filters.quote_status"
                    label="Quote Status"
                    :options="quoteStatusOptions"
                    class="w-full"
                />
                <DatePicker
                    v-model="filters.deadline_date"
                    name="deadline_date"
                    label="Deadline Date"
                />
            </div>
            <div class="flex justify-end gap-3 mb-4">
                <x-button size="sm" color="#ff5e00" type="submit">Search</x-button>
                <x-button size="sm" color="primary">
                    Reset
                </x-button>
            </div>
        </x-form>
        <DataTable
            v-model:items-selected="quotesSelected"
            table-class-name="tablefixed"
            :headers="tableHeader"
            :loading="loader.table"
            :items="renewalBatches.data || []"
            border-cell
            hide-rows-per-page
            hide-footer
            fixed-checkbox
        ></DataTable>
        <Pagination
            :links="{
            next: renewalBatches.next_page_url,
            prev: renewalBatches.prev_page_url,
            current: renewalBatches.current_page,
            from: renewalBatches.from,
            to: renewalBatches.to,
          }"
        />
    </div>
</template>

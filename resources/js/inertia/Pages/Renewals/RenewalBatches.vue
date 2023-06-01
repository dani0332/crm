<script setup>
import {computed, reactive} from "vue";
import { Head, router, usePage, Link, useForm } from '@inertiajs/vue3';

defineProps({
    quoteStatuses: Array,
});

const page = usePage();

const filters = reactive({
    deadline_date: '',
    quote_status: '',
    page: 1,
});

const quoteStatusOptions = computed(() => {
    return page.props.quoteStatuses.map(status => ({
        value: status.id,
        label: status.text,
    }));
});

</script>

<template>
    <div>
        <Head title="Renewal Batches" />
        <div class="flex justify-between items-center">
            <h2 class="text-xl font-semibold">Renewal Batches</h2>
        </div>
        <x-divider class="my-4" />
        <x-form :auto-focus="false">
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
    </div>
</template>

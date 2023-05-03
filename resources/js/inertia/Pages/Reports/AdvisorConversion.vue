<script>
defineProps({
    data: Array,
});

const loader = ref(false);
const totalLeadsModal = reactive({
    show: false,
    data: null,
});

const tableHeader = [{
        text: 'Batch Number',
        value: 'batch_name'
    },
    {
        text: 'Start Date',
        value: 'start_date'
    },
    {
        text: 'Stop Date',
        value: 'end_date'
    },
    {
        text: 'Advisor Name',
        value: 'advisor_name'
    },
    {
        text: 'Total Leads',
        value: 'total_leads'
    },
    {
        text: 'New Leads',
        value: 'new_leads'
    },
    {
        text: 'Not Interested',
        value: 'not_interested'
    },
    {
        text: 'In Progress',
        value: 'in_progress'
    },
    {
        text: 'Bad Leads',
        value: 'bad_leads'
    },
    {
        text: 'Sale Leads',
        value: 'sale_leads'
    },
    {
        text: 'Created Sale Leads',
        value: 'created_sale_leads'
    },
    {
        text: 'IM Renewals',
        value: 'afia_renewals_count'
    },
    {
        text: 'Manual Created',
        value: 'manual_created_bad_leads'
    },
    {
        text: 'Gross Conversion',
        value: 'gross_conversion'
    },
    {
        text: 'Net Conversion',
        value: 'net_conversion'
    },
];

function calculateGrossConversion(item) {

    if (item) {
        const totalLeadsCount = item.total_leads;
        const manualCreated = item.manual_created;
        const saleLeads = item.sale_leads;
        const createdSaleLeads = item.created_sale_leads;
        const numerator = saleLeads - createdSaleLeads;
        const denominator = totalLeadsCount - manualCreated;
        if (denominator > 0) {
            return parseFloat((numerator / denominator) * 100).toFixed(2) + ' %';
        } else {
            return 'NaN';
        }
    } else {
        return 'undefined';
    }
}

function calculateNetConversion(row) {
    const totalLeads = row.total_leads;
    const manualCreated = row.manual_created;
    const badLeads = row.bad_leads;
    const manualCreatedBadLeads = row.manual_created_bad_leads;
    const saleLeads = row.sale_leads;
    const createdSaleLeads = row.created_sale_leads;
    const numerator = saleLeads - createdSaleLeads;
    const denominator = (totalLeads - manualCreated) - (badLeads - manualCreatedBadLeads);
    if (denominator > 0) {
        return parseFloat((numerator / denominator) * 100).toFixed(2) + ' %';
    } else {
        return 'NaN';
    }
}
</script>

<template>
<div>

    <Head title="Advisor Conversion Report" />
    <h1 class="text-2xl font-bold text-center text-primary-500 mb-4">
        Advisor Conversion Report
    </h1>

    <DataTable table-class-name="tablefixed" :loading="loader" :headers="tableHeader" :items="data || []" :rows-per-page="-1" border-cell hide-rows-per-page hide-footer>
        <template #item-gross_conversion="item">
            {{ calculateGrossConversion(item) }}
        </template>
        <template #item-net_conversion="item">
            {{ calculateNetConversion(item) }}
        </template>
        <template #item-total_leads="{ total_leads }">
            <button @click="totalLeadsModal.show = true">{{ total_leads }}</button>
        </template>
    </DataTable>

    <x-modal v-model="totalLeadsModal.show" size="lg" show-close backdrop>
        <template #header> Total Leads Modal </template>
        Hi there
    </x-modal>
</div>
</template>

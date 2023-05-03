<script setup>
defineProps({
  data: Array,
});

const loader = ref(false);
const totalLeadsModal = reactive({
  show: false,
  data: null,
});

const tableHeader = [
  { text: 'Batch Number', value: 'batch_name' },
  { text: 'Start Date', value: 'start_date' },
  { text: 'Stop Date', value: 'end_date' },
  { text: 'Advisor Name', value: 'advisor_name' },
  { text: 'Total Leads', value: 'total_leads' },
  { text: 'New Leads', value: 'new_leads' },
  { text: 'Not Interested', value: 'not_interested' },
  { text: 'In Progress', value: 'in_progress' },
  { text: 'Bad Leads', value: 'bad_leads' },
  { text: 'Sale Leads', value: 'sale_leads' },
  { text: 'Created Sale Leads', value: 'created_sale_leads' },
  { text: 'IM Renewals', value: 'afia_renewals_count' },
  { text: 'Manual Created', value: 'manual_created_bad_leads' },
  { text: 'Gross Conversion', value: '' },
  { text: 'Net Conversion', value: '' },
];
</script>

<template>
  <div>
    <Head title="Advisor Conversion Report" />
    <h1 class="text-2xl font-bold text-center text-primary-500 mb-4">
      Advisor Conversion Report
    </h1>

    <DataTable
      table-class-name="tablefixed"
      :loading="loader"
      :headers="tableHeader"
      :items="data || []"
      border-cell
      hide-rows-per-page
      hide-footer
    >
      <template #item-total_leads="{ total_leads }">
        <a @click="totalLeadsModal.show = true" class="text-primary-500 underline">{{ total_leads }}</a>
      </template>
    </DataTable>

    <!-- <Pagination
      :links="{
        next: data.next_page_url,
        prev: data.prev_page_url,
        current: data.current_page,
        from: data.from,
        to: data.to,
      }"
    /> -->

    <x-modal v-model="totalLeadsModal.show" size="lg" show-close backdrop>
      <template #header> Total Leads Modal </template>
      Hi there
    </x-modal>
  </div>
</template>

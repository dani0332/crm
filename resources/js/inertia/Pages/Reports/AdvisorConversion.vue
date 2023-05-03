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
  { text: 'Batch Number', value: '' },
  { text: 'Start Date', value: '' },
  { text: 'Stop Date', value: '' },
  { text: 'Advisor Name', value: 'advisorId' },
  { text: 'Total Leads', value: 'total_leads' },
  { text: 'New Leads', value: '' },
  { text: 'Not Interested', value: '' },
  { text: 'In Progress', value: '' },
  { text: 'Bad Leads', value: '' },
  { text: 'Sale Leads', value: '' },
  { text: 'Created Sale Leads', value: '' },
  { text: 'IM Renewals', value: '' },
  { text: 'Manual Created', value: '' },
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
        <button @click="totalLeadsModal.show = true">{{ total_leads }}</button>
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

<script setup>
const page = usePage();

const showModal = inject('showSageAPILogsModal');
const sageAPILogs = inject('sageAPILogs');
console.clear();
console.log('Show API Logs Component');
console.log(showModal);
console.log(sageAPILogs.table);
const dateFormat = date => useDateFormat(date, 'DD-MM-YYYY h:mm:ss a');
</script>

<template>
  <div>
    <x-modal v-model="showModal" size="xl" backdrop>
      <template #header>
        <span>Sage API Logs </span>
      </template>
      <DataTable
        table-class-name="compact tablefixed"
        :headers="sageAPILogs.table"
        :items="sageAPILogs.data || []"
        border-cell
        hide-rows-per-page
        :rows-per-page="15"
        :hide-footer="sageAPILogs.data?.length < 15"
      >
        <template #item-created_at="{ created_at }">
          {{ dateFormat(created_at).value }}
        </template>
      </DataTable>
    </x-modal>
  </div>
</template>

<style scoped></style>

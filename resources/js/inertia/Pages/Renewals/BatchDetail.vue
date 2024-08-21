<script setup>
defineProps({
  emailBatches: Object,
  batch: String,
  hideSendEmailButton: Number,
});

const page = usePage();

const hasRole = role => useHasRole(role);
const rolesEnum = page.props.rolesEnum;

const role = [rolesEnum.Admin, rolesEnum.LeadPool, rolesEnum.LifeManager];
const roleLeadPool = [rolesEnum.LeadPool];
const hasAnyRole = role => useHasAnyRole(role);

const loader = reactive({
  table: false,
  export: false,
});

const tableHeader = [
  { text: 'Id', value: 'id' },
  { text: 'Batch', value: 'batch' },
  { text: 'Total Leads', value: 'total_leads' },
  { text: 'Total Sent', value: 'total_sent' },
  { text: 'Total Failed', value: 'total_failed' },
  { text: 'Status', value: 'status' },
  { text: 'User', value: 'createdby.email' },
  { text: 'Created At', value: 'created_at' },
  { text: 'Updated At', value: 'updated_at' },
];

const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;
</script>

<template>
  <div>
    <Head title="Email Batch Details" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">Email Batch Details</h2>
      <div class="space-x-3">
        <x-button color="#ff5e00" href="/renewals/batches" class="btn-2"
          >Batches List</x-button
        >
        <x-button
          color="primary"
          onclick="return confirm('Do you want to send emails?');"
          :href="`/renewals/batches/${batch}/schedule-renewals-ocb`"
          >Send Emails</x-button
        >
      </div>
    </div>
    <x-divider class="my-4" />

    <x-alert color="error" class="mb-5" v-if="$page.props?.errors.error">
      {{ $page.props?.errors.error }}
    </x-alert>

    <DataTable
      table-class-name="tablefixed"
      :loading="loader.table"
      :headers="tableHeader"
      :items="emailBatches.data || []"
      border-cell
      hide-rows-per-page
      hide-footer
    >
    </DataTable>

    <Pagination
      :links="{
        next: emailBatches.next_page_url,
        prev: emailBatches.prev_page_url,
        current: emailBatches.current_page,
        from: emailBatches.from,
        to: emailBatches.to,
      }"
    />
  </div>
</template>

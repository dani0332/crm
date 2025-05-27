<script setup>
defineProps({
  process: Object,
  batch: String,
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
  { text: 'Completed', value: 'total_completed' },
  { text: 'Failed', value: 'total_failed' },
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
    <Head title="Plans Processes" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">Plans Processes</h2>
      <div class="space-x-3">
        <x-button color="#ff5e00" href="/renewals/batches" class="btn-2"
          >Batches List</x-button
        >
        <x-button
          color="primary"
          onclick="return confirm('Do you want to fetch Plans?');"
          :href="`/renewals/batches/${batch}/fetch-plans`"
          >Fetch Plans</x-button
        >
      </div>
    </div>
    <x-divider class="my-4" />

    <DataTable
      table-class-name="tablefixed"
      :loading="loader.table"
      :headers="tableHeader"
      :items="process.data || []"
      border-cell
      hide-rows-per-page
      hide-footer
    >
      <template #item-good="{ id, good }">
        <Link
          :href="`/renewals/uploaded-leads/${id}/validation-passed`"
          class="text-primary-500 hover:underline btn-passed"
        >
          {{ good }}
        </Link>
      </template>
      <template #item-cannot_upload="{ id, cannot_upload }">
        <Link
          :href="`/renewals/uploaded-leads/${id}/validation-failed`"
          class="text-primary-500 hover:underline btn-passed"
        >
          {{ cannot_upload }}
        </Link>
      </template>
      <template #item-skip_plans="{ skip_plans }">
        {{
          !skip_plans
            ? EnumGenericNo
            : skip_plans == EnumSkipPlansNonGCC
              ? 'YES - NON GCC'
              : EnumGenericYes
        }}
      </template>
      <template #item-uploaded_by="{ uploaded_by }">
        {{ uploaded_by }}
      </template>
    </DataTable>

    <Pagination
      :links="{
        next: process.next_page_url,
        prev: process.prev_page_url,
        current: process.current_page,
        from: process.from,
        to: process.to,
      }"
    />
  </div>
</template>

<script setup>
defineProps({
  leads: Object,
  EnumGenericNo: String,
  EnumGenericYes: String,
  EnumSkipPlansNonGCC: String,
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
  { text: 'Upload Type', value: 'renewal_import_type' },
  { text: 'Upload Code', value: 'renewal_import_code' },
  { text: 'File name', value: 'file_name' },
  { text: 'Total records', value: 'total_records' },
  { text: 'Good', value: 'good' },
  { text: 'Bad', value: 'cannot_upload' },
  { text: 'Status', value: 'status' },
  { text: 'SIC', value: 'is_sic' },
  { text: 'Skip Plans', value: 'skip_plans' },
  { text: 'Submitted By', value: 'uploaded_by' },
  { text: 'Submitted At', value: 'created_at' },
  { text: 'Updated At', value: 'updated_at' },
];

const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;
</script>

<template>
  <div>
    <Head title="Uploaded Renewal Leads Files" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">Uploaded Renewal Leads Files</h2>
      <div class="space-x-3"></div>
    </div>
    <x-divider class="my-4" />

    <DataTable
      table-class-name="tablefixed"
      :loading="loader.table"
      :headers="tableHeader"
      :items="leads.data || []"
      border-cell
      hide-rows-per-page
      hide-footer
    >
      <template #item-good="{ id, good }">
        <x-button
          size="xs"
          light
          color="success"
          :href="`/renewals/uploaded-leads/${id}/validation-passed`"
        >
          {{ good }}
        </x-button>
      </template>
      <template #item-cannot_upload="{ id, cannot_upload }">
        <x-button
          size="xs"
          light
          color="error"
          :href="route('renewal-validation-failed', id)"
        >
          {{ cannot_upload }}
        </x-button>
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
        next: leads.next_page_url,
        prev: leads.prev_page_url,
        current: leads.current_page,
        from: leads.from,
        to: leads.to,
      }"
    />
  </div>
</template>

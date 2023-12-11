<script setup>
const props = defineProps({
  tmLeadStatuses: Array,
  handlers: Array,
  tmLeadTypes: Array,
  tmInsuranceTypes: Array,
  isCurrentUserIsAdvisor: String,
});

const loader = reactive({ table: false });
const handlersOptions = ref([
  ...[
    { name: 'All', id: '' },
    { name: 'Unassigned', id: 'Unassigned' },
    { name: 'MyLeads', id: 'MyLeads' },
  ],
  ...props.handlers,
]);

const filters = reactive({
  search_by: '',
  leadStatusid: '',
  assigned_to_id: '',
  tm_lead_types_id: '',
  tm_insurance_types_id: '',
});

const tableHeader = ref([
  { text: 'Ref-ID', value: 'uuid' },
  { text: 'TM ID', value: 'first_name' },
  { text: 'CUSTOMER NAME', value: 'last_name' },
  { text: 'INSURANCE TYPE', value: 'quote_status' },
  { text: 'LEAD STATUS', value: 'advisor' },
  { text: 'NOTES', value: 'created_at' },
  { text: 'ENQUIER DATE', value: 'updated_at' },
  { text: 'ALLOCATION DATE', value: 'transapp_code' },
  { text: 'NEXT FOLLOW-UP DATE', value: 'source' },
  { text: 'ADVISOR', value: 'lost_reason' },
  { text: 'CREATED AT', value: 'premium' },
  { text: 'UPDATED AT', value: 'policy_number' },
]);
</script>
<template>
  <div>
    <Head title="TM Leads" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">TM Leads</h2>
      <x-button size="sm" color="#ff5e00"> Create TM Lead </x-button>
    </div>
    <x-divider class="my-4" />
    <!--   filters     -->
    <x-form @submit="onSubmit" :auto-focus="false">
      <div class="grid sm:grid-cols-2 md:grid-cols-3 gap-4">
        <x-field label="Search By">
          <x-select
            v-model="filters.search_by"
            placeholder="Search by"
            :options="[
              { value: 'cdbID', label: 'TM ID' },
              { value: 'emailAddress', label: 'Email Address' },
              { value: 'phoneNumber', label: 'Phone Number' },
              { value: 'created_at', label: 'Created At' },
              { value: 'updated_at', label: 'Updated At' },
              { value: 'next_followup_date', label: 'Next Followup Date' },
              { value: 'next_followup_date', label: 'Next Followup Date' },
              { value: 'enquiry_date', label: 'Enquiry Date' },
              { value: 'enquiry_date', label: 'Enquiry Date' },
              { value: 'allocation_date', label: 'Allocation Date' },
            ]"
            class="w-full"
          />
        </x-field>
        <x-field label="Search Value">
          <x-input placeholder="Search value" class="w-full" />
        </x-field>
        <x-field label="Lead Status">
          <x-select
            v-model="filters.leadStatusid"
            placeholder="Lead status"
            :options="
              tmLeadStatuses.map(item => ({
                value: item.id,
                label: item.text,
              }))
            "
            class="w-full"
          />
        </x-field>
        <x-field label="Lead Owner">
          <x-select
            v-model="filters.assigned_to_id"
            placeholder="Search value"
            :options="
              handlersOptions.map(item => ({
                value: item.id,
                label: item.name,
              }))
            "
            class="w-full"
          />
        </x-field>
        <x-field label="Lead Type">
          <x-select
            v-model="filters.tm_lead_types_id"
            placeholder="Search value"
            :options="
              tmLeadTypes.map(item => ({
                value: item.id,
                label: item.name,
              }))
            "
            class="w-full"
          />
        </x-field>
        <x-field label="Insurance Type">
          <x-select
            v-model="filters.tm_insurance_types_id"
            placeholder="Search value"
            :options="
              tmInsuranceTypes.map(item => ({
                value: item.id,
                label: item.name,
              }))
            "
            class="w-full"
          />
        </x-field>
      </div>
      <div class="flex justify-end gap-3 mb-4 mt-1">
        <div class="flex justify-self-end gap-3">
          <x-button size="sm" color="#ff5e00" type="submit">Search</x-button>
          <x-button size="sm" color="primary" @click.prevent="onReset">
            Reset
          </x-button>
        </div>
      </div>
    </x-form>

    <DataTable
      v-model:items-selected="quotesSelected"
      table-class-name="tablefixed"
      :headers="tableHeader"
      :loading="loader.table"
      :items="quotes.data || []"
      border-cell
      hide-rows-per-page
      hide-footer
      fixed-checkbox
    >
      <template #item-uuid="{ code, uuid }">
        <Link class="text-primary-500 hover:underline">
          {{ code }}
        </Link>
        <!-- <span v-else>{{ code }}</span> -->
      </template>
    </DataTable>

    <!-- <Pagination
      :links="{
        next: quotes.next_page_url,
        prev: quotes.prev_page_url,
        current: quotes.current_page,
        from: quotes.from,
        to: quotes.to,
      }"
    /> -->
  </div>
</template>
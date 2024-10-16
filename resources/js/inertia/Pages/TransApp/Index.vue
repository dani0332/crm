<script setup>
defineProps({
  transactors: Array,
  handlers: Array,
  insuranceCompanies: Array,
  paymentModes: Array,
  reasons: Array,
  isTransappAdmin: Boolean,
  teams: Array,
  isCarManager: Boolean,
  data: Object,
});

const tableHeader = ref([
  { text: 'Approval Code', value: '' },
  { text: 'Transaction Date', value: '' },
  { text: 'Insurance Company', value: '' },
  { text: 'Premium', value: '' },
  { text: 'Name', value: '' },
  { text: 'Risk Detail', value: '' },
  { text: 'Transactor', value: '' },
  { text: 'Advisor', value: '' },
  { text: 'Payment mode', value: '' },
  { text: 'Previous Approval Code', value: '' },
]);

const filters = reactive({
  transapp_start_date: null,
  transapp_stop_date: null,
  transapp_approval_code: null,
  transapp_customer_email: null,
  transapp_customer_name: null,
  created_at_start: new Date() || '',
  created_at_end: new Date() || '',
  sub_team: '',
  quote_status: [],
  advisors: [],
  is_ecommerce: '',
  is_renewal: '',
  previous_quote_policy_number: '',
  renewal_batch: '',
  date: null,
  assigned_to_date_start: '',
  assigned_to_date_end: '',
  payment_status: [],
  is_cold: false,
  is_stale: false,
  status_filters: null,
  payment_due_date: '',
  booking_date: '',
  policy_expiry_date: '',
  policy_expiry_date_end: '',
  segment_filter: '',
  transaction_approved_dates: '',
});
</script>

<template>
  <div>
    <Head title="Transaction List" />
    <StickyHeader>
      <template v-slot:header>
        <h2 class="text-xl font-semibold">Transaction List</h2>
      </template>
    </StickyHeader>
    <x-divider class="my-4" />
    <x-form @submit="" :auto-focus="false">
      <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-4">
        <x-field label="Start Date">
          <DatePicker
            name="date_of_purchase"
            class="w-full"
            model-type="yyyy-MM-dd"
          />
        </x-field>
        <x-field label="Stop Date">
          <DatePicker
            name="date_of_purchase"
            class="w-full"
            model-type="yyyy-MM-dd"
          />
        </x-field>
        <x-field label="Transactor">
          <x-select
            placeholder="Select Transactor"
            class="w-full"
            filterable
            :options="
              transactors.map(item => ({ label: item.name, value: item.id }))
            "
          />
        </x-field>
        <x-field label="Advisor">
          <x-select
            :options="
              handlers.map(item => ({ label: item.name, value: item.id }))
            "
            filterable
            placeholder="Select Advisor"
            class="w-full"
          />
        </x-field>
        <x-field label="Insurance Company">
          <x-select
            :options="
              insuranceCompanies.map(item => ({
                label: item.name,
                value: item.id,
              }))
            "
            filterable
            placeholder="Select Insurance Company"
            class="w-full"
          />
        </x-field>
        <x-field label="Reason">
          <x-select
            :options="
              reasons.map(item => ({
                label: item.name,
                value: item.id,
              }))
            "
            filterable
            placeholder="Reason"
            class="w-full"
          />
        </x-field>
        <x-field label="Customer Email">
          <x-input placeholder="Customer Email" class="w-full" />
        </x-field>
        <x-field label="Approval Code">
          <x-input placeholder="Approval Code" class="w-full" />
        </x-field>
        <x-field label="Payment mode">
          <x-select
            :options="
              paymentModes.map(item => ({
                label: item.name,
                value: item.id,
              }))
            "
            filterable
            placeholder="Payment mode"
            class="w-full"
          />
        </x-field>
        <!-- <x-field label="Quote type">
          <x-select
            v-model="filters.quote_type"
            placeholder="Select Quote Type"
            :options="quoteTypesOptions"
            class="w-full"
          />
        </x-field>
        <x-field label="UUID">
          <x-input
            v-model="filters.uuid"
            type="search"
            name="first_name"
            class="w-full"
            placeholder="Type here"
          />
        </x-field>
        <x-field label="Is Synced?">
          <x-select
            v-model="filters.is_synced"
            placeholder="Select Is Synced?"
            :options="isSyncedOptions"
            class="w-full"
          />
        </x-field>
        <x-field label="Status">
          <x-select
            v-model="filters.status"
            placeholder="Select Status"
            :options="quoteSyncStatusOptions"
            class="w-full"
          />
        </x-field>
        <x-field label="Synced At">
          <DatePicker
            v-model="filters.synced_at"
            name="date_of_purchase"
            class="w-full"
            model-type="yyyy-MM-dd"
            range
            max-range="7"
          />
        </x-field>
        <x-field label="Created At">
          <DatePicker
            v-model="filters.created_at"
            name="date_of_purchase"
            class="w-full"
            model-type="yyyy-MM-dd"
            range
            max-range="7"
          />
        </x-field>
        <x-field label="Distinct">
          <x-select
            v-model="filters.distinct"
            placeholder="Select Distinct"
            :options="distinctOptions"
            class="w-full"
          />
        </x-field> -->
      </div>
      <div class="flex justify-end">
        <div class="flex justify-self-end gap-3">
          <x-button size="sm" color="#ff5e00" type="submit">Search</x-button>
          <x-button size="sm" color="primary" @click.prevent="">Reset</x-button>
        </div>
      </div>
    </x-form>

    <x-divider class="my-4" />

    <DataTable
      table-class-name="compact text-wrap"
      :headers="tableHeader"
      :items="data.data || []"
      border-cell
      hide-rows-per-page
      hide-footer
    >
    </DataTable>

    <!-- <Pagination
      :links="{
        next: logs.next_page_url,
        prev: logs.prev_page_url,
        current: logs.current_page,
        from: logs.from,
        to: logs.to,
      }"
    /> -->
  </div>
</template>

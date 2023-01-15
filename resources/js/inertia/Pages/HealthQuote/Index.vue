<script setup>
import { reactive, computed } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import TheTable from '../../Components/TheTable.vue';

defineProps({
  quotes: Array,
  currentPage: Number,
  hasMore: Boolean,
  nextPageUrl: String,
  prevPageUrl: String,
  leadStatuses: Array,
  advisors: Array,
});

const table = reactive({
  isLoading: false,
  columns: [
    {
      label: 'CDB ID',
      field: 'code',
      sortable: false,
      isKey: true,
    },
    {
      label: 'First Name',
      field: 'first_name',
      width: '10%',
    },
    {
      label: 'Last Name',
      field: 'last_name',
      width: '10%',
    },
    {
      label: 'Lead Status',
      field: 'quote_status_id_text',
      width: '10%',
    },
    {
      label: 'Advisor',
      field: 'advisor_id_text',
      width: '10%',
    },
    {
      label: 'WC Advisor',
      field: 'wcu_id_text',
      width: '10%',
    },
    {
      label: 'Created Date',
      field: 'created_at',
      width: '10%',
    },
    {
      label: 'Last Modified Date',
      field: 'updated_at',
      sortable: false,
    },
    {
      label: 'Health Team Type',
      field: 'health_team_type',
      width: '10%',
    },
    {
      label: 'Transapp Code',
      field: 'transapp_code',
      width: '10%',
    },
    {
      label: 'Lost Reason',
      field: 'lost_reason',
      width: '10%',
    },
    {
      label: 'Premium',
      field: 'premium',
      width: '10%',
    },
    {
      label: 'Policy Number',
      field: 'policy_number',
      width: '10%',
    },
    {
      label: 'Source',
      field: 'source',
      width: '10%',
    },
    {
      label: 'Lead Type',
      field: 'lead_type_id_text',
      width: '10%',
    },
    {
      label: 'Salary Band',
      field: 'salary_band_id_text',
      width: '10%',
    },
    {
      label: 'Member Category',
      field: 'member_category_id_text',
      width: '10%',
    },
    {
      label: 'Currently Insured With',
      field: 'currently_insured_with_id_text',
      width: '10%',
    },
    {
      label: 'Is Ecommerce',
      field: 'is_ecommerce',
      width: '10%',
    },
    {
      label: '',
      field: 'actions',
      width: '2%',
    },
  ],
});

const filters = reactive({
  code: '',
  first_name: '',
  last_name: '',
  email: '',
  mobile_no: '',
  created_at_start: '',
  created_at_end: '',
  sub_team: '',
  quote_status: [],
  advisors: [],
  is_ecommerce: '',
  is_renewal: '',
  page: 1,
});

const subTeamOptions = [
  { value: '', label: 'All' },
  { value: 'RM-NB', label: 'RM-NB' },
  { value: 'RM-Speed', label: 'RM-Speed' },
  { value: 'EBP', label: 'EBP' },
  { value: 'Wow-Call', label: 'Wow-Call' },
  { value: 'No-Type', label: 'No-Type' },
];

const user = computed(() => usePage().props.auth.user);

const leadStatusOptions = computed(() => {
  return usePage().props.leadStatuses.map(status => ({
    value: status.id,
    label: status.text,
  }));
});

const advisorOptions = computed(() => {
  return usePage().props.advisors.map(advisor => ({
    value: advisor.id,
    label: advisor.name,
  }));
});

function onSubmit(isValid) {
  if (isValid) {
    filters.page = 1;
    Object.keys(filters).forEach(
      key =>
        (filters[key] === '' || filters[key].length === 0) &&
        delete filters[key],
    );
    router.visit('/quotes/health', {
      method: 'get',
      data: filters,
      replace: true,
      preserveState: true,
      onBefore: () => (table.isLoading = true),
      onSuccess: () => (table.isLoading = false),
    });
  } else {
    console.log('Invalid');
  }
}

function onReset() {
  router.visit('/quotes/health', {
    method: 'get',
    replace: true,
    onBefore: () => (table.isLoading = true),
    onSuccess: () => (table.isLoading = false),
  });
}

const onPaginate = isNext => {
  const pageUrl = isNext
    ? usePage().props.nextPageUrl
    : usePage().props.prevPageUrl;

  router.visit(pageUrl, {
    method: 'get',
    replace: true,
    onBefore: () => (table.isLoading = true),
    onSuccess: () => (table.isLoading = false),
  });
};
</script>

<template>
  <div>
    <Head title="Health Quotes" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">Health Quotes List</h2>
      <x-button size="sm" color="#ff5e00">Create Lead</x-button>
    </div>
    <x-divider class="my-4" />
    <x-form @submit="onSubmit" :auto-focus="false">
      <div class="grid grid-cols-4 gap-4">
        <x-input
          v-model="filters.code"
          type="search"
          name="code"
          label="CDB ID"
          class="w-full"
          placeholder="Search by CDB ID"
        />
        <x-input
          v-model="filters.first_name"
          type="search"
          name="first_name"
          label="First Name"
          class="w-full"
          placeholder="Search by First Name"
        />
        <x-input
          v-model="filters.last_name"
          type="search"
          name="last_name"
          label="Last Name"
          class="w-full"
          placeholder="Search by Last Name"
        />
        <x-input
          v-model="filters.email"
          type="search"
          name="email"
          label="Email"
          class="w-full"
          placeholder="Search by Email"
        />
        <x-input
          v-model="filters.mobile_no"
          type="search"
          name="mobile_no"
          label="Mobile No"
          class="w-full"
          placeholder="Search by Mobile No"
        />
        <x-input
          v-model="filters.created_at_start"
          type="date"
          name="created_at_start"
          label="Created Date"
          class="w-full"
        />
        <x-input
          v-model="filters.created_at_end"
          type="date"
          name="created_at_end"
          label="Created Date End"
          class="w-full"
        />
        <x-select
          v-model="filters.sub_team"
          label="Sub Team"
          :options="subTeamOptions"
          placeholder="Search by Sub Team"
          class="w-full"
        />
        <x-select
          v-model="filters.quote_status"
          label="Lead Status"
          placeholder="Search by Lead Status"
          :options="leadStatusOptions"
          multiple
          class="w-full"
        />
        <x-select
          v-model="filters.advisors"
          label="Advisor"
          placeholder="Search by Advisor"
          :options="advisorOptions"
          multiple
          class="w-full"
        />
        <x-select
          v-model="filters.is_ecommerce"
          label="Is Ecommerce"
          placeholder="Search by Ecommerce"
          :options="[
            { value: '', label: 'All' },
            { value: 'Yes', label: 'Yes' },
            { value: 'No', label: 'No' },
          ]"
          class="w-full"
        />
        <x-select
          v-model="filters.is_renewal"
          label="Is Renewal"
          placeholder="Search by Renewal"
          :options="[
            { value: '', label: 'All' },
            { value: 'Yes', label: 'Yes' },
            { value: 'No', label: 'No' },
          ]"
          class="w-full"
        />
      </div>
      <div class="flex justify-end gap-3 mb-4">
        <x-button size="sm" color="#ff5e00" type="submit">Search</x-button>
        <x-button size="sm" color="primary" @click.prevent="onReset">
          Reset
        </x-button>
      </div>
    </x-form>
    <TheTable
      :is-slot-mode="true"
      :is-loading="table.isLoading"
      :columns="table.columns"
      :rows="quotes || []"
      :has-more="hasMore"
      :total="quotes.length || 0"
      :page="currentPage"
      :noDataText="'No Quotes Found'"
      @do-search="onPaginate"
      @is-finished="table.isLoading = false"
    >
      <template v-slot:code="data">
        <a
          :href="`/quotes/health/${data.value.uuid}`"
          target="_blank"
          class="text-primary-500 hover:underline"
        >
          {{ data.value.code }}
        </a>
      </template>
    </TheTable>
  </div>
</template>

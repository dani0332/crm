<script setup>
import { reactive, ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import MainLayout from '../../Layouts/MainLayout.vue';
import TheTable from '../../Components/TheTable.vue';

defineProps({
  quotes: Array,
  currentPage: Number,
  hasMore: Boolean,
  nextPageUrl: String,
  prevPageUrl: String,
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
});
const selected = ref(''),
  selectedMultiple = ref([]);
const dummy = [
  { value: 'A', label: 'Option A' },
  { value: 'B', label: 'Option B' },
  { value: 'C', label: 'Option C' },
  { value: 'D', label: 'Option D' },
  { value: 'E', label: 'Option E' },
  { value: 'F', label: 'Option F' },
];
const rules = {
  isEmail: v =>
    /^\w+([.-]?\w+)*@\w+([.-]?\w+)*(\.\w{2,3})+$/.test(v) ||
    'E-mail must be valid',
};

function onSubmit(isValid) {
  if (isValid) console.log('Valid! Sumitted.');
  else console.log('Invalid! Form has errors');
}
</script>

<template>
  <MainLayout>
    <Head title="Health Quotes" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">Health Quotes List</h2>
      <x-button color="#ff5e00">Create Lead</x-button>
    </div>
    <x-divider class="my-4" />
    <x-form @submit="onSubmit">
      <div class="grid grid-cols-4 gap-4">
        <x-input
          v-model="filters.code"
          name="code"
          label="CDB ID"
          class="w-full"
          placeholder="Search by CDB ID"
        />
        <x-input
          v-model="filters.email"
          :rules="[rules.isEmail]"
          name="email"
          label="Email"
          class="w-full"
          placeholder="Search by Email"
        />
        <x-select
          v-model="selected"
          label="Simple select"
          :options="dummy"
          placeholder="Placeholder"
          class="w-full"
        />
        <x-select
          v-model="selectedMultiple"
          label="Multi select"
          placeholder="Placeholder"
          :options="dummy"
          multiple
          class="w-full"
        />
      </div>
    </x-form>
    <TheTable
      :is-slot-mode="true"
      :is-loading="table.isLoading"
      :columns="table.columns"
      :rows="quotes || []"
      :has-more="hasMore"
      :next="nextPageUrl"
      :prev="prevPageUrl"
      :total="quotes.length || 0"
      :page="currentPage"
      :noDataText="'No Quotes Found'"
      @is-finished="table.isLoading = false"
    />
  </MainLayout>
</template>

<script setup>
import { reactive, computed, onMounted, ref } from 'vue';
import { Head, router, usePage, Link } from '@inertiajs/vue3';
import Pagination from '@/inertia/Components/Pagination.vue';
import ExportExcel from '@/inertia/Components/ExportExcel.vue';
import ComboBox from '@/inertia/Components/ComboBox.vue';

defineProps({
    'quotes': Object,
    'dropdownSource': Object,
});

const page = usePage();

const filters = reactive({
  code: '',
  first_name: '',
  last_name: '',
  email: '',
  mobile_no: '',
  created_at_start: '',
  created_at_end: '',
  quote_status: [],
  advisors: [],
  is_ecommerce: '',
  payment_status: '',
  page: 1,
});

const tableHeader = []

const paymentStatusOptions = computed(() => {
    return page.props.dropdownSource.payment_status.map((item) => {
        return {
            value: item.id,
            label: item.text
        }
    });
});

const advisorsOptions = computed(() => {
    return page.props.dropdownSource.advisors.map((item) => {
        return {
            value: item.id,
            label: item.name
        }
    });
});

const leadsStatusOptions = computed(() => {
    return page.props.dropdownSource.leads.map((item) => {
        return {
            value: item.id,
            label: item.text
        }
    });
});

onMounted(() => {
    console.log(page.props.dropdownSource);
});
</script>

<template>
  <div>
    <Head title="Travel List" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">Lead List</h2>
      <div class="space-x-3">
        <Link href="/quotes/health-cards">
          <x-button size="sm" color="#1d83bc" tag="div"> Cards View </x-button>
        </Link>

        <Link href="/quotes/travel/create">
          <x-button size="sm" color="#ff5e00" tag="div"> Create Lead </x-button>
        </Link>
      </div>
    </div>
    <x-divider class="my-4" />
    <x-form @submit="onSubmit" :auto-focus="false">
      <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-4">
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
          label="Mobile Number"
          class="w-full"
          placeholder="Search by Mobile Number"
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

        <ComboBox
          v-model="filters.quote_status"
          label="Lead Status"
          name="quote_status"
          placeholder="Search by Lead Status"
          :options="leadsStatusOptions"
        />
        <ComboBox
          v-model="filters.advisors"
          label="Advisor"
          placeholder="Search by Advisor"
          :options="advisorsOptions"
        />
        <x-select
          v-model="filters.is_ecommerce"
          label="Ecommerce"
          placeholder="Search by Ecommerce"
          :options="[
            { value: 'Yes', label: 'Yes' },
            { value: 'No', label: 'No' },
          ]"
          class="w-full"
        />
        <x-select
          name="payment_status_id"
          v-model="filters.payment_status"
          label="PAYMENT STATUS"
          placeholder="Search by Payment Status"
          :options="paymentStatusOptions"
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
  </div>
</template>

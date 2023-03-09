<script setup>
import { reactive, computed, onMounted, ref } from "vue";
import { Head, router, usePage, Link, useForm } from "@inertiajs/vue3";
import { useNotifications } from "@indielayer/ui";

defineProps({
  model: Object,
  leadStatuses: Array,
  advisors: Array,
  isManagerORDeputy: Boolean,
  quotes: Object,
});

const page = usePage();

const loader = reactive({
  table: false,
  export: false,
});

const filters = reactive({
  code: "",
  first_name: "",
  last_name: "",
  email: "",
  mobile_no: "",
  created_at_start: "",
  created_at_end: "",
  leadStatus: "",
  advisor_id: '',
  page: 1,
});

const leadStatusOptions = computed(() => {
  return page.props.leadStatuses.map((status) => ({
    value: status.id,
    label: status.text,
  }));
});

const advisorOptions = computed(() => {
  return page.props.advisors.map((advisor) => ({
    value: advisor.id,
    label: advisor.name,
  }));
});
const tableHeader = [
  { text: "CDB ID", value: "code" },
  { text: "FIRST NAME", value: "first_name" },
  { text: "LAST NAME", value: "last_name" },
  { text: "LEAD STATUS", value: "leadStatus" },
  { text: "ADVISOR", value: "advisor_id_text" },
  { text: "PREMIUM", value: "premium" },
  { text: "Company Name", value: "company_name" },
  { text: "POLICY NUMBER", value: "policy_number" },
  { text: "LOST REASON", value: "lost_reason" },
  { text: "SOURCE", value: "source" },
  { text: "CREATED DATE", value: "created_at" },
  { text: "Updated Date", value: "updated_at" },
];

function resetFilters() {
  for (const key in filters) {
    filters[key] = "";
  }
  filterQuotes(true);
}

function filterQuotes(isValid) {
  if (!isValid) {
    return;
  }
  for (const key in filters) {
    if (filters[key] === "") {
      delete filters[key];
    }
  }
  router.visit("/medical/amt", {
    method: "get",
    data: {
      ...filters,
    },
    preserveState: true,
    preserveScroll: true,
    onFinish: () => {
      loader.table = false;
    },
    onBefore: () => {
      filters.page = 1;
      loader.table = true;
    },
  });
}

function setQueryFilters() {
  let query = router.page.url.split("?")[1];
  if (query) {
    query = query.split("&");
    query.forEach((item) => {
      const [key, value] = item.split("=");

      if (key === "advisor_id") {
        let id = key.slice(0, -2);
        if (filters[id]) {
          filters[id].push(parseInt(value));
        }
      } else {
        filters[key] = value;
      }
    });
  }
}

onMounted(() => {
  setQueryFilters();
  console.log(page.props.quotes.data);
});
</script>

<template>
  <div>
    <Head title="AMT List" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">Lead List</h2>
      <div class="space-x-3">
        <Link href="/quotes/health/create">
          <x-button size="sm" color="#ff5e00" tag="div"> Create Lead </x-button>
        </Link>
      </div>
    </div>
    <x-divider class="my-4" />
    <x-form @submit="filterQuotes" :auto-focus="false">
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
        <DatePicker
          v-model="filters.created_at_start"
          name="created_at_start"
          label="Created Date Start"
        />
        <DatePicker
          v-model="filters.created_at_end"
          name="created_at_end"
          label="Created Date End"
        />

        <x-select
          v-model="filters.leadStatus"
          label="Lead Status"
          name="leadStatus"
          placeholder="Search by Lead Status"
          :options="leadStatusOptions"
        />

        <x-select
          v-model="filters.advisor_id"
          label="Advisor"
          placeholder="Search by Advisor"
          :options="advisorOptions"
        />
      </div>
      <div class="flex justify-end gap-3 mb-4">
        <x-button size="sm" color="#ff5e00" type="submit">Search</x-button>
        <x-button size="sm" color="primary" @click.prevent="resetFilters">
          Reset
        </x-button>
      </div>
    </x-form>

    <DataTable
      table-class-name="tablefixed"
      :loading="loader.table"
      :headers="tableHeader"
      :items="quotes.data || []"
      border-cell
      hide-rows-per-page
      hide-footer
      fixed-checkbox
    >
      <template #item-code="{ code, uuid }">
        <Link :href="`/medical/amt/${uuid}`" class="text-primary-500 hover:underline">
          {{ code }}
        </Link>
      </template>

      <template #item-source="{ source }">
        <a
          :href="source && source.includes('http') ? source : '#'"
          :target="source && source.includes('http') ? '_blank' : '_self'"
          class="text-primary-500 hover:underline"
        >
          {{ source }}
        </a>
      </template>
    </DataTable>

    <Pagination
      :links="{
        next: quotes.next_page_url,
        prev: quotes.prev_page_url,
        current: quotes.current_page,
        from: quotes.from,
        to: quotes.to,
      }"
    />
  </div>
</template>

<script setup>
import LeadAssignment from '../PersonalQuote/Partials/LeadAssignment';
import * as dayjs from 'dayjs';

defineProps({
  aml: Object,
  quoteTypes: Array,
});

const page = usePage();
const notification = useToast();
const loader = reactive({
  table: false,
  export: false,
});

const { isRequired } = useRules();

const tableHeader = [
    { text: 'AML Id', value: 'id' },
    { text: 'Quote Type', value: 'quote_type_text' },
    { text: 'Ref-ID', value: 'cdb_id' },
    { text: 'Input', value: 'input' },
    { text: 'Screenshot', value: 'screenshot' },
    { text: 'Created At', value: 'created_at' },
    { text: 'Updated At', value: 'updated_at' },
];

const dateFormat = date => useDateFormat(date, 'DD-MM-YYYY h:mm:ss');

let availableFilters = {
  quoteType: null,
  searchType: '',
  searchField: '',
  matchFound: '',
  amlCreatedStartDate: '',
  amlCreatedEndDate: '',
  page: 1,
};

const filtersForm = useForm({
  quoteType: null,
  searchType: '',
  searchField: '',
  matchFound: '',
  amlCreatedStartDate: '',
  amlCreatedEndDate: '',
  page: 1,
});

function onReset() {
  router.visit('/kyc/aml', {
    method: 'get',
    data: { page: 1 },
    preserveScroll: true,
    onBefore: () => (loader.table = true),
    onSuccess: () => (loader.table = false),
  });
}

function onSubmit(isValid) {
    if (!isValid) return;

    //remove empty fields
    Object.keys(filtersForm).forEach(
      key => filtersForm[key] === '' && delete filtersForm[key],
    );
    filtersForm.get(`/kyc/aml`, {
      preserveScroll: true,
      onBefore: () => {
        if (dayjs(filtersForm.amlCreatedEndDate).diff(dayjs(filtersForm.amlCreatedStartDate), 'day') > 30) {
          filtersForm.setError(
            'amlCreatedStartDate',
            'Allowed no. of days between start & end dates are 30 days.',
          );
          return false;
        }
        loader.table = true;
      },
      onSuccess: () => (loader.table = false),
        onError: (errors) => {
            Object.keys(errors).forEach(function(key) {
                notification.error({
                    title: errors[key],
                    position: 'top',
                });
            });
            return false;
        }
    });
}

function setQueryStringFilters() {
  let queryString = window.location.search;
  let urlParams = new URLSearchParams(queryString);

  for (const [key] of Object.entries(availableFilters)) {
    if (urlParams.has(key)) {
      filtersForm[key] = urlParams.get(key);
    }
  }
}

const quoteTypeOptions = computed(() =>
  ref(
    [{ value: '', label: 'Select' }].concat(
      page.props.quoteTypes.map(item => ({
        value: item.code,
        label: item.text,
      })),
    ),
  ),
);
onMounted(() => {
  setQueryStringFilters();
});

</script>

<template>
  <div>
    <Head title="AMl" />
    <h2 class="text-xl font-semibold">AML List</h2>
    <x-divider class="my-4" />
    <!--   filters     -->
    <x-form @submit="onSubmit" :auto-focus="false">
      <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-4">
        <x-select
          v-model="filtersForm.quoteType"
          :rules="[isRequired]"
          label="Quote Type"
          placeholder=""
          :options="quoteTypeOptions.value"
          class="w-full"
        />

        <x-select
          v-model="filtersForm.searchType"
          label="Search By"
          placeholder=""
          :options="[
            { value: 'cdbId', label: 'Ref-ID' },
            { value: 'customerEmail', label: 'Customer Email' },
            { value: 'id', label: 'AML ID' },
          ]"
          class="w-full"
        />
        <x-input
          v-model="filtersForm.searchField"
          type="Search Value"
          name="code"
          label="Search Value"
          class="w-full"
          placeholder="Search Value"
        />

        <x-select
          v-model="filtersForm.matchFound"
          label="Match found"
          placeholder=""
          :options="[
            { value: 0, label: 'False' },
            { value: 1, label: 'True' },
          ]"
          class="w-full"
        />

        <DatePicker
          v-model="filtersForm.amlCreatedStartDate"
          name="created_at_end"
          label="Created Date Start"
          class="w-full"
          :rules="[isRequired]"
          :customError="filtersForm.errors.amlCreatedStartDate"
        />
        <DatePicker
          v-model="filtersForm.amlCreatedEndDate"
          name="created_at_end"
          label="Created Date End"
          class="w-full"
          :rules="[isRequired]"
          :customError="filtersForm.errors.amlCreatedEndDate"
        />
      </div>
      <div class="flex justify-end gap-3 mb-4">
        <x-button size="sm" color="#ff5e00" type="submit">Search</x-button>
        <x-button size="sm" color="primary" @click.prevent="onReset">
          Reset
        </x-button>
      </div>
    </x-form>

    <DataTable
      table-class-name="tablefixed"
      :headers="tableHeader"
      :loading="loader.table"
      :items="aml.data || []"
      border-cell
      hide-rows-per-page
      hide-footer
      fixed-checkbox
    >
      <template #item-id="{ code, id }">
        <Link :href="`/kyc/aml/${id}`" class="text-primary-500 hover:underline">
          {{ id }}
        </Link>
      </template>
      <template #item-cdb_id="item">
        <Link
          :href="`/kyc/aml/${item.quote_type_id}/details/${item.quote_request_id}`"
          class="text-primary-500 hover:underline"
        >
          {{ item.cdb_id }}
        </Link>
      </template>
      <template #item-screenshot="{ screenshot }">
        <a :href="screenshot" download>
          <img :src="screenshot" alt="IMCRM" class="w-10 h-10" />
        </a>
      </template>
    </DataTable>

    <Pagination
      :links="{
        next: aml.next_page_url,
        prev: aml.prev_page_url,
        current: aml.current_page,
        from: aml.from,
        to: aml.to,
      }"
    />
  </div>
</template>

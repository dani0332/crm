<script setup>
import { useGetShowPageRoute } from '../../Composables/utilities';
import dayjs from 'dayjs/esm/index.js';
import NProgress from 'nprogress';

const { isRequired } = useRules();

const props = defineProps({
  quotes: Object,
  quoteTypes: Array,
});

const page = usePage();
const notification = useToast();
const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;

const dateFormat = date => useDateFormat(date, 'DD-MM-YYYY h:mm:ss');

const quotesSelected = ref([]);
const isQuoteTypeEmpty = ref(false);

const loader = reactive({
  table: false,
  export: false,
});

const tableHeader = [
  { text: 'Ref-ID', value: 'code', is_active: true },
  { text: 'FIRST NAME', value: 'first_name', is_active: true },
  { text: 'LAST NAME', value: 'last_name', is_active: true },
  { text: 'LEAD STATUS', value: 'quote_status', is_active: true },
  { text: 'ADVISOR', value: 'advisor', is_active: true },
  { text: 'POLICY NUMBER', value: 'policy_number', is_active: true },
  { text: 'CREATED DATE', value: 'created_at', is_active: true },
  { text: 'LAST MODIFIED DATE', value: 'updated_at', is_active: true },
  { text: 'SOURCE', value: 'source', is_active: true },
  { text: 'PRICE', value: 'premium', is_active: true },
];

let availableFilters = {
  quoteType: '',
  created_at_start: '',
  created_at_end: '',
  page: 1,
};

const filtersForm = useForm({
  quoteType: null,
  created_at_start: '',
  created_at_end: '',
  page: 1,
});

function fetchQuotes() {
  isQuoteTypeEmpty.value = !filtersForm.quoteType;
  if (!filtersForm.quoteType) return;

  //remove empty fields
  Object.keys(filtersForm).forEach(
    key => filtersForm[key] === '' && delete filtersForm[key],
  );
  filtersForm.get(route('bulk-policy-booking.index'), {
    preserveScroll: true,
    onBefore: () => {
      loader.table = true;
    },
    onSuccess: () => (loader.table = false),
    onError: errors => {
      Object.keys(errors).forEach(function (key) {
        notification.error({
          title: errors[key],
          position: 'top',
        });
      });
      return false;
    },
  });
}

function onReset() {
  router.visit(route('bulk-policy-booking.index'), {
    method: 'get',
    preserveScroll: true,
    onBefore: () => (loader.table = true),
    onSuccess: () => (loader.table = false),
  });
}

const bookPoliciesOnSage = async () => {
  if (quotesSelected.value.length === 0) {
    notification.error({
      title: 'Please select at least one quote to book.',
      position: 'top',
    });
    return;
  }
  const quoteIDs = quotesSelected.value.map(e => e.id);

  try {
    NProgress.start();
    const response = await axios.post(
      route('send-policies-for-bulk-sage-booking'),
      {
        selectedQuoteIds: quoteIDs,
        model_type: filtersForm.quoteType,
      },
    );

    NProgress.done();

    if (response.data.success) {
      notification.success({
        title:
          'Quotes are submitted for Booking! Status will be updated once booked!.',
        position: 'top',
      });
      loader.table = false;
      quotesSelected.value = [];
    } else {
      let message = response.data?.message;
      if (message) {
        notification.error({
          title: message,
          position: 'top',
        });
      } else {
        notification.error({
          title: 'Failed to Book Policies',
          position: 'top',
        });
      }
    }
  } catch (error) {
    if (error.response && error.response.status === 422) {
      const errors = error.response.data.errors;
      Object.keys(errors).forEach(field => {
        errors[field].forEach(errorMsg => {
          notification.error({
            title: errorMsg,
            position: 'top',
          });
        });
      });
    }
  }
};

function setQueryStringFilters() {
  let queryString = window.location.search;
  let urlParams = new URLSearchParams(queryString);

  for (const [key] of Object.entries(availableFilters)) {
    if (urlParams.has(key)) {
      filtersForm[key] = urlParams.get(key);
    }
  }
}

function getQuoteDetailPageURL(uuid, business_type_of_insurance_id) {
  let quoteTypeID = props.quoteTypes.find(
    quoteType => quoteType.code === filtersForm.quoteType,
  )?.id;
  return useGetShowPageRoute(
    uuid,
    quoteTypeID,
    business_type_of_insurance_id ?? null,
  );
}

const quoteTypeOptions = computed(() =>
  ref(
    page.props.quoteTypes.map(item => ({
      value: item.code,
      label: item.text,
    })),
  ),
);

const rules = {
  created_at_start: v => {
    if (v) {
      const date = new Date(v);
      let isDate = date instanceof Date;
      return isDate || 'Date format is incorrect';
    }
    return !!v || 'This field is required';
  },
  created_at_end: v => {
    if (v) {
      const endDate = new Date(filtersForm.created_at_end);
      const startDate = new Date(filtersForm.created_at_start);
      if (startDate >= endDate) {
        return 'End date should be greater than Start Date';
      } else {
        let startDateDay = dayjs(filtersForm.created_at_start);
        let endDateDay = dayjs(filtersForm.created_at_end);
        let isDiffMoreThan30Days = endDateDay.diff(startDateDay, 'day') > 31;
        if (isDiffMoreThan30Days) {
          return (
            !isDiffMoreThan30Days ||
            'Allowed no. of days between start & end dates are 31 days.'
          );
        }
      }
      let isDate = endDate instanceof Date;
      return isDate || 'Date format is incorrect';
    }
    return !!v || 'This field is required';
  },
};

watch(
  () => filtersForm,
  () => {
    let queryString = window.location.search;
    let urlParams = new URLSearchParams(queryString);
  },
  { deep: true, immediate: true },
);

onMounted(() => {
  setQueryStringFilters();
});
</script>

<template>
  <div>
    <Head title="Bulk Policy Booking" />
    <h2 class="text-xl font-semibold">Policy Booking List</h2>
    <x-divider class="my-4" />
    <!--   filters     -->
    <x-form @submit="fetchQuotes" :auto-focus="false">
      <div class="grid sm:grid-cols-2 md:grid-cols-3 gap-3">
        <ComboBox
          v-model="filtersForm.quoteType"
          label="Quote Type"
          placeholder="Search by Quote Type"
          :options="quoteTypeOptions.value"
          :single="true"
          :hasError="isQuoteTypeEmpty"
          :rules="[isRequired]"
        />
        <DatePicker
          v-model="filtersForm.created_at_start"
          name="created_at_start"
          label="Created Date Start"
          class="w-full"
          :rules="[rules.created_at_start]"
        />
        <DatePicker
          v-model="filtersForm.created_at_end"
          name="created_at_end"
          label="Created Date End"
          class="w-full"
          :rules="[rules.created_at_end]"
        />
      </div>
      <div class="flex justify-between gap-3 mb-4 mt-1">
        <x-button
          v-if="can(permissionsEnum.BOOK_BULK_POLICY_ON_SAGE)"
          size="sm"
          color="emerald"
          :href="route('home')"
          class="justify-self-start"
          :disabled="quotesSelected.length === 0"
          @click.prevent="bookPoliciesOnSage"
        >
          Book Policy
        </x-button>
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
      :items="quotes?.data || []"
      border-cell
      hide-rows-per-page
      hide-footer
    >
      <template #item-code="{ code, uuid, business_type_of_insurance_id }">
        <Link
          :href="getQuoteDetailPageURL(uuid, business_type_of_insurance_id)"
          target="_blank"
          class="text-primary-500 hover:underline"
        >
          <span>{{ code }}</span>
        </Link>
      </template>
      <template #item-advisor="{ advisor }">
        {{ advisor?.name }}
      </template>
      <template #item-quote_status="{ quote_status }">
        {{ quote_status?.code }}
      </template>
      <template #item-nationality="{ nationality }">
        {{ nationality?.code }}
      </template>
      <template #item-lost_reason="{ life_quote_request_detail }">
        {{ life_quote_request_detail?.lost_reason?.text }}
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

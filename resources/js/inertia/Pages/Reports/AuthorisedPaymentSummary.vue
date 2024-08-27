<script setup>
const props = defineProps({
  reportData: Object,
  defaultFilters: Object,
});

const loaders = reactive({
  table: false,
});

// const { isRequired } = useRules();

const filters = reactive({
  expireDate: '',
  todayDate: [],
  tomorrowDate: '',
  thisWeek: [],
  customDate: [],
  teams: [],
  page: 1,
});

const teams = computed(() => {
  return Object.keys(props.defaultFilters.teams).map(key => ({
    value: key,
    label: props.defaultFilters.teams[key],
  }));
});

const onSubmit = isValid => {
  if (!isValid) return;
  filters.page = 1;
  router.visit(route('authorized-payment-summary'), {
    method: 'get',
    data: useGenerateQueryString(filters),
    preserveState: true,
    preserveScroll: true,
    onBefore: () => (loaders.table = true),
    onFinish: () => (loaders.table = false),
  });
};

function onReset() {
  router.visit(route('authorized-payment-summary'), {
    method: 'get',
    data: { page: 1 },
    preserveScroll: true,
    onBefore: () => (loaders.table = true),
    onSuccess: () => (loaders.table = false),
  });
}

const tableHeader = reactive([
  {
    text: 'TOTAL AUTHORISED PAYMENT',
    value: 'total_leads',
  },
  {
    text: 'ADVISOR',
    value: 'advisor_name',
  },
  {
    text: 'TOTAL PREMIUM',
    value: 'total_premium',
  },
]);
const isCustomDate = ref(false);

function formatNumber(number) {
  if (number === 0 || number === '' || number === null) {
    return '0.00';
  } else {
      return new Intl.NumberFormat('en-US', {
          minimumFractionDigits: 2,
          maximumFractionDigits: 2,
      }).format(parseFloat(number));
  }
}
const activeButton = ref('');
function setExpireDate() {
  isCustomDate.value = false;
  activeButton.value = 'expire';
  const currentDate = new Date();
  const expireDate = new Date(currentDate.getTime() - 7 * 24 * 60 * 60 * 1000);
  const formattedExpireDate = expireDate.toISOString();
  filters.expireDate = formattedExpireDate;
  filters.todayDate = [];
  filters.tomorrowDate = '';
  filters.thisWeek = [];
  filters.customDate = [];
}
function setTodayDate() {
  isCustomDate.value = false;
  activeButton.value = 'today';
  const currentDate = new Date();
  const startOfDay = new Date(
    currentDate.getFullYear(),
    currentDate.getMonth(),
    currentDate.getDate(),
    0,
    0,
    0,
  ); // Set time to 00:00:00
  const endOfDay = new Date(
    currentDate.getFullYear(),
    currentDate.getMonth(),
    currentDate.getDate(),
    23,
    59,
    59,
  ); // Set time to 23:59:59
  const formattedStartOfDay = startOfDay.toISOString();
  const formattedEndOfDay = endOfDay.toISOString();
  filters.todayDate = [formattedStartOfDay, formattedEndOfDay];
  filters.tomorrowDate = '';
  filters.expireDate = '';
  filters.thisWeek = [];
  filters.customDate = [];
}
function setTomorrowDate() {
  isCustomDate.value = false;
  activeButton.value = 'tomorrow';
  const currentDate = new Date();
  const tomorrowDate = new Date(currentDate.getTime() + 24 * 60 * 60 * 1000); // Add 1 day
  const formattedTomorrowDate = tomorrowDate.toISOString();
  filters.tomorrowDate = formattedTomorrowDate;
  filters.todayDate = [];
  filters.expireDate = '';
  filters.thisWeek = [];
  filters.customDate = [];
}
function setThisWeek() {
  isCustomDate.value = false;
  activeButton.value = 'thisWeek';
  const currentDate = new Date();
  const currentDay = currentDate.getDay();
  const startOfWeekDate = new Date(currentDate);
  startOfWeekDate.setDate(currentDate.getDate() - currentDay);
  const endOfWeekDate = new Date(currentDate);
  endOfWeekDate.setDate(currentDate.getDate() + (6 - currentDay));
  const formattedStartWeek = startOfWeekDate.toISOString();
  const formattedEndWeek = endOfWeekDate.toISOString();

  filters.thisWeek = [formattedStartWeek, formattedEndWeek];
  filters.tomorrowDate = '';
  filters.todayDate = [];
  filters.expireDate = '';
  filters.customDate = [];
}
function showCustomDate() {
  activeButton.value = 'customDate';
  isCustomDate.value = true;
  filters.tomorrowDate = '';
  filters.todayDate = [];
  filters.expireDate = '';
  filters.thisWeek = [];
}
</script>
<template>
  <Head title="Authorised Payment Report" />
  <h1 class="text-2xl font-bold text-center text-primary-500 mb-4">
    Authorised Payment Summary
  </h1>
  <x-divider class="my-4" />
  <x-form @submit="onSubmit" :auto-focus="false">
    <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-4">
      <x-field label="Teams">
        <ComboBox
          v-model="filters.teams"
          placeholder="Search By Teams"
          :options="teams"
          :max-limit="3"
          deselect-all
        />
      </x-field>
    </div>
    <div class="flex gap-3 pt-3">
      <x-button
        size="sm"
        :color="activeButton === 'expire' ? 'black' : 'primary'"
        @click="setExpireDate"
        type="submit"
        >Expired</x-button
      >
      <x-button
        size="sm"
        :color="activeButton === 'today' ? 'black' : 'primary'"
        @click="setTodayDate"
        type="submit"
        >Today</x-button
      >
      <x-button
        size="sm"
        :color="activeButton === 'tomorrow' ? 'black' : 'primary'"
        @click="setTomorrowDate"
        type="submit"
        >Tomorrow</x-button
      >
      <x-button
        size="sm"
        :color="activeButton === 'thisWeek' ? 'black' : 'primary'"
        @click="setThisWeek"
        type="submit"
        >ThisWeek</x-button
      >
      <x-button
        size="sm"
        :color="activeButton === 'customDate' ? 'black' : 'primary'"
        @click="showCustomDate"
        >Custom</x-button
      >
    </div>
    <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-4 pt-2">
      <x-field label="Custom Date" v-if="isCustomDate">
        <DatePicker
          v-model="filters.customDate"
          placeholder="Select Start & End Date"
          range
          :max-range="92"
          size="sm"
          model-type="yyyy-MM-dd"
        />
      </x-field>
    </div>

    <div class="flex gap-3 justify-end">
      <x-button size="sm" color="#ff5e00" type="submit">Search</x-button>
      <x-button size="sm" color="primary" @click.prevent="onReset">
        Reset
      </x-button>
    </div>
  </x-form>
  <DataTable
    class="mt-4"
    table-class-name=""
    :loading="loaders.table"
    :headers="tableHeader"
    :items="props.reportData.data || []"
    border-cell
    :empty-message="'No Records Available'"
    :sort-by="'net_conversion'"
    :sort-type="'desc'"
    hide-footer
  >
    <template #item-total_premium="{ total_premium }">
      <div class="text-left">AED {{ formatNumber(total_premium) }}</div>
    </template>
    <template #item-advisor_name="{ advisor_name, advisor_id }">
      <div class="text-left">
        <Link
            v-bind:href="`/quotes/car?page=1&segment_filter=all&payment_status_id=4&advisor_id[]=${[advisor_id]}`"
          class="text-black underline"
        >
          {{ advisor_name }}
        </Link>
      </div>
    </template>
  </DataTable>
  <Pagination
    :links="{
      next: props.reportData.next_page_url,
      prev: props.reportData.prev_page_url,
      current: props.reportData.current_page,
      from: props.reportData.from,
      to: props.reportData.to,
    }"
  />
</template>

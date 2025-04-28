<script setup>
defineProps({
  process: Object,
  results: Object,
  quoteTypes: Object,
});

const formatDate = date =>
  date ? useDateFormat(date, 'DD-MM-YYYY HH:mm:ss').value : '-';
const { isRequired } = useRules();
const params = useUrlSearchParams('history');
const serverOptions = ref({
  page: 1,
});

const loader = reactive({
  cards: false,
});

const filters = reactive({
  quoteType: '',
  uuid: '',
  processType: '',
  startEndDate: [],
});

const page = usePage();
const processTypes = ref([]);
const hasSearched = ref(false);

const canSearch = computed(() => !!filters.uuid && !!filters.quoteType);

function resetFilters() {
  for (const key in filters) {
    filters[key] = '';
  }
  hasSearched.value = false;
  router.visit(route('process-tracker.index'), {
    method: 'get',
    data: { page: 1 },
    preserveState: true,
    preserveScroll: true,
    onFinish: () => {
      loader.cards = false;
    },
    onBefore: () => {
      loader.cards = true;
    },
  });
}

function search(isValid) {
  if (!isValid || !canSearch.value) {
    return;
  }
  hasSearched.value = true;
  serverOptions.value.page = 1;
  for (const key in filters) {
    if (filters[key] === '') {
      delete filters[key];
    }
  }
  router.visit(route('process-tracker.index'), {
    method: 'get',
    data: {
      ...filters,
      ...serverOptions.value,
    },
    preserveState: true,
    preserveScroll: true,
    onFinish: () => {
      loader.cards = false;
    },
    onBefore: () => {
      loader.cards = true;
    },
  });
}

const resolveProcessTypes = (event, reset = true) => {
  if (reset) {
    filters.processType = '';
  }
  processTypes.value =
    page.props.quoteTypes.find(quoteType => quoteType.value === event)
      ?.processTypes || [];
};

function setQueryFilters() {
  for (const [key] of Object.entries(params)) {
    if (key.includes('[]')) {
      filters[key.substring(0, key.length - 2)] = params[key];
    } else {
      filters[key] = params[key];
    }
  }
  resolveProcessTypes(filters.quoteType, false);
}

onMounted(() => {
  setQueryFilters();
});

watch(
  serverOptions,
  value => {
    search(true);
  },
  { deep: true },
);
</script>

<template>
  <div>
    <Head title="Process Tracker" />

    <x-divider class="my-4" />

    <x-form @submit="search" :auto-focus="false">
      <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-4">
        <x-field label="Quote Type">
          <x-select
            v-model="filters.quoteType"
            placeholder="Select Quote Type"
            :options="quoteTypes"
            class="w-full"
            :rules="[isRequired]"
            @update:model-value="resolveProcessTypes"
          />
        </x-field>
        <x-field :label="(filters.quoteType || '') + ' UUID'">
          <x-input
            v-model="filters.uuid"
            type="search"
            class="w-full"
            :placeholder="'Type ' + (filters.quoteType || '') + ' UUID'"
            :rules="[isRequired]"
          />
        </x-field>
        <x-field label="Process Type">
          <x-select
            v-model="filters.processType"
            placeholder="Select Process Type"
            :options="processTypes"
            class="w-full"
            :rules="[isRequired]"
          />
        </x-field>
        <DatePicker
          v-model="filters.startEndDate"
          label="Date Range"
          placeholder="Specify Date Range"
          range
          :maxDate="new Date()"
          size="sm"
          model-type="yyyy-MM-dd"
        />
      </div>
      <div class="flex justify-between">
        <div></div>
        <div class="flex justify-self-end gap-3">
          <x-button
            size="sm"
            color="#ff5e00"
            type="submit"
            :disabled="!canSearch"
            >Search</x-button
          >
          <x-button size="sm" color="primary" @click.prevent="resetFilters"
            >Reset</x-button
          >
        </div>
      </div>
    </x-form>

    <x-divider class="my-4" v-if="results.data.length > 0" />

    <div class="text-center">
      <h3 v-if="!loader.cards && results.data.length === 0 && hasSearched">
        No Records Available
      </h3>
      <x-loader v-if="loader.cards" label="Loading" status="active" />
    </div>

    <!-- Modern Timeline Results -->
    <div
      v-if="
        !loader.cards && canSearch && hasSearched && results.data.length > 0
      "
      class="space-y-8 max-w-4xl mx-auto"
    >
      <div class="relative">
        <!-- Timeline Line -->
        <div
          class="absolute left-4 top-0 bottom-0 w-1 bg-gradient-to-b from-blue-500 via-primary-400 to-green-300"
        ></div>
        <div
          v-for="(itr, idx) in results.data"
          :key="itr.performedAt"
          class="relative pl-16 pb-8"
        >
          <!-- Timeline Dot -->
          <div
            class="absolute left-2 w-5 h-5 rounded-full border-4 shadow-lg"
            :class="[
              idx === 0 ? 'bg-green-400 border-green-100' : '',
              idx === results.data.length - 1
                ? 'bg-blue-400 border-blue-100'
                : '',
              idx !== 0 && idx !== results.data.length - 1
                ? 'bg-primary-400 border-primary-100'
                : '',
            ]"
          ></div>
          <!-- Timeline Card -->
          <div
            class="bg-gradient-to-br from-white to-gray-50 rounded-xl p-6 shadow-md border border-gray-100 hover:shadow-lg transition-all duration-200"
          >
            <div class="flex items-center gap-2 mb-2">
              <span
                class="px-3 py-1 text-xs font-semibold rounded-full shadow-sm bg-orange-100 text-orange-700 border border-orange-300"
              >
                {{ formatDate(itr.created_at) }}
              </span>
              <p class="line-clamp-1 flex-1 text-base" v-html="itr.summary"></p>
            </div>
            <ol class="relative border-s border-gray-200 ml-2 mt-4">
              <li v-for="step in itr.steps" :key="step.step" class="mb-8 ms-4">
                <div
                  class="absolute -start-[8.5px] mt-1.5 size-4 rounded-full border border-white bg-primary-400"
                />
                <h4
                  class="mb-1 text-sm font-semibold"
                  v-html="step.description"
                ></h4>
                <time class="text-xs font-normal leading-none text-gray-700">
                  {{ formatDate(step.performedAt) }}
                </time>
                <div class="mt-2 flex flex-wrap gap-2">
                  <x-tag v-for="(v, k) in step.data" :key="k" color="secondary">
                    {{ k }}: {{ v }}
                  </x-tag>
                </div>
              </li>
            </ol>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

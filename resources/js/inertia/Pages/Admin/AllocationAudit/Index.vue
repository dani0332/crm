<script setup>
const props = defineProps({
  audits: Object,
  quoteTypes: Object,
  assignmentTypes: Object,
});

const formatDate = date =>
  date ? useDateFormat(date, 'DD-MM-YYYY HH:mm:ss').value : '-';

const { isRequired } = useRules();
const params = useUrlSearchParams('history');
const serverOptions = ref({
  page: 1,
  rowsPerPage: 15,
  sortBy: 'created_at',
  sortType: 'desc',
});

const loader = reactive({
  cards: false,
});

const filters = reactive({
  quote_type: '',
  uuid: '',
});

const tableHeader = [
  {
    text: 'Quote Type',
    value: 'quote_type_id',
    sortable: true,
  },
  {
    text: 'Assignment Type',
    value: 'assignment_type',
    sortable: true,
  },
  {
    text: 'Advisor ID',
    value: 'advisor_id',
    sortable: true,
  },
  {
    text: 'Action By ID',
    value: 'action_by_id',
    sortable: true,
  },
  {
    text: 'Created At',
    value: 'created_at',
    sortable: true,
  },
  {
    text: 'Actions',
    value: 'actions',
    align: 'right',
  },
];

function setQueryFilters() {
  for (const [key] of Object.entries(params)) {
    if (key.includes('[]')) {
      filters[key.substring(0, key.length - 2)] = params[key];
    } else {
      filters[key] = params[key];
    }
  }
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

const hasUuid = computed(() => !!filters.uuid);

function resetFilters() {
  for (const key in filters) {
    filters[key] = '';
  }

  router.visit(route('admin.allocation-audit.index'), {
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
  if (!isValid) {
    return;
  }

  serverOptions.value.page = 1;

  for (const key in filters) {
    if (filters[key] === '') {
      delete filters[key];
    }
  }

  router.visit(route('admin.allocation-audit.index'), {
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
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <Head title="Allocation Audit" />

      <!-- Header Section -->
      <div class="mb-8 flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900 flex items-center">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              class="h-8 w-8 mr-3 text-primary-600"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
              />
            </svg>
            Allocation Audit
          </h1>
          <p class="mt-2 text-sm text-gray-600">
            Track and monitor allocation changes across the system
          </p>
        </div>
      </div>

      <!-- Search Card -->
      <div
        class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-8 transition-all duration-300 hover:shadow-lg"
      >
        <x-form
          @submit="search"
          :auto-focus="false"
          class="flex items-center gap-4"
        >
          <x-field label="Quote Type" :rules="[isRequired]" class="flex-1">
            <x-select
              v-model="filters.quote_type"
              placeholder="Select Quote Type"
              :options="props.quoteTypes"
              class="w-full rounded-xl border-gray-200 focus:border-primary-500 focus:ring focus:ring-primary-200 focus:ring-opacity-50 transition-all duration-200 h-12"
            />
          </x-field>
          <x-field
            :label="(filters.quote_type || '') + ' UUID'"
            :rules="[isRequired]"
            class="flex-1"
          >
            <x-input
              v-model="filters.uuid"
              type="search"
              class="w-full rounded-xl border-gray-200 focus:border-primary-500 focus:ring focus:ring-primary-200 focus:ring-opacity-50 transition-all duration-200 h-12"
              :placeholder="'Type ' + (filters.quote_type || '') + ' UUID'"
            />
          </x-field>
          <div class="flex gap-2 items-center h-12">
            <x-button
              size="sm"
              color="primary"
              @click.prevent="resetFilters"
              class="px-6 h-12 rounded-xl transition-all duration-200 hover:shadow-md flex items-center font-semibold"
            >
              Reset Filters
            </x-button>
            <x-button
              size="sm"
              color="#ff5e00"
              type="submit"
              class="px-6 h-12 rounded-xl transition-all duration-200 hover:shadow-md flex items-center font-semibold"
            >
              Search
            </x-button>
          </div>
        </x-form>
      </div>

      <!-- Empty State or Prompt -->
      <div v-if="!hasUuid && !loader.cards" class="text-center">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12">
          <div class="flex flex-col items-center">
            <div
              class="bg-gradient-to-br from-blue-100 to-blue-50 rounded-full p-6 mb-4"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-16 w-16 text-blue-400"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                />
              </svg>
            </div>
            <div class="text-blue-500 text-lg font-medium">
              Please search for a UUID to view allocation audit logs.
            </div>
            <p class="text-gray-400 mt-2">
              Enter a UUID and click Search above.
            </p>
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loader.cards" class="flex justify-center items-center py-12">
        <div
          class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"
        ></div>
      </div>

      <!-- Audit Timeline -->
      <div
        v-if="hasUuid && !loader.cards && props.audits?.length > 0"
        class="space-y-6"
      >
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
          <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold text-gray-900">Audit Timeline</h3>
            <span class="text-sm text-gray-500"
              >Total Changes: {{ props.audits.length }}</span
            >
          </div>

          <div class="relative">
            <!-- Timeline Line -->
            <div
              class="absolute left-8 top-0 bottom-0 w-1 bg-gradient-to-b from-blue-500 via-primary-400 to-green-300 timeline-line"
            ></div>

            <!-- Audit Items -->
            <div
              v-for="(audit, index) in props.audits"
              :key="index"
              class="relative pl-16 pb-8"
            >
              <!-- Timeline Dot -->
              <div
                :class="[
                  'absolute left-6 w-5 h-5 rounded-full border-4 shadow-lg timeline-dot',
                  audit.assignment_type_text === 'Assigned'
                    ? 'bg-green-400 border-green-100'
                    : '',
                  audit.assignment_type_text === 'Unassigned'
                    ? 'bg-red-400 border-red-100'
                    : '',
                  audit.assignment_type_text === 'Reassigned'
                    ? 'bg-blue-400 border-blue-100'
                    : '',
                ]"
              ></div>

              <!-- Audit Card -->
              <div
                class="bg-gradient-to-br from-white to-gray-50 rounded-xl p-6 shadow-md border border-gray-100 hover:shadow-lg transition-all duration-200 audit-card"
              >
                <div class="flex items-start justify-between">
                  <div class="space-y-4">
                    <!-- Assignment Type Badge -->
                    <div class="flex items-center space-x-3">
                      <span
                        class="px-3 py-1 text-sm font-semibold rounded-full shadow-sm"
                        :class="{
                          'bg-green-100 text-green-700 border border-green-300':
                            audit.assignment_type_text === 'Assigned',
                          'bg-red-100 text-red-700 border border-red-300':
                            audit.assignment_type_text === 'Unassigned',
                          'bg-blue-100 text-blue-700 border border-blue-300':
                            audit.assignment_type_text === 'Reassigned',
                        }"
                      >
                        {{ audit.assignment_type_text }}
                      </span>
                      <span class="text-xs text-gray-500 font-mono">{{
                        formatDate(audit.created_at)
                      }}</span>
                    </div>

                    <!-- Advisor and Action By Info -->
                    <div class="grid grid-cols-2 gap-4">
                      <div class="space-y-2">
                        <div class="flex items-center space-x-2">
                          <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-4 w-4 text-blue-400"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                          >
                            <path
                              stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="2"
                              d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                            />
                          </svg>
                          <span class="text-sm font-medium text-gray-900"
                            >Advisor</span
                          >
                        </div>
                        <p class="text-sm text-gray-600 ml-6">
                          {{ audit.advisor_name }}
                        </p>
                      </div>
                      <div class="space-y-2">
                        <div class="flex items-center space-x-2">
                          <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-4 w-4 text-primary-400"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                          >
                            <path
                              stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="2"
                              d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"
                            />
                          </svg>
                          <span class="text-sm font-medium text-gray-900"
                            >Action By</span
                          >
                        </div>
                        <p class="text-sm text-gray-600 ml-6">
                          {{ audit.action_by_name }}
                        </p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Custom scrollbar */
::-webkit-scrollbar {
  @apply w-2;
}

::-webkit-scrollbar-track {
  @apply bg-gray-100 rounded-full;
}

::-webkit-scrollbar-thumb {
  @apply bg-gray-300 rounded-full hover:bg-gray-400 transition-colors duration-200;
}

/* Timeline animations */
.timeline-dot {
  @apply transition-all duration-300;
}

.timeline-dot:hover {
  @apply transform scale-125;
}

/* Card hover effects */
.audit-card {
  @apply transition-all duration-300;
}

.audit-card:hover {
  @apply transform -translate-y-1 shadow-xl;
}

/* Timeline line animation */
.timeline-line {
  @apply transition-all duration-300;
}

.timeline-line:hover {
  @apply w-1;
}

/* Card content animations */
.audit-card:hover .card-content {
  @apply transform translate-x-1;
}
</style>

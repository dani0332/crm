<script setup>
const props = defineProps({
  audits: Object,
  quoteTypes: Object,
  assignmentTypes: Object,
});

// Simple date formatter function
const formatDate = date => {
  if (!date) return '';

  const dateObj = new Date(date);
  const day = String(dateObj.getDate()).padStart(2, '0');
  const month = String(dateObj.getMonth() + 1).padStart(2, '0');
  const year = dateObj.getFullYear();
  const hours = String(dateObj.getHours()).padStart(2, '0');
  const minutes = String(dateObj.getMinutes()).padStart(2, '0');

  return `${day}-${month}-${year} ${hours}:${minutes}`;
};

const { isRequired } = useRules();
const params = useUrlSearchParams('history');

const loader = reactive({
  cards: false,
});

const filters = reactive({
  quote_type: '',
  uuid: '',
});

// Track if search has been performed
const hasSearched = ref(false);

function setQueryFilters() {
  for (const [key] of Object.entries(params)) {
    if (key.includes('[]')) {
      filters[key.substring(0, key.length - 2)] = params[key];
    } else {
      filters[key] = params[key];
    }
  }

  // Check if URL already has search params
  hasSearched.value = !!params.uuid;
}

onMounted(() => {
  setQueryFilters();
});

const hasUuid = computed(() => !!filters.uuid);

function resetFilters() {
  for (const key in filters) {
    filters[key] = '';
  }

  // Reset the search state
  hasSearched.value = false;

  router.visit(route('admin.allocation-audit.index'), {
    method: 'get',
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

  // Set search state to true
  hasSearched.value = true;

  for (const key in filters) {
    if (filters[key] === '') {
      delete filters[key];
    }
  }

  router.visit(route('admin.allocation-audit.index'), {
    method: 'get',
    data: {
      ...filters,
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
    </div>
  </div>

  <!-- Search Card -->
  <div
    class="bg-white rounded-2xl shadow-sm border border-gray-100 px-6 pt-6 mb-8 transition-all duration-300 hover:shadow-lg"
  >
    <x-form @submit="search" :auto-focus="false" class="flex flex-col">
      <div class="flex gap-4">
        <div class="flex-1">
          <x-field label="Quote Type" :rules="[isRequired]">
            <ComboBox
              v-model="filters.quote_type"
              placeholder="Select Quote Type"
              :options="props.quoteTypes"
              :single="true"
              class="w-full"
            />
          </x-field>
        </div>
        <div class="flex-1">
          <x-field
            :label="(filters.quote_type || '') + ' UUID'"
            :rules="[isRequired]"
          >
            <x-input
              v-model="filters.uuid"
              type="search"
              class="w-full"
              :placeholder="'Type ' + (filters.quote_type || '') + ' UUID'"
            />
          </x-field>
        </div>
      </div>
      <div class="flex justify-end gap-4">
        <x-button
          size="sm"
          color="#ff5e00"
          type="submit"
          class="px-12 rounded-md"
          :disabled="!filters.quote_type || !filters.uuid"
        >
          Search
        </x-button>
        <x-button
          size="sm"
          color="primary"
          @click.prevent="resetFilters"
          class="px-12 rounded-md"
        >
          Reset
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
        <p class="text-gray-400 mt-2">Enter a UUID and click Search above.</p>
      </div>
    </div>
  </div>

  <!-- No Records Found State -->
  <div
    v-if="
      hasSearched &&
      hasUuid &&
      !loader.cards &&
      (!props.audits || props.audits.length === 0)
    "
    class="text-center"
  >
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12">
      <div class="flex flex-col items-center">
        <div
          class="bg-gradient-to-br from-amber-100 to-amber-50 rounded-full p-6 mb-4"
        >
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="h-16 w-16 text-amber-400"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
            />
          </svg>
        </div>
        <div class="text-amber-500 text-lg font-medium">
          No audit records found
        </div>
        <p class="text-gray-400 mt-2">
          No allocation audit records were found for the specified UUID.
        </p>
        <button
          @click="resetFilters"
          class="mt-4 px-4 py-2 text-sm font-medium text-white bg-amber-500 rounded-lg hover:bg-amber-600 transition-colors duration-200 flex items-center"
        >
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="h-4 w-4 mr-1.5"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
            />
          </svg>
          Reset Search
        </button>
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
    v-if="hasSearched && hasUuid && !loader.cards && props.audits?.length > 0"
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
          class="relative pl-16 pb-5"
        >
          <!-- Timeline Dot -->
          <div
            :class="[
              'absolute left-6 w-5 h-5 rounded-full border-4 shadow-lg timeline-dot',
              audit.assignment_type == 1
                ? 'bg-emerald-500 border-emerald-100'
                : '',
              audit.assignment_type == 2 ? 'bg-amber-500 border-amber-100' : '',
              audit.assignment_type == 3 ? 'bg-blue-500 border-blue-100' : '',
              audit.assignment_type == 4
                ? 'bg-indigo-500 border-indigo-100'
                : '',
              audit.assignment_type == 5 ? 'bg-rose-500 border-rose-100' : '',
              audit.assignment_type == 6
                ? 'bg-purple-500 border-purple-100'
                : '',
            ]"
          ></div>

          <!-- Audit Card - Redesigned for compact modern look -->
          <div
            class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 hover:shadow-md transition-all duration-200 audit-card"
          >
            <!-- Header with Assignment Type and Date -->
            <div class="flex items-center justify-between mb-3">
              <span
                class="px-3 py-1 text-xs font-semibold rounded-full shadow-sm"
                :class="{
                  'bg-emerald-100 text-emerald-700 border border-emerald-300':
                    audit.assignment_type == 1,
                  'bg-amber-100 text-amber-700 border border-amber-300':
                    audit.assignment_type == 2,
                  'bg-blue-100 text-blue-700 border border-blue-300':
                    audit.assignment_type == 3,
                  'bg-indigo-100 text-indigo-700 border border-indigo-300':
                    audit.assignment_type == 4,
                  'bg-rose-100 text-rose-700 border border-rose-300':
                    audit.assignment_type == 5,
                  'bg-purple-100 text-purple-700 border border-purple-300':
                    audit.assignment_type == 6,
                }"
              >
                {{ audit.assignment_type_text }}
              </span>

              <!-- Ribbon Style Time -->
              <div class="relative">
                <div
                  class="flex items-center px-3 py-1 text-xs font-medium text-gray-700 bg-gray-100 border border-gray-200 shadow-sm ribbon"
                >
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-3 w-3 mr-1 text-gray-500"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                    />
                  </svg>
                  {{ formatDate(audit.created_at) }}
                </div>
              </div>
            </div>

            <!-- User Information -->
            <div
              class="grid grid-cols-2 gap-3 p-3 bg-gray-50 rounded-lg text-sm"
            >
              <!-- Advisor Info -->
              <div class="space-y-1">
                <div
                  class="flex items-center gap-1 text-gray-700 font-medium mb-1"
                >
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-4 w-4 text-blue-500"
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
                  Advisor
                </div>
                <div class="ml-5 text-gray-700">
                  {{ audit.advisor_name || 'N/A' }}
                </div>
                <div class="ml-5 text-gray-500 text-xs">
                  {{ audit.advisor_email || 'N/A' }}
                </div>
              </div>

              <!-- Action By Info -->
              <div class="space-y-1">
                <div
                  class="flex items-center gap-1 text-gray-700 font-medium mb-1"
                >
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-4 w-4 text-primary-500"
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
                  Action By
                </div>
                <div class="ml-5 text-gray-700">
                  {{ audit.action_by_name || 'N/A' }}
                </div>
                <div class="ml-5 text-gray-500 text-xs">
                  {{ audit.action_by_email || 'N/A' }}
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
  @apply transform -translate-y-1;
}

/* Timeline line animation */
.timeline-line {
  @apply transition-all duration-300;
}

.timeline-line:hover {
  @apply w-1;
}

/* Ribbon style for date/time */
.ribbon {
  @apply rounded-r-sm rounded-tl-sm rounded-bl-sm;
  position: relative;
  right: -1px;
}

.ribbon-fold {
  z-index: 1;
}
</style>

<script setup>
const props = defineProps({});

const notification = useToast();
const { isRequired } = useRules();

const form = useForm({
  query: '',
  iterations: 1,
  fetch_data: false,
});

const loader = ref(false);
const exectionTime = ref(null);
const errorMessage = ref(null);
const queryResults = ref(null);
const rowCount = ref(null);

// Separate handler for the two button actions
const handleGetData = () => {
  if (form.query === '') {
    notification.error({
      title: 'Query is required',
      position: 'top',
    });
    return;
  }

  form.clearErrors();
  form.fetch_data = true;
  submitQuery();
};

const handleGetBenchmark = () => {
  if (form.query === '') {
    notification.error({
      title: 'Query is required',
      position: 'top',
    });
    return;
  }

  form.clearErrors();
  form.fetch_data = false;
  submitQuery();
};

const submitQuery = () => {
  exectionTime.value = null;
  errorMessage.value = null;
  queryResults.value = null;
  rowCount.value = null;
  loader.value = true;

  axios
    .post(route('admin.benchmarker.query.process'), form)
    .then(response => {
      loader.value = false;
      let { execution_time_ms, error, message, results, row_count } =
        response.data;

      if (error === true) {
        errorMessage.value = message;
        notification.error({
          title: 'An error occurred',
          position: 'top',
        });
      } else {
        exectionTime.value = execution_time_ms;

        // Only set results if we requested them
        if (form.fetch_data && results) {
          queryResults.value = results;
          rowCount.value = row_count;
        }
      }
    })
    .catch(error => {
      loader.value = false;

      if (error?.response?.status === 422) {
        errorMessage.value =
          error?.response?.data?.message || 'An error occurred';
      } else {
        errorMessage.value = 'An error occurred';
      }

      notification.error({
        title: 'Error Occurred',
        position: 'top',
      });
    });
};

// Helper to extract table headers from results
const getTableHeaders = results => {
  if (!results || !results.length) return [];
  return Object.keys(results[0]);
};

// Export to CSV functionality
const exportToCSV = () => {
  if (!queryResults.value || queryResults.value.length === 0) {
    notification.error({
      title: 'No data to export',
      position: 'top',
    });
    return;
  }

  try {
    const headers = getTableHeaders(queryResults.value);

    // Create CSV content
    let csvContent = headers.join(',') + '\n';

    // Add data rows
    queryResults.value.forEach(row => {
      const values = headers.map(header => {
        const value = row[header];

        // Handle different data types
        if (value === null || value === undefined) {
          return '';
        }

        // Escape quotes and wrap in quotes if contains comma, newline, or quote
        const stringValue = String(value);
        if (
          stringValue.includes(',') ||
          stringValue.includes('\n') ||
          stringValue.includes('"')
        ) {
          return `"${stringValue.replace(/"/g, '""')}"`;
        }

        return stringValue;
      });

      csvContent += values.join(',') + '\n';
    });

    // Create blob and download
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);

    link.setAttribute('href', url);
    link.setAttribute('download', `query_results_${new Date().getTime()}.csv`);
    link.style.visibility = 'hidden';

    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    notification.success({
      title: 'CSV exported successfully',
      position: 'top',
    });
  } catch (error) {
    notification.error({
      title: 'Failed to export CSV',
      position: 'top',
    });
  }
};
</script>
<template>
  <Head title="Query Benchmarker" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">Query Benchmarker</h2>
  </div>
  <x-divider class="my-4" />
  <div class="space-y-4">
    <div class="grid">
      <x-input
        type="number"
        min="1"
        max="5"
        v-model="form.iterations"
        placeholder="Iterations"
        class="w-full"
        label="Iterations"
        required
      />
    </div>
    <div class="grid">
      <x-textarea
        type="text"
        v-model="form.query"
        :adjust-to-text="false"
        class="w-full"
        :error="form.errors.query"
        rows="20"
        columns="50"
        label="Query"
        required
      />
    </div>

    <div v-if="exectionTime || errorMessage || queryResults" class="mt-4">
      <p class="font-medium" v-if="exectionTime">
        Execution Time: <span class="text-red-500">{{ exectionTime }}</span>
      </p>

      <p class="font-medium" v-if="rowCount !== null">
        Rows Returned: <span class="text-blue-500">{{ rowCount }}</span>
      </p>

      <p class="font-medium text-red-500" v-if="errorMessage">
        {{ errorMessage }}
      </p>
    </div>

    <x-divider class="my-4" />
    <div class="flex justify-end gap-3 mb-4">
      <x-button
        size="md"
        color="emerald"
        @click="handleGetData"
        :loading="loader"
        :disabled="loader"
        v-if="$page.props.auth.user.can_impersonate"
        type="button"
      >
        Get Data
      </x-button>

      <x-button
        size="md"
        color="blue"
        @click="handleGetBenchmark"
        :loading="loader"
        :disabled="loader"
        type="button"
      >
        Get Benchmark
      </x-button>
    </div>

    <!-- Query Results Table -->
    <div
      v-if="queryResults && queryResults.length > 0"
      class="mt-8 bg-white rounded-xl shadow-lg overflow-hidden border border-gray-100"
    >
      <div class="p-4 border-b bg-gradient-to-r from-blue-600 to-indigo-700">
        <div class="flex justify-between items-center">
          <h3 class="text-lg font-medium text-white">Query Results</h3>
          <div class="flex items-center gap-4">
            <span
              class="px-3 py-1 rounded-full bg-white/20 text-white text-sm font-medium flex items-center gap-1"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-4 w-4"
                viewBox="0 0 20 20"
                fill="currentColor"
              >
                <path
                  fill-rule="evenodd"
                  d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                  clip-rule="evenodd"
                />
              </svg>
              <span>{{ exectionTime }}</span>
            </span>
            <span
              class="px-3 py-1 rounded-full bg-white/20 text-white text-sm font-medium flex items-center gap-1"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-4 w-4"
                viewBox="0 0 20 20"
                fill="currentColor"
              >
                <path
                  d="M7 3a1 1 0 000 2h6a1 1 0 100-2H7zM4 7a1 1 0 011-1h10a1 1 0 110 2H5a1 1 0 01-1-1zM2 11a2 2 0 012-2h12a2 2 0 012 2v4a2 2 0 01-2 2H4a2 2 0 01-2-2v-4z"
                />
              </svg>
              <span>{{ rowCount }} rows</span>
            </span>
            <button
              @click="exportToCSV"
              class="px-4 py-2 rounded-lg bg-white text-blue-600 text-sm font-medium hover:bg-gray-50 transition-colors flex items-center gap-2 shadow-md"
              type="button"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-4 w-4"
                viewBox="0 0 20 20"
                fill="currentColor"
              >
                <path
                  fill-rule="evenodd"
                  d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z"
                  clip-rule="evenodd"
                />
              </svg>
              <span>Export CSV</span>
            </button>
          </div>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full">
          <thead>
            <tr class="bg-gray-50 border-b">
              <th
                class="sticky left-0 bg-gray-50 z-10 px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider border-r"
              >
                <div
                  class="flex items-center justify-center w-8 h-8 rounded-lg bg-gray-100 text-gray-700"
                >
                  #
                </div>
              </th>
              <th
                v-for="header in getTableHeaders(queryResults)"
                :key="header"
                class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider"
              >
                <div class="flex items-center gap-1">
                  <span>{{ header }}</span>
                </div>
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr
              v-for="(row, rowIndex) in queryResults"
              :key="rowIndex"
              class="transition-colors hover:bg-gray-50"
            >
              <td
                class="sticky left-0 z-10 px-4 py-3 text-sm font-medium text-gray-700 border-r whitespace-nowrap"
                :class="rowIndex % 2 === 0 ? 'bg-white' : 'bg-gray-50'"
              >
                <div
                  class="flex items-center justify-center w-8 h-8 rounded-lg bg-blue-50 text-blue-600"
                >
                  {{ rowIndex + 1 }}
                </div>
              </td>
              <td
                v-for="header in getTableHeaders(queryResults)"
                :key="`${rowIndex}-${header}`"
                class="px-4 py-3 text-sm text-gray-800 whitespace-nowrap"
              >
                <template v-if="row[header] === null">
                  <span
                    class="inline-block px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-500"
                    >NULL</span
                  >
                </template>
                <template v-else>
                  <span
                    :class="{
                      'font-mono': typeof row[header] === 'number',
                      'text-blue-600':
                        typeof row[header] === 'string' &&
                        row[header].match(/^[\w-\.]+@([\w-]+\.)+[\w-]{2,4}$/g),
                      'text-indigo-600 font-medium':
                        typeof row[header] === 'boolean',
                      'font-medium': typeof row[header] === 'number',
                    }"
                  >
                    {{ String(row[header]) }}
                  </span>
                </template>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div v-else-if="queryResults && queryResults.length === 0" class="mt-8">
      <div class="bg-white p-6 rounded-xl shadow-lg text-center">
        <svg
          xmlns="http://www.w3.org/2000/svg"
          class="h-12 w-12 mx-auto text-gray-400"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"
          />
        </svg>
        <p class="mt-4 text-gray-600 font-medium">
          Query executed successfully, but returned no results.
        </p>
      </div>
    </div>
  </div>
</template>

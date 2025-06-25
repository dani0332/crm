<script setup>
import dayjs from 'dayjs';

defineProps({
  aml: Object,
  quoteTypes: Array,
});

const page = usePage();
const notification = useToast();
const loader = reactive({
  table: false,
  export: false,
  exportAmlRiskScore: false,
});
const permissionsEnum = page.props.permissionsEnum;
const can = permission => useCan(permission);
const { isRequired } = useRules();

const tableHeader = [
  { text: 'Quote Type', value: 'quote_type_text' },
  { text: 'Ref-ID', value: 'cdb_id' },
  { text: 'Created At', value: 'created_at' },
  { text: 'Updated At', value: 'updated_at' },
];

const dateFormat = date => useDateFormat(date, 'DD-MM-YYYY h:mm:ss');

let availableFilters = {
  quoteType: '',
  searchType: '',
  searchField: '',
  matchFound: '',
  amlCreatedStartDate: '',
  amlCreatedEndDate: '',
  page: 1,
};

const isDateMandatory = ref(true);
const isSearchValueRequired = ref(false);
const isQuoteTypeEmpty = ref(false);
const filtersForm = useForm(availableFilters);
const customErrors = reactive({
  amlCreatedStartDate: '',
  amlCreatedEndDate: '',
});

function onReset() {
  router.visit('/kyc/aml', {
    method: 'get',
    preserveScroll: true,
    onBefore: () => (loader.table = true),
    onSuccess: () => (loader.table = false),
  });
}

function checkDateValidation() {
  isDateMandatory.value = filtersForm.searchType === '';
  isSearchValueRequired.value = filtersForm.searchType !== '';
}

function onSubmit(isValid) {
  isQuoteTypeEmpty.value = !filtersForm.quoteType;
  if (!isValid || !filtersForm.quoteType) return;

  //remove empty fields
  removeEmptyFields(filtersForm);

  filtersForm.get(`/kyc/aml`, {
    preserveScroll: true,
    onBefore: () => {
      if (
        dayjs(filtersForm.amlCreatedEndDate).diff(
          dayjs(filtersForm.amlCreatedStartDate),
          'day',
        ) > 30
      ) {
        filtersForm.setError(
          'amlCreatedStartDate',
          'Allowed no. of days between start & end dates are 30 days.',
        );
        return false;
      }
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

const resetBeforeSubmit = () => {
  checkDateValidation();
  resetCustomErrors();
};

const resetCustomErrors = () => {
  customErrors.amlCreatedStartDate = '';
  customErrors.amlCreatedEndDate = '';
};

const onDataExport = async (exportType = 'download') => {
  isDateMandatory.value = true;
  isQuoteTypeEmpty.value = false;
  resetCustomErrors();

  let hasErrors = false;
  if (!filtersForm.amlCreatedStartDate) {
    customErrors.amlCreatedStartDate = 'This field is required';
    hasErrors = true;
  }

  if (!filtersForm.amlCreatedEndDate) {
    customErrors.amlCreatedEndDate = 'This field is required';
    hasErrors = true;
  }

  if (
    dayjs(filtersForm.amlCreatedEndDate).diff(
      dayjs(filtersForm.amlCreatedStartDate),
      'day',
    ) > 30
  ) {
    customErrors.amlCreatedStartDate =
      'Allowed no. of days between start & end dates are 30 days.';
    hasErrors = true;
  }

  if (hasErrors) {
    return;
  }

  const exportData = {};
  Object.keys(availableFilters).forEach(key => {
    exportData[key] = filtersForm[key];
  });

  //remove empty fields
  removeEmptyFields(exportData);

  const url = `/kyc/export`;
  exportData.exportType = exportType;
  const data = useObjToUrl(exportData);

  const payload = {
    // quote_type_id: getQuoteTypeId(page.props.quoteTypes, 'Aml'),
    exportType: exportType,
    url: url + '?' + new URLSearchParams(data).toString(),
  };

  loader.export = true;
  if (exportType == 'email') {
    const exportResponse = await axios
      .get(payload.url)
      .then(resp => {
        // return resp.data;
        if (resp.data.message) {
          notification.success({
            title: resp.data.message,
            position: 'top',
          });
        }

        if (resp)
          setTimeout(() => {
            loader.export = false;
          }, 1000);
      })
      .catch(err => {
        notification.error({
          title: err.response.data.message
            ? err.response.data.message
            : 'Unable to start an export',
          position: 'top',
        });
        setTimeout(() => {
          loader.export = false;
        }, 1000);
        throw err;
      });
  } else {
    setTimeout(() => {
      loader.export = false;
    }, 1000);
    window.open(url + '?' + useObjToUrl(exportData));
  }
};

function removeEmptyFields(obj) {
  Object.keys(obj).forEach(key => {
    if (obj[key] === '' || obj[key] === null || obj[key] === undefined) {
      delete obj[key];
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

watch(
  () => filtersForm,
  () => {
    let queryString = window.location.search;
    let urlParams = new URLSearchParams(queryString);

    isDateMandatory.value = !(
      (urlParams.get('searchType') !== null &&
        urlParams.get('searchField') !== null) ||
      (filtersForm.searchType !== '' && filtersForm.searchField !== '')
    );
  },
  { deep: true, immediate: true },
);

const quoteTypeOptions = computed(() =>
  ref(
    page.props.quoteTypes.map(item => ({
      value: item.code,
      label: item.text,
    })),
  ),
);

async function downloadAmlCtfReport() {
  resetCustomErrors();

  // Validate that either search criteria or date range is provided
  const hasSearchCriteria =
    filtersForm.quoteType || filtersForm.searchField || filtersForm.searchType;
  const hasDateRange =
    filtersForm.amlCreatedStartDate && filtersForm.amlCreatedEndDate;

  if (!hasSearchCriteria && !hasDateRange) {
    customErrors.amlCreatedStartDate =
      'Please provide either search criteria (Quote Type, Search Field, or Search Type) or both start and end dates.';
    return;
  }

  // Only validate date difference if both dates are provided
  if (filtersForm.amlCreatedStartDate && filtersForm.amlCreatedEndDate) {
    const daysDifference = calculateDaysDifference(
      filtersForm.amlCreatedStartDate,
      filtersForm.amlCreatedEndDate,
    );
    if (daysDifference > 30) {
      customErrors.amlCreatedStartDate =
        'Allowed no. of days between start & end dates are 30 days.';
      return;
    }
  }

  const exportData = {};
  Object.keys(availableFilters).forEach(key => {
    exportData[key] = filtersForm[key];
  });

  //remove empty fields
  removeEmptyFields(exportData);
  // const data = useObjToUrl(exportData);

  const payload = {
    url: route('aml-ctf-report-export'),
    method: 'post',
    data: { ...exportData, exportType: 'email' },
    type: 'aml-ctf-report',
    exportType: 'email',
  };

  try {
    loader.exportAmlRiskScore = true;
    const response = await logAndExportQuotes(payload);
    if (response.data.message) {
      notification.success({
        title: response.data.message,
        position: 'top',
      });
    }
  } catch (error) {
    notification.error({
      title: error.response.data.message,
      position: 'top',
    });
  } finally {
    loader.exportAmlRiskScore = false;
  }
}

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
      <div class="grid sm:grid-cols-2 md:grid-cols-3 gap-3">
        <x-select
          v-model="filtersForm.quoteType"
          label="Quote Type"
          placeholder="Search by Quote Type"
          :options="[
            { value: '', label: 'Select Quote Type' },
            ...quoteTypeOptions.value,
          ]"
          filterable
          filterPlaceholder="Filter Quote Type...."
          :rules="[isRequired]"
        />

        <x-select
          v-model="filtersForm.searchType"
          label="Search By"
          placeholder=""
          :options="[
            { value: 'cdbId', label: 'Ref-ID' },
            { value: 'customerEmail', label: 'Customer Email' },
          ]"
          class="w-full"
          @update:model-value="checkDateValidation"
        />
        <x-input
          v-model="filtersForm.searchField"
          type="Search Value"
          name="code"
          label="Search Value"
          class="w-full"
          :rules="isSearchValueRequired ? [isRequired] : []"
          placeholder="Search Value"
        />
        <DatePicker
          v-model="filtersForm.amlCreatedStartDate"
          name="created_at_end"
          label="Created Date Start"
          class="w-full"
          :rules="isDateMandatory ? [isRequired] : []"
          :customError="filtersForm.errors.amlCreatedStartDate"
          :error="customErrors.amlCreatedStartDate"
        />
        <DatePicker
          v-model="filtersForm.amlCreatedEndDate"
          name="created_at_end"
          label="Created Date End"
          class="w-full"
          :rules="isDateMandatory ? [isRequired] : []"
          :customError="filtersForm.errors.amlCreatedEndDate"
          :error="customErrors.amlCreatedEndDate"
        />
      </div>
      <div class="flex justify-end gap-3 mb-4">
        <x-button
          v-if="can(permissionsEnum.DATA_EXTRACTION)"
          size="sm"
          color="#48bb78"
          @click.prevent="onDataExport('email')"
          :disabled="loader.export"
          :loading="loader.export"
        >
          Export via email
        </x-button>
        <x-button
          v-if="can(permissionsEnum.DATA_EXTRACTION)"
          size="sm"
          color="#48bb78"
          @click.prevent="downloadAmlCtfReport()"
          :disabled="loader.exportAmlRiskScore"
          :loading="loader.exportAmlRiskScore"
        >
          Export AML Risk Score Report
        </x-button>
        <x-button
          size="sm"
          color="#ff5e00"
          type="submit"
          :disabled="loader.table"
          @click="resetBeforeSubmit()"
          >Search</x-button
        >
        <x-button
          size="sm"
          color="primary"
          :disabled="loader.table"
          @click.prevent="onReset"
        >
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
    >
      <template #item-id="{ code, id }">
        <Link :href="`/kyc/aml/${id}`" class="text-primary-500 hover:underline">
          {{ id }}
        </Link>
      </template>
      <template #item-cdb_id="item">
        <Link
          :href="`/kyc/aml/${item.quote_type_id}/details/${item.id}`"
          class="text-primary-500 hover:underline"
        >
          {{ item.cdb_id }}
        </Link>
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

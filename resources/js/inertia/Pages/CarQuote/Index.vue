<script setup>
import LeadAssignment from '../PersonalQuote/Partials/LeadAssignment';
import FollowUpModal from './Partials/FollowUpModal.vue';

const notification = useToast();

defineProps({
  quotes: Object,
  quoteStatuses: Array,
  quoteBatches: Object,
  advisors: Array,
  kyoEndPoint: String,
});

const quoteType = 'car';
const isLoading = ref(false);
const hasAnyRole = roles => useHasAnyRole(roles);
const advisorOptions = computed(() => {
  return page.props.advisors.map(advisor => ({
    value: advisor.id,
    label: advisor.name,
  }));
});

const dateFormat = date => {
  return date ? useDateFormat(date, 'DD-MM-YYYY').value : '-';
};

const page = usePage();

const hasRole = role => useHasRole(role);
const rolesEnum = page.props.rolesEnum;

const showFollowUpModal = ref(false);
const disableFollowUp = computed(() => {
  if (!page.props.quotes.data) return true;
  else return page.props.quotes?.data?.length == 0 ?? false;
});
const loader = reactive({
  table: false,
  export: false,
});

let availableFilters = {
  code: '',
  first_name: '',
  last_name: '',
  email: '',
  previous_quote_policy_number: '',
  renewal_batch: '',
  quote_batch_id: '',
  page: 1,
};

const filters = reactive(availableFilters);
function onSubmit(isValid) {
  if (isValid) {
    filters.page = 1;

    const hasAnyFilterSelected = Object.values(filters).some(
      value => value !== '' && value.length > 0,
    );

    if (!hasAnyFilterSelected) {
      notification.error({
        title: 'Please select at least one filter before submitting',
        position: 'top',
      });
      return; // Stop the form submission
    }

    Object.keys(filters).forEach(
      key =>
        (filters[key] === '' || filters[key].length === 0) &&
        delete filters[key],
    );

    router.visit('/personal-quotes/car/car-quotes-search', {
      method: 'get',
      data: filters,
      preserveState: true,
      preserveScroll: true,
      onBefore: () => (loader.table = true),
      onSuccess: () => (loader.table = false),
    });
  } else {
    notification.error({
      title: 'Error while fetching quotes. Please try again',
      position: 'top',
    });
  }
}

function onReset() {
  router.visit('/personal-quotes/car/car-quotes-search', {
    method: 'get',
    preserveScroll: true,
    onBefore: () => (loader.table = true),
    onSuccess: () => (loader.table = false),
  });
}

function setQueryStringFilters() {
  let queryString = window.location.search;
  let urlParams = new URLSearchParams(queryString);

  for (const [key] of Object.entries(availableFilters)) {
    if (urlParams.has(key)) {
      filters[key] = urlParams.get(key);
    }
  }
}

onMounted(() => {
  setQueryStringFilters();
});

const tableHeader = [
  { text: 'Ref-ID', value: 'code' },
  { text: 'FIRST NAME', value: 'first_name' },
  { text: 'LAST NAME', value: 'last_name' },
  { text: 'SOURCE', value: 'source' },
  { text: 'NATIONALITY', value: 'nationality' },
  { text: 'CAR MAKE', value: 'car_make' },
  { text: 'CAR MODEL', value: 'car_model' },
  { text: 'CAR MODEL YEAR', value: 'year_of_manufacture' },
  { text: 'TYPE OF CAR INSURANCE', value: 'car_type_insurance_id' },
  { text: 'CURRENTLY INSURED WITH', value: 'insurance_provider' },
  { text: 'CREATED DATE', value: 'created_at' },
  {
    text: 'ADVISOR ASSIGNED DATE',
    value: 'advisor_assigned_date',
  },
  { text: 'ADVISOR', value: 'advisor' },
];

const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;

const quotesSelected = ref([]),
  assignAdvisor = ref(null),
  assignmentType = ref(null),
  isDisabled = ref(false);

const manualAssignmentSuccess = () => {
  quotesSelected.value = [];
  notification.success({
    title: `${quoteType.capitalizeFirstChar()} Manual Leads Assigned`,
    position: 'top',
  });
};

const manualAssignmentError = () => {
  notification.error({
    title: 'Manual Assignment Failed',
    position: 'top',
  });
};

const sendtemplateForm = data => {
  isLoading.value = true;
  data.renewal_batch = filters.renewal_batch;
  data.quote_batch_id = filters.quote_batch_id;
  axios
    .post(`${page.props.kyoEndPoint}/workflows`, data)
    .then(response => {
      showFollowUpModal.value = false;
      notification.success({
        title: response.data.message,
        position: 'top',
      });
    })
    .catch(error => {
      notification.error({
        title: 'Error! Sending Follow up emails',
        position: 'top',
      });
    })
    .finally(() => {
      isLoading.value = false;
    });
};

const openFollowUpModal = () => {
  let title =
    !filters.renewal_batch && !filters.quote_batch_id
      ? 'Please select quote batch or renewl batch'
      : filters.renewal_batch && filters.quote_batch_id
        ? 'Please select either quote batch or renewl batch'
        : '';
  if (title) notification.error({ title: title, position: 'top' });
  else showFollowUpModal.value = true;
};

const followupLead = reactive({
  modal: false,
  type: '',
});

const sendFollowupLead = () => {
  router.post(route('event-followups-new-business'), {
    followup_type: followupLead.type,
    uuids: quotesSelected.value.map(e => e.uuid),
  });
  followupLead.modal = false;
};
const openNBFollowupModal = () => {
  followupLead.modal = true;
};
const nbFollowupTemplates = [
  { value: 'nb_template_holiday', label: 'Holiday Template' },
  {
    value: 'nb_template_followup_1',
    label:
      'Follow up 1: Preview Text: Time-Sensitive: Secure Your Motor Insurance Today!',
  },
  {
    value: 'nb_template_followup_2',
    label: 'Follow up 2: Preview Text: Complete Your Motor Insurance Today!',
  },
  {
    value: 'nb_template_followup_3',
    label:
      "Follow up 3: Preview Text: Making Sure You Don't Miss Out on Motor Insurance Coverage",
  },
];
</script>

<template>
  <div>
    <Head title="Car Search" />

    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">Search</h2>
      <x-button
        v-if="can(permissionsEnum.CarQuotesCreate)"
        size="sm"
        color="#ff5e00"
        href="/quotes/car/create"
      >
        Create Lead
      </x-button>
    </div>
    <x-divider class="my-4" />

    <!--   filters     -->
    <x-form @submit="onSubmit" :auto-focus="false">
      <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-4">
        <div>
          <x-tooltip placement="bottom">
            <label
              class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-600"
            >
              Ref-ID
            </label>
            <template #tooltip> Reference ID </template>
          </x-tooltip>
          <x-input
            v-model="filters.code"
            type="search"
            name="code"
            class="w-full"
            placeholder="Search by Ref-ID"
          />
        </div>
        <x-input
          v-model="filters.email"
          type="search"
          name="email"
          label="Email"
          class="w-full"
          placeholder="Search by Email"
        />
        <x-input
          v-model="filters.previous_quote_policy_number"
          type="search"
          name="previous_quote_policy_number"
          label="Policy Number"
          class="w-full"
          placeholder="Search by Policy Number"
        />

        <x-input
          v-model="filters.renewal_batch"
          type="search"
          name="renewal_batch"
          label="Renewal Batch"
          class="w-full"
          placeholder="Search by Renewal Batch"
        />

        <x-field label="Quote Batch">
          <ComboBox
            v-model="filters.quote_batch_id"
            placeholder="Search by Quote Batch"
            :options="
              quoteBatches.map(quoteBatch => ({
                value: quoteBatch.id,
                label: quoteBatch.name,
              }))
            "
          />
        </x-field>
      </div>
      <div class="flex justify-end gap-3 mb-5">
        <x-button size="sm" color="#ff5e00" type="submit">Search</x-button>
        <x-button size="sm" color="primary" @click.prevent="onReset">
          Reset
        </x-button>
      </div>

      <div
        class="flex justify-end gap-3 mb-4 mt-4"
        v-show="
          hasRole(rolesEnum.CarManager) &&
          can(permissionsEnum.PAUSE_AUTO_FOLLOWUPS)
        "
      >
        <x-button
          size="sm"
          color="#ff5e00"
          type="button"
          :disabled="disableFollowUp"
          @click.prevent="openFollowUpModal"
          >Send Followup Emails
        </x-button>
      </div>
      <div class="flex gap-3 mt-4 mb-4">
        <div
          class="ml-auto"
          v-if="
            quotesSelected.length > 0 &&
            hasAnyRole([
              rolesEnum.CarManager,
              rolesEnum.Admin,
              rolesEnum.Engineering,
            ])
          "
        >
          <x-button
            size="sm"
            color="#ff5e00"
            @click.prevent="openNBFollowupModal"
          >
            Send NB Followup Email
          </x-button>
        </div>
      </div>
    </x-form>
    <FollowUpModal
      v-model:modelValue="showFollowUpModal"
      :isLoading="isLoading"
      @sendTemplateForm="form => sendtemplateForm(form)"
    />

    <Transition name="fade">
      <div v-if="quotesSelected.length > 0" class="mb-4">
        <LeadAssignment
          :selected="quotesSelected.map(e => e.id)"
          :advisors="advisorOptions"
          :quoteType="quoteType"
          @success="manualAssignmentSuccess"
          @error="manualAssignmentError"
        />
      </div>
    </Transition>

    <div class="mb-4 font-bold">Total Records : {{ quotes.total || 0 }}</div>
    <DataTable
      v-model:items-selected="quotesSelected"
      table-class-name="tablefixed"
      :loading="loader.table"
      :headers="tableHeader"
      :items="quotes.data || []"
      border-cell
      hide-rows-per-page
      hide-footer
    >
      <template #item-code="{ code, uuid }">
        <a
          :href="route('car.show', uuid)"
          target="_blank"
          rel="noopener noreferrer"
          class="text-primary-500 hover:underline"
        >
          {{ code }}
        </a>
      </template>

      <template #item-advisor="{ advisor }">
        {{ advisor?.name }}
      </template>

      <template #item-insurance_provider="{ insurance_provider }">
        {{ insurance_provider?.text }}
      </template>

      <template #item-car_type_insurance_id="{ car_type_insurance_id }">
        {{ car_type_insurance_id?.text }}
      </template>

      <template #item-car_make="{ car_make }">
        {{ car_make?.text }}
      </template>

      <template #item-car_model="{ car_model }">
        {{ car_model?.text }}
      </template>

      <template #item-nationality="{ nationality }">
        {{ nationality?.text }}
      </template>

      <template #item-advisor_assigned_date="item">
        {{ item?.car_quote_request_detail?.advisor_assigned_date }}
      </template>
    </DataTable>

    <Pagination
      v-if="quotes.total > 0"
      :links="{
        next: quotes.next_page_url,
        prev: quotes.prev_page_url,
        current: quotes.current_page,
        from: quotes.from,
        to: quotes.to,
        total: quotes.total,
      }"
    />

    <x-modal
      v-model="followupLead.modal"
      size="md"
      title="Send Followup Lead"
      show-close
      backdrop
    >
      <div class="w-full grid gap-5 mb-4">
        <p class="text-md font-bold text-gray-500">
          Please select one of the templates provided below to use:
        </p>
      </div>
      <div
        class="w-full grid gap-5"
        v-for="item in nbFollowupTemplates"
        :key="item.value"
      >
        <x-form-group v-model="followupLead.type">
          <x-radio :value="item.value" :label="item.label" />
        </x-form-group>
      </div>
      <template #actions>
        <x-button
          ghost
          tabindex="-1"
          size="md"
          type="button"
          @click.prevent="followupLead.modal = false"
        >
          Cancel
        </x-button>
        <x-button
          size="md"
          color="emerald"
          type="button"
          @click.prevent="sendFollowupLead"
        >
          OK
        </x-button>
      </template>
    </x-modal>
  </div>
</template>

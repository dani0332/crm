<script setup>
import QuoteFilters from '@/inertia/Pages/CarShared/QuoteFilters.vue';
import LeadAssignment from '@/inertia/Pages/PersonalQuote/Partials/LeadAssignment.vue';

defineProps({
  quotes: Object,
  leadStatuses: Object,
});

const page = usePage();
const toast = useToast();

const loader = reactive({
  table: false,
  export: false,
});

const dateFormat = (date, with_time = true) => {
  if (!date) return '';
  let date_time_format = with_time ? 'DD-MM-YYYY H:i:s' : 'DD-MM-YYYY';
  return useDateFormat(date, date_time_format);
};

const quotesSelected = ref([]),
  assignAdvisor = ref(null),
  assignmentType = ref(null),
  isDisabled = ref(false);

const tableHeader = [
  { text: 'CDB ID', value: 'code' },
  { text: 'BATCH', value: 'batch' },
  { text: 'FIRST NAME', value: 'first_name' },
  { text: 'LAST NAME', value: 'last_name' },
  { text: 'DATE OF BIRTH', value: 'dob' },
  { text: 'LEAD SOURCE', value: 'source' },
  { text: 'NATIONALITY', value: 'nationality' },
  { text: 'UAE LICENCE HELD FOR', value: 'uae_license_held_for' },
  { text: 'CAR MAKE', value: 'car_make' },
  { text: 'CAR MODEL', value: 'car_model' },
  { text: 'CAR MODEL YEAR', value: 'year_of_manufacture' },
  { text: 'FIRST REGISTRATION DATE', value: 'year_of_first_registration' },
  { text: 'CAR VALUE', value: 'car_value' },
  { text: 'VEHICLE TYPE', value: 'vehicle_type' },
  { text: 'TYPE OF CAR INSURANCE', value: 'car_type_insurance' },
  { text: 'CURRENTLY INSURED WITH', value: 'currently_insured_with' },
  { text: 'CLAIM HISTORY', value: 'claim_history' },
  { text: 'CREATED DATE', value: 'created_at' },
  { text: 'ADVISOR ASSIGNED DATE', value: 'advisor_assign_date' },
  { text: 'LEAD STATUS', value: 'quote_status' },
  { text: 'PAYMENT STATUS', value: 'payment_status' },
  { text: 'ECOMMERCE', value: 'is_ecommerce' },
  { text: 'TIER NAME', value: 'tier' },
  { text: 'VISIT COUNT', value: 'quote_view_count' },
  { text: 'FOLLOW UP DATE', value: 'follow_up_date' },
  { text: 'LAST MODIFIED DATE', value: 'updated_at' },
  { text: 'UPDATED BY', value: 'updated_by' },
  { text: 'ADDITIONAL NOTES', value: 'additional_notes' },
  { text: 'ADVISOR', value: 'advisor' },
  { text: 'POLICY NUMBER', value: 'policy_number' },
  { text: 'POLICY EXPIRY DATE', value: 'policy_expiry_date' },
  { text: 'IS GCC STANDARD', value: 'is_gcc_standard' },
  { text: 'PREMIUM', value: 'premium' },
  { text: 'LOST REASON', value: 'lost_reason' },
  { text: 'QUOTE LINK', value: 'quote_link' },
];

const advisorOptions = computed(() => {
  return page.props.leadStatuses.advisors.map(advisor => ({
    value: advisor.id,
    label: advisor.name,
  }));
});
</script>

<template>
  <QuoteFilters dynamic_route="revival" :lead-statuses="leadStatuses" />

  <Transition name="fade">
    <div v-if="quotesSelected.length > 0" class="mb-4">
      <LeadAssignment
        :selected="quotesSelected.map(e => e.id)"
        :advisors="advisorOptions"
        model_type="car"
      />
    </div>
  </Transition>

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
      <Link
        :href="`/quotes/revival/${uuid}`"
        class="text-primary-500 hover:underline"
      >
        {{ code }}
      </Link>
    </template>
    <template #item-dob="{ dob }">
      {{ dateFormat(dob, false).value }}
    </template>
    <template #item-batch="{ batch }">
      {{ batch?.name }}
    </template>
    <template #item-nationality="{ nationality }">
      {{ nationality?.text }}
    </template>
    <template #item-uae_license_held_for="{ uae_license_held_for }">
      {{ uae_license_held_for?.text }}
    </template>
    <template #item-car_make="{ car_make }">
      {{ car_make?.text }}
    </template>
    <template #item-car_model="{ car_model }">
      {{ car_model?.text }}
    </template>
    <template #item-vehicle_type="{ vehicle_type }">
      {{ vehicle_type?.text }}
    </template>
    <template #item-car_type_insurance="{ car_type_insurance }">
      {{ car_type_insurance?.text }}
    </template>
    <template #item-currently_insured_with="{ currently_insured_with }">
      {{ currently_insured_with?.text }}
    </template>
    <template #item-claim_history="{ claim_history }">
      {{ claim_history?.text }}
    </template>
    <template #item-advisor_assign_date="{ car_quote_request_detail }">
      {{ car_quote_request_detail?.advisor_assigned_date }}
    </template>
    <template #item-quote_status="{ quote_status }">
      {{ quote_status?.text }}
    </template>
    <template #item-payment_status="{ payment_status }">
      {{ payment_status?.text }}
    </template>
    <template #item-is_ecommerce="{ is_ecommerce }">
      <div class="text-center">
        <x-tag size="sm" :color="is_ecommerce ? 'success' : 'error'">
          {{ is_ecommerce ? 'Yes' : 'No' }}
        </x-tag>
      </div>
    </template>
    <template #item-tier="{ tier }">
      {{ tier?.name }}
    </template>
    <template #item-quote_view_count="{ quote_view_count }">
      {{ quote_view_count?.visit_count }}
    </template>
    <template #item-follow_up_date="{ car_quote_request_detail }">
      {{ car_quote_request_detail?.next_followup_date }}
    </template>
    <template #item-advisor="{ advisor }">
      {{ advisor?.name }}
    </template>
    <template #item-lost_reason="{ car_quote_request_detail }">
      {{ car_quote_request_detail?.lost_reason?.text }}
    </template>
    <template #item-is_gcc_standard="{ is_gcc_standard }">
      <div class="text-center">
        <x-tag size="sm" :color="is_gcc_standard ? 'success' : 'error'">
          {{ is_gcc_standard ? 'Yes' : 'No' }}
        </x-tag>
      </div>
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
</template>

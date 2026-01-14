<script setup>
import { createReusableTemplate } from '@vueuse/core';

const emit = defineEmits(['update', 'close']);

const props = defineProps({
  modelValue: Boolean,
  planDetails: Object,
  quote: Object,
  lockLeadSectionsDetails: Object,
  lookUpData: Object,
  localLookups: Object,
});

const page = usePage();
const notification = useNotifications('toast');

// Define tabs for plan details modal
const planDetailsTabs = ref([
  { index: 0, label: 'Plan Details' },
  { index: 1, label: 'Addons and Riders' },
  { index: 2, label: 'Included Benefits' },
  { index: 3, label: 'Eligibility' },
  { index: 4, label: 'Charges' },
  { index: 5, label: 'Plan Documents' },
]);

// Get the plan data from localLookups.providerPlans for eligibilities and currency coverages
const providerPlanData = computed(() => {
  if (!props.planDetails?.planId && !props.planDetails?.id) return null;
  const planId = props.planDetails.planId || props.planDetails.id;
  return props.localLookups?.providerPlans?.find(p => p.id === planId) || null;
});

// Currency Options - from plan's currency_coverages or fallback to localLookups
const currencyOptions = computed(() => {
  // If plan has currency coverages, use them
  if (providerPlanData.value?.currency_coverages?.length) {
    return providerPlanData.value.currency_coverages
      .filter(cc => cc.currency)
      .map(cc => ({
        value: cc.currency.id,
        label: cc.currency.text || cc.currency.code,
      }));
  }

  // Fallback to localLookups currencies
  return (
    props.localLookups?.currencies?.map(item => ({
      value: item.code || item.id,
      label: item.text,
    })) || [
      { value: 'AED', label: 'AED' },
      { value: 'USD', label: 'USD' },
      { value: 'EUR', label: 'EUR' },
      { value: 'GBP', label: 'GBP' },
    ]
  );
});

// Investment Frequency Options - filtered by plan's investment_frequency
const investmentFrequencyOptions = computed(() => {
  const allFrequencies = props.localLookups?.investmentFrequencies?.map(
    item => ({
      value: item.id,
      label: item.text,
    }),
  ) || [
    { value: 'regular', label: 'Regular' },
    { value: 'lumpsum', label: 'Lumpsum' },
  ];

  // If plan has investment_frequency, show only that option
  if (providerPlanData.value?.investment_frequency) {
    const planFrequencyId = providerPlanData.value.investment_frequency;
    const filtered = allFrequencies.filter(f => f.value === planFrequencyId);
    return filtered.length ? filtered : allFrequencies;
  }

  return allFrequencies;
});

// Payment Term Options - from plan's eligibilities (type = PAYMENT_TERM)
const paymentTermOptions = computed(() => {
  // If plan has eligibilities with PAYMENT_TERM type, use them
  if (providerPlanData.value?.eligibilities?.length) {
    const planPaymentTerms = providerPlanData.value.eligibilities
      .filter(e => e.type === 'PAYMENT_TERM')
      .map(e => ({
        value: e.code?.toLowerCase(),
        label: e.text || e.code,
      }));

    if (planPaymentTerms.length) {
      return planPaymentTerms;
    }
  }

  // Fallback to localLookups payment terms
  return (
    props.localLookups?.paymentTerms?.map(item => ({
      value: item.code,
      label: item.text,
    })) || [
      { value: 'monthly', label: 'Monthly' },
      { value: 'quarterly', label: 'Quarterly' },
      { value: 'semi_annually', label: 'Semi-Annually' },
      { value: 'annually', label: 'Annually' },
      { value: 'single_payment', label: 'Single Payment' },
    ]
  );
});

// Form for individual plan updates
const planForm = useForm({
  quote_uuid: '',
  plan_id: '',
  provider_name: '',
  actual_premium: 0,
  insurer_quote_no: '',
  is_disabled: false,
  is_manual_update: false,
  current_url: '',
});

// Calculate functionality placeholder
const calculatePlan = () => {
  notification.info({
    title: 'Calculate functionality will be implemented',
    position: 'top',
  });
};

// Create reusable template for manual toggle
const [ToggleManualButtonTemplate, ToggleManualButtonReuseTemplate] =
  createReusableTemplate();

// Manual toggle state
const toggleManualLoader = ref(false);

const onToggleManual = () => {
  toggleManualLoader.value = true;
  setTimeout(() => {
    toggleManualLoader.value = false;
  }, 300);
};

const onUpdateIndividualPlan = () => {
  if (!props.planDetails) return;

  planForm.quote_uuid = props.quote.uuid;
  planForm.plan_id = props.planDetails.id;
  planForm.provider_name = props.planDetails.providerName;
  planForm.actual_premium = props.planDetails.actualPremium || 0;
  planForm.insurer_quote_no = props.planDetails.insurerQuoteNo || '';
  planForm.is_disabled = props.planDetails.isDisabled;
  planForm.is_manual_update = props.planDetails.isManualUpdate;
  planForm.current_url = usePage().url;

  planForm.post(route('savingsPlanUpdate'), {
    preserveScroll: true,
    onSuccess: () => {
      emit('update');
    },
    onError: errors => {
      Object.keys(errors).forEach(function (key) {
        notification.error({
          title: errors[key],
          position: 'top',
        });
      });
    },
  });
};

// Tooltip mappings for plan details
const PLAN_TOOLTIP_MAPPINGS = {
  eligibility: {
    'Entry age': 'Eligible age to buy the plan',
    'Policy term (years)': 'Policy duration available for the plan',
    'Minimum investment': 'Minimum amount of investment required',
  },
  includedBenefits: {
    'Flexible premium payments':
      'Monthly, quarterly, semi-annual, or annual options',
    'Added life insurance coverage': 'Financial security for loved ones',
    'Investment options': 'Wide range of investment funds managed by experts',
    'Rate of return': 'Potential growth on your investment',
  },
};

const getPlanDetailTooltip = (fieldText, section) => {
  const tooltips = PLAN_TOOLTIP_MAPPINGS[section];
  if (!tooltips || !fieldText) return null;

  for (const [key, value] of Object.entries(tooltips)) {
    if (fieldText.toLowerCase().includes(key.toLowerCase())) {
      return value;
    }
  }
  return null;
};

const getEligibilityTooltip = fieldText =>
  getPlanDetailTooltip(fieldText, 'eligibility');
const getIncludedBenefitsTooltip = fieldText =>
  getPlanDetailTooltip(fieldText, 'includedBenefits');

const modalVisible = computed({
  get: () => props.modelValue,
  set: val => emit('update:modelValue', val),
});

// Riders data storage
const ridersData = ref([]);

// Show riders section if the plan has riders
const showRiders = computed(() => {
  return ridersData.value.length > 0;
});

// Get rider details from API and populate ridersData
const getRiderDetails = async planId => {
  if (!planId) return;

  try {
    const res = await axios.get(`/personal-quotes/savings/riders/${planId}`);

    // Populate ridersData directly from API response
    ridersData.value = res.data.map(item => ({
      id: item.id,
      riderId: item.rider_id,
      text: item.rider?.text || 'Rider',
      code: item.rider?.code,
      active: 0,
      coverValue: 0,
      coverValue2: 0,
      inputRequired: item.input_required || false,
      inputType: item.input_type || null,
      coverType: item.cover_type || null,
      maxAge: item.max_age || null,
    }));
  } catch (error) {
    console.error('Error fetching rider details:', error);
    ridersData.value = [];
  }
};

// Watch for modal visibility and planDetails changes to fetch riders
watch(
  [() => props.modelValue, () => props.planDetails],
  ([newVisible, newPlanDetails]) => {
    if (newVisible && newPlanDetails) {
      const planId = newPlanDetails.planId || newPlanDetails.id;
      if (planId) {
        getRiderDetails(planId);
      }
    }
  },
  { immediate: true },
);
</script>

<template>
  <x-modal
    v-model="modalVisible"
    size="xl"
    :title="`${planDetails?.providerName} - ${planDetails?.name}`"
    show-close
    backdrop
  >
    <div v-if="planDetails" class="w-full no-border">
      <TabGroup>
        <TabList
          class="flex flex-row flex-wrap gap-2 rounded-xl bg-slate-100 p-1.5 w-full"
        >
          <Tab
            v-for="{ index, label } in planDetailsTabs"
            as="template"
            :key="index"
            v-slot="{ selected }"
          >
            <button
              :class="[
                'rounded-lg px-3 py-2 md:min-w-[15%] text-sm font-medium text-gray-800 transition duration-200 ease-in-out uppercase',
                'ring-white ring-opacity-60 ring-offset-2 ring-offset-primary-50 focus:outline-none focus:ring-2',
                selected
                  ? 'bg-white shadow text-primary-600'
                  : 'hover:bg-white/50',
              ]"
            >
              {{ label }}
            </button>
          </Tab>
        </TabList>

        <TabPanels class="mt-2 text-sm min-h-[50vh]">
          <!-- Plan Details Tab -->
          <TabPanel class="bg-white">
            <div class="p-6">
              <!-- Row 1: Toggle Controls -->
              <div class="grid md:grid-cols-2 gap-x-8 gap-y-4 mb-6">
                <div>
                  <x-toggle
                    v-model="planDetails.isDisabled"
                    color="default"
                    label="Hide Plan?"
                    :disabled="lockLeadSectionsDetails?.plan_selection"
                  />
                </div>
                <div>
                  <ToggleManualButtonTemplate v-slot="{ isDisabled }">
                    <x-toggle
                      v-model="planDetails.isManualUpdate"
                      color="success"
                      label="Manual"
                      :disabled="isDisabled"
                      @change="onToggleManual"
                      :loading="toggleManualLoader"
                    />
                  </ToggleManualButtonTemplate>

                  <x-tooltip
                    v-if="lockLeadSectionsDetails?.plan_selection"
                    placement="bottom"
                  >
                    <ToggleManualButtonReuseTemplate :isDisabled="true" />
                    <template #tooltip>
                      No further action allowed on issued policy, If changes are
                      required, such as increase in price, please proceed
                      through the 'Send Update' feature using the 'Correction of
                      Policy' option.
                    </template>
                  </x-tooltip>
                  <ToggleManualButtonReuseTemplate v-else />
                </div>
              </div>

              <!-- Row 2: Provider Name & Plan Name -->
              <div class="grid md:grid-cols-2 gap-x-8 gap-y-4 mb-4">
                <div class="flex items-center">
                  <span class="text-sm text-gray-600 w-36">Provider Name</span>
                  <span class="text-sm text-gray-900">{{
                    planDetails.providerName
                  }}</span>
                </div>
                <div class="flex items-center">
                  <span class="text-sm text-gray-600 w-36">Plan Name:</span>
                  <span class="text-sm text-gray-900">{{
                    planDetails.name
                  }}</span>
                </div>
              </div>

              <!-- Row 3: Plan Type & Currency -->
              <div class="grid md:grid-cols-2 gap-x-8 gap-y-4 mb-4">
                <div class="flex items-center">
                  <span class="text-sm text-gray-600 w-36">Plan Type</span>
                  <span class="text-sm text-gray-900">{{
                    planDetails.planTypeName
                  }}</span>
                </div>
                <div class="flex items-center">
                  <span class="text-sm text-gray-600 w-36">Currency</span>
                  <x-select
                    v-model="planDetails.currencyId"
                    :options="currencyOptions"
                    placeholder="Select Currency"
                    size="sm"
                    class="flex-1"
                    :disabled="
                      !planDetails.isManualUpdate ||
                      lockLeadSectionsDetails?.plan_selection
                    "
                  />
                </div>
              </div>

              <!-- Row 4: Price & Investment Frequency -->
              <div class="grid md:grid-cols-2 gap-x-8 gap-y-4 mb-4">
                <div class="flex items-center">
                  <span class="text-sm text-gray-600 w-36">Price</span>
                  <x-input
                    v-model="planDetails.actualPremium"
                    placeholder="Enter price"
                    size="sm"
                    type="number"
                    class="flex-1"
                    :disabled="
                      !planDetails.isManualUpdate ||
                      lockLeadSectionsDetails?.plan_selection
                    "
                  />
                </div>
                <div class="flex items-center">
                  <span class="text-sm text-gray-600 w-36"
                    >Investment Frequency</span
                  >
                  <x-select
                    v-model="planDetails.investmentCriteriaId"
                    :options="investmentFrequencyOptions"
                    placeholder="Select Frequency"
                    size="sm"
                    class="flex-1"
                    :disabled="
                      !planDetails.isManualUpdate ||
                      lockLeadSectionsDetails?.plan_selection
                    "
                  />
                </div>
              </div>

              <!-- Row 5: Payment Term & Expected Rate of Return -->
              <div class="grid md:grid-cols-2 gap-x-8 gap-y-4 mb-4">
                <div class="flex items-center">
                  <span class="text-sm text-gray-600 w-36">Payment Term</span>
                  <x-select
                    v-model="planDetails.paymentTerm"
                    :options="paymentTermOptions"
                    placeholder="Select Payment Term"
                    size="sm"
                    class="flex-1"
                    :disabled="
                      !planDetails.isManualUpdate ||
                      lockLeadSectionsDetails?.plan_selection
                    "
                  />
                </div>
                <div class="flex items-center">
                  <span class="text-sm text-gray-600 w-36"
                    >Expected Rate of Return (%)</span
                  >
                  <div class="flex-1 relative">
                    <x-input
                      v-model="planDetails.expectedRor"
                      placeholder="Enter rate"
                      size="sm"
                      type="number"
                      class="w-full"
                      :disabled="
                        !planDetails.isManualUpdate ||
                        lockLeadSectionsDetails?.plan_selection
                      "
                    />
                    <span
                      class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 text-sm"
                      >%</span
                    >
                  </div>
                </div>
              </div>

              <!-- Row 6: Tenure of Savings & Lumpsum Amount -->
              <div class="grid md:grid-cols-2 gap-x-8 gap-y-4 mb-4">
                <div class="flex items-center">
                  <span class="text-sm text-gray-600 w-36"
                    >Tenure of Savings (Years)</span
                  >
                  <x-input
                    v-model="planDetails.tenure"
                    placeholder="Enter tenure"
                    size="sm"
                    type="number"
                    class="flex-1"
                    :disabled="
                      !planDetails.isManualUpdate ||
                      lockLeadSectionsDetails?.plan_selection
                    "
                  />
                </div>
                <div class="flex items-center">
                  <span class="text-sm text-gray-600 w-36">Lumpsum Amount</span>
                  <x-input
                    v-model="planDetails.lumpSumPayout"
                    placeholder="Enter amount"
                    size="sm"
                    type="number"
                    class="flex-1"
                    :disabled="
                      !planDetails.isManualUpdate ||
                      lockLeadSectionsDetails?.plan_selection
                    "
                  />
                </div>
              </div>

              <!-- Row 7: Insurer Quote Number -->
              <div class="grid md:grid-cols-2 gap-x-8 gap-y-4 mb-6">
                <div class="flex items-center">
                  <span class="text-sm text-gray-600 w-36"
                    >Insurer Quote Number</span
                  >
                  <x-input
                    v-model="planDetails.insurerQuoteNo"
                    placeholder="Enter quote number"
                    size="sm"
                    class="flex-1"
                    :disabled="
                      !planDetails.isManualUpdate ||
                      lockLeadSectionsDetails?.plan_selection
                    "
                  />
                </div>
              </div>

              <!-- Bottom section with Total Price, dates and buttons -->
              <div class="border-t border-gray-200 pt-6 mt-6">
                <div class="flex justify-between items-end">
                  <div>
                    <span class="text-sm font-semibold text-gray-800"
                      >Total Price:</span
                    >
                    <span class="text-sm text-gray-900 ml-2"
                      >{{ planDetails.currency || 'AED' }}:
                      {{
                        planDetails.actualPremium
                          ? parseFloat(planDetails.actualPremium).toFixed(2)
                          : '0.00'
                      }}</span
                    >
                  </div>
                  <div class="text-right">
                    <div class="text-sm text-gray-600 mb-1">
                      <span class="font-medium">Created Date:</span>
                      <span class="ml-1">{{ quote.created_at }}</span>
                    </div>
                    <div class="text-sm text-gray-600 mb-4">
                      <span class="font-medium">Updated At:</span>
                      <span class="ml-1">{{ quote.updated_at }}</span>
                    </div>
                    <div class="space-x-3">
                      <x-button
                        color="orange"
                        size="sm"
                        type="button"
                        @click="calculatePlan"
                      >
                        Calculate
                      </x-button>
                      <x-button
                        color="primary"
                        size="sm"
                        :disabled="lockLeadSectionsDetails?.plan_selection"
                        @click="onUpdateIndividualPlan"
                        :loading="planForm.processing"
                      >
                        Update
                      </x-button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </TabPanel>

          <!-- Addons and Riders Tab -->
          <TabPanel>
            <div class="p-6">
              <!-- Add on Section -->
              <div class="mb-8">
                <h4 class="text-sm font-bold text-gray-800 mb-4">Add on</h4>
                <div
                  v-if="planDetails.addons && planDetails.addons.length > 0"
                  class="space-y-3"
                >
                  <div
                    v-for="addon in planDetails.addons"
                    :key="addon.id"
                    class="flex items-center"
                  >
                    <span class="text-sm text-gray-700 w-64">{{
                      addon.text
                    }}</span>
                    <span class="text-sm text-gray-900">{{ addon.value }}</span>
                  </div>
                </div>
                <div v-else class="text-sm text-gray-500">
                  No add-ons available
                </div>
              </div>

              <!-- RIDERS Section (Dynamic based on selected plan - same pattern as Life) -->
              <div v-if="showRiders" class="border-t border-gray-200 pt-5 mt-2">
                <div class="bg-gray-100 px-2 py-2 mb-4 rounded-lg">
                  <h4 class="font-bold text-gray-800 text-md">RIDERS</h4>
                </div>

                <div class="px-4">
                  <!-- Dynamic Riders -->
                  <div
                    v-for="rider in ridersData"
                    :key="rider.id"
                    class="flex w-full items-center mb-4 gap-4"
                  >
                    <!-- Rider Name -->
                    <div class="w-[20%]">
                      <span class="text-sm text-gray-700">{{
                        rider.text || 'Rider'
                      }}</span>
                      <span
                        v-if="rider.inputRequired"
                        class="ml-1 text-xs text-orange-500"
                        title="Input Required"
                        >*</span
                      >
                    </div>
                    <!-- Status -->
                    <div class="w-[15%]">
                      <span class="text-sm text-gray-500">{{
                        rider.active ? 'Included' : 'Optional'
                      }}</span>
                    </div>
                    <!-- Cover Input -->
                    <div class="w-[20%]">
                      <x-input
                        v-model="rider.coverValue"
                        type="number"
                        size="sm"
                        placeholder="0"
                        class="!mb-0 [&>label]:!mb-0"
                        :disabled="
                          !rider.active ||
                          lockLeadSectionsDetails?.plan_selection
                        "
                      />
                    </div>
                    <!-- Toggle -->
                    <div class="w-[15%] flex justify-center">
                      <x-toggle
                        v-model="rider.active"
                        color="success"
                        size="sm"
                        :disabled="lockLeadSectionsDetails?.plan_selection"
                      />
                    </div>
                    <!-- Price Input -->
                    <div class="w-[20%]">
                      <x-input
                        v-model="rider.coverValue2"
                        type="number"
                        size="sm"
                        placeholder="0"
                        class="!mb-0 [&>label]:!mb-0"
                        :disabled="
                          !rider.active ||
                          lockLeadSectionsDetails?.plan_selection
                        "
                      />
                    </div>
                  </div>

                  <div
                    v-if="!ridersData.length"
                    class="text-center text-gray-500 py-4"
                  >
                    No riders available for this plan
                  </div>
                </div>
              </div>

              <!-- No Riders Section -->
              <div v-else class="border-t border-gray-200 pt-5 mt-2">
                <div class="bg-gray-100 px-2 py-2 mb-4 rounded-lg">
                  <h4 class="font-bold text-gray-800 text-md">RIDERS</h4>
                </div>
                <div class="px-4 text-sm text-gray-500">
                  No riders available for this plan
                </div>
              </div>

              <!-- Bottom section with Total Price and Save button -->
              <div class="border-t border-gray-200 pt-6 mt-6">
                <div class="flex justify-between items-center">
                  <div>
                    <span class="text-sm font-semibold text-gray-800"
                      >Total Price:</span
                    >
                    <span class="text-sm text-gray-900 ml-2"
                      >{{ planDetails.currency || 'AED' }}:
                      {{
                        planDetails.actualPremium
                          ? parseFloat(planDetails.actualPremium).toFixed(2)
                          : '0.00'
                      }}</span
                    >
                  </div>
                  <div>
                    <x-button
                      color="primary"
                      size="sm"
                      :disabled="lockLeadSectionsDetails?.plan_selection"
                      @click="onUpdateIndividualPlan"
                      :loading="planForm.processing"
                    >
                      Save
                    </x-button>
                  </div>
                </div>
              </div>
            </div>
          </TabPanel>

          <!-- Included Benefits Tab -->
          <TabPanel>
            <div class="p-6">
              <div
                v-if="
                  planDetails.includedBenefits &&
                  planDetails.includedBenefits.length > 0
                "
                class="grid grid-cols-2 gap-x-8 gap-y-6"
              >
                <div
                  v-for="item in planDetails.includedBenefits"
                  :key="item.id"
                  class="grid grid-cols-2 gap-x-4"
                >
                  <div class="text-gray-700 font-medium text-sm">
                    <x-tooltip
                      placement="bottom"
                      v-if="getIncludedBenefitsTooltip(item.text)"
                    >
                      <span
                        class="underline decoration-dotted decoration-primary-700"
                      >
                        {{ item.text }}
                      </span>
                      <template #tooltip>{{
                        getIncludedBenefitsTooltip(item.text)
                      }}</template>
                    </x-tooltip>
                    <span v-else>{{ item.text }}</span>
                  </div>
                  <div class="text-gray-900 text-sm">
                    {{ item.value }}
                  </div>
                </div>
              </div>
              <div v-else class="text-center py-8 text-gray-500">
                No included benefits available
              </div>
            </div>
          </TabPanel>

          <!-- Eligibility Tab -->
          <TabPanel>
            <div class="p-6">
              <div
                v-if="
                  planDetails.eligibilities &&
                  planDetails.eligibilities.length > 0
                "
                class="grid grid-cols-2 gap-x-8 gap-y-6"
              >
                <div
                  v-for="item in planDetails.eligibilities"
                  :key="item.id"
                  class="grid grid-cols-2 gap-x-4"
                >
                  <div class="text-gray-700 font-medium text-sm">
                    <x-tooltip
                      placement="bottom"
                      v-if="getEligibilityTooltip(item.text)"
                    >
                      <span
                        class="underline decoration-dotted decoration-primary-700"
                      >
                        {{ item.text }}
                      </span>
                      <template #tooltip>{{
                        getEligibilityTooltip(item.text)
                      }}</template>
                    </x-tooltip>
                    <span v-else>{{ item.text }}</span>
                  </div>
                  <div class="text-gray-900 text-sm">
                    {{ item.value }}
                  </div>
                </div>
              </div>
              <div v-else class="text-center py-8 text-gray-500">
                No eligibility criteria available
              </div>
            </div>
          </TabPanel>

          <!-- Charges Tab -->
          <TabPanel>
            <div class="p-6">
              <div v-if="planDetails.charges">
                <!-- Regular premium charges Section -->
                <div
                  v-if="planDetails.charges.regularPremiumCharges"
                  class="mb-6"
                >
                  <h4
                    class="text-sm font-bold text-gray-800 mb-3 border-b border-dotted border-gray-300 pb-1"
                  >
                    Regular premium charges
                  </h4>
                  <div class="space-y-2 ml-2">
                    <div
                      v-for="(item, index) in planDetails.charges
                        .regularPremiumCharges"
                      :key="'rpm-' + index"
                      class="flex"
                    >
                      <span class="text-sm text-gray-700 w-64">{{
                        item.text
                      }}</span>
                      <span class="text-sm text-primary-600">{{
                        item.value
                      }}</span>
                    </div>
                  </div>
                </div>

                <!-- Policy administration charge -->
                <div
                  v-if="planDetails.charges.policyAdministrationCharge"
                  class="mb-6 flex"
                >
                  <span class="text-sm font-bold text-gray-800 w-64"
                    >Policy administration charge</span
                  >
                  <span class="text-sm text-primary-600 ml-4">{{
                    planDetails.charges.policyAdministrationCharge
                  }}</span>
                </div>

                <!-- Fund Charges -->
                <div v-if="planDetails.charges.fundCharges" class="mb-6 flex">
                  <span
                    class="text-sm font-bold text-gray-800 w-64 border-b border-dotted border-gray-300"
                    >Fund Charges</span
                  >
                  <a
                    v-if="planDetails.charges.fundChargesLink"
                    :href="planDetails.charges.fundChargesLink"
                    target="_blank"
                    class="text-sm text-primary-600 ml-4 hover:underline"
                    >{{ planDetails.charges.fundCharges }}</a
                  >
                  <span v-else class="text-sm text-primary-600 ml-4">{{
                    planDetails.charges.fundCharges
                  }}</span>
                </div>

                <!-- Transactional charges Section -->
                <div v-if="planDetails.charges.transactionalCharges">
                  <h4
                    class="text-sm font-bold text-gray-800 mb-3 border-b border-dotted border-gray-300 pb-1"
                  >
                    Transactional charges
                  </h4>
                  <div class="space-y-2 ml-2">
                    <div
                      v-for="(item, index) in planDetails.charges
                        .transactionalCharges"
                      :key="'tc-' + index"
                      class="flex"
                    >
                      <span class="text-sm text-gray-700 w-64">{{
                        item.text
                      }}</span>
                      <a
                        v-if="item.link"
                        :href="item.link"
                        target="_blank"
                        class="text-sm text-primary-600 hover:underline"
                        >{{ item.value }}</a
                      >
                      <span v-else class="text-sm text-primary-600">{{
                        item.value
                      }}</span>
                    </div>
                  </div>
                </div>

                <!-- Fallback for old format (array of charges) -->
                <div
                  v-if="
                    Array.isArray(planDetails.charges) &&
                    planDetails.charges.length > 0
                  "
                  class="space-y-3"
                >
                  <div
                    v-for="item in planDetails.charges"
                    :key="item.id"
                    class="flex"
                  >
                    <span class="text-sm text-gray-700 w-64">{{
                      item.text
                    }}</span>
                    <span class="text-sm text-primary-600">{{
                      item.value
                    }}</span>
                  </div>
                </div>
              </div>
              <div v-else class="text-center py-8 text-gray-500">
                No charges information available
              </div>
            </div>
          </TabPanel>

          <!-- Plan Documents Tab -->
          <TabPanel>
            <div class="p-4">
              <!-- Key Feature Documents Section -->
              <div
                v-if="
                  planDetails.keyFeatureDocument &&
                  planDetails.keyFeatureDocument.length > 0
                "
                class="mb-6"
              >
                <h4 class="text-sm font-semibold text-gray-700 mb-3">
                  Key Feature Documents
                </h4>
                <div class="flex flex-col space-y-3 w-fit">
                  <div
                    v-for="doc in planDetails.keyFeatureDocument"
                    :key="doc.id"
                    class="inline-flex items-center gap-2 px-4 py-3 bg-white border border-gray-200 rounded-lg shadow-sm hover:shadow-md transition-shadow duration-200"
                  >
                    <div class="flex-shrink-0">
                      <svg
                        class="w-6 h-6 text-red-500"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                      >
                        <path
                          fill-rule="evenodd"
                          d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z"
                          clip-rule="evenodd"
                        ></path>
                      </svg>
                    </div>
                    <div class="flex-shrink-0">
                      <x-tooltip placement="bottom">
                        <span
                          class="text-blue-600 font-medium underline decoration-dotted decoration-primary-700"
                          >{{ doc.text }}</span
                        >
                        <template #tooltip
                          >Summary of plan benefits and features</template
                        >
                      </x-tooltip>
                    </div>
                    <div class="flex-shrink-0">
                      <a
                        :href="doc.value"
                        target="_blank"
                        class="text-blue-600 hover:text-blue-800"
                      >
                        <svg
                          class="w-5 h-5"
                          fill="none"
                          stroke="currentColor"
                          viewBox="0 0 24 24"
                        >
                          <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                          ></path>
                        </svg>
                      </a>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Policy Wordings Section -->
              <div
                v-if="
                  planDetails.policyWordings &&
                  planDetails.policyWordings.length > 0
                "
              >
                <h4 class="text-sm font-semibold text-gray-700 mb-3">
                  Policy Wordings
                </h4>
                <div class="flex flex-col space-y-3 w-fit">
                  <div
                    v-for="doc in planDetails.policyWordings"
                    :key="doc.id"
                    class="inline-flex items-center gap-2 px-4 py-3 bg-white border border-gray-200 rounded-lg shadow-sm hover:shadow-md transition-shadow duration-200"
                  >
                    <div class="flex-shrink-0">
                      <svg
                        class="w-6 h-6 text-red-500"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                      >
                        <path
                          fill-rule="evenodd"
                          d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z"
                          clip-rule="evenodd"
                        ></path>
                      </svg>
                    </div>
                    <div class="flex-shrink-0">
                      <x-tooltip placement="bottom">
                        <span
                          class="text-blue-600 font-medium underline decoration-dotted decoration-primary-700"
                          >{{ doc.text }}</span
                        >
                        <template #tooltip
                          >Policy wordings for this savings plan</template
                        >
                      </x-tooltip>
                    </div>
                    <div class="flex-shrink-0">
                      <a
                        :href="doc.link"
                        target="_blank"
                        class="text-blue-600 hover:text-blue-800"
                      >
                        <svg
                          class="w-5 h-5"
                          fill="none"
                          stroke="currentColor"
                          viewBox="0 0 24 24"
                        >
                          <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                          ></path>
                        </svg>
                      </a>
                    </div>
                  </div>
                </div>
              </div>

              <!-- No documents message -->
              <div
                v-if="
                  (!planDetails.keyFeatureDocument ||
                    planDetails.keyFeatureDocument.length === 0) &&
                  (!planDetails.policyWordings ||
                    planDetails.policyWordings.length === 0)
                "
                class="text-center py-8 text-gray-500"
              >
                No plan documents available
              </div>
            </div>
          </TabPanel>
        </TabPanels>
      </TabGroup>
    </div>
  </x-modal>
</template>

<style scoped>
.no-border {
  border: none !important;
}

.no-border :deep(.tab-group),
.no-border :deep(.tab-list),
.no-border :deep(.tab-panel),
.no-border :deep(.tab-panels) {
  border: none !important;
}
</style>

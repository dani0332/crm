<script setup>
import { useSavingsPlans } from '@/inertia/Composables/useSavingsPlans';
import { cleanFormattedValueToFloat } from '@/inertia/Composables/utilities';

const notification = useNotifications('toast');
const props = defineProps({
  quote: Object,
  insuranceProviders: Array,
  availablePlans: Array,
  lookUpData: Object,
  localLookups: Object,
});

const page = usePage();

// Initialize useSavingsPlans composable
const {
  // State
  modals,
  // Lookup Options
  currencyOptions,
  investmentFrequencyOptions,
  paymentTermOptions,
  tenureOfSavingsOptions,
  planTypeOptions,
  insuranceProviderOptions,
  // Helpers
  isLumpsumFrequency: checkIsLumpsumFrequency,
  formatPrice,
  calculatePlanPayout,
  // Riders
  ridersData,
  showRiders,
  getRiderDetails,
  // Plans
  providerPlans,
  providerPlansLoading,
  fetchProviderPlans,
  // API
  createPlan,
  // Form Sync
  syncCurrencyId,
  syncTenureId,
  syncInvestmentFrequencyId,
  syncPaymentTermByFrequency,
} = useSavingsPlans({
  quote: props.quote,
  localLookups: computed(() => props.localLookups),
  lookUpData: computed(() => props.lookUpData),
  insuranceProviders: computed(() => props.insuranceProviders),
  notification,
});

// Display values for formatted inputs (format on blur only)
const investmentAmountDisplay = ref('');
const lumpsumPayoutDisplay = ref('');

// Sync form value while typing (without formatting)
watch(investmentAmountDisplay, val => {
  addPlanForm.investment_amount = cleanFormattedValueToFloat(val) || null;
});

watch(lumpsumPayoutDisplay, val => {
  addPlanForm.lumpsum_payout = cleanFormattedValueToFloat(val) || null;
});

// Handle blur - format the display value
const onInvestmentAmountBlur = () => {
  const value = addPlanForm.investment_amount;
  if (value) {
    investmentAmountDisplay.value = formatPrice(value);
  }
};

const onLumpsumPayoutBlur = () => {
  const value = addPlanForm.lumpsum_payout;
  if (value) {
    lumpsumPayoutDisplay.value = formatPrice(value);
  }
};

const isEmptyField = ref(false);

const { isRequired, maxPrice, minPrice } = useRules();

const isRequiredAllowZero = v =>
  (v !== null && v !== undefined && v !== '') || 'This field is required';

const addPlanForm = useForm({
  quote_uuid: page.props.quote.uuid,
  is_disabled: false,
  is_create: true,
  plan_type: null,
  insurance_provider_id: '',
  savings_plan_id: null,
  currency: 'AED',
  currency_id: 1,
  investment_amount: null,
  actual_premium: null, // Required by backend (same as investment_amount)
  investment_frequency: null,
  investment_frequency_id: null,
  payment_term: null,
  expected_rate_of_return: null,
  tenure_of_savings: null,
  tenure_id: null,
  lumpsum_payout: null,
  insurer_quote_no: '',
  is_manual_update: true,
  // Dynamic Riders - will be populated based on selected plan
  riders: [],
});

// Level 2: Plans filtered by Provider - fetched from API
const insuranceProviderPlanOptions = computed(() => {
  if (!addPlanForm.insurance_provider_id) return [];

  return providerPlans.value.map(plan => ({
    value: plan.id,
    label: plan.text || plan.code,
    ...plan, // Keep full plan data for auto-population
  }));
});

// Level 3: Auto-populate fields when plan is selected
const selectedPlanData = computed(() => {
  if (!addPlanForm.savings_plan_id) return null;
  return insuranceProviderPlanOptions.value.find(
    plan => plan.value === addPlanForm.savings_plan_id,
  );
});

// Watch for plan selection and fetch riders
watch(
  () => addPlanForm.savings_plan_id,
  newPlanId => {
    if (newPlanId) {
      getRiderDetails(newPlanId);

      // Auto-populate fields from selected plan if available
      if (selectedPlanData.value) {
        const plan = selectedPlanData.value;
        addPlanForm.currency = plan.currency || 'AED';
        addPlanForm.expected_rate_of_return = plan.expected_return || null;
      }
    } else {
      // Reset riders when no plan selected
      ridersData.value = [];
    }
  },
  { immediate: true },
);

// Helper to check if investment frequency is lumpsum type
const isLumpsumFrequency = computed(() => {
  return checkIsLumpsumFrequency(addPlanForm.investment_frequency);
});

// Payment Terms - filtered by Investment Frequency only (NOT dependent on plan)
const filteredPaymentTermOptions = computed(() => {
  const allTerms = paymentTermOptions.value;

  if (isLumpsumFrequency.value) {
    // Lumpsum → Single Payment only (filter by label)
    return allTerms.filter(term =>
      term.label?.toLowerCase().includes('single'),
    );
  } else if (addPlanForm.investment_frequency) {
    // Regular → All except Single Payment
    return allTerms.filter(
      term => !term.label?.toLowerCase().includes('single'),
    );
  }

  // If no investment frequency selected, show all
  return allTerms;
});

// Reset plan when plan type changes
watch(
  () => addPlanForm.plan_type,
  () => {
    addPlanForm.savings_plan_id = null;
    addPlanForm.insurance_provider_id = '';
  },
);

// Fetch plans when provider changes
watch(
  () => addPlanForm.insurance_provider_id,
  newProviderId => {
    addPlanForm.savings_plan_id = null;
    if (newProviderId) {
      fetchProviderPlans(newProviderId);
    } else {
      providerPlans.value = [];
    }
  },
);

// Reset payment term when investment frequency changes and sync ID
watch(
  () => addPlanForm.investment_frequency,
  newFreq => {
    // Sync investment frequency ID using composable helper
    syncInvestmentFrequencyId(addPlanForm, newFreq);

    // Auto-select payment term based on frequency using composable helper
    syncPaymentTermByFrequency(addPlanForm, newFreq);
  },
);

// Sync currency ID when currency changes
watch(
  () => addPlanForm.currency,
  () => {
    syncCurrencyId(addPlanForm, addPlanForm.currency);
  },
);

// Sync tenure ID when tenure changes
watch(
  () => addPlanForm.tenure_of_savings,
  () => {
    syncTenureId(addPlanForm, addPlanForm.tenure_of_savings);
  },
);

// Sync actual_premium with investment_amount (backend requires both)
watch(
  () => addPlanForm.investment_amount,
  newAmount => {
    addPlanForm.actual_premium = parseFloat(newAmount) || 0;
  },
);

const createQuotePlan = async isValid => {
  if (
    addPlanForm.insurance_provider_id === '' ||
    addPlanForm.insurance_provider_id === null
  ) {
    isEmptyField.value = true;
    return;
  } else {
    isEmptyField.value = false;
  }

  if (!isValid) return;

  try {
    addPlanForm.processing = true;
    await createPlan(addPlanForm, page.props.quote.uuid, {
      riders: ridersData.value,
    });
    addPlanForm.reset();
    ridersData.value = []; // Reset riders
  } catch (error) {
    console.error('Plan creation error:', error);
  } finally {
    modals.createPlan = false;
    addPlanForm.processing = false;
  }
};

const calculatePlan = () => {
  const result = calculatePlanPayout({
    actualPremium: addPlanForm.investment_amount,
    expectedRor: addPlanForm.expected_rate_of_return,
    tenure: addPlanForm.tenure_of_savings,
    paymentTerm: addPlanForm.payment_term,
    investmentFrequency: addPlanForm.investment_frequency,
  });

  if (result) {
    addPlanForm.lumpsum_payout = result.payout;
    lumpsumPayoutDisplay.value = result.formattedPayout; // Update display
  }
};
</script>

<template>
  <x-form @submit="createQuotePlan" :auto-focus="false">
    <!-- Row 1: Plan Type & Insurance Provider -->
    <div class="flex flex-wrap md:flex-nowrap gap-6 w-full pb-5">
      <div class="w-full md:w-1/2">
        <x-select
          v-model="addPlanForm.plan_type"
          :rules="[isRequired]"
          :options="planTypeOptions"
          placeholder="Select Plan Type"
          filterable
          required
          label="Plan Type"
        />
      </div>
      <div class="w-full md:w-1/2">
        <x-select
          v-model="addPlanForm.insurance_provider_id"
          :rules="[isRequired]"
          :options="insuranceProviderOptions"
          placeholder="Select Insurance Provider"
          filterable
          required
          label="Insurance Provider"
          :hasError="isEmptyField"
        />
      </div>
    </div>

    <!-- Row 2: Plan & Currency + Investment Amount -->
    <div class="flex flex-wrap md:flex-nowrap gap-6 w-full pb-5">
      <div class="w-full md:w-1/2">
        <x-select
          label="Plan"
          required
          v-model="addPlanForm.savings_plan_id"
          :rules="[isRequired]"
          :options="insuranceProviderPlanOptions"
          placeholder="Select Plan"
          class="w-full"
          :loading="providerPlansLoading"
        />
      </div>
      <div class="w-full md:w-1/2">
        <label class="block text-sm font-medium text-gray-700 mb-1">
          Currency <span class="text-red-500">*</span> &nbsp;&nbsp;&nbsp;
          Investment Amount <span class="text-red-500">*</span>
        </label>
        <div class="flex gap-2">
          <x-select
            v-model="addPlanForm.currency"
            :options="currencyOptions"
            placeholder="Currency"
            class="w-24"
            :rules="[isRequired]"
          />
          <x-input
            v-model="investmentAmountDisplay"
            @blur="onInvestmentAmountBlur"
            :rules="[isRequired]"
            class="flex-1"
            placeholder="0.00"
            type="text"
          />
        </div>
      </div>
    </div>

    <!-- Row 3: Investment Frequency & Payment Term -->
    <div class="flex flex-wrap md:flex-nowrap gap-6 w-full pb-5">
      <div class="w-full md:w-1/2">
        <x-select
          v-model="addPlanForm.investment_frequency"
          :rules="[isRequired]"
          :options="investmentFrequencyOptions"
          placeholder="Select Investment Frequency"
          required
          label="Investment Frequency"
        />
      </div>
      <div class="w-full md:w-1/2">
        <x-select
          v-model="addPlanForm.payment_term"
          :rules="[isRequiredAllowZero]"
          :options="filteredPaymentTermOptions"
          placeholder="Select Payment Terms"
          required
          label="Payment Term"
        />
      </div>
    </div>

    <!-- Row 4: Expected Rate of Return & Tenure of Savings -->
    <div class="flex flex-wrap md:flex-nowrap gap-6 w-full pb-5">
      <div class="w-full md:w-1/2">
        <label class="block text-sm font-medium text-gray-700 mb-1">
          Expected Rate of Return <span class="text-red-500">*</span>
        </label>
        <div class="relative">
          <x-input
            v-model="addPlanForm.expected_rate_of_return"
            :rules="[isRequired, maxPrice(100), minPrice(0)]"
            class="w-full"
            placeholder="Enter Expected Rate of Return"
            type="number"
            step="any"
            disabled
          />
          <span
            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 text-sm"
            >%</span
          >
        </div>
      </div>
      <div class="w-full md:w-1/2">
        <x-select
          v-model="addPlanForm.tenure_of_savings"
          :rules="[isRequired]"
          :options="tenureOfSavingsOptions"
          placeholder="Select Tenure of Savings"
          required
          label="Tenure of Savings (Years)"
        />
      </div>
    </div>

    <!-- Row 5: Lumpsum Payout & Insurer Quote Number -->
    <div class="flex flex-wrap md:flex-nowrap gap-6 w-full pb-5">
      <div class="w-full md:w-1/2">
        <x-input
          label="Lumpsum Payout"
          required
          v-model="lumpsumPayoutDisplay"
          @blur="onLumpsumPayoutBlur"
          :rules="[isRequired]"
          class="w-full"
          placeholder="0.00"
          type="text"
        />
      </div>
      <div class="w-full md:w-1/2">
        <x-input
          label="Insurer Quote Number"
          v-model="addPlanForm.insurer_quote_no"
          class="w-full"
          placeholder="Enter Insurer Quote Number"
          maxlength="50"
        />
      </div>
    </div>

    <!-- RIDERS Section (Dynamic based on selected plan) -->
    <div v-if="showRiders" class="border-t border-gray-200 pt-5 mt-2">
      <div class="bg-gray-100 px-2 py-2 mb-4 rounded-lg">
        <h4 class="font-bold text-gray-800 text-md">RIDERS</h4>
      </div>

      <div class="px-4">
        <!-- Dynamic Riders -->
        <div
          v-for="(rider, index) in ridersData"
          :key="index"
          class="flex w-full items-center mb-4 gap-4"
        >
          <!-- Rider Name -->
          <div class="w-[20%]">
            <span class="text-sm text-gray-700">{{ rider.text }}</span>
          </div>
          <!-- Status -->
          <div class="w-[15%]">
            <span class="text-sm text-gray-500">{{
              rider.active ? 'Included' : 'Optional'
            }}</span>
          </div>
          <!-- First Input (Cover Value) -->
          <div class="w-[20%]">
            <x-input
              v-model="rider.coverValue"
              type="number"
              size="sm"
              placeholder="0"
              class="!mb-0 [&>label]:!mb-0"
              :disabled="!rider.active"
              min="0"
            />
          </div>
          <!-- Toggle -->
          <div class="w-[15%] flex justify-center">
            <x-toggle v-model="rider.active" color="success" size="sm" />
          </div>
          <!-- Second Input (Price) -->
          <div class="w-[20%]">
            <x-input
              v-model="rider.price"
              type="number"
              size="sm"
              placeholder="0"
              class="!mb-0 [&>label]:!mb-0"
              :disabled="!rider.active"
              min="0"
            />
          </div>
        </div>

        <div v-if="!ridersData.length" class="text-center text-gray-500 py-4">
          No riders available for this plan
        </div>
      </div>
    </div>

    <!-- Action Buttons -->
    <div class="text-right space-x-4 pt-4">
      <x-button size="sm" color="orange" type="button" @click="calculatePlan">
        Calculate
      </x-button>
      <x-button
        size="sm"
        color="emerald"
        :loading="addPlanForm.processing"
        type="submit"
      >
        Save
      </x-button>
    </div>
  </x-form>
</template>

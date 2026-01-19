<script setup>
import { useSavingsCalculator } from '@/inertia/Composables/useSavingsCalculator';
import {
  cleanFormattedValueToFloat,
  useFormatPrice,
} from '@/inertia/Composables/utilities';
import { defineEmits, watchEffect } from 'vue';

const emit = defineEmits(['success', 'error']);

const notification = useNotifications('toast');
const props = defineProps({
  quote: Object,
  insuranceProviders: Array,
  availablePlans: Array,
  lookUpData: Object,
  localLookups: Object,
});

const page = usePage();

// Use savings calculator composable for lumpsum payout calculation
const { calculatePayout } = useSavingsCalculator();

// Display values for formatted inputs (format on blur only)
const investmentAmountDisplay = ref('');
const lumpsumPayoutDisplay = ref('');

// Format price with commas and 2 decimals
const formatPrice = value => {
  if (!value && value !== 0) return '';
  return useFormatPrice(value, true);
};

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

// Options for dynamically fetched data
const options = reactive({
  providerPlans: [],
  loading: false,
});

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

// Plan Type Options - from localLookups
const planTypeOptions = computed(() => {
  return (
    props.localLookups?.planTypes?.map(item => ({
      value: item.code || item.id,
      label: item.text,
    })) || [
      { value: 'savings', label: 'Savings' },
      { value: 'whole_of_life', label: 'Whole of Life' },
    ]
  );
});

// Currency Options - from selected plan's currency_coverages or fallback to localLookups
const currencyOptions = computed(() => {
  // Fallback to localLookups currencies
  return props.localLookups?.currencies?.map(item => ({
    value: item.code || item.id,
    label: item.text,
    id: item.id,
  }));
});

// Investment Frequency Options - from CMS (NOT dependent on plan)
const investmentFrequencyOptions = computed(() => {
  return (
    props.localLookups?.investmentFrequencies?.map(item => ({
      value: item.code?.toLowerCase() || item.text?.toLowerCase(),
      label: item.text,
      id: item.id,
    })) || [
      { value: 'regular', label: 'Regular', id: null },
      { value: 'lumpsum', label: 'Lumpsum', id: null },
    ]
  );
});

// Payment Term Options - from localLookups (using ID for API)
// Payment term API values: 0 = Single Payment, 1 = Annual, 3 = Quarterly, 6 = Semi-Annual, 12 = Monthly
const paymentTermOptions = computed(() => {
  return props.localLookups?.paymentTerms?.map(item => ({
    value: item.value,
    label: item.text,
  }));
});

// Tenure of Savings Options - from lookUpData or generate 1-30 years
const tenureOfSavingsOptions = computed(() => {
  if (props.lookUpData?.savingsTenure?.length) {
    return props.lookUpData.savingsTenure.map(item => ({
      value: parseInt(item.code) || parseInt(item.text) || item.id,
      label: item.text,
      id: item.id,
    }));
  }
  // Fallback: generate 1-30 years
  const options = [];
  for (let i = 1; i <= 30; i++) {
    options.push({ value: i, label: `${i} Year${i > 1 ? 's' : ''}`, id: null });
  }
  return options;
});

// Map payment term to frequency for calculator
// Payment term values: 0 = Lumpsum, 1 = Annual, 3 = Quarterly, 6 = Semi-Annual, 12 = Monthly
const getFrequencyFromPaymentTerm = term => {
  const termValue = parseInt(term);
  const map = {
    12: 'Monthly',
    3: 'Quarterly',
    6: 'Half Yearly',
    1: 'Yearly',
    0: 'Single Payment',
  };
  return map[termValue] || 'Monthly';
};

// Level 1: Insurance Provider Options (all providers for now)
const insuranceProviderOptions = computed(() => {
  return (
    props.insuranceProviders?.map(provider => ({
      value: provider.id,
      label: provider.text,
    })) || []
  );
});

// Level 2: Plans filtered by Provider - fetched from API
const insuranceProviderPlanOptions = computed(() => {
  if (!addPlanForm.insurance_provider_id) return [];

  return options.providerPlans.map(plan => ({
    value: plan.id,
    label: plan.text || plan.code,
    ...plan, // Keep full plan data for auto-population
  }));
});

// Fetch provider plans from API (like Life)
const fetchProviderPlans = () => {
  if (!addPlanForm.insurance_provider_id) {
    options.providerPlans = [];
    return;
  }

  options.loading = true;
  options.providerPlans = [];

  axios
    .get(
      `/personal-quotes/savings/provider-plans/${addPlanForm.insurance_provider_id}`,
    )
    .then(res => {
      if (res.data.plans) {
        // TODO: Filter out plans that already exist in availablePlans (commented for now)
        options.providerPlans = res.data.plans.filter(
          plan =>
            !props.availablePlans?.some(
              existingPlan => existingPlan.planId === plan.id,
            ),
        );
        options.providerPlans = res.data.plans;
      } else {
        options.providerPlans = [];
      }
    })
    .catch(err => {
      console.error('Error fetching provider plans:', err);
      notification.error({
        title: 'Failed to fetch plans',
        position: 'top',
      });
      options.providerPlans = [];
    })
    .finally(() => {
      options.loading = false;
    });
};

// Level 3: Auto-populate fields when plan is selected
const selectedPlanData = computed(() => {
  if (!addPlanForm.savings_plan_id) return null;
  return insuranceProviderPlanOptions.value.find(
    plan => plan.value === addPlanForm.savings_plan_id,
  );
});

const ridersData = ref([]);

const showRiders = computed(() => {
  return ridersData.value.length > 0;
});

const getRiderDetails = async planId => {
  try {
    const res = await axios.get(`/personal-quotes/savings/riders/${planId}`);

    // Map riders data like Life
    ridersData.value = res.data.map(rider => ({
      riderId: rider.rider_id,
      active: 0,
      price: 0,
      coverValue: 0,
      text: rider.rider?.text || 'Rider',
      inputRequired: rider.input_required,
    }));
  } catch (error) {
    console.error('Error fetching rider details:', error);
    notification.error({
      title: 'Error fetching rider details',
      position: 'top',
    });
    ridersData.value = [];
  }
};

watch(
  () => addPlanForm.savings_plan_id,
  newPlanId => {
    if (newPlanId) {
      getRiderDetails(newPlanId);

      // Auto-populate fields from selected plan if available
      // Note: Investment Frequency and Payment Terms are NOT based on plan
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
  const freq = addPlanForm.investment_frequency;
  if (!freq) return false;

  // Check by string value
  if (typeof freq === 'string' && freq.toLowerCase() === 'lumpsum') return true;

  // Check by ID - find the selected option and check its label
  const selectedOption = investmentFrequencyOptions.value.find(
    opt => opt.value === freq || opt.id === freq,
  );
  return selectedOption?.label?.toLowerCase() === 'lumpsum';
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
      fetchProviderPlans();
    } else {
      options.providerPlans = [];
    }
  },
);

// Reset payment term when investment frequency changes and sync ID
watch(
  () => addPlanForm.investment_frequency,
  newFreq => {
    // Sync investment frequency ID first
    const selectedOption = investmentFrequencyOptions.value.find(
      opt => opt.value === newFreq || opt.id === newFreq,
    );
    addPlanForm.investment_frequency_id = selectedOption?.id || null;

    // Check if lumpsum frequency
    const isLumpsum =
      selectedOption?.label?.toLowerCase() === 'lumpsum' ||
      (typeof newFreq === 'string' && newFreq.toLowerCase() === 'lumpsum');

    // Find Single Payment option by label
    const singlePaymentOption = paymentTermOptions.value.find(opt =>
      opt.label?.toLowerCase().includes('single'),
    );
    // Auto-select payment term based on frequency
    if (isLumpsum && singlePaymentOption) {
      addPlanForm.payment_term = singlePaymentOption.value; // Single Payment ID
    } else if (addPlanForm.payment_term === singlePaymentOption?.value) {
      addPlanForm.payment_term = null; // Reset if was single payment
    }
  },
);

// Sync currency ID when currency changes
watchEffect(
  () => addPlanForm.currency,
  () => {
    const selectedOption = currencyOptions.value.find(
      opt => opt.value === addPlanForm.currency,
    );
    addPlanForm.currency_id = selectedOption?.id || null;
  },
);

// Sync tenure ID when tenure changes
watch(
  () => addPlanForm.tenure_of_savings,
  () => {
    const selectedOption = tenureOfSavingsOptions.value.find(
      opt => opt.value === addPlanForm.tenure_of_savings,
    );
    addPlanForm.tenure_id = selectedOption?.id || null;
  },
);

// Sync actual_premium with investment_amount (backend requires both)
watch(
  () => addPlanForm.investment_amount,
  newAmount => {
    addPlanForm.actual_premium = parseFloat(newAmount) || 0;
  },
);

const createQuotePlan = isValid => {
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

  // Process riders data for API payload
  const processedRiders = ridersData.value.map(rider => ({
    riderId: rider.riderId,
    active: rider.active ? true : false,
    price: Number(parseFloat(rider.price).toFixed(2)) || 0,
    coverValue: Number(parseFloat(rider.coverValue).toFixed(2)) || 0,
  }));

  // Get investment frequency label for API
  const investmentFrequencyOption = investmentFrequencyOptions.value.find(
    opt => opt.value === addPlanForm.investment_frequency,
  );

  // Build API payload in expected format
  const apiPayload = {
    // CamelCase structure for Ken API
    quoteUID: page.props.quote.uuid,
    update: !addPlanForm.is_create, // false for new plan, true for update
    plans: [
      {
        planId: addPlanForm.savings_plan_id,
        plan_type: addPlanForm.plan_type,
        isDisabled: addPlanForm.is_disabled,
        isManualUpdate: addPlanForm.is_manual_update,
        insurerQuoteNo: addPlanForm.insurer_quote_no || '',
        investmentAmount: parseFloat(addPlanForm.investment_amount) || 0,
        currency: addPlanForm.currency,
        currencyId: addPlanForm.currency_id,
        paymentTerm: parseInt(addPlanForm.payment_term) || 0, // Ensure number
        tenure: addPlanForm.tenure_of_savings,
        tenureId: addPlanForm.tenure_id,
        ror: parseFloat(addPlanForm.expected_rate_of_return) || 0,
        investmentFrequency: investmentFrequencyOption?.label || 'Regular',
        investmentFrequencyId: addPlanForm.investment_frequency_id,
        lumpSumPayout: parseFloat(addPlanForm.lumpsum_payout) || 0,
        riders: processedRiders,
      },
    ],
  };

  // Submit using axios with camelCase payload
  axios
    .post(
      `/quotes/savings/${page.props.quote.uuid}/savings-plan-manual-process`,
      apiPayload,
    )
    .then(() => {
      notification.success({
        title: 'Savings plan created successfully',
        position: 'top',
      });
      emit('success');
      addPlanForm.reset();
      ridersData.value = []; // Reset riders
    })
    .catch(error => {
      notification.error({
        title: error.response?.data?.message || 'Failed to create plan',
        position: 'top',
      });
      emit('error', error.response?.data?.errors || {});
    });
};

const calculatePlan = () => {
  const amount = parseFloat(addPlanForm.investment_amount) || 0;
  const rate = parseFloat(addPlanForm.expected_rate_of_return) || 0;
  const years = parseInt(addPlanForm.tenure_of_savings) || 0;

  // Validate required fields
  if (!amount || !rate || !years) {
    notification.warning({
      title: 'Please fill Investment Amount, Rate of Return, and Tenure',
      position: 'top',
    });
    return;
  }

  // Determine frequency based on investment type
  const frequency = isLumpsumFrequency.value
    ? 'Single Payment'
    : getFrequencyFromPaymentTerm(addPlanForm.payment_term);

  // Calculate and set lumpsum payout
  const payout = calculatePayout({ amount, rate, years, frequency });
  addPlanForm.lumpsum_payout = payout;
  lumpsumPayoutDisplay.value = formatPrice(payout); // Update display

  notification.success({
    title: `Lumpsum Payout: ${formatPrice(payout)}`,
    position: 'top',
  });
};

const validateDecimal = event => {
  if (
    event.key === '.' ||
    event.key === 'Backspace' ||
    event.key === 'Delete'
  ) {
    return;
  }
  const regex = /^\d+(\.\d{0,2})?$/;
  if (!regex.test(event.key)) {
    event.preventDefault();
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
          :loading="options.loading"
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
          :rules="[isRequired]"
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
            @keydown="validateDecimal"
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
          :rules="[isRequired]"
          class="w-full"
          required
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
            <!-- <span
              v-if="rider.inputRequired"
              class="ml-1 text-xs text-orange-500"
              title="Input Required"
              >*</span
            > -->
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

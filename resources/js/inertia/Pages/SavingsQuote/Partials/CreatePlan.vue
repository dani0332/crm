<script setup>
import { useSavingsCalculator } from '@/inertia/Composables/useSavingsCalculator';
import {
  cleanFormattedValueToFloat,
  useFormatPrice,
} from '@/inertia/Composables/utilities';
import { defineEmits } from 'vue';

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

const quotePlansTable = reactive({
  columns: [
    { text: 'Provider Name', value: 'providerName' },
    { text: 'Plan Name', value: 'name' },
    { text: 'Investment Frequency', value: 'investmentFrequency' },
    { text: 'Price', value: 'actualPremium' },
  ],
});

const addPlanForm = useForm({
  quote_uuid: page.props.quote.uuid,
  is_disabled: 0,
  is_create: 1,
  plan_type: null,
  insurance_provider_id: '',
  savings_plan_id: null,
  currency: 'AED',
  investment_amount: null,
  investment_frequency: null,
  payment_term: null,
  expected_rate_of_return: null,
  tenure_of_savings: null,
  lumpsum_payout: null,
  insurer_quote_no: '',
  is_manual_update: true,
  // Riders
  life_cover_enabled: false,
  life_cover_value: 0,
  life_cover_value_2: 0,
  critical_illness_enabled: false,
  critical_illness_value: 0,
  critical_illness_value_2: 0,
  total_permanent_disability_enabled: false,
  total_permanent_disability_value: 0,
  total_permanent_disability_value_2: 0,
  waiver_of_premium_enabled: false,
  waiver_of_premium_value: 0,
  waiver_of_premium_value_2: 0,
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
  // If a plan is selected, use its currency coverages
  if (selectedPlanData.value?.currency_coverages?.length) {
    return selectedPlanData.value.currency_coverages
      .filter(cc => cc.currency) // Ensure currency relation is loaded
      .map(cc => ({
        value: cc.currency.code,
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

// Investment Frequency Options - from localLookups
const investmentFrequencyOptions = computed(() => {
  return (
    props.localLookups?.investmentFrequencies?.map(item => ({
      value: item.id,
      label: item.text,
    })) || [
      { value: 'regular', label: 'Regular' },
      { value: 'lumpsum', label: 'Lumpsum' },
    ]
  );
});

// Payment Term Options - from localLookups
const paymentTermOptions = computed(() => {
  return (
    props.localLookups?.paymentTerms?.map(item => ({
      value: item.code || item.id,
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

// Tenure of Savings Options - from lookUpData or generate 1-30 years
const tenureOfSavingsOptions = computed(() => {
  if (props.lookUpData?.savingsTenure?.length) {
    return props.lookUpData.savingsTenure.map(item => ({
      value: parseInt(item.code) || item.id,
      label: item.text,
    }));
  }
  // Fallback: generate 1-30 years
  const options = [];
  for (let i = 1; i <= 30; i++) {
    options.push({ value: i, label: `${i} Year${i > 1 ? 's' : ''}` });
  }
  return options;
});

// Show riders section only for "Whole of Life" plan type
const showRiders = computed(() => {
  return addPlanForm.plan_type === 'whole_of_life';
});

// Map payment term to frequency for calculator
const getFrequencyFromPaymentTerm = term => {
  const map = {
    monthly: 'Monthly',
    quarterly: 'Quarterly',
    semi_annual: 'Half Yearly',
    annual: 'Yearly',
  };
  return map[term] || 'Monthly';
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

// Level 2: Plans filtered by Provider + Plan Type
const insuranceProviderPlanOptions = computed(() => {
  if (!addPlanForm.insurance_provider_id) return [];

  let plans = props.localLookups?.providerPlans || [];

  // Filter by insurance provider
  plans = plans.filter(
    plan => plan.provider_id === addPlanForm.insurance_provider_id,
  );

  // Filter by plan type if selected (based on plan's code or text containing the type)
  // This filtering logic can be adjusted based on actual data structure
  if (addPlanForm.plan_type) {
    // For now, we'll show all plans from the provider
    // Adjust this if providerPlans has a plan_type_id field
  }

  return plans.map(plan => ({
    value: plan.id,
    label: plan.text || plan.code,
    ...plan, // Keep full plan data for auto-population
  }));
});

const isLoadingPlans = ref(false);

// Level 3: Auto-populate fields when plan is selected
const selectedPlanData = computed(() => {
  if (!addPlanForm.savings_plan_id) return null;
  return insuranceProviderPlanOptions.value.find(
    plan => plan.value === addPlanForm.savings_plan_id,
  );
});

// Watch for plan selection to auto-populate dependent fields
watch(
  () => addPlanForm.savings_plan_id,
  newPlanId => {
    if (newPlanId && selectedPlanData.value) {
      const plan = selectedPlanData.value;
      // Auto-populate fields from plan data if available
      // These can be adjusted based on actual plan data structure
      addPlanForm.currency = plan.currency || 'AED';
      addPlanForm.investment_frequency = plan.investment_frequency || null;
      addPlanForm.expected_rate_of_return = plan.expected_return || null;
      // addPlanForm.tenure_of_savings = plan.policyTerm || null;
    }
  },
);

// Level 4: Payment Terms from selected plan's eligibilities (type = PAYMENT_TERM)
const filteredPaymentTermOptions = computed(() => {
  // If a plan is selected, use its eligibilities for payment terms
  if (selectedPlanData.value?.eligibilities?.length) {
    const planEligibilities = selectedPlanData.value.eligibilities
      .filter(e => e.type === 'PAYMENT_TERM')
      .map(e => ({
        value: e.code?.toLowerCase(),
        label: e.text || e.code,
      }));

    if (planEligibilities.length) {
      // Further filter based on investment frequency
      if (addPlanForm.investment_frequency === 'lumpsum') {
        return planEligibilities.filter(
          term => term.value === 'single_payment',
        );
      } else if (addPlanForm.investment_frequency === 'regular') {
        return planEligibilities.filter(
          term => term.value !== 'single_payment',
        );
      }
      return planEligibilities;
    }
  }

  // Fallback to localLookups payment terms
  const allTerms = paymentTermOptions.value;

  if (addPlanForm.investment_frequency === 'lumpsum') {
    // Lumpsum → Single Payment only
    return allTerms.filter(term => term.value === 'single_payment');
  } else if (addPlanForm.investment_frequency === 'regular') {
    // Regular → Monthly, Quarterly, Semi-Annually, Annually (exclude Single Payment)
    return allTerms.filter(term => term.value !== 'single_payment');
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

// Reset plan when provider changes
watch(
  () => addPlanForm.insurance_provider_id,
  () => {
    addPlanForm.savings_plan_id = null;
  },
);

// Reset payment term when investment frequency changes
watch(
  () => addPlanForm.investment_frequency,
  newFreq => {
    // Auto-select payment term based on frequency
    if (newFreq === 'lumpsum') {
      addPlanForm.payment_term = 'single_payment';
    } else if (
      newFreq === 'regular' &&
      addPlanForm.payment_term === 'single_payment'
    ) {
      addPlanForm.payment_term = null; // Reset if was single payment
    }
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

  addPlanForm.post(
    `/quotes/savings/${page.props.quote.uuid}/savings-plan-manual-process`,
    {
      preserveScroll: true,
      onSuccess: () => {
        notification.success({
          title: 'Savings plan created successfully',
          position: 'top',
        });
        emit('success');
        addPlanForm.reset();
      },
      onError: errors => {
        notification.error({
          title: 'Failed to create plan',
          position: 'top',
        });
        emit('error', errors);
      },
    },
  );
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
  const frequency =
    addPlanForm.investment_frequency === 'lumpsum'
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
          :loading="isLoadingPlans"
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
          required
          v-model="addPlanForm.insurer_quote_no"
          :rules="[isRequired]"
          class="w-full"
          placeholder="Enter Insurer Quote Number"
          maxlength="50"
        />
      </div>
    </div>

    <!-- RIDERS Section (Only for Whole of Life) -->
    <div v-if="showRiders" class="border-t border-gray-200 pt-5 mt-2">
      <div class="bg-gray-100 px-2 py-2 mb-4 rounded-lg">
        <h4 class="font-bold text-gray-800 text-md">RIDERS</h4>
      </div>

      <div class="px-4">
        <!-- Life Cover -->
        <div class="flex items-center gap-4 mb-4">
          <div class="w-[20%]">
            <span class="text-sm text-gray-700">Life Cover</span>
          </div>
          <div class="w-[15%]">
            <span class="">Included</span>
          </div>
          <div class="w-[20%]">
            <x-input
              v-model="addPlanForm.life_cover_value"
              type="number"
              size="sm"
              placeholder="0"
            />
          </div>
          <div class="w-[15%] flex justify-center">
            <x-toggle
              v-model="addPlanForm.life_cover_enabled"
              color="success"
              size="sm"
            />
          </div>
          <div class="w-[20%]">
            <x-input
              v-model="addPlanForm.life_cover_value_2"
              type="number"
              size="sm"
              placeholder="0"
            />
          </div>
        </div>

        <!-- Critical Illness -->
        <div class="flex items-center gap-4 mb-4">
          <div class="w-[20%]">
            <span class="text-sm text-gray-700">Critical Illness</span>
          </div>
          <div class="w-[15%]">
            <span class="">Included</span>
          </div>
          <div class="w-[20%]">
            <x-input
              v-model="addPlanForm.critical_illness_value"
              type="number"
              size="sm"
              placeholder="0"
            />
          </div>
          <div class="w-[15%] flex justify-center">
            <x-toggle
              v-model="addPlanForm.critical_illness_enabled"
              color="success"
              size="sm"
            />
          </div>
          <div class="w-[20%]">
            <x-input
              v-model="addPlanForm.critical_illness_value_2"
              type="number"
              size="sm"
              placeholder="0"
            />
          </div>
        </div>

        <!-- Total Permanent Disability -->
        <div class="flex items-center gap-4 mb-4">
          <div class="w-[20%]">
            <span class="text-sm text-gray-700"
              >Total Permanent Disability</span
            >
          </div>
          <div class="w-[15%]">
            <span class="">Optional</span>
          </div>
          <div class="w-[20%]">
            <x-input
              v-model="addPlanForm.total_permanent_disability_value"
              type="number"
              size="sm"
              placeholder="0"
            />
          </div>
          <div class="w-[15%] flex justify-center">
            <x-toggle
              v-model="addPlanForm.total_permanent_disability_enabled"
              color="success"
              size="sm"
            />
          </div>
          <div class="w-[20%]">
            <x-input
              v-model="addPlanForm.total_permanent_disability_value_2"
              type="number"
              size="sm"
              placeholder="0"
            />
          </div>
        </div>

        <!-- Waiver of Premium -->
        <div class="flex items-center gap-4 mb-4">
          <div class="w-[20%]">
            <span class="text-sm text-gray-700">Waiver of Premium</span>
          </div>
          <div class="w-[15%]">
            <span class="">Optional</span>
          </div>
          <div class="w-[20%]">
            <x-input
              v-model="addPlanForm.waiver_of_premium_value"
              type="number"
              size="sm"
              placeholder="0"
            />
          </div>
          <div class="w-[15%] flex justify-center">
            <x-toggle
              v-model="addPlanForm.waiver_of_premium_enabled"
              color="success"
              size="sm"
            />
          </div>
          <div class="w-[20%]">
            <x-input
              v-model="addPlanForm.waiver_of_premium_value_2"
              type="number"
              size="sm"
              placeholder="0"
            />
          </div>
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

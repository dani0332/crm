<script setup>
import { ref, computed, watch, onMounted } from 'vue';

const props = defineProps({
  uuid: String,
  insuranceProviders: Array,
  currencies: Array,
  lifeRiders: Array,
  plan: Object,
  modelValue: Boolean,
  quote: Object,
  paymentTermEnum: Object,
});

const notification = useNotifications('toast');

const { isRequired } = useRules();

const lifeCoverToggled = true;


const shown = computed({
  get: () => props.modelValue,
  set: value => emit('update:modelValue', value),
});

const getCurrencyId = currencyCode => {
  return props.currencies.find(currency => currency.text === currencyCode)?.id;
};

const validateSumAssured = () => {
  if (!props.plan.isApi) return true;

  const currencyRange = currencyRanges.value.find(
    range => range.currency.code === createForm.currency,
  );

  if (currencyRange) {
    if (createForm.sumAssured < parseFloat(currencyRange.min_cover)) {
      return (
        'Sum assured must be between ' +
        currencyRange.min_cover +
        ' - ' +
        currencyRange.max_cover
      );
    }
    if (createForm.sumAssured > parseFloat(currencyRange.max_cover)) {
      return (
        'Sum assured must be between ' +
        currencyRange.min_cover +
        ' - ' +
        currencyRange.max_cover
      );
    }
  }
  return true;
};

// Add reactive property for currency ranges from API
const currencyRanges = ref([]);

// Function to get currency coverages from API
const getCurrencyCoverages = async planId => {
  if (!props.plan.isApi) return;

  try {
    const res = await axios.get(
      `/personal-quotes/life/currency-coverages/${planId}`,
    );
    currencyRanges.value = res.data || [];
  } catch (error) {
    console.error('Error fetching currency coverages:', error);
    currencyRanges.value = [];
  }
};

// Add watch for modal visibility to call getRiderDetails when opened
watch(
  () => shown.value,
  newVal => {
    if (newVal && props.plan?.planId) {
      getRiderDetails(props.plan.planId);
      getCurrencyCoverages(props.plan.planId);
      submitType.value = props.plan.isApi ? 'getQuote' : 'onSubmit';
      exitAge.value = props.plan?.exitAge;
    }
  },
);

const preventInvalidInputs = (e, allowDecimals = true) => {
  const invalidChars = ['e', '-'];
  if (!allowDecimals) {
    invalidChars.push('.');
  }
  if (invalidChars.includes(e.key)) {
    e.preventDefault();
    return;
  }

  // Limit to 2 decimal places
  if (allowDecimals && e.key === '.') {
    const value = e.target.value;
    if (value.includes('.')) {
      e.preventDefault();
      return;
    }
  }

  // Check if input would create more than 2 decimal places
  if (allowDecimals && /^\d$/.test(e.key)) {
    const value = e.target.value;
    const dotIndex = value.indexOf('.');
    if (
      dotIndex !== -1 &&
      value.length - dotIndex > 2 &&
      e.target.selectionStart > dotIndex
    ) {
      e.preventDefault();
    }
  }
};

const validatePriceRange = value => {
  if (!props.plan.isManualPlan) return true;
  const price = parseFloat(value);

  if (price < 1 || price > 100000000) {
    return 'Value must be between 1 and 100,000,000';
  }
  return true;
};

const validatePolicyTerm = value => {
  if (!value) return true;

  const policyTerm = parseFloat(value);
  if (isNaN(policyTerm)) {
    return 'Policy term must be a number';
  }

  if (policyTerm < 1 || policyTerm > 100) {
    return 'Policy term must be between 1 to 100';
  }

  // Only validate for api plans i.e Zurich for now
  if (props.plan.isApi) {
    const maxAllowedTerm = exitAge.value - clientAge;
    if (policyTerm > maxAllowedTerm) {
      return `Maximum Policy Term can not be more than ${maxAllowedTerm} years (Exit Age ${exitAge.value} - Client Age ${clientAge})`;
    }
  }

  return true;
};

const riders = props.lifeRiders.map(rider => ({
  riderId: rider.id,
  active: 0,
  price: 0,
  coverValue: 0,
  text: rider.text,
}));

const ridersData = ref(riders);
const page = usePage();
const emit = defineEmits(['success', 'error']);

const dateFormat = date => useDateFormat(date, 'DD-MM-YYYY').value;
const active = ref(false);

const paymentTerms = [
  { value: props.paymentTermEnum.MONTHLY, label: 'Monthly' },
  { value: props.paymentTermEnum.QUARTERLY, label: 'Quarterly' },
  { value: props.paymentTermEnum.SEMI_ANNUALLY, label: 'Semi-Annually' },
  { value: props.paymentTermEnum.ANNUALLY, label: 'Annually' },
];


const filteredPaymentTerms = computed(() => {
  return createForm.providerId === 180
    ? paymentTerms.filter(term => ![4, 2].includes(term.value))
    : paymentTerms;
});

const availableInsuranceProviders = computed(() => {
  return props.insuranceProviders;
});

const errorMessage = ref(null);
let alreadyQuoted = ref(null);

const createForm = reactive({
  providerId: null,
  planId: null,
  loading: false,
  isUW: 0,
  currency: null,
  paymentTerm: null,
  sumAssured: null,
  policyTerm: null,
  actualPremium: null,
  insurerQuoteNo: null,
  isVariant: true,
  update: false,
  getQuoteLoading: false,
});

// Ensure numeric fields are never negative
watch(
  () => createForm.sumAssured,
  val => {
    if (val !== null && val !== undefined && Number(val) < 0) {
      createForm.sumAssured = 0;
    }
  },
);

watch(
  () => createForm.policyTerm,
  val => {
    if (val !== null && val !== undefined && Number(val) < 0) {
      createForm.policyTerm = 0;
    }
  },
);

watch(
  () => createForm.actualPremium,
  val => {
    if (val !== null && val !== undefined && Number(val) < 0) {
      createForm.actualPremium = 0;
    }
  },
);

// Watch for negative values in riders
watch(
  () => ridersData.value,
  newRiders => {
    newRiders.forEach(rider => {
      if (Number(rider.price) < 0) rider.price = 0;
      if (Number(rider.coverValue) < 0) rider.coverValue = 0;
    });
  },
  { deep: true },
);

watch(
  () => props.plan,
  newVal => {
    createForm.providerId = props.plan?.providerId;
    createForm.planId = props.plan?.planId;
    createForm.currency = props.plan?.currency;
    createForm.sumAssured = props.plan?.sumInsured
      ? Math.max(0, Number(props.plan.sumInsured))
      : null;
    createForm.policyTerm = props.plan?.policyTerm
      ? Math.max(0, Number(props.plan.policyTerm))
      : null;
    createForm.paymentTerm = props.plan?.paymentTerm;
    createForm.actualPremium = null;
    if (!props.plan?.isApi) {
      createForm.actualPremium = props.plan?.actualPremium
        ? Math.max(0, Number(props.plan.actualPremium))
        : null;
    }
    createForm.insurerQuoteNo = null;
    errorMessage.value = null;

    // Handle rider data on plan change
    if (props.plan?.riders && props.plan.riders.length > 0) {
      ridersData.value = props.plan.riders.map(rider => ({
        riderId: rider.id,
        active: rider.active ?? 0,
        price: Math.max(0, parseFloat(rider.price) || 0),
        coverValue: Math.max(0, parseFloat(rider.coverValue) || 0),
        text: rider.text,
      }));
    } else {
      ridersData.value = props.lifeRiders.map(rider => ({
        riderId: rider.id,
        active: 0,
        price: 0,
        coverValue: 0,
        text: rider.text,
      }));
    }
  },
  { deep: true },
);

const getQuote = () => {
  submitType.value = 'getQuote';
  createForm.getQuoteLoading = true;

  const policyTermValidation = validatePolicyTerm(createForm.policyTerm);
  const sumAssuredValidation = validatePriceRange(createForm.sumAssured);

  const processedRiders = ridersData.value.map(rider => ({
    ...rider,
    price: isNaN(rider.price)
      ? 0
      : Number(parseFloat(rider.price).toFixed(2)) || 0,
    coverValue: isNaN(rider.coverValue)
      ? 0
      : Number(parseFloat(rider.coverValue).toFixed(2)) || 0,
  }));

  createForm.sumAssured =
    Number(parseFloat(createForm.sumAssured).toFixed(2)) || 0;
  createForm.actualPremium =
    Number(parseFloat(createForm.actualPremium).toFixed(2)) || 0;
  createForm.riders = processedRiders;

  axios
    .post(`/personal-quotes/get-life-provider-plan`, {
      data: {
        quoteUID: props.uuid,
        planId: props.plan.planId,
        providerCode: props.plan.providerCode,
        isIndividualLoading: true,
        planData: {
          currency: createForm.currency,
          sumAssured: parseFloat(createForm.sumAssured).toFixed(2),
          policyTerm: createForm.policyTerm,
          paymentTerm: createForm.paymentTerm,
          riders: processedRiders,
        },
        lang: 'en',
      },
    })
    .then(res => {
      if (res.data.providerPlan.message) {
        errorMessage.value = res.data.providerPlan.message;
        return;
      }
      if (res.data) {
        createForm.actualPremium = Math.max(
          0,
          Number(res.data.providerPlan.plan.actualPremium),
        );
        errorMessage.value = null;
      }

      submitType.value = 'onSubmit';
    })
    .catch(err => {
      errorMessage.value = err.response.data.message;
    })
    .finally(() => {
      createForm.getQuoteLoading = false;
    });
};

const submitType = props?.plan?.isApi ? ref('getQuote') : ref('onSubmit');

const handleSubmit = isValid => {
  if (!isValid) {
    return;
  }

  if (submitType.value === 'getQuote') {
    getQuote();
  } else {
    onSubmit();
  }
};

const onSubmit = () => {
  const processedRiders = ridersData.value.map(rider => ({
    ...rider,
    price: Number(parseFloat(rider.price).toFixed(2)) || 0,
    coverValue: Number(parseFloat(rider.coverValue).toFixed(2)) || 0,
  }));

  createForm.loading = true;
  createForm.sumAssured =
    Number(parseFloat(createForm.sumAssured).toFixed(2)) || 0;
  createForm.actualPremium =
    Number(parseFloat(createForm.actualPremium).toFixed(2)) || 0;
  createForm.riders = processedRiders;

  axios
    .post('/personal-quotes/life-plan-manual-create', {
      quoteUID: props.uuid,
      formData: createForm,
    })
    .then(res => {
      if (res?.data?.msg) {
        errorMessage.value = res.data.msg;
        return;
      }

      notification.success({
        title: res.data.message,
        position: 'top',
      });

      setTimeout(() => {
        location.reload();
      }, 2000);
    })
    .catch(err => {
      emit('error');

      const alreadyQuotedMessage = 'This plan detail is already quoted';
      if (
        err?.response?.data?.message &&
        err?.response?.data?.message.includes(alreadyQuotedMessage)
      ) {
        errorMessage.value = 'This plan detail is already quoted';
        return;
      }

      notification.error({
        title: res.data.msg,
        position: 'top',
      });
    })
    .finally(() => {
      createForm.loading = false;
    });
};

const exitAge = ref(null);
const clientAge = props.quote?.life_quote?.age;
const riderOptions = ref([]);

const getRiderDetails = async planId => {
  try {
    const res = await axios.get(
      `/personal-quotes/life/rider-details/${planId}`,
    );

    // Clear existing options if needed
    riderOptions.value = [];

    // Loop through the data with new format
    res.data.forEach(item => {
      if (item.currency_coverages && Array.isArray(item.currency_coverages)) {
        item.currency_coverages.forEach(coverage => {
          riderOptions.value.push({
            riderId: item.rider_id,
            currency_id: coverage.currency_id,
            range_minimum: coverage.min_cover,
            range_maximum: coverage.max_cover,
          });
        });
      }
    });
  } catch (error) {
    console.error('Error fetching rider details:', error);
  }
};

// Add onMounted hook to load rider data when component is mounted
onMounted(() => {
  if (props.plan) {
    // Initialize form data from plan
    createForm.providerId = props.plan.providerId;
    createForm.planId = props.plan.planId;
    createForm.currency = props.plan.currency;
    createForm.sumAssured = props.plan.sumInsured
      ? Math.max(0, Number(props.plan.sumInsured))
      : null;
    createForm.policyTerm = props.plan.policyTerm
      ? Math.max(0, Number(props.plan.policyTerm))
      : null;
    createForm.paymentTerm = props.plan.paymentTerm;
    createForm.actualPremium = null;
    if (!props.plan.isApi) {
      createForm.actualPremium = props.plan.actualPremium
        ? Math.max(0, Number(props.plan.actualPremium))
        : null;
    }

    // Handle rider data initialization
    if (
      props.plan.riders &&
      Array.isArray(props.plan.riders) &&
      props.plan.riders.length > 0
    ) {
      ridersData.value = props.plan.riders.map(rider => {
        const mappedRider = {
          riderId: rider.id,
          active: rider.active ?? 0,
          price: Math.max(0, parseFloat(rider.price) || 0),
          coverValue: Math.max(0, parseFloat(rider.coverValue) || 0),
          text: rider.text || rider.name,
        };
        return mappedRider;
      });
    } else {
      ridersData.value = props.lifeRiders.map(rider => {
        const mappedRider = {
          riderId: rider.id,
          active: 0,
          price: 0,
          coverValue: 0,
          text: rider.text || rider.name,
        };
        return mappedRider;
      });
    }

    getRiderDetails(props.plan.planId);
    exitAge.value = props.plan?.exitAge;
  }
});

// Add validation for non-negative numbers
const isNonNegative = value => {
  if (value === null || value === undefined || value === '') return true;
  return parseFloat(value) >= 0 || 'Value must be non-negative';
};

const validateCoverValue = value => {
  if (props.plan.isApi) return true;
  if (parseFloat(value) > parseFloat(createForm.sumAssured)) {
    return `Cover value must not exceed ${createForm.sumAssured}`;
  }
  return true;
};

const validateRiderCoverValue = (value, riderId) => {
  // First check if it's an API plan
  if (!props.plan?.isApi) return true;

  // Skip validation if value is empty
  if (!value) return true;

  let selectedCurrencyId = getCurrencyId(createForm.currency);

  // Find matching rider option by rider ID
  const matchingOption = riderOptions.value.find(
    option =>
      option.riderId === riderId && option.currency_id === selectedCurrencyId,
  );

  if (matchingOption) {
    const numValue = parseFloat(value);

    if (
      matchingOption.range_minimum &&
      numValue < parseFloat(matchingOption.range_minimum)
    ) {
      return `Minimum value allowed is ${matchingOption.range_minimum}`;
    }

    if (
      matchingOption.range_maximum &&
      numValue > parseFloat(matchingOption.range_maximum)
    ) {
      return `Maximum value allowed is ${matchingOption.range_maximum}`;
    }
  }

  return true;
};
</script>

<template>
  <x-modal
    v-model="shown"
    size="lg"
    title="Add Variant"
    show-close
    backdrop
    is-form
    @submit="handleSubmit"
  >
    <div class="mx-auto p-6 bg-white rounded-lg">
      <h2
        class="bg-gray-100 text-gray-700 font-semibold text-center rounded-lg px-6 py-3 -mt-4 mb-2"
      >
        {{ props.plan.providerName }} - {{ props.plan.planName }}
      </h2>
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block font-medium text-gray-700 mb-1"
            >Insurance Provider <span class="text-red-500">*</span></label
          >
          <x-select
            v-model="createForm.providerId"
            :options="
              availableInsuranceProviders.map((insuranceProver, index) => ({
                value: insuranceProver.id,
                label: insuranceProver.text,
              }))
            "
            :rules="[isRequired]"
            placeholder=""
            class="w-full"
            disabled
          />
        </div>
        <div>
          <label class="block font-medium text-gray-700 mb-1"
            >Plan <span class="text-red-500">*</span></label
          >
          <x-select
            v-model="createForm.planId"
            placeholder="Select Plan"
            class="w-full"
            :options="[
              {
                value: plan.planId,
                label: plan.planName,
              },
            ]"
            :rules="[isRequired]"
            disabled
          />
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block font-medium text-gray-700 mb-1"
              >Currency <span class="text-red-500">*</span></label
            >
            <x-select
              v-model="createForm.currency"
              placeholder="AED"
              class="w-full"
              :options="
                props.currencies?.map(currency => ({
                  value: currency.text,
                  label: currency.text,
                }))
              "
              :rules="[isRequired]"
            />
          </div>
          <div>
            <label class="block font-medium text-gray-700 mb-1"
              >Sum Assured <span class="text-red-500">*</span></label
            >
            <x-input
              v-model="createForm.sumAssured"
              placeholder="Enter Sum Assured"
              :rules="[
                isRequired,
                isNonNegative,
                val => validatePriceRange(val),
                validateSumAssured,
              ]"
              class="w-full"
              type="number"
              min="0"
              step="any"
              @keydown="e => preventInvalidInputs(e, true)"
            />
          </div>
        </div>

        <div>
          <label class="block font-medium text-gray-700 mb-1"
            >Policy Term <span class="text-red-500">*</span></label
          >
          <x-input
            v-model="createForm.policyTerm"
            placeholder="Enter Policy Term"
            :rules="[isRequired, isNonNegative, validatePolicyTerm]"
            class="w-full"
            type="number"
            min="0"
            step="any"
            @keydown="e => preventInvalidInputs(e, false)"
          />
        </div>

        <div>
          <label class="block font-medium text-gray-700 mb-1"
            >Payment Terms <span class="text-red-500">*</span></label
          >
          <x-select
            v-model="createForm.paymentTerm"
            placeholder="Select Payment Terms"
            class="w-full"
            :options="filteredPaymentTerms"
            :rules="[isRequired]"
          />
        </div>

        <div>
          <label class="block font-medium text-gray-700 mb-1"
            >Price (VAT not applicable)
            <span class="text-red-500">*</span></label
          >
          <x-input
            v-model="createForm.actualPremium"
            placeholder="Enter Price"
            :rules="
              submitType === 'getQuote'
                ? [isNonNegative, val => validatePriceRange(val)]
                : [isRequired, isNonNegative, val => validatePriceRange(val)]
            "
            class="w-full"
            type="number"
            min="0"
            step="any"
            @keydown="e => preventInvalidInputs(e, true)"
            :disabled="plan.isApi"
          />
        </div>

        <div>
          <label class="block font-medium text-gray-700 mb-1"
            >Insurer Quote Number</label
          >
          <x-input
            v-model="createForm.insurerQuoteNo"
            placeholder="Enter Insurer Quote Number"
            class="w-full"
            :disabled="plan.isApi"
          />
        </div>
      </div>

      <div class="mt-6">
        <h3 class="font-semibold bg-gray-100 p-4 rounded-md text-gray-700">
          RIDERS
        </h3>

        <div class="grid grid-cols-6 items-center gap-4 p-2 border-b">
          <span class="text-gray-700 col-span-2">Life Cover</span>
          <span class="text-gray-700">Included</span>
          <x-input
            type="number"
            min="0"
            step="any"
            @keydown="e => preventInvalidInputs(e, true)"
            class="w-full h-10 p-2 rounded-md"
            v-model="createForm.sumAssured"
            disabled
          />

          <x-toggle color="emerald" size="lg" disabled v-model="lifeCoverToggled" />
          <x-input
            type="number"
            min="0"
            step="any"
            @keydown="e => preventInvalidInputs(e, true)"
            class="w-full h-10 p-2 rounded-md"
            v-model="createForm.actualPremium"
            disabled
          />
        </div>
        <div
          class="grid grid-cols-6 items-center gap-4 p-2 border-b"
          v-for="(rider, index) in ridersData"
          :key="rider.riderId"
        >
          <span class="text-gray-700 col-span-2">{{ rider.text }}</span>
          <span class="text-gray-700">{{
            rider.active ? 'Included' : 'Optional'
          }}</span>
          <x-input
            type="number"
            :rules="
              rider.active
                ? [
                    isNonNegative,
                    validateCoverValue,
                    isRequired,
                    val => validateRiderCoverValue(val, rider.riderId),
                  ]
                : []
            "
            step="any"
            @keydown="e => preventInvalidInputs(e, false)"
            :disabled="!rider.active"
            class="w-full h-10 p-2 rounded-md"
            v-model="rider.coverValue"
          />
          <x-toggle v-model="rider.active" color="success" size="lg" />
          <x-input
            type="number"
            :rules="
              rider.active && props.plan.isManualPlan
                ? [isNonNegative, isRequired]
                : []
            "
            step="any"
            @keydown="e => preventInvalidInputs(e, false)"
            class="w-full h-10 p-2 rounded-md"
            :disabled="!rider.active || !props.plan.isManualPlan"
            v-model="rider.price"
          />
        </div>
      </div>
    </div>

    <template #actions>
      <div class="flex justify-between w-full">
        <!-- Left side: Text -->
        <div class="flex-1 flex items-start mr-4 overflow-hidden">
          <p class="text-red-500 text-sm break-words">{{ errorMessage }}</p>
        </div>

        <!-- Right side: Button -->
        <div class="flex-shrink-0 flex justify-end">
          <x-button class="mr-2" @click="shown = false"> Cancel </x-button>
          <x-button
            type="submit"
            v-if="!createForm.actualPremium && plan.isApi"
            color="emerald"
            :loading="createForm.getQuoteLoading"
          >
            Get Quote
          </x-button>

          <x-button
            v-else
            type="submit"
            color="emerald"
            :loading="createForm.loading"
          >
            Save
          </x-button>
        </div>
      </div>
    </template>
  </x-modal>
</template>

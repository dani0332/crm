<script setup>
import { ref, computed, onMounted } from 'vue';
import moment from 'moment';
import {
  numberFormat,
  preventInvalidInputs,
  useFormattedNumberField,
  cleanFormattedValueToFloat,
} from '@/inertia/Composables/utilities.js';

const props = defineProps({
  uuid: String,
  insuranceProviders: Array,
  plans: Array,
  currencies: Array,
  lifeRiders: Array,
  selectedPlan: Object,
  paymentTermEnum: Object,
});

const { isRequired } = useRules();

const shown = computed({
  get: () => props.modelValue,
  set: value => emit('update:modelValue', value),
});

const getCurrencyId = currencyCode => {
  return props.currencies.find(currency => currency.text === currencyCode)?.id;
};

let riders = props.lifeRiders.map(rider => ({
  riderId: rider.id,
  active: 0,
  price: 0,
  coverValue: 0,
  text: rider.text,
  loading: 0,
  finalPrice: 0,
}));

const showInsurerError = ref(false);
const showGetQuoteBtn = ref(false);
const showSaveButton = ref(false);

let selectedTabIndex = ref(0);
let overallLoadingState = ref(false);
let errorMessage = ref(null);

const formatDate = timestamp => {
  return moment(timestamp).format('DD-MM-YYYY HH:mm:ss');
};

const validatePriceRange = value => {
  if (!value) return true;
  const price = cleanFormattedValueToFloat(value);
  if ((editForm.isManualPlan && price < 1) || price > 100000000) {
    return 'Value must be between 1 and 100,000,000';
  }
  return true;
};

const ridersData = ref(riders);

const page = usePage();

const emit = defineEmits(['success', 'error']);

const dateFormat = date => useDateFormat(date, 'DD-MM-YYYY').value;

const active = ref(false);

const lifeCoverToggled = true;

const notification = useNotifications('toast');

const paymentTerms = [
  { value: props.paymentTermEnum.MONTHLY, label: 'Monthly' },
  { value: props.paymentTermEnum.QUARTERLY, label: 'Quarterly' },
  { value: props.paymentTermEnum.SEMI_ANNUALLY, label: 'Semi-Annually' },
  { value: props.paymentTermEnum.ANNUALLY, label: 'Annually' },
];

const filteredPaymentTerms = computed(() => {
  return editForm.providerId === 180
    ? paymentTerms.filter(term => ![4, 2].includes(term.value))
    : paymentTerms;
});

const options = reactive({
  providerPlans: [],
  loading: false,
});

const updateQuotesLoader = ref(false);

const availableInsuranceProviders = computed(() => {
  if (editForm.isUW) {
    return props.insuranceProviders;
  }
  return props.insuranceProviders.filter(item => {
    return !props.plans.some(plan => plan.providerId === item.id);
  });
});

const extraAttr = reactive({
  getQuoteLoading: false,
  loading: false,
  generatePdfLoading: false,
});

const isPdfGenerated = props?.selectedPlan?.isPdfGenerated ?? false;
const editForm = reactive({
  providerId: props?.selectedPlan?.providerId ?? null,
  planId: props?.selectedPlan?.planId ?? null,
  isUW: props.selectedPlan?.isUnderwritten ?? false,
  currency: props.selectedPlan.currency,
  paymentTerm: props.selectedPlan?.paymentTerm ?? null,
  sumAssured: parseFloat(props.selectedPlan.sumInsured) ?? 0,
  policyTerm: props.selectedPlan.policyTerm,
  actualPremium: parseFloat(props.selectedPlan.actualPremium) ?? 0,
  insurerQuoteNo: props.selectedPlan.insurerQuoteNo ?? null,
  isVariant: false,
  update: true,
  isDisabled: props.selectedPlan.isDisabled ?? false,
  isManualPlan: props.selectedPlan.isManualPlan,
  version: props.selectedPlan.version,
  isApi: props.selectedPlan.isApi,
  isRateCalculator: props.selectedPlan.isRateCalculator,
  isManualUpdate: props.selectedPlan.isManualPlan || props.selectedPlan.isApi,
  overallLoading: props?.selectedPlan?.overallLoading ?? 0,
  discountPremium: props?.selectedPlan?.discountPremium ?? 0,
  isInstantPolicy: props?.selectedPlan?.instantPolicy ?? false,
  ridersPrice: props?.selectedPlan?.ridersPrice ?? 0,
});

const isPlanGeneralInfoEditable = computed(
  () => !editForm.isApi && !editForm.isRateCalculator,
);

// Make actualPremium a computed value to ensure reactivity
const actualPremium = computed(() => {
  const basePremium = Math.max(0, parseFloat(editForm.actualPremium) || 0);
  const overallLoadingValue = Math.max(
    0,
    parseFloat(editForm.overallLoading) || 0,
  );
  const riderPrice = Math.max(0, parseFloat(getRiderPrice()) || 0);
  return basePremium + overallLoadingValue + riderPrice;
});

const discountPremiumTotal = computed(() => {
  return editForm.discountPremium;
});

const totalPrice = computed(() => {
  if (
    editForm.isApi &&
    editForm.isInstantPolicy &&
    editForm.paymentTerm === props.paymentTermEnum?.ANNUALLY
  ) {
    // metlife annually
    const currentRidersPrice = getRiderPrice();
    return discountPremiumTotal.value + currentRidersPrice;
  }

  if (editForm.isApi && editForm.isInstantPolicy) {
    // metlife monthly, quarterly, semi-annually
    const currentRidersPrice = getRiderPrice();
    return editForm.actualPremium + currentRidersPrice;
  }

  if (editForm.isApi && !editForm.isInstantPolicy) {
    // zurich
    return actualPremium.value;
  }

  // manual plan
  return actualPremium.value;
});

/** computes is the plan is API or rate calculator*/
const isApiOrRC = computed(() => {
  return props.selectedPlan.isApi || props.selectedPlan.isRateCalculator;
});

const toggleVisiblity = () => {
  axios
    .post('/personal-quotes/life/toggle-life-plan-visibility', {
      quoteUID: props.uuid,
      providerId: editForm.providerId,
      planId: editForm.planId,
      version: editForm.version,
      isDisabled: editForm.isDisabled,
    })
    .then(res => {
      extraAttr.loading = false;

      emit('success');

      notification.success({
        title: res.data.message,
        position: 'top',
      });
    })
    .catch(err => {
      emit('error', err);
      extraAttr.loading = false;
    })
    .finally(() => {
      extraAttr.loading = false;
    });
};

// Update the Plan (if it is manual)
const onSubmit = () => {
  editForm.actualPremium = Number(
    parseFloat(editForm.actualPremium).toFixed(2),
  );
  editForm.sumAssured = Number(parseFloat(editForm.sumAssured).toFixed(2));
  editForm.overallLoading = Number(
    parseFloat(editForm.overallLoading).toFixed(2),
  );
  extraAttr.loading = true;

  // Ensure riders have numeric values by converting strings to floats and preventing negative values
  const processedRiders = ridersData.value.map(rider => ({
    ...rider,
    price: Number(parseFloat(rider.price).toFixed(2)) || 0,
    loading: Number(parseFloat(rider.loading).toFixed(2)) || 0,
    finalPrice: Number(parseFloat(rider.finalPrice).toFixed(2)) || 0,
    coverValue: Number(parseFloat(rider.coverValue).toFixed(2)) || 0,
    monthlyPremium:
      Number(parseFloat(rider?.monthlyPremium ?? 0).toFixed(2)) || 0,
    annualPremium:
      Number(parseFloat(rider?.annualPremium ?? 0).toFixed(2)) || 0,
    slug: rider?.slug ?? '',
  }));

  editForm.riders = processedRiders;

  axios
    .post('/personal-quotes/life-plan-manual-create', {
      quoteUID: props.uuid,
      formData: editForm,
    })
    .then(res => {
      extraAttr.loading = false;

      if (res?.data?.code) {
        notification.error({
          title: res.data.msg,
          position: 'top',
        });
        return;
      }

      notification.success({
        title: res.data.message,
        position: 'top',
      });

      emit('success');

      closeModal();
    })
    .catch(err => {
      emit('error', err);
      extraAttr.loading = false;
      errorMessage.value = err.response.data.message;
    })
    .finally(() => {
      extraAttr.loading = false;
    });
};

const leadSourceEnum = page.props.leadSource;

// Geenerate Pdf
const generatePdf = () => {
  extraAttr.generatePdfLoading = true;
  axios
    .post('/personal-quotes/life-plan-selected', {
      planId: props.selectedPlan.planId,
      quoteId: props.uuid,
      version: props.selectedPlan.version,
      saveQuote: true,
      callSource: leadSourceEnum?.IMCRM?.toLowerCase(),
    })
    .then(response => {
      if (response?.data?.code) {
        notification.error({
          title: response.data.msg,
          position: 'top',
        });

        return;
      }

      notification.success({
        title:
          'Pdf generated successfully, it will be uploaded in the Documents section shortly.',
        position: 'top',
      });

      emit('success');

      setTimeout(() => {
        location.reload();
      }, 2000);
    })
    .catch(error => {
      emit('error', error);
      notification.error({
        title: error?.response?.data?.message ?? 'something went wrong',
        position: 'top',
      });
    })
    .finally(() => {
      extraAttr.generatePdfLoading = false;
    });
};

// Get updated provider plan (API Mode only)
const getQuote = () => {
  extraAttr.getQuoteLoading = true;

  // Ensure riders have numeric values by converting strings to floats and preventing negative values
  const processedRiders = ridersData.value.map(rider => ({
    ...rider,
    price: isNaN(rider.price)
      ? parseFloat(0)
      : parseFloat(Number(rider.price).toFixed(2)),
    loading: isNaN(rider.loading)
      ? parseFloat(0)
      : parseFloat(Number(rider.loading).toFixed(2)),
    finalPrice: isNaN(rider.finalPrice)
      ? parseFloat(0)
      : parseFloat(Number(rider.finalPrice).toFixed(2)),
    coverValue: isNaN(rider.coverValue)
      ? parseFloat(0)
      : parseFloat(Number(rider.coverValue).toFixed(2)),
  }));



  editForm.sumAssured = Number(parseFloat(editForm.sumAssured).toFixed(2));
  editForm.actualPremium = Number(
    parseFloat(editForm.actualPremium).toFixed(2),
  );

  axios
    .post(`/personal-quotes/get-life-provider-plan`, {
      data: {
        quoteUID: props.uuid,
        planId: props.selectedPlan.planId,
        providerCode: props.selectedPlan.providerCode,
        isIndividualLoading: true,
        planData: {
          currency: editForm.currency,
          sumAssured: editForm.sumAssured,
          policyTerm: editForm.policyTerm,
          paymentTerm: editForm.paymentTerm,
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
        editForm.actualPremium = res.data.providerPlan.plan.actualPremium;
        // No need to set actualPremium.value as it's now a computed property
        errorMessage.value = null;
      }

      extraAttr.getQuoteLoading = false;
      showGetQuoteBtn.value = false;
      showSaveButton.value = true;
      submitType.value = 'onSubmit';
    })
    .catch(err => {
      errorMessage.value = err.response.data.message;
    })
    .finally(() => {
      extraAttr.getQuoteLoading = false;
    });
};

// On Modal Open, Load the Addons (Riders)
onMounted(() => {
  if (editForm.providerId) {
    ridersData.value = props.selectedPlan.riders.map(rider => ({
      riderId: rider.id,
      code: rider.insurerCode,
      conflictsWith: rider.conflictsWith,
      autoSelectedWith: rider.autoSelectedWith,
      active: rider.active ?? 0,
      price: parseFloat(rider.price) || 0,
      coverValue: rider.coverValue ?? 0,
      text: rider.text,
      loading: parseInt(rider?.loading) ?? 0,
      finalPrice: parseInt(rider?.finalPrice) ?? 0,
      inputRequired: rider?.inputRequired ?? false,
      monthlyPremium: parseFloat(rider?.monthlyPremium ?? 0) || 0,
      annualPremium: parseFloat(rider?.annualPremium ?? 0) || 0,
      slug: rider?.slug ?? '',
    }));

    getRiderDetails(props.selectedPlan.planId);
  }
});

const getRiderPrice = () => {
  // Calculate the total price for active riders
  const totalActivePrice = ridersData.value
    .filter(rider => rider.active == 1)
    .reduce(
      (sum, rider) =>
        Math.max(0, parseFloat(sum)) +
        Math.max(0, parseFloat(rider.price) || 0),
      0,
    );

  const totalRiderLoading = ridersData.value
    .filter(rider => rider.active == parseInt(1)) // Filter active riders
    .reduce(
      (sum, rider) =>
        Math.max(0, parseFloat(sum)) +
        Math.max(0, parseFloat(rider.loading) || 0),
      0,
    );

  const totalFinalPrice = ridersData.value
    .filter(rider => rider.active == parseInt(1)) // Filter active riders
    .reduce((sum, rider) => Math.max(0, parseFloat(sum)), 0);

  let totalRiderPrice = Math.max(0, parseFloat(totalActivePrice));
  // Disable Overall Loading if there is rider loading added on rider level
  if (totalRiderLoading > 0) {
    overallLoadingState.value = true;
    totalRiderPrice = Math.max(0, totalRiderPrice + totalRiderLoading);
  } else if (totalFinalPrice > 0) {
    totalRiderPrice = Math.max(0, totalRiderPrice + totalFinalPrice);
  }

  if (totalRiderLoading == 0) {
    overallLoadingState.value = false;
  }
  return totalRiderPrice;
};

watch(
  ridersData,
  () => {
    // Only update ridersPrice for MetLife plans (for display purposes)
    if (editForm.isApi && editForm.isInstantPolicy) {
      const newTotalRiderPrice = getRiderPrice();
      editForm.ridersPrice = newTotalRiderPrice;
    }
  },
  { deep: true },
);

// tabs
const tabs = ref([
  { index: 0, label: 'General Info' },
  { index: 1, label: 'Addons' },
  { index: 2, label: 'Inclusions' },
  { index: 3, label: 'Policy Detail' },
]);

const setActiveTab = (index, selected) => {
  selectedTabIndex.value = index;
  if (index == 1) {
    showGetQuoteBtn.value = true;
    return;
  }
  showGetQuoteBtn.value = false;
};

const getInputRules = rider => {
  return parseInt(rider.active) == 1 ? [isRequired] : [];
};

const computedFinalPrice = rider =>
  computed(() => {
    const price =
      !rider.price || rider.price === ''
        ? 0
        : Math.max(0, parseFloat(rider.price));
    const loading =
      !rider.loading || rider.loading === ''
        ? 0
        : Math.max(0, parseFloat(rider.loading));
    const finalPrice = price + loading;
    rider.finalPrice = finalPrice;
    return finalPrice;
  });

const closeModal = () => {
  shown.value = false;
  document.body.style.overflow = 'auto';
  emit('close');
};
const validatePolicyTerm = value => {
  if (!value) return true;
  const policyTerm = parseFloat(value);
  if (policyTerm < 1 || policyTerm > 100) {
    return 'Value must be between 1 to 100';
  }
  return true;
};

const isNonNegative = value => {
  if (value === null || value === undefined || value === '') return true;
  return parseFloat(value) >= 0 || 'Value must be non-negative';
};

const validateCoverValue = value => {
  if (props.selectedPlan.isApi) {
    return true;
  }
  // only validate for manual plans
  if (
    parseFloat(value) > cleanFormattedValueToFloat(formattedSumAssured.value)
  ) {
    return `Cover value must not exceed ${editForm.sumAssured}`;
  }
  return true;
};

const hidePlan = () => {
  toggleVisiblity();
};

const submitType = isApiOrRC.value ? ref('getQuote') : ref('onSubmit');

const riderOptions = ref([]);

/**
 * Rider selection rules (per insurer code mapping):
 *
 * deselects[] — rider codes to force OFF when this rider is turned ON
 * selects[]   — rider codes to force ON  when this rider is turned ON
 *
 * R1023 CI Acc   ↔ R1024 CI Add  : mutually exclusive
 * R1026 PTDA     ↔ R1027 PTDAS   : mutually exclusive
 * R1028 WP (Waiver of Premium) is independent — not auto-selected or auto-deselected by PTD riders.
 */
const RIDER_RULES = {
  R1023: { deselects: ['R1024'], selects: [] }, // CI Acc deselects CI Add
  R1024: { deselects: ['R1023'], selects: [] }, // CI Add deselects CI Acc
  R1026: { deselects: ['R1027'], selects: [] }, // PTDA deselects PTDAS
  R1027: { deselects: ['R1026'], selects: [] }, // PTDAS deselects PTDA
};

const HOSPITAL_INDEMNITY_RIDER_CODE = 'R1025';
const HOSPITAL_INDEMNITY_COVER_VALUE_OPTIONS = {
  AED: [100, 200, 300, 400, 500, 600, 730],
  USD: [25, 50, 75, 100, 125, 150, 175, 200],
};

const getHospitalIndemnityCoverValueOptions = () => {
  const values = HOSPITAL_INDEMNITY_COVER_VALUE_OPTIONS[editForm.currency];
  if (!values) {
    return [];
  }
  return values.map(value => ({ value, label: String(value) }));
};

const isHospitalIndemnityRider = rider =>
  rider.code === HOSPITAL_INDEMNITY_RIDER_CODE &&
  getHospitalIndemnityCoverValueOptions().length > 0;

const handleRiderToggle = toggledRider => {
  const isNowActive = toggledRider.active == 1 || toggledRider.active === true;
  const rules = RIDER_RULES[toggledRider.code];

  /** ridersData has props conflictsWith, autoSelectedWith contain add-ons code that we can use to create rather than static on in
   *  RIDER_RULES in the future. */

  if (isNowActive) {
    // Deselect mutually exclusive riders
    rules?.deselects?.forEach(codeToDeselect => {
      const target = ridersData.value.find(r => r.code === codeToDeselect);
      if (target) {
        target.active = 0;
        target.coverValue = 0;
        // target.price = 0;
      }
    });

    // Auto-select dependent riders
    rules?.selects?.forEach(codeToSelect => {
      const target = ridersData.value.find(r => r.code === codeToSelect);
      if (target) {
        target.active = 1;
      }
    });
  }
};

// get rider details
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
const validateRiderCoverValue = (value, riderId) => {
  // First check if it's an API plan
  if (!props.selectedPlan?.isApi) return true;

  // Skip validation if value is empty
  if (!value) return true;

  let selectedCurrencyId = getCurrencyId(editForm.currency);

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
      return `Value can be between ${matchingOption.range_minimum} and ${matchingOption.range_maximum}`;
    }

    if (
      matchingOption.range_maximum &&
      numValue > parseFloat(matchingOption.range_maximum)
    ) {
      return `Value can be between ${matchingOption.range_minimum} and ${matchingOption.range_maximum}`;
    }
  }

  return true;
};
const formattedSumAssured = useFormattedNumberField(editForm, 'sumAssured');
const formattedActualPremium = useFormattedNumberField(
  editForm,
  'actualPremium',
);
const formattedDiscountPremium = useFormattedNumberField(
  editForm,
  'discountPremium',
);
const insuranceProviderCodeEnum = page.props.insuranceProviderCodeEnum;

const getDisplayPrice = computed({
  get: () => {
    return editForm.isApi &&
      editForm.isInstantPolicy &&
      editForm.paymentTerm === props.paymentTermEnum?.ANNUALLY
      ? formattedDiscountPremium.value
      : formattedActualPremium.value;
  },
  set: value => {
    if (
      editForm.isApi &&
      editForm.isInstantPolicy &&
      editForm.paymentTerm === props.paymentTermEnum?.ANNUALLY
    ) {
      formattedDiscountPremium.value = value;
    } else {
      formattedActualPremium.value = value;
    }
  },
});
</script>

<template>
  <x-modal
    :hasActions="false"
    v-model="shown"
    size="lg"
    backdrop
    is-form
    persistent
    @submit="handleSubmit"
  >
    <template #header>
      <div class="flex items-center justify-between w-full px-6 py-4">
        <h3 class="text-2xl font-semibold">
          {{ selectedPlan?.providerName ?? 'Edit Plan' }}
        </h3>
        <span
          @click="closeModal"
          class="cursor-pointer close-icon closeTag text-2xl"
          >&times;</span
        >
      </div>
    </template>
    <div class="w-full">
      <TabGroup>
        <TabList
          class="flex flex-row flex-wrap rounded-xl bg-slate-100 p-1.5 w-full"
        >
          <Tab
            v-for="{ index, label } in tabs"
            as="template"
            :key="index"
            v-slot="{ selected }"
            @click="setActiveTab(index, selected)"
          >
            <button
              :class="[
                'rounded-lg px-3 py-2 flex-auto md:min-w-[20%] text-sm font-medium text-gray-800 transition duration-200 ease-in-out uppercase',
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
        <TabPanels class="mt-2 text-sm min-h-[40vh]">
          <!-- General Info -->
          <TabPanel>
            <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4 p-4">
              <div :class="{ 'col-span-2': editForm.isApi }">
                <x-toggle
                  v-if="!props.selectedPlan.systemHidden"
                  v-model="editForm.isDisabled"
                  color="success"
                  label="Hide"
                  @update:modelValue="hidePlan"
                />
              </div>

              <div v-if="!editForm.isApi" class="grid sm:grid-cols-2 mb-3">
                <x-toggle
                  v-model="editForm.isManualPlan"
                  color="success"
                  label="Manual"
                  disabled
                />
              </div>

              <div class="grid sm:grid-cols-2">
                <dt class="">Provider Name</dt>
                <dd>{{ props.selectedPlan.providerName }}</dd>
              </div>

              <div class="grid sm:grid-cols-2">
                <dt class="">Plan Name</dt>
                <dd>
                  {{ props.selectedPlan.planName }}
                  <x-tag
                    v-if="props.selectedPlan.isUnderwritten"
                    size="xs"
                    color="error"
                    class="mt-0.5 text-[10px] bg-green-300 text-green-800 font-semibold px-2 py-1 rounded-md"
                  >
                    UW
                  </x-tag>
                  <x-tag
                    v-else-if="props.selectedPlan.isApi"
                    size="xs"
                    color="error"
                    class="mt-0.5 text-[10px] bg-orange-200 text-orange-700 font-semibold px-2 py-1 rounded-md"
                  >
                    API
                  </x-tag>
                  <x-tag
                    v-else-if="props.selectedPlan.isManualPlan"
                    size="xs"
                    color="error"
                    class="mt-0.5 text-[10px] bg-gray-200 text-gray-700 font-semibold px-2 py-1 rounded-md"
                  >
                    Manual
                  </x-tag>
                  <x-tag
                    v-else-if="props.selectedPlan.isRateCalculator"
                    size="xs"
                    color="error"
                    class="mt-0.5 text-[10px] bg-green-200 text-green-700 font-semibold px-2 py-1 rounded-md"
                  >
                    Rate Calculator
                  </x-tag>
                </dd>
              </div>

              <div class="grid sm:grid-cols-2">
                <dt class="mt-2">Insurer Quote No.:</dt>
                <x-input
                  v-model="editForm.insurerQuoteNo"
                  :disabled="!isPlanGeneralInfoEditable"
                  :error="showInsurerError ? 'This field is required' : ''"
                  maxlength="50"
                  size="sm"
                />
              </div>

              <div class="grid sm:grid-cols-2">
                <dt class="mt-2">Price:</dt>
                <x-input
                  v-model="getDisplayPrice"
                  :disabled="!isPlanGeneralInfoEditable"
                  :rules="[isRequired, validatePriceRange, isNonNegative]"
                  size="sm"
                  type="text"
                  @keydown="e => preventInvalidInputs(e, true, true)"
                />
              </div>

              <div class="grid sm:grid-cols-2">
                <dt class="mt-2">Currency:</dt>
                <x-select
                  v-model="editForm.currency"
                  placeholder="AED"
                  class="w-full"
                  :disabled="!isPlanGeneralInfoEditable"
                  :options="
                    props.currencies?.map(currency => ({
                      value: currency.text,
                      label: currency.text,
                    }))
                  "
                  :rules="[isRequired]"
                />
              </div>

              <div class="grid sm:grid-cols-2">
                <dt class="mt-2">Sum Assured:</dt>
                <x-input
                  v-model="formattedSumAssured"
                  :disabled="!isPlanGeneralInfoEditable"
                  :rules="[isRequired, validatePriceRange, isNonNegative]"
                  size="sm"
                  type="text"
                  @keydown="e => preventInvalidInputs(e, true, false)"
                />
              </div>

              <div class="grid sm:grid-cols-2">
                <dt class="mt-2">Policy Term:</dt>
                <x-input
                  v-model="editForm.policyTerm"
                  :disabled="!isPlanGeneralInfoEditable"
                  :rules="[isRequired, validatePolicyTerm, isNonNegative]"
                  size="sm"
                  type="number"
                  min="0"
                  @keydown="e => preventInvalidInputs(e, false)"
                />
              </div>

              <div class="grid sm:grid-cols-2">
                <dt class="mt-2">Payment Frequency</dt>
                <x-select
                  v-model="editForm.paymentTerm"
                  placeholder="Select Payment Frequency"
                  class="w-full"
                  :options="filteredPaymentTerms"
                  :disabled="!isPlanGeneralInfoEditable"
                  :rules="[isRequired]"
                />
              </div>
            </dl>
          </TabPanel>
          <!-- General Info end -->

          <TabPanel>
            <div class="mt-6">
              <h3 class="font-semibold rounded-md text-gray-700">Add On</h3>
              <p></p>
            </div>
            <div class="mt-6">
              <div class="grid grid-cols-10 items-center gap-2 p-2 border-b">
                <div class="col-span-2">
                  <span class="text-gray-700 font-bold">Riders</span>
                </div>
                <div class="col-span-3">
                  <span class="text-gray-700 font-bold">Cover</span>
                </div>
                <div class="col-span-2">
                  <span class="text-gray-700 font-bold">Price</span>
                </div>
                <div
                  class="col-span-2"
                  v-if="props.selectedPlan.isUnderwritten"
                >
                  <span class="text-gray-700 font-bold">Rider Loading</span>
                </div>
                <div
                  class="col-span-1"
                  v-if="props.selectedPlan.isUnderwritten"
                >
                  <span class="text-gray-700 font-bold">Final Price</span>
                </div>
              </div>

              <div class="grid grid-cols-10 items-center gap-4 p-2 border-b">
                <div>
                  <span class="text-gray-700">Life Cover</span>
                </div>

                <div>
                  <span class="text-gray-700">Included</span>
                </div>

                <div class="col-span-2">
                  <x-input
                    type="number"
                    @keydown="e => preventInvalidInputs(e, false)"
                    class="w-full h-10 p-2 rounded-md"
                    v-model="editForm.sumAssured"
                    min="0"
                    disabled
                  />
                </div>

                <div>
                  <x-toggle
                    v-model="lifeCoverToggled"
                    color="emerald"
                    size="lg"
                    disabled
                  />
                </div>
                <div class="col-span-2">
                  <x-input
                    type="text"
                    @keydown="e => preventInvalidInputs(e, false)"
                    class="w-full h-10 p-2 rounded-md"
                    v-model="getDisplayPrice"
                    disabled
                  />
                </div>
                <div
                  class="col-span-2"
                  v-if="props.selectedPlan.isUnderwritten"
                >
                  <x-input
                    type="number"
                    @keydown="e => preventInvalidInputs(e, false)"
                    class="w-full h-10 p-2 rounded-md"
                    disabled
                  />
                </div>
                <div class="col-span-1">
                  <x-input
                    type="number"
                    @keydown="e => preventInvalidInputs(e, false)"
                    v-if="props.selectedPlan.isUnderwritten"
                    class="w-full h-10 p-2 rounded-md"
                    v-model="editForm.actualPremium"
                    disabled
                  />
                </div>
              </div>
              <div
                class="grid grid-cols-10 items-center gap-4 p-2"
                v-for="rider in ridersData"
                :key="rider.id"
              >
                <div>
                  <span class="text-gray-700 col-span-2">{{ rider.text }}</span>
                </div>
                <div>
                  <span class="text-gray-700">{{
                    rider.active ? 'Included' : 'Optional'
                  }}</span>
                </div>

                <div class="col-span-2">
                  <template v-if="isHospitalIndemnityRider(rider)">
                    <x-select
                      :disabled="!rider.active"
                      :options="getHospitalIndemnityCoverValueOptions()"
                      :rules="rider.active ? [isRequired] : []"
                      placeholder="Select cover value"
                      class="w-full hospital-cash-benefit-select"
                      v-model="rider.coverValue"
                    />
                    <p
                      v-if="
                        rider.active &&
                        rider.coverValue &&
                        !getHospitalIndemnityCoverValueOptions().some(
                          option => option.value == rider.coverValue,
                        )
                      "
                      class="text-red-500 text-sm mt-1"
                    >
                      Selected value is invalid ({{rider.coverValue}}). Select a valid option.
                    </p>
                  </template>
                  <x-input
                    v-else
                    :disabled="!rider.active"
                    :rules="
                      rider.active
                        ? [
                            rider.inputRequired ? isRequired : () => true,
                            isNonNegative,
                            val => validateCoverValue(val),
                            val => validateRiderCoverValue(val, rider.riderId),
                          ]
                        : []
                    "
                    type="number"
                    @keydown="e => preventInvalidInputs(e, false, true)"
                    min="0"
                    class="w-full h-10 p-2 rounded-md"
                    v-model="rider.coverValue"
                  />
                </div>

                <div>
                  <x-toggle
                    v-model="rider.active"
                    color="success"
                    size="lg"
                    @update:modelValue="handleRiderToggle(rider)"
                  />
                </div>

                <div class="col-span-2">
                  <x-input
                    type="number"
                    :disabled="
                      !props.selectedPlan.isManualPlan || !rider.active
                    "
                    @keydown="e => preventInvalidInputs(e, true)"
                    :rules="rider.active ? [isNonNegative] : []"
                    class="w-full h-10 p-2 rounded-md"
                    v-model="rider.price"
                  />
                </div>

                <div
                  class="col-span-2"
                  v-if="props.selectedPlan.isUnderwritten"
                >
                  <x-input
                    type="number"
                    :disabled="
                      !props.selectedPlan.isManualPlan ||
                      !rider.active ||
                      editForm.overallLoading > 0
                    "
                    @keydown="e => preventInvalidInputs(e, true)"
                    :rules="rider.active ? [isNonNegative] : []"
                    class="w-full h-10 p-2 rounded-md"
                    v-model="rider.loading"
                  />
                </div>

                <div
                  class="col-span-1"
                  v-if="props.selectedPlan.isUnderwritten"
                >
                  <div
                    class="appearance-none block w-24 overflow-hidden placeholder-secondary-400 dark:placeholder-secondary-500 outline-transparent outline outline-2 outline-offset-[-1px] transition-all duration-150 ease-in-out border-secondary-300 dark:border-secondary-700 border shadow-sm rounded-md px-3 py-2 bg-secondary-100 dark:bg-secondary-700 text-secondary-400 dark:text-secondary-600 cursor-not-allowed focus:outline-[color:var(--x-input-border)]"
                  >
                    {{ computedFinalPrice(rider) }}
                  </div>
                </div>
              </div>
            </div>
          </TabPanel>

          <!-- Inclusion -->
          <TabPanel>
            <div
              class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 mb-2 mt-6"
              v-if="props?.selectedPlan?.benefits?.inclusion"
            >
              <dl
                v-for="data in props?.selectedPlan?.benefits?.inclusion || []"
                :key="data.code"
                class="mb-3 text-center"
              >
                <dt class="font-semibold">{{ data.text }}</dt>
                <dd>{{ data.value ?? 'Included' }}</dd>
              </dl>
            </div>
          </TabPanel>

          <!-- Policy Wordings -->
          <TabPanel>
            <dl class="grid md:grid-cols-2 gap-5 p-4">
              <div
                v-for="data in props?.selectedPlan?.policyWordings || []"
                :key="data"
              >
                <a :href="data.link" class="font-medium mb-1" target="_blank">{{
                  data.text
                }}</a>
              </div>
            </dl>
          </TabPanel>
        </TabPanels>
      </TabGroup>
    </div>
    <div>
      <template v-if="selectedTabIndex == 0">
        <x-divider></x-divider>
        <div class="flex justify-between gap-4 mt-4">
          <div class="flex-col">
            <p v-if="errorMessage" class="text-red-600">{{ errorMessage }}</p>
          </div>
        </div>
        <div class="flex justify-between gap-4">
          <!-- Price section aligned to the left -->
          <dl class="flex flex-row">
            <dt class="font-bold text-lg ml-4">Total Price:</dt>
            <dd class="text-lg">
              &nbsp; {{ editForm.currency }} {{ numberFormat(totalPrice) }}
            </dd>
          </dl>

          <!-- Timestamps aligned to the right -->
          <div class="flex flex-col items-end">
            <dd>
              <strong>Created Date:</strong>
              {{ formatDate(props.selectedPlan.created_at) }}
            </dd>
            <dd>
              <strong>Updated at:</strong>
              {{ formatDate(props.selectedPlan.updated_at) }}
            </dd>

            <x-button
              v-if="
                editForm.isApi &&
                !editForm.isUnderwritten &&
                !isPdfGenerated &&
                props.selectedPlan.providerCode ===
                  insuranceProviderCodeEnum?.ZILL
              "
              @click="generatePdf()"
              class="mt-2"
              color="orange"
              :loading="extraAttr.generatePdfLoading"
              >Generate Quote Pdf</x-button
            >
          </div>
        </div>

        <!-- Buttons section aligned to the right -->
        <div class="flex justify-end gap-4 mt-4">
          <div v-if="isPlanGeneralInfoEditable">
            <x-button type="submit" color="blue" :loading="extraAttr.loading">
              Save
            </x-button>
          </div>
        </div>
      </template>

      <template v-else-if="selectedTabIndex == 1 && isApiOrRC">
        <x-divider></x-divider>
        <div class="flex justify-between gap-4 mt-4">
          <!-- Price section aligned to the left -->
          <div class="flex flex-col">
            <p v-if="errorMessage" class="text-red-600">{{ errorMessage }}</p>
            <dl class="flex flex-row">
              <dt class="font-bold text-sm ml-4">Total Price:</dt>
              <dd class="text-sm">
                &nbsp; {{ editForm.currency }} {{ numberFormat(totalPrice) }}
              </dd>
            </dl>
          </div>
          <!-- Timestamps aligned to the right -->
        </div>

        <!-- Buttons section aligned to the right -->
        <div class="flex justify-end gap-4 mt-4">
          <div v-if="showSaveButton">
            <x-button type="submit" color="blue" :loading="extraAttr.loading">
              Save
            </x-button>
          </div>

          <div v-else-if="showGetQuoteBtn">
            <x-button
              type="submit"
              color="blue"
              :loading="extraAttr.getQuoteLoading"
            >
              Update Quotes
            </x-button>
          </div>
        </div>
      </template>

      <template
        v-else-if="
          selectedTabIndex == 1 && editForm.isManualPlan && !editForm.isApi
        "
      >
        <x-divider></x-divider>
        <div class="flex justify-between gap-4 mt-4 items-center">
          <!-- Main container pushed to the right -->

          <div class="ml-auto flex items-center gap-4">
            <!-- Overall Loading input field -->
            <div
              class="flex items-center"
              v-if="props.selectedPlan.isUnderwritten"
            >
              <span class="mr-2">Overall Loading:</span>
              <x-input
                type="number"
                @keydown="e => preventInvalidInputs(e, false)"
                @input="updatePriceWithOverloading()"
                :disabled="overallLoadingState"
                min="0"
                v-model="editForm.overallLoading"
                class="w-32 h-10 pt-3"
              />
              <!-- Adjust width as needed -->
            </div>

            <!-- Total Price section -->
            <div class="flex items-center">
              <span class="font-bold mr-2">Total Price:</span>
              <span class="">
                {{ editForm.currency }} {{ numberFormat(totalPrice) }}
              </span>
            </div>
          </div>
        </div>

        <!-- Buttons section aligned to the right -->
        <div class="flex justify-end gap-4 mt-4">
          <div v-if="!editForm.isApi">
            <x-button type="submit" color="blue" :loading="extraAttr.loading">
              Save
            </x-button>
          </div>
        </div>
      </template>
    </div>
  </x-modal>
</template>

<style scoped>
.hospital-cash-benefit-select {
  display: block;
}

.hospital-cash-benefit-select :deep(.v-popper) {
  display: block;
}

.hospital-cash-benefit-select :deep(.truncate) {
  height: 2.5rem;
  box-sizing: border-box;
  display: flex;
  align-items: center;
}
</style>

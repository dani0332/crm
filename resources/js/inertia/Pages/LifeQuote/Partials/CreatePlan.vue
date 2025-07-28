<script setup>
import { watch } from 'vue';
import {
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
  paymentTermEnum: Object,
});

const { isRequired } = useRules();

// Add validation for non-negative numbers
const isNonNegative = value => {
  if (value === null || value === undefined || value === '') return true;
  return parseFloat(value) >= 0 || 'Value must be non-negative';
};

const validatePriceRange = value => {
  if (!value) return true;
  const price = cleanFormattedValueToFloat(value);
  if (price < 1 || price > 100000000) {
    return 'Value must be between 1 and 100,000,000';
  }
  return true;
};

const validatePolicyTerm = value => {
  if (!value) return true;
  const policyTerm = parseFloat(value);
  if (policyTerm < 1 || policyTerm > 100) {
    return 'Value must be between 1 to 100';
  }
  return true;
};

const shown = computed({
  get: () => props.modelValue,
  set: value => emit('update:modelValue', value),
});

const ridersData = ref({});

// Ensure rider values are always non-negative
watch(
  ridersData,
  newValue => {
    newValue.forEach(rider => {
      if (parseFloat(rider.price) < 0) rider.price = 0;
      if (parseFloat(rider.coverValue) < 0) rider.coverValue = 0;
    });
  },
  { deep: true },
);

const page = usePage();

const emit = defineEmits(['success', 'error']);

const dateFormat = date => useDateFormat(date, 'DD-MM-YYYY').value;

const active = ref(false);

const lifeCoverToggled = true;

const notification = useNotifications('toast');

const options = reactive({
  providerPlans: [],
  loading: false,
});

const availableInsuranceProviders = computed(() => {
  return props.insuranceProviders;
});

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
  isVariant: false,
  update: false,
  initialPrice: 0,
});

// Ensure form numeric values are always non-negative
watch(
  () => [
    createForm.sumAssured,
    createForm.actualPremium,
    createForm.policyTerm,
  ],
  ([sumAssured, actualPremium, policyTerm]) => {
    if (parseFloat(sumAssured) < 0) createForm.sumAssured = 0;
    if (parseFloat(actualPremium) < 0) createForm.actualPremium = 0;
    if (parseFloat(policyTerm) < 0) createForm.policyTerm = 0;
  },
);

const onSubmit = isValid => {
  if (!isValid) {
    return;
  }
  createForm.loading = true;

  // Convert riders' price and coverValue to floats
  const processedRiders = ridersData.value.map(rider => ({
    ...rider,
    price: Number(parseFloat(rider.price).toFixed(2)) || 0,
    coverValue: Number(parseFloat(rider.coverValue).toFixed(2)) || 0,
  }));

  // Ensure numeric form values are properly converted
  createForm.sumAssured =
    Number(parseFloat(createForm.sumAssured).toFixed(2)) || 0;
  createForm.actualPremium =
    Number(parseFloat(createForm.actualPremium).toFixed(2)) || 0;
  createForm.policyTerm = parseInt(createForm.policyTerm) || 0;

  createForm.riders = processedRiders;
  createForm.initialPrice =
    parseFloat(createForm.actualPremium).toFixed(2) || 0;

  // remove loading from createForm
  // const data  = createForm.filter((item) => item !== 'loading');
  axios
    .post('/personal-quotes/life-plan-manual-create', {
      quoteUID: props.uuid,
      formData: createForm,
    })
    .then(res => {
      if (res.status == 200) {
        emit('success');
      }
    })
    .catch(err => {
      notification.error({
        title: err?.response?.data?.message ?? 'Something Went wrong',
        position: 'top',
      });
      emit('error');
    })
    .finally(() => {
      createForm.loading = false;
    });
};

watch(
  () => createForm?.providerId,
  value => {
    if (value) {
      fetchProviderPlans();
    }
  },
);

watch(
  () => createForm?.isUW,
  value => {
    if (createForm.providerId) {
      fetchProviderPlans();
    }
  },
);

// Watch for plan changes with immediate and deep options
watch(
  () => createForm.planId,
  newPlanId => {
    if (newPlanId) {
      getRiderDetails(newPlanId);
    }
  },
  { immediate: true },
);

const fetchProviderPlans = () => {
  if (!createForm.providerId) {
    return;
  }

  options.loading = true;
  options.providerPlans = [];
  axios
    .get(`/personal-quotes/life/provider-plans/${createForm.providerId}`)
    .then(res => {
      if (res.data.plans) {
        if (!createForm.isUW) {
          options.providerPlans = res.data.plans.filter(
            plan =>
              !props.plans.some(
                existingPlan => existingPlan.planId === plan.id,
              ),
          );
        } else if (createForm.isUW) {
          options.providerPlans = res.data.plans.filter(
            plan =>
              !props.plans.some(
                existingPlan =>
                  existingPlan.planId === plan.id &&
                  existingPlan.isUnderwritten,
              ),
          );
        }
      } else {
        options.providerPlans = [];
      }
    })
    .catch(err => {
      emit('error');
    })
    .finally(() => {
      options.loading = false;
    });
};

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

const validateCoverValue = value => {
  const cleanValue = cleanFormattedValueToFloat(value);
  if (cleanValue < 0) {
    return 'Cover value must be non-negative';
  }
  if (cleanValue > cleanFormattedValueToFloat(formattedSumAssured.value)) {
    return `Cover value must not exceed ${createForm.sumAssured}`;
  }
  return true;
};

const getRiderDetails = async planId => {
  try {
    const res = await axios.get(`/personal-quotes/life/riders/${planId}`);

    // Clear existing options if needed
    ridersData.value = res.data.map(rider => ({
      riderId: rider.rider_id,
      active: 0,
      price: 0,
      coverValue: 0,
      text: rider.rider.text,
      inputRequired: rider.input_required,
    }));
  } catch (error) {
    console.log(error);

    notification.error({
      title: 'Error fetching rider details',
      position: 'top',
    });
  }
};

const formattedSumAssured = useFormattedNumberField(createForm, 'sumAssured');
const formattedActualPremium = useFormattedNumberField(
  createForm,
  'actualPremium',
);
</script>

<template>
  <x-modal
    v-model="shown"
    size="lg"
    title="Add Plan"
    show-close
    backdrop
    is-form
    @submit="onSubmit"
  >
    <div class="mx-auto p-6 bg-white rounded-lg">
      <div class="grid grid-cols-2 gap-4">
        <div>
          <div class="flex items-center space-x-6">
            <x-label>
              Is the quote underwritten? <span class="text-red-500">*</span>
            </x-label>
            <x-form-group v-model="createForm.isUW" class="mt-5">
              <x-radio :value="1" label="Yes" />
              <x-radio :value="0" label="No" />
            </x-form-group>
          </div>
        </div>
        <div>
          <label for="providerId" class="block font-medium text-gray-700 mb-1"
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
            id="providerId"
          />
        </div>
        <div>
          <label for="planId" class="block font-medium text-gray-700 mb-1"
            >Plan <span class="text-red-500">*</span></label
          >
          <x-select
            v-model="createForm.planId"
            placeholder="Select Plan"
            class="w-full"
            :options="
              options.providerPlans?.map(item => ({
                value: item.id,
                label: item.text,
              }))
            "
            :loading="options.loading"
            :rules="[isRequired]"
            id="planId"
          />
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label for="currency" class="block font-medium text-gray-700 mb-1"
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
              id="currency"
            />
          </div>
          <div>
            <label for="sumAssured" class="block font-medium text-gray-700 mb-1"
              >Sum Assured <span class="text-red-500">*</span></label
            >
            <x-input
              v-model="formattedSumAssured"
              placeholder="Enter Sum Assured"
              :rules="[isRequired, isNonNegative, validatePriceRange]"
              class="w-full"
              type="text"
              @keydown="e => preventInvalidInputs(e, true, false)"
              id="sumAssured"
            />
          </div>
        </div>

        <div>
          <label for="policyTerm" class="block font-medium text-gray-700 mb-1"
            >Policy Term <span class="text-red-500">*</span></label
          >
          <x-input
            v-model="createForm.policyTerm"
            placeholder="Enter Policy Term"
            :rules="[isRequired, isNonNegative, validatePolicyTerm]"
            class="w-full"
            type="number"
            min="0"
            @keydown="e => preventInvalidInputs(e, false)"
            id="policyTerm"
          />
        </div>

        <div>
          <label for="paymentTerm" class="block font-medium text-gray-700 mb-1"
            >Payment Frequency <span class="text-red-500">*</span></label
          >
          <x-select
            v-model="createForm.paymentTerm"
            placeholder="Select Payment Frequency"
            class="w-full"
            :options="filteredPaymentTerms"
            :rules="[isRequired]"
            id="paymentTerm"
          />
        </div>

        <div>
          <label for="price" class="block font-medium text-gray-700 mb-1"
            >Price (VAT not applicable)
            <span class="text-red-500">*</span></label
          >
          <x-input
            v-model="formattedActualPremium"
            placeholder="Enter Price"
            :rules="[isRequired, isNonNegative, validatePriceRange]"
            class="w-full"
            type="text"
            @keydown="e => preventInvalidInputs(e, true, true)"
            id="price"
          />
        </div>

        <div>
          <label
            for="insurerQuoteNo"
            class="block font-medium text-gray-700 mb-1"
            >Insurer Quote Number <span class="text-red-500">*</span></label
          >
          <x-input
            v-model="createForm.insurerQuoteNo"
            placeholder="Enter Insurer Quote Number"
            :rules="[isRequired]"
            class="w-full"
            id="insurerQuoteNo"
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
            @keydown="e => preventInvalidInputs(e, true)"
            class="w-full h-10 p-2 rounded-md"
            v-model="createForm.sumAssured"
            min="0"
            disabled
          />

          <x-toggle
            v-model="lifeCoverToggled"
            color="emerald"
            size="lg"
            disabled
          />
          <x-input
            type="number"
            @keydown="
              e => (e.key === 'e' || e.key === '-') && e.preventDefault()
            "
            class="w-full h-10 p-2 rounded-md"
            v-model="createForm.actualPremium"
            min="0"
            disabled
          />
        </div>
        <div
          class="grid grid-cols-6 items-center gap-4 p-2 border-b"
          v-for="(rider, index) in ridersData"
          :key="rider.id"
        >
          <span class="text-gray-700 col-span-2">{{ rider.text }}</span>
          <span class="text-gray-700">{{
            rider.active ? 'Included' : 'Optional'
          }}</span>
          <x-input
            type="number"
            :disabled="!rider.active"
            @keydown="e => preventInvalidInputs(e, true, false)"
            class="w-full h-10 p-2 rounded-md"
            v-model="rider.coverValue"
            min="0"
            :rules="
              rider.active
                ? [
                    isNonNegative,
                    val => validateCoverValue(val),
                    rider.inputRequired ? isRequired : null,
                  ].filter(Boolean)
                : []
            "
          />
          <x-toggle v-model="rider.active" color="success" size="lg" />
          <x-input
            type="number"
            :disabled="!rider.active"
            @keydown="e => preventInvalidInputs(e, true)"
            class="w-full h-10 p-2 rounded-md"
            v-model="rider.price"
            :rules="rider.active ? [isNonNegative] : []"
          />
        </div>
      </div>
    </div>

    <template #actions>
      <div class="flex justify-end">
        <x-button type="submit" color="emerald" :loading="createForm.loading">
          Save
        </x-button>
      </div>
    </template>
  </x-modal>
</template>

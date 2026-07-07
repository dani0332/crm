<script setup>
import { h, defineComponent } from 'vue';
const { isRequired } = useRules();

// Field Loader component for OCR loading indicators
const FieldLoader = defineComponent({
  props: {
    loading: {
      type: Boolean,
      default: false,
    },
  },
  setup(props, { slots }) {
    return () =>
      h('div', { class: 'relative' }, [
        slots.default && slots.default(),
        props.loading &&
          h(
            'div',
            {
              class:
                'absolute inset-0 bg-white bg-opacity-70 flex items-center justify-center rounded z-10',
            },
            [
              h('div', {
                class:
                  'animate-spin h-5 w-5 border-2 border-gray-600 border-t-transparent rounded-full',
              }),
            ],
          ),
      ]);
  },
});

const props = defineProps({
  sendUpdateLog: {
    type: Object,
    required: true,
  },
  insuranceProviders: {
    type: Array,
    required: true,
  },
  sendUpdateStatusEnum: {
    type: Array,
    required: true,
  },
  quote: {
    type: Object,
    required: true,
  },
  quoteType: {
    type: String,
    required: true,
  },
  isUpdateBooked: {
    type: Boolean,
    required: true,
  },
  isEditDisabledForQueuedBooking: Boolean,
  ocrLoadingDocType: {
    type: [String, null],
    default: null,
  },
  showOcrNotification: {
    type: Boolean,
    default: false,
  },
  ocrLoadingDocTypes: {
    type: Object,
    default: () => new Set(),
  },
  isDocTypeLoading: {
    type: Function,
    required: false,
  },
});

const state = reactive({
  isEdit: false,
});

const page = usePage();
const notification = useToast();
const ocrDocumentTypeEnum = page.props.ocrDocumentTypeEnum;

// Helper function to check if a document type is currently being processed
const localIsDocTypeLoading = docType => {
  let result = false;

  // Handle both function and string types for backwards compatibility
  if (typeof props.ocrLoadingDocType === 'function') {
    result = props.ocrLoadingDocType(docType);
  }
  // Check the reactive Set if available
  else if (
    props.ocrLoadingDocTypes &&
    props.ocrLoadingDocTypes.has &&
    props.ocrLoadingDocTypes.has(docType)
  ) {
    result = true;
  }
  // Check the function prop if available
  else if (typeof props.isDocTypeLoading === 'function') {
    result = props.isDocTypeLoading(docType);
  }
  // Fall back to old string comparison
  else {
    result = props.ocrLoadingDocType === docType;
  }

  return result;
};

const dateToYMD = date => {
  if (date) {
    // Check if date is already in YMD format
    const ymdRegex = /^\d{4}-\d{2}-\d{2}( \d{2}:\d{2}:\d{2})?$/;
    if (ymdRegex.test(date)) {
      return date.split(' ')[0]; // Return only the date part
    }
    const [day, month, year] = date.split('-');
    return `${year}-${month}-${day}`;
  }
  return '';
};

const issuanceStatus = page.props.issuanceStatuses;

const issuanceStatusOptions = computed(() => {
  return issuanceStatus.map(status => {
    return { label: status.text, value: status.id };
  });
});

const issuanceStatusText = computed(() => {
  const statusMap = new Map(
    issuanceStatus.map(status => [status.id, status.text]),
  );

  return value => statusMap.get(value);
});

/*const issuanceStatusTextById = computed(() => {
  const statusMap = new Map(page.props.issuanceStatuses.map(status => [status.id, status.text]));
  return (id) => statusMap.get(id);
});*/

const isCIR = computed(() => {
  return props.sendUpdateLog.category.code === props.sendUpdateStatusEnum.CIR;
});

const isCPD = computed(() => {
  return props.sendUpdateLog.category.code === props.sendUpdateStatusEnum.CPD;
});

const dateFormat = date =>
  date ? useDateFormat(date, 'DD-MM-YYYY').value : '-';

const insuranceProvidersOptions = computed(() => {
  return props?.insuranceProviders?.map(provider => ({
    value: provider.id,
    label: provider.text,
  }));
});

const providerName = computed(() => {
  let provider;
  let insuranceProviderId =
    props.sendUpdateLog?.insurance_provider_id ||
    props.quote?.insurance_provider_id ||
    props.quote?.car_plan_provider_id ||
    null;
  if (insuranceProviderId) {
    provider = props?.insuranceProviders?.find(
      provider => provider.id === insuranceProviderId,
    );
  } else {
    return null;
  }

  return provider ? provider.text : null;
});

const filledExpiryDate = computed(() => {
  if (isCPD.value) {
    return (
      props.sendUpdateLog?.expiry_date ||
      props.quote?.policy_expiry_date ||
      null
    );
  }

  return props.sendUpdateLog?.expiry_date || null;
});

const policyDetailsForm = useForm({
  code: props.sendUpdateLog?.code,
  first_name:
    props.sendUpdateLog?.first_name || props.quote?.first_name || null,
  last_name: props.sendUpdateLog?.last_name || props.quote?.last_name || null,
  provider_name:
    props.sendUpdateLog?.provider_name || providerName.value || null,
  insurance_provider_id:
    props.sendUpdateLog?.insurance_provider_id ||
    props.quote?.insurance_provider_id ||
    props.quote?.car_plan_provider_id ||
    null,
  plan_id: props.sendUpdateLog?.plan_id || props.quote?.plan_id || null,
  policy_number:
    props.sendUpdateLog?.policy_number || props.quote?.policy_number || null,
  issuance_date:
    props.sendUpdateLog?.issuance_date ||
    props.quote?.policy_issuance_date ||
    null,
  start_date:
    props.sendUpdateLog?.start_date ||
    dateToYMD(props.quote?.policy_start_date) ||
    null,
  expiry_date: dateToYMD(filledExpiryDate.value),
  insurer_quote_number:
    props.sendUpdateLog?.insurer_quote_number ||
    props.quote?.insurer_quote_number ||
    null,
  issuance_status_id:
    props.sendUpdateLog?.issuance_status_id ||
    props.quote?.policy_issuance_status_id ||
    null,
  id: props.sendUpdateLog.id,
  quote_type: props.quoteType,
});

const onUpdate = isValid => {
  if (!isValid) return;
  policyDetailsForm.post(route('send-update.save-policy-details'), {
    preserveScroll: true,
    onSuccess: ({ props }) => {
      notification.success({
        title: 'The request has been updated',
        position: 'top',
      });
      state.isEdit = false;
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

const quoteTypesToCheck = ['Car', 'Health', 'Travel']; //Ecommerce LOBs
const isEcom = computed(() => {
  return quoteTypesToCheck.includes(props.quoteType);
});

const plansOptions = ref([]);

const fetchPlans = (quoteType, providerId) => {
  let url = `/get-plans/${quoteType}/${providerId}`;
  axios
    .get(url)
    .then(res => {
      plansOptions.value = res.data.map(plan => ({
        label: plan.text,
        value: plan.id,
      }));
    })
    .catch(err => {
      console.log(err);
    });
};

onMounted(() => {
  if (isEcom.value && props.sendUpdateLog?.insurance_provider_id) {
    fetchPlans(props.quoteType, props.sendUpdateLog.insurance_provider_id);
  }
});

watch(
  () => policyDetailsForm.insurance_provider_id,
  providerId => {
    if (isEcom.value && providerId) {
      fetchPlans(props.quoteType, providerId);
    }
  },
);

const isMobile = computed(() => {
  return window.innerWidth <= 768; // Adjust the breakpoint as needed
});

const onEdit = () => {
  if (props.isUpdateBooked) {
    notification.error({
      title: 'Update already booked',
      position: 'top',
    });
  } else {
    state.isEdit = true;
  }
};

const onCancel = () => {
  state.isEdit = false;
  policyDetailsForm.first_name =
    props.sendUpdateLog?.first_name || props.quote?.first_name || null;
  policyDetailsForm.last_name =
    props.sendUpdateLog?.last_name || props.quote?.last_name || null;
  policyDetailsForm.provider_name =
    props.sendUpdateLog?.provider_name || providerName.value || null;
  policyDetailsForm.insurance_provider_id =
    props.sendUpdateLog?.insurance_provider_id ||
    props.quote?.insurance_provider_id ||
    props.quote?.car_plan_provider_id ||
    null;
  policyDetailsForm.plan_id =
    props.sendUpdateLog?.plan_id || props.quote?.plan_id || null;
  policyDetailsForm.policy_number =
    props.sendUpdateLog?.policy_number || props.quote?.policy_number || null;
  policyDetailsForm.issuance_date =
    props.sendUpdateLog?.issuance_date ||
    props.quote?.policy_issuance_date ||
    null;
  policyDetailsForm.start_date =
    props.sendUpdateLog?.start_date ||
    dateToYMD(props.quote?.policy_start_date) ||
    null;
  policyDetailsForm.expiry_date = dateToYMD(filledExpiryDate.value);
  policyDetailsForm.insurer_quote_number =
    props.sendUpdateLog?.insurer_quote_number ||
    props.quote?.insurer_quote_number ||
    null;
  policyDetailsForm.issuance_status_id =
    props.sendUpdateLog?.issuance_status_id ||
    props.quote?.policy_issuance_status_id ||
    null;
};

const rules = {
  expiry_date: v => {
    if (v) {
      const date = new Date(policyDetailsForm.expiry_date);
      const startDate = new Date(policyDetailsForm.start_date);
      if (startDate >= date) {
        return 'Expiry date should be greater than Start Date';
      }
      let isDate = date instanceof Date;
      return isDate || 'Date format is incorrect';
    }
    return !!v || 'This field is required';
  },
};
</script>

<template>
  <div class="p-4 rounded shadow mb-6 bg-white">
    <Collapsible expanded>
      <template #header>
        <div class="flex justify-between gap-4 items-center">
          <h3 class="font-semibold text-primary-800 text-lg">Policy Details</h3>
        </div>
      </template>

      <template #body>
        <x-divider class="my-4" />
        <x-form @submit="onUpdate" :auto-focus="false">
          <div class="text-sm">
            <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4 break-words">
              <!-- First name -->
              <div class="grid sm:grid-cols-2">
                <dt>
                  <x-tooltip>
                    <label
                      class="font-bold text-gray-800 underline decoration-dotted decoration-primary-700"
                    >
                      FIRST NAME
                    </label>
                    <template #tooltip>
                      This field captures the policyholder's first name,
                      representing the primary contact person associated with
                      the policy.
                    </template>
                  </x-tooltip>
                </dt>
                <dd>
                  <x-input
                    v-if="isCPD"
                    v-model="policyDetailsForm.first_name"
                    :disabled="!state.isEdit"
                    class="w-full"
                  />
                  <span v-else>{{ policyDetailsForm.first_name }}</span>
                </dd>
              </div>

              <!-- Last name -->
              <div class="grid sm:grid-cols-2">
                <dt>
                  <x-tooltip>
                    <label
                      class="font-bold text-gray-800 underline decoration-dotted decoration-primary-700"
                    >
                      LAST NAME
                    </label>
                    <template #tooltip>
                      Records the policyholder's surname or family name.
                    </template>
                  </x-tooltip>
                </dt>
                <dd>
                  <x-input
                    v-if="isCPD"
                    v-model="policyDetailsForm.last_name"
                    :disabled="!state.isEdit"
                    class="w-full"
                  />
                  <span v-else>{{ policyDetailsForm.last_name }}</span>
                </dd>
              </div>

              <!-- Provider Name -->
              <div class="grid sm:grid-cols-2">
                <dt>
                  <x-tooltip>
                    <label
                      class="font-bold text-gray-800 underline decoration-dotted decoration-primary-700"
                    >
                      PROVIDER NAME
                    </label>
                    <template #tooltip>
                      Provider is fixed based on the original policy and cannot
                      be changed in a correction request.
                    </template>
                  </x-tooltip>
                </dt>
                <dd>
                  <span>{{
                    policyDetailsForm.provider_name || 'Not Set'
                  }}</span>
                </dd>
              </div>

              <!-- Plan Name -->
              <div class="grid sm:grid-cols-2">
                <dt>
                  <x-tooltip>
                    <label
                      class="font-bold text-gray-800 underline decoration-dotted decoration-primary-700"
                    >
                      PLAN NAME
                    </label>
                    <template #tooltip>
                      Identifies the specific coverage or insurance plan offered
                      by the provider.
                    </template>
                  </x-tooltip>
                </dt>
                <dd>
                  <x-select
                    v-if="isCPD && isEcom"
                    v-model="policyDetailsForm.plan_id"
                    :options="plansOptions"
                    placeholder="Plan Name"
                    :disabled="!state.isEdit"
                    filterable
                    filterPlaceholder="Filter Plan Name...."
                  />
                </dd>
              </div>

              <!-- Policy Number -->
              <div class="grid sm:grid-cols-2">
                <dt>
                  <x-tooltip>
                    <label
                      class="font-bold text-gray-800 underline decoration-dotted decoration-primary-700"
                    >
                      POLICY NUMBER
                    </label>
                    <template #tooltip>
                      The unique Insurance policy number for the chosen
                      insurance plan offered by the provider.
                    </template>
                  </x-tooltip>
                </dt>
                <dd>
                  <FieldLoader
                    v-if="isCPD || isCIR"
                    :loading="
                      props.showOcrNotification &&
                      localIsDocTypeLoading(
                        ocrDocumentTypeEnum?.MOTOR_INSURANCE_POLICY_SCHEDULE
                          ?.value,
                      )
                    "
                  >
                    <x-input
                      :disabled="!state.isEdit"
                      v-model="policyDetailsForm.policy_number"
                      placeholder="Enter policy number"
                      class="w-full"
                    />
                  </FieldLoader>
                  <span v-else>{{ policyDetailsForm.policy_number }}</span>
                </dd>
              </div>

              <!-- Issuance Date -->
              <div class="grid sm:grid-cols-2">
                <dt>
                  <x-tooltip>
                    <label
                      class="font-bold text-gray-800 underline decoration-dotted decoration-primary-700"
                    >
                      ISSUANCE DATE
                    </label>
                    <template #tooltip>
                      Signifies the date when the insurance policy was
                      officially issued.
                    </template>
                  </x-tooltip>
                </dt>
                <dd>
                  <DatePicker
                    v-if="isCPD"
                    v-model="policyDetailsForm.issuance_date"
                    name="issuance_date"
                    :disabled="!state.isEdit"
                    placeholder="dd-mm-yyyy"
                    class="w-full"
                  />
                  <span v-else>{{
                    dateFormat(policyDetailsForm.issuance_date)
                  }}</span>
                </dd>
              </div>

              <!-- Start Date -->
              <div class="grid sm:grid-cols-2">
                <dt>
                  <x-tooltip>
                    <label
                      class="font-bold text-gray-800 underline decoration-dotted decoration-primary-700"
                    >
                      START DATE
                    </label>
                    <template #tooltip>
                      Indicates the commencement date of the insurance coverage,
                      marking when the policy becomes effective.
                    </template>
                  </x-tooltip>
                </dt>
                <dd>
                  <FieldLoader
                    v-if="isCPD || isCIR"
                    :loading="
                      props.showOcrNotification &&
                      localIsDocTypeLoading(
                        ocrDocumentTypeEnum?.MOTOR_INSURANCE_POLICY_SCHEDULE
                          ?.value,
                      )
                    "
                  >
                    <DatePicker
                      v-model="policyDetailsForm.start_date"
                      name="start_date"
                      :disabled="!state.isEdit"
                      placeholder="dd-mm-yyyy"
                      class="w-full"
                    />
                  </FieldLoader>
                  <span v-else>{{
                    dateFormat(policyDetailsForm.start_date)
                  }}</span>
                </dd>
              </div>

              <!-- Expiry Date -->
              <div class="grid sm:grid-cols-2">
                <dt>
                  <x-tooltip>
                    <label
                      class="font-bold text-gray-800 underline decoration-dotted decoration-primary-700"
                    >
                      EXPIRY DATE
                    </label>
                    <template #tooltip>
                      This field records the date when the insurance coverage is
                      set to expire, marking the end of the policy's validity.
                    </template>
                  </x-tooltip>
                </dt>
                <dd>
                  <FieldLoader
                    :loading="
                      props.showOcrNotification &&
                      localIsDocTypeLoading(
                        ocrDocumentTypeEnum?.MOTOR_INSURANCE_POLICY_SCHEDULE
                          ?.value,
                      )
                    "
                  >
                    <DatePicker
                      v-model="policyDetailsForm.expiry_date"
                      name="expiry_date"
                      :rules="[rules.expiry_date]"
                      :disabled="!state.isEdit"
                      placeholder="dd-mm-yyyy"
                      class="w-full"
                      :custom-error="policyDetailsForm.errors.expiry_date"
                    />
                  </FieldLoader>
                </dd>
              </div>

              <!-- Insurer Quote Number -->
              <div class="grid sm:grid-cols-2">
                <dt>
                  <x-tooltip>
                    <label
                      class="font-bold text-gray-800 underline decoration-dotted decoration-primary-700"
                    >
                      INSURER QUOTE NUMBER
                    </label>
                    <template #tooltip>
                      Refers to the unique identifier associated with the
                      initial quote provided by the insurer.
                    </template>
                  </x-tooltip>
                </dt>
                <dd>
                  <x-input
                    v-if="isCPD"
                    :disabled="!state.isEdit"
                    v-model="policyDetailsForm.insurer_quote_number"
                    placeholder="Enter Insurer Quote number"
                    class="w-full"
                  />
                  <span v-else>{{
                    policyDetailsForm.insurer_quote_number
                  }}</span>
                </dd>
              </div>

              <!-- Issuance Status -->
              <div class="grid sm:grid-cols-2">
                <dt>
                  <x-tooltip>
                    <label
                      class="font-bold text-gray-800 underline decoration-dotted decoration-primary-700"
                    >
                      ISSUANCE STATUS
                    </label>
                    <template #tooltip>
                      Indicates the current state or progress of policy
                      issuance, tracking whether it's pending, approved, or
                      completed.
                    </template>
                  </x-tooltip>
                </dt>
                <dd>
                  <x-select
                    v-if="isCPD"
                    v-model="policyDetailsForm.issuance_status_id"
                    :options="issuanceStatusOptions"
                    placeholder="Select Status"
                    :disabled="!state.isEdit"
                    class="w-fit"
                    filterable
                    filterPlaceholder="Filter Status...."
                  />
                  <span v-else>{{
                    issuanceStatusText(policyDetailsForm.issuance_status_id) ??
                    'N/A'
                  }}</span>
                </dd>
              </div>
            </dl>
          </div>
          <x-divider class="my-4 mt-10" />
          <div class="flex justify-end gap-2">
            <template v-if="!state.isEdit">
              <x-tooltip v-if="props.isEditDisabledForQueuedBooking">
                <x-button
                  size="sm"
                  @click="onEdit"
                  :disabled="props.isEditDisabledForQueuedBooking"
                >
                  Edit
                </x-button>
                <template #tooltip>
                  <span class="custom-tooltip-content">
                    No further action can be taken on Update Booking Queued or
                    Failed status.
                  </span>
                </template>
              </x-tooltip>
              <x-button
                v-else
                size="sm"
                @click="onEdit"
                class="focus:ring-2 focus:ring-black"
              >
                Edit
              </x-button>
            </template>
            <template v-else>
              <x-button
                class="focus:ring-2 focus:ring-black"
                size="sm"
                color="orange"
                @click="onCancel"
                :loading="policyDetailsForm.processing"
                :disabled="policyDetailsForm.processing"
              >
                Cancel
              </x-button>
              <x-button
                class="focus:ring-2 focus:ring-black"
                size="sm"
                color="primary"
                type="submit"
                :loading="policyDetailsForm.processing"
                :disabled="policyDetailsForm.processing"
                >Update</x-button
              >
            </template>
          </div>
        </x-form>
      </template>
    </Collapsible>
  </div>
</template>

<script setup>
import LazyPlanDetails from './Partials/PlanDetails.vue';
import QuoteDocuments from '../PersonalQuote/Partials/QuoteDocuments';
import LazyPolicyDetails from './Partials/PolicyDetails.vue';
import LazyBookingDetails from './Partials/BookingDetails.vue';
import LazyProviderDetails from './Partials/ProviderDetails.vue';
import { XInput } from '@indielayer/ui';

const props = defineProps({
  quoteType: String,
  sendUpdateLog: Object,
  sendUpdateOptions: Array,
  insuranceProviders: Object,
  sendUpdateStatusEnum: Array,
  quote: Object,
  indicativePrice: Object,
  membersDetail: Array,
  memberCategories: Array,
  documentTypes: Object,
  quoteDocuments: Object,
  storageUrl: String,
  realQuote: Object,
  isNegativeValue: Boolean,
  bookingDetails: Array,
  updateBtn: String,
  uploadedDocuments: Array,
  payments: Array,
  isPaymentVisible: Boolean,
  paymentDocumentTypes: Object,
  paymentStatusEnum: Array,
  paymentTooltipEnum: Object,
  paymentMethods: Object,
  quoteRequest: Object,
  isPolicyDetailsEnabled: Boolean,
  additionalField: {
    type: Object,
    default: [],
  },
  isPlanDetailAvailable: Boolean,
  quoteLink: String,
  isEditDisabledForQueuedBooking: Boolean,
  insuranceProviderId: Number,
  isCommVatNotAppEnabled: Boolean,
  isSentOrBooked: Boolean,
  disableMainBtn: String,
});

const page = usePage();
const notification = useToast();
const modelClass = 'App\\Models\\SendUpdateLog';
const { isRequired } = useRules();

const state = reactive({
  edit: false,
  redirectURL: '',
});

// as per the link 'Transaction Type' column -> https://docs.google.com/spreadsheets/d/1TE7RfMpEtL7kenl8s1DUVKRvP_DbUvCJ82XyCFYJ7Rw/edit#gid=803033517
const transactionType = computed(() => {
  if (
    [
      props.sendUpdateStatusEnum.CI,
      props.sendUpdateStatusEnum.CIR,
      props.sendUpdateStatusEnum.CPD,
    ].includes(props.sendUpdateLog?.category?.code) ||
    [
      props.sendUpdateStatusEnum.MPC,
      props.sendUpdateStatusEnum.CAAFE,
      props.sendUpdateStatusEnum.DTSI,
    ].includes(props.sendUpdateLog?.option?.code) ||
    (props.sendUpdateStatusEnum.EF === props.sendUpdateLog?.category?.code &&
      props.sendUpdateLog.option.code !== props.sendUpdateStatusEnum.ATICB)
  ) {
    return 'Endorsement';
  } else if (
    props.sendUpdateLog.option.code === props.sendUpdateStatusEnum.ATICB
  ) {
    return page.props.parentText;
  }

  return null;
});

const updateLogOptions = computed(() => {
  return props.sendUpdateOptions.map(child => ({
    value: child.id,
    label: child.title,
    slug: child.code,
  }));
});

const isUpdateBooked = computed(() => {
  return (
    props.sendUpdateLog.status === props.sendUpdateStatusEnum.UPDATE_BOOKED &&
    [
      props.sendUpdateStatusEnum.EF,
      props.sendUpdateStatusEnum.CI,
      props.sendUpdateStatusEnum.CIR,
      props.sendUpdateStatusEnum.CPD,
    ].includes(props.sendUpdateLog.category.code)
  );
});

const changeReasonOptions = computed(() => {
  return [];
});

const sendUpdateForm = useForm({
  notes: props.sendUpdateLog?.notes || '',
  category_id: props.sendUpdateLog?.category_id || null,
  option_id: props.sendUpdateLog?.option_id || null,
  change_reason: props.sendUpdateLog?.change_reason || '',
  quote_type_id: props.sendUpdateLog?.quote_type_id || null,
  personal_quote_id: props.sendUpdateLog?.personal_quote_id || null,
  status: props.sendUpdateLog?.status || '',
  quote_uuid: props.realQuote.uuid,
  car_addons: props.sendUpdateLog?.car_addons || null,
  emirates_id: props.sendUpdateLog?.emirates_id || null,
  seating_capacity: props.sendUpdateLog?.seating_capacity || null,
  endorsement_number: props.sendUpdateLog?.endorsement_number || null,
});

onMounted(() => {
  const params = new URLSearchParams(
    decodeURIComponent(page.url.split('?')[1]),
  );
  state.redirectURL = params.get('refURL');
});

const permissionsEnum = page.props.permissionsEnum;
const can = permission => useCan(permission);

const onEdit = () => {
  if (isUpdateBooked.value && !can(permissionsEnum.SEND_UPDATE_EDIT_NOTES)) {
    notification.error({
      title: 'Update already booked',
      position: 'top',
    });
  } else {
    state.edit = true;
  }
};

const onCancel = () => {
  state.edit = false;
  sendUpdateForm.notes = props.sendUpdateLog?.notes || '';
  sendUpdateForm.option_id = props.sendUpdateLog?.option_id || null;
  sendUpdateForm.car_addons = props.sendUpdateLog?.car_addons || null;
  sendUpdateForm.emirates_id = props.sendUpdateLog?.emirates_id || null;
  sendUpdateForm.seating_capacity =
    props.sendUpdateLog?.seating_capacity || null;
  sendUpdateForm.endorsement_number =
    props.sendUpdateLog?.endorsement_number || null;
};

const onUpdateLog = isValid => {
  if (!isValid) return;
  sendUpdateForm.patch(
    route('send-update.update', { id: props.sendUpdateLog.id }),
    {
      preserverScroll: true,
      onSuccess: ({ props }) => {
        notification.success({
          title: 'The request has been updated',
          position: 'top',
        });
        state.edit = false;
      },
      onError: errors => {
        Object.keys(errors).forEach(function (key) {
          notification.error({
            title: errors[key],
            position: 'top',
          });
        });
      },
    },
  );
};

const memberCategoryText = memberCategoryId =>
  computed(() => {
    return props.memberCategories.find(
      category => category.id === memberCategoryId,
    )?.text;
  });

const memberDataDocs = membersDetail => {
  return membersDetail
    .map(member => ({
      id: member.id,
      name: memberCategoryText(member.member_category_id).value,
    }))
    .filter(member => member.name !== undefined);
};

// it will only show the Booking Details section if send update types are in array.
const isBookingDetailsVisible = computed(() => {
  const validSlugs = [
    props.sendUpdateStatusEnum.EF,
    props.sendUpdateStatusEnum.CI,
    props.sendUpdateStatusEnum.CIR,
    props.sendUpdateStatusEnum.CPD,
  ];

  return validSlugs.includes(props.sendUpdateLog.category.code);
});

const additionalFieldOptions = computed(() => {
  if (props.additionalField) {
    return props.additionalField.map(additional => ({
      value: additional.id,
      label: additional.text,
    }));
  }
});

const onKeyPress = event => {
  if (event.key === 'e' || event.key === 'E') {
    event.preventDefault();
  }
};

const isAdditionalFieldError = ref(false);
function handleErrorStatusUpdate(newStatus) {
  isAdditionalFieldError.value = newStatus;
}

const showBookingFailedAlert = () => {
  if (
    props.isEditDisabledForQueuedBooking &&
    props.sendUpdateLog?.status ===
      props.sendUpdateStatusEnum.UPDATE_BOOKING_FAILED
  ) {
    notification.error({
      title: 'Endorsement Booking Failed! Please contact finance',
      position: 'top',
      timeout: 30000,
    });
  }
};

onBeforeMount(() => {
  showBookingFailedAlert();
});

const isEFOrEN = computed(() => {
  return ![
    props.sendUpdateStatusEnum.CI,
    props.sendUpdateStatusEnum.CIR,
    props.sendUpdateStatusEnum.CPU,
    props.sendUpdateStatusEnum.CPD,
  ].includes(props.sendUpdateLog.category.code);
});

const isAOCOV = computed(
  () => props.sendUpdateLog?.option?.code === props.sendUpdateStatusEnum.AOCOV,
);

const isCOEOrCOE_NFI = computed(() => {
  return [
    props.sendUpdateStatusEnum.COE,
    props.sendUpdateStatusEnum.COE_NFI,
  ].includes(props.sendUpdateLog?.option?.code);
});

const isCISCOrCISC_NFI = computed(() => {
  return [
    props.sendUpdateStatusEnum.CISC,
    props.sendUpdateStatusEnum.CISC_NFI,
  ].includes(props.sendUpdateLog?.option?.code);
});

const endorsementNumberValidation = event => {
  const charCode = event.charCode || event.keyCode;
  const char = String.fromCharCode(charCode);
  const regex = /^[a-zA-Z0-9\\|\/-]$/;

  if (!regex.test(char)) {
    event.preventDefault();
  }
};

const isEndorsementNumberRequired = computed(() => {
  return [
    props.sendUpdateStatusEnum.EF,
    props.sendUpdateStatusEnum.CI,
    props.sendUpdateStatusEnum.CIR,
  ].includes(props.sendUpdateLog.category.code);
});

const isLegacyPolicy = computed(() => {
  return (
    props.quote?.insly_migrated ||
    props.realQuote?.insly_migrated ||
    props.realQuote?.insly_id
  );
});
</script>

<template>
  <Head>
    <title>Send Update {{ sendUpdateLog.category.text }}</title>
  </Head>
  <div>
    <div class="p-4 rounded shadow mb-6 bg-white">
      <Collapsible expanded>
        <template #header>
          <div class="flex gap-2 w-100 flex-grow justify-between">
            <h3
              class="text-xs sm:text-lg font-semibold text-primary-800 capitalize"
            >
              {{ sendUpdateLog.category.text }}
            </h3>
            <Link :href="quoteLink">
              <x-button color="primary" size="sm" class="mr-5 text-xs"
                >Go back to lead</x-button
              >
            </Link>
          </div>
        </template>
        <template #body>
          <x-form @submit="onUpdateLog">
            <x-divider class="my-4" />
            <div class="text-sm">
              <dl class="grid md:grid-cols-2">
                <div class="grid sm:grid-cols-2 mb-2">
                  <dt>
                    <x-tooltip>
                      <label
                        class="font-bold text-gray-800 underline decoration-dotted decoration-primary-700"
                      >
                        SU REF ID
                      </label>
                      <template #tooltip>
                        A unique reference identifier assigned to each "Send
                        Update" request, allowing for easy tracking and
                        reference.
                      </template>
                    </x-tooltip>
                  </dt>
                  <dd>{{ sendUpdateLog.code }}</dd>
                </div>
                <div class="grid md:grid-cols-2">
                  <dt class="font-bold">NOTES</dt>
                  <dd>
                    <x-textarea
                      v-model="sendUpdateForm.notes"
                      size="xs"
                      :disabled="!state.edit"
                      maxlength="250"
                      class="h-7"
                      rows="1"
                    />
                    <p class="text-xs text-right" v-if="state.edit">
                      {{ sendUpdateForm.notes.length }} / 250
                    </p>
                  </dd>
                </div>
                <div class="grid sm:grid-cols-2 mb-2 h-10">
                  <template
                    v-if="
                      props.sendUpdateLog.category.code !==
                        props.sendUpdateStatusEnum.EN &&
                      props.sendUpdateLog.category.code !==
                        props.sendUpdateStatusEnum.CPU
                    "
                  >
                    <dt>
                      <x-tooltip>
                        <label
                          class="font-bold text-gray-800 underline decoration-dotted decoration-primary-700"
                        >
                          TRANSACTION TYPE
                        </label>
                        <template #tooltip>
                          Refers to category of the financial transaction
                          associated with the policy. It helps classify the
                          specific type of transaction being recorded or
                          processed within the system.
                        </template>
                      </x-tooltip>
                    </dt>
                    <dd>{{ transactionType || '' }}</dd>
                  </template>
                </div>
                <div class="grid md:grid-cols-2 mb-2">
                  <dt>
                    <x-tooltip>
                      <label
                        class="font-bold text-gray-800 underline decoration-dotted decoration-primary-700"
                      >
                        STATUS
                      </label>
                      <template #tooltip>
                        The current status of the ""Send Update"" request,
                        indicating whether it is pending, transaction approved,
                        or declined, among other possible states.
                      </template>
                    </x-tooltip>
                  </dt>
                  <dd>{{ sendUpdateLog.display_status }}</dd>
                </div>
                <div v-if="isEFOrEN" class="grid sm:grid-cols-2">
                  <template v-if="isEFOrEN">
                    <dt>
                      <x-tooltip>
                        <label
                          class="font-bold text-gray-800 underline decoration-dotted decoration-primary-700"
                        >
                          SUB TYPE
                        </label>
                        <template #tooltip>
                          A further classification of the "Send Update" request,
                          providing additional context or details.
                        </template>
                      </x-tooltip>
                    </dt>
                    <dd>
                      <x-select
                        size="xs"
                        :disabled="!state.edit || isUpdateBooked"
                        v-model="sendUpdateForm.option_id"
                        :options="updateLogOptions"
                        class="w-3/4"
                      />
                    </dd>
                  </template>
                </div>
                <div
                  class="grid sm:grid-cols-2 mb-2"
                  v-if="
                    props.quoteType === page.props.quoteTypeCodeEnum.Car &&
                    (isAOCOV || isCOEOrCOE_NFI || isCISCOrCISC_NFI)
                  "
                >
                  <template v-if="props.additionalField && isAOCOV">
                    <dt>
                      <label
                        class="font-bold text-gray-800 decoration-dotted decoration-primary-700"
                      >
                        ADDONS
                      </label>
                    </dt>
                    <dd>
                      <x-select
                        :rules="[isRequired]"
                        v-model="sendUpdateForm.car_addons"
                        placeholder="Select Addons"
                        :options="additionalFieldOptions"
                        size="xs"
                        :disabled="!state.edit"
                        :class="{ 'pointer-events-none': !state.edit }"
                        multiple
                        :error="isAdditionalFieldError"
                      />
                    </dd>
                  </template>
                  <template v-else-if="props.additionalField && isCOEOrCOE_NFI">
                    <dt>
                      <label
                        class="font-bold text-gray-800 decoration-dotted decoration-primary-700"
                      >
                        EMIRATE OF REGISTRATION
                      </label>
                    </dt>
                    <dd>
                      <x-select
                        :rules="[isRequired]"
                        v-model="sendUpdateForm.emirates_id"
                        placeholder="Select Emirate of Registration"
                        :options="additionalFieldOptions"
                        size="xs"
                        :disabled="!state.edit"
                        :error="isAdditionalFieldError"
                      />
                    </dd>
                  </template>
                  <template
                    v-else-if="props.additionalField && isCISCOrCISC_NFI"
                  >
                    <dt>
                      <label
                        class="font-bold text-gray-800 decoration-dotted decoration-primary-700"
                      >
                        SEATING CAPACITY
                      </label>
                    </dt>
                    <dd>
                      <x-input
                        :rules="[isRequired]"
                        placeholder="Enter Seating Capacity"
                        v-model="sendUpdateForm.seating_capacity"
                        size="xs"
                        :disabled="!state.edit"
                        type="number"
                        @keypress="onKeyPress"
                        :error="isAdditionalFieldError"
                      />
                    </dd>
                  </template>
                </div>
                <div
                  class="grid sm:grid-cols-2"
                  v-if="can(permissionsEnum.TAP_BETA_ACCESS)"
                >
                  <dt>
                    <x-tooltip>
                      <label
                        class="font-bold text-gray-800 underline decoration-dotted decoration-primary-700"
                      >
                        ENDORSEMENT NUMBER
                      </label>
                      <template #tooltip>
                        The number associated with a specific endorsement or
                        update to the policy. This is the endorsement number
                        available on the tax invoice or provided by the
                        insurance provider.
                      </template>
                    </x-tooltip>
                  </dt>
                  <dd>
                    <x-input
                      :rules="isEndorsementNumberRequired ? [isRequired] : []"
                      v-model="sendUpdateForm.endorsement_number"
                      size="xs"
                      :disabled="!state.edit || props.isSentOrBooked"
                      placeholder="Enter Endorsement Number"
                      maxlength="23"
                      @keypress="endorsementNumberValidation"
                      class="w-3/4"
                    />
                  </dd>
                </div>
              </dl>
            </div>
            <div class="flex justify-end">
              <template v-if="!state.edit">
                <x-tooltip v-if="props.isEditDisabledForQueuedBooking">
                  <x-button
                    class="focus:ring-2 focus:ring-black"
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
                <x-button v-else size="sm" @click="onEdit"> Edit </x-button>
              </template>
              <template v-else>
                <x-button
                  size="sm"
                  color="orange"
                  @click="onCancel"
                  class="mr-3 focus:ring-2 focus:ring-black"
                  :loading="sendUpdateForm.processing"
                  :disabled="sendUpdateForm.processing"
                >
                  Cancel
                </x-button>
                <x-button
                  class="focus:ring-2 focus:ring-black"
                  size="sm"
                  color="primary"
                  type="submit"
                  :loading="sendUpdateForm.processing"
                  :disabled="sendUpdateForm.processing"
                >
                  Update
                </x-button>
              </template>
            </div>
          </x-form>
        </template>
      </Collapsible>
    </div>

    <LazyProviderDetails
      v-if="isLegacyPolicy"
      :sendUpdateLog="sendUpdateLog"
      :insuranceProviders="props.insuranceProviders"
      :insurance-provider-id="props.insuranceProviderId"
    />

    <!-- Indicative additional price & Plan details comp -->
    <LazyPlanDetails
      v-if="props.isPlanDetailAvailable"
      :sendUpdateLog="sendUpdateLog"
      :updateLogOptions="updateLogOptions"
      :insuranceProviders="props.insuranceProviders"
      :quoteType="quoteType"
      :isUpdateBooked="isUpdateBooked"
      :isEditDisabledForQueuedBooking="props.isEditDisabledForQueuedBooking"
    />

    <PaymentTableNew
      v-if="props.isPaymentVisible"
      :quoteType="props.quoteType"
      :payments="props.payments || []"
      :proformaPayment="
        payments.find(
          item =>
            item.payment_methods_code ===
            page.props.paymentMethodsEnum.ProformaPaymentRequest,
        )
      "
      :paymentDocument="props.paymentDocumentTypes"
      :quoteRequest="props.quoteRequest"
      :paymentStatusEnum="props.paymentStatusEnum"
      :paymentTooltipEnum="props.paymentTooltipEnum"
      :paymentMethods="
        props.paymentMethods.map(pm => {
          return { value: pm.code, label: pm.name, tooltip: pm.tool_tip };
        })
      "
      :storageUrl="props.storageUrl"
      :send-update="sendUpdateLog"
      :send-update-status-enum="props.sendUpdateStatusEnum"
      :insuranceProviders="props.insuranceProviders"
      :quoteDocuments="props.quoteDocuments"
      :expanded="sectionExpanded"
    />

    <LazyPolicyDetails
      v-if="props.isPolicyDetailsEnabled"
      :sendUpdateLog="sendUpdateLog"
      :insuranceProviders="props.insuranceProviders"
      :send-update-status-enum="props.sendUpdateStatusEnum"
      :quote="props.realQuote"
      :isUpdateBooked="isUpdateBooked"
      :quote-type="props.quoteType"
      :isEditDisabledForQueuedBooking="props.isEditDisabledForQueuedBooking"
    />

    <QuoteDocuments
      :document-types="props.documentTypes"
      :quote-documents="props.quoteDocuments || []"
      :storageUrl="props.storageUrl"
      :quote="props.realQuote"
      :expanded="true"
      :extras="{
        pageType: 'send-update',
        quoteType: props.quoteType,
        sendLogId: props.sendUpdateLog.id,
        members: memberDataDocs(props.membersDetail),
      }"
      :send-update-log="props.sendUpdateLog"
      :update-btn="props.updateBtn"
      :quote-type="props.quoteType"
      :is-sent-or-booked="props.isSentOrBooked"
    />

    <LazyBookingDetails
      v-if="isBookingDetailsVisible"
      :sendUpdateLog="sendUpdateLog"
      :insuranceProviders="props.insuranceProviders"
      :quote="quote"
      :quoteType="quoteType"
      :isUpdateBooked="isUpdateBooked"
      :is-negative-value="isNegativeValue"
      :booking-details="props.bookingDetails"
      :real-quote="props.realQuote"
      :update-btn="props.updateBtn"
      :uploaded-documents="props.uploadedDocuments"
      :payments="props.payments"
      :modelClass="modelClass"
      @update-error-status="handleErrorStatusUpdate"
      :isEditDisabledForQueuedBooking="props.isEditDisabledForQueuedBooking"
      :is-comm-vat-not-app-enabled="props.isCommVatNotAppEnabled"
      :disable-main-btn="props.disableMainBtn"
    />

    <AuditLogs
      :type="modelClass"
      :id="$page.props.sendUpdateLog.id"
      :quoteType="'SendUpdateLog'"
      :expanded="true"
    />
  </div>
</template>

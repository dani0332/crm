<script setup>
import LazyPlanDetails from './Partials/PlanDetails.vue';
import QuoteDocuments from '../PersonalQuote/Partials/QuoteDocuments';
import LazyPolicyDetails from './Partials/PolicyDetails.vue';
import LazyBookingDetails from './Partials/BookingDetails.vue';

const props = defineProps({
  quoteType: String,
  sendUpdateLog: Object,
  sendUpdateOptions: Array,
  insuranceProviders: Object,
  sendUpdateStatusEnum: Object,
  quote: Object,
  indicativePrice: Object,
  isBookingDetailsVisible: Boolean,
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
});

const page = usePage();
const notification = useToast();

const { isRequired } = useRules();

const state = reactive({
  edit: false,
  redirectURL: '',
});

const selectedCategory = computed(() => {
  let category = null;
  for (let mainCategory of props.sendUpdateOptions) {
    for (let subCategory of mainCategory.childs || []) {
      if (subCategory.id === props.sendUpdateLog.category_id) {
        category = { ...mainCategory };
        delete category.childs;

        category.subCategory = { ...subCategory };
        delete category.subCategory.childs;

        if (subCategory.childs?.length) {
          for (let option of subCategory.childs || []) {
            if (option.id === props.sendUpdateLog.option_id) {
              category.subCategory.option = { ...option };
            }
          }
          category.subCategory.options = [...subCategory.childs];
        } else {
          category.subCategory.option = null;
          category.subCategory.options = [];
        }
      }
    }
  }

  return category;
});

// as per the link 'Transaction Type' column -> https://docs.google.com/spreadsheets/d/1TE7RfMpEtL7kenl8s1DUVKRvP_DbUvCJ82XyCFYJ7Rw/edit#gid=803033517
const transactionType = computed(() => {
  if (
    [
      page.props.sendUpdateLogStatusEnum.CI,
      page.props.sendUpdateLogStatusEnum.CIR,
      page.props.sendUpdateLogStatusEnum.CPD,
    ].includes(selectedCategory?.value?.subCategory.slug)
  ) {
    return 'Endorsement';
  }

  return null;
});

const updateLogOptions = computed(() => {
  return selectedCategory?.value?.subCategory.options.map(child => ({
    value: child.id,
    label: child.title,
    slug: child.slug,
  }));
});

const isUpdateBooked = computed(() => {
  return (
    props.sendUpdateLog.status === props.sendUpdateStatusEnum.UPDATE_BOOKED &&
    [
      page.props.sendUpdateLogStatusEnum.EF,
      page.props.sendUpdateLogStatusEnum.CI,
      page.props.sendUpdateLogStatusEnum.CIR,
      page.props.sendUpdateLogStatusEnum.CPD,
    ].includes(selectedCategory?.value?.subCategory.slug)
  );
});

const changeReasonOptions = computed(() => {
  return [];
});

const sendUpdateForm = useForm({
  notes: props.sendUpdateLog?.notes || '',
  option_id: props.sendUpdateLog?.option_id || null,
  change_reason: props.sendUpdateLog?.change_reason || '',
  reportable_id: props.sendUpdateLog?.reportable_id || null,
  quote_type_id: props.sendUpdateLog?.quote_type_id || null,
  reportable_uuid: props.sendUpdateLog?.reportable_uuid || null,
  personal_quote_id: props.sendUpdateLog?.personal_quote_id || null,
  childCategory: selectedCategory?.value?.subCategory,
  status: props.sendUpdateLog?.status || '',
  quote_uuid: props.realQuote.uuid,
  car_addons: props.sendUpdateLog?.car_addons || null,
  emirates_registration: props.sendUpdateLog?.emirates_registration || null,
  seating_capacity: props.sendUpdateLog?.seating_capacity || null,
});

onMounted(() => {
  const params = new URLSearchParams(
    decodeURIComponent(page.url.split('?')[1]),
  );
  state.redirectURL = params.get('refURL');
});

const onEdit = () => {
  if (isUpdateBooked.value) {
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
  sendUpdateForm.emirates_registration = props.sendUpdateLog?.emirates_registration || null;
  sendUpdateForm.seating_capacity = props.sendUpdateLog?.seating_capacity || null;
};

const onUpdateLog = (isValid) => {
  if (!isValid) return;
  sendUpdateForm.patch(
    route('send-update-logs.update', { id: props.sendUpdateLog.id }),
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

  return validSlugs.includes(selectedCategory?.value?.subCategory.slug);
});

const additionalFieldOptions = computed(() => {
  if (props.additionalField) {
    return props.additionalField.map(additional => ({
      value: additional.id,
      label: additional.text,
    }));
  }
});

const onKeyPress = (event) => {
  if (event.key === 'e' || event.key === 'E') {
    event.preventDefault();
  }
};
</script>

<template>
  <Head>
    <title>Send Update {{ selectedCategory.subCategory.title }}</title>
  </Head>
  <div>
    <div class="p-4 rounded shadow mb-6 bg-white">
      <x-form @submit="onUpdateLog">
        <div class="flex gap-2 w-100 flex-grow justify-between">
        <h3 class="text-lg font-semibold text-primary-800 capitalize">
          {{ selectedCategory.subCategory.title }}
        </h3>
        <Link :href="state.redirectURL">
          <x-button color="primary" size="sm" class="mr-5"
            >Go back to lead</x-button
          >
        </Link>
      </div>
      <x-divider class="my-4" />
      <div class="text-sm">
        <dl class="grid md:grid-cols-2 gap-y-4">
          <div class="grid sm:grid-cols-2">
            <dt>
              <x-tooltip position="left">
                <label
                  class="font-bold text-gray-800 underline decoration-dotted decoration-primary-700"
                >
                  SU ref ID
                </label>
                <template #tooltip>
                  A unique reference identifier assigned to each "Send Update"
                  request, allowing for easy tracking and reference.
                </template>
              </x-tooltip>
            </dt>
            <dd>{{ sendUpdateLog.code }}</dd>
          </div>
          <div class="grid md:grid-cols-2 gap-y-4">
            <dt>Notes</dt>
            <dd>
              <x-input
                v-model="sendUpdateForm.notes"
                size="xs"
                :disabled="!state.edit"
              />
            </dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <template
              v-if="
                selectedCategory.subCategory.slug !== 'EN' &&
                selectedCategory.subCategory.slug !== 'CPU'
              "
            >
              <dt>
                <x-tooltip position="left">
                  <label
                    class="font-bold text-gray-800 underline decoration-dotted decoration-primary-700"
                  >
                    Transaction Type
                  </label>
                  <template #tooltip>
                    Refers to category of the financial transaction associated
                    with the policy. It helps classify the specific type of
                    transaction being recorded or processed within the system.
                  </template>
                </x-tooltip>
              </dt>
              <dd>{{ transactionType || selectedCategory.title }}</dd>
            </template>
          </div>
          <div class="grid md:grid-cols-2 gap-y-4">
            <dt>
              <x-tooltip position="left">
                <label
                  class="font-bold text-gray-800 underline decoration-dotted decoration-primary-700"
                >
                  Status
                </label>
                <template #tooltip>
                  The current status of the ""Send Update"" request, indicating
                  whether it is pending, transaction approved, or declined,
                  among other possible states.
                </template>
              </x-tooltip>
            </dt>
            <dd>{{ sendUpdateLog.status }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <template
              v-if="
                selectedCategory.subCategory.slug !==
                  page.props.sendUpdateLogStatusEnum.CI &&
                selectedCategory.subCategory.slug !==
                  page.props.sendUpdateLogStatusEnum.CIR &&
                selectedCategory.subCategory.slug !==
                  page.props.sendUpdateLogStatusEnum.CPU &&
                selectedCategory.subCategory.slug !==
                  page.props.sendUpdateLogStatusEnum.CPD
                "
            >
              <dt>
                <x-tooltip position="left">
                  <label
                    class="font-bold text-gray-800 underline decoration-dotted decoration-primary-700"
                  >
                    Sub Type
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
                  :disabled="!state.edit"
                  v-model="sendUpdateForm.option_id"
                  :options="updateLogOptions"
                />
              </dd>
            </template>
            <template
              v-else-if="
                selectedCategory.subCategory.slug !== 'CPU' &&
                selectedCategory.subCategory.slug !== 'CPD'
                "
            >
              <!-- <dt class="font-bold text-right mr-10">Reason</dt>
              <dd>
                <x-select
                  size="xs"
                  :disabled="!state.edit"
                  v-model="sendUpdateForm.change_reason"
                  :options="changeReasonOptions"
                />
              </dd> -->
            </template>
          </div>
        <div class="grid sm:grid-cols-2">
              <template v-if="props.additionalField && selectedCategory?.subCategory?.option?.slug === props.sendUpdateStatusEnum.AOC">
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
                      multiple
                    />
                </dd>
              </template>
              <template v-else-if="props.additionalField && (selectedCategory?.subCategory?.option?.slug === props.sendUpdateStatusEnum.COE || selectedCategory?.subCategory?.option?.slug === props.sendUpdateStatusEnum.COE_NFI)">
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
                      v-model="sendUpdateForm.emirates_registration"
                      placeholder="Select Emirate of Registration"
                      :options="additionalFieldOptions"
                      size="xs"
                      :disabled="!state.edit"
                    />
                </dd>
              </template>
              <template v-else-if="props.additionalField && (selectedCategory?.subCategory?.option?.slug === props.sendUpdateStatusEnum.CISC || selectedCategory?.subCategory?.option?.slug === props.sendUpdateStatusEnum.CISC_NFI)">
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
                  />
                </dd>
              </template>
            </div>
          </dl>
      </div>
      <div class="flex justify-end">
        <x-button size="sm" @click="onEdit" v-if="!state.edit"> Edit </x-button>
        <template v-else>
          <x-button
            size="sm"
            color="orange"
            @click="onCancel"
            class="mr-3"
            :loading="sendUpdateForm.processing"
            :disabled="sendUpdateForm.processing"
            >Cancel</x-button
          >
          <x-button
            size="sm"
            color="primary"
            type="submit"
              :loading="sendUpdateForm.processing"
              :disabled="sendUpdateForm.processing"
              >
              Update
            </x-button
            >
          </template>
        </div>
      </x-form>
    </div>

    <!-- Indicative additional price & Plan details comp -->
    <LazyPlanDetails
      :sendUpdateLog="sendUpdateLog"
      :updateLogOptions="updateLogOptions"
      :selectedCategory="selectedCategory"
      :insuranceProviders="props.insuranceProviders"
      :quoteType="quoteType"
      :isUpdateBooked="isUpdateBooked"
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
      :send-update-status-enum="page.props.sendUpdateStatusEnum"
      :insuranceProviders="props.insuranceProviders"
      :quoteDocuments="props.quoteDocuments"
    />

    <LazyPolicyDetails
      v-if="props.isPolicyDetailsEnabled"
      :sendUpdateLog="sendUpdateLog"
      :insuranceProviders="props.insuranceProviders"
      :selectedCategory="selectedCategory"
      :quote="props.realQuote"
      :isUpdateBooked="isUpdateBooked"
      :quote-type="props.quoteType"
    />

    <QuoteDocuments
      :document-types="props.documentTypes"
      :quote-documents="props.quoteDocuments || []"
      :storageUrl="props.storageUrl"
      :quote="props.realQuote"
      :expanded="true"
      :extras="{
        pageType: 'send-update-log',
        quoteType: props.quoteType,
        sendLogId: props.sendUpdateLog.id,
        members: memberDataDocs(props.membersDetail),
      }"
      :selectedCategory="selectedCategory"
      :update-btn="props.updateBtn"
    />

    <LazyBookingDetails
      v-if="isBookingDetailsVisible"
      :sendUpdateLog="sendUpdateLog"
      :insuranceProviders="props.insuranceProviders"
      :selectedCategory="selectedCategory"
      :quote="quote"
      :quoteType="quoteType"
      :isUpdateBooked="isUpdateBooked"
      :is-negative-value="isNegativeValue"
      :booking-details="props.bookingDetails"
      :real-quote="props.realQuote"
      :update-btn="props.updateBtn"
      :uploaded-documents="props.uploadedDocuments"
    />

    <AuditLogs
      :type="'App\\Models\\SendUpdateLog'"
      :id="$page.props.sendUpdateLog.id"
      :expanded="true"
    />
  </div>
</template>

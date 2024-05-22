<script setup>
import moment from 'moment';

const page = usePage();

const props = defineProps({
  record: {
    type: Object,
    default: {},
  },
  modelType: {
    type: String,
    default: '',
  },
  payments: {
    type: Array,
    default: [],
  },
  expanded: {
    required: false,
    type: Boolean,
    default: true,
  },
});
const paymentStatusEnum = page.props.paymentStatusEnum;
const notification = useNotifications('toast');
const hasRole = role => useHasRole(role);
const rolesEnum = page.props.rolesEnum;
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
const productionProcessTooltipEnum = page.props.productionProcessTooltipEnum;
const quoteTypeCodeEnum = page.props.quoteTypeCodeEnum;
const quoteIssuanceStatusEnum = page.props.quoteIssuanceStatusEnum;
const quoteStatusEnum= page.props.quoteStatusEnum;

const policyIssuanceStatusOptions = computed(() => {
  let policyIssuanceStatus= page.props.policyIssuanceStatus;
  if( props.record.quote_status_id != quoteStatusEnum.PolicyIssued){
    policyIssuanceStatus = policyIssuanceStatus.filter(item => item.text !== "Policy Issued");
  }
  return policyIssuanceStatus.map(item => {
    return {
      value: item.id,
      label: item.text,
    };
  });

 });

const planQuoteInsurerNumber = computed(() => {
  let obj = page.props?.listQuotePlans?.filter(
    item => item.id == page.props.record.plan_id,
  );

  return obj === undefined ? null : obj[0]?.insurerQuoteNo || null;
});

const policyDetailsState = reactive({
  isEditing: false,
});
const policyDetailsForm = useForm({
  quote_policy_number:
    page.props.record.policy_number == 'NULL'
      ? ''
      : page.props.record.policy_number || '',

  quote_policy_issuance_date:
    dateToYMD(page.props.record.policy_issuance_date) ||
    new Date().toJSON().slice(0, 10),
  price_vat_notapplicable: page.props.record.price_vat_not_applicable || '',
  amount: page.props.record.price_without_vat || '',
  vat: page.props.record.vat || '',
  quote_policy_start_date: dateToYMD(page.props.record.policy_start_date) || '',
  quote_policy_expiry_date:
    dateToYMD(page.props.record.renewal_expiry_date) || '',
  amount_with_vat: '',
  quote_plan_insurer_quote_number:
    planQuoteInsurerNumber.value || page.props.record.insurer_quote_number,
  quote_policy_issuance_status: page.props.record.policy_issuance_status_id,
  quote_policy_issuance_status_other:
    page.props.record.policy_issuance_status_other || '',
  modelType: props.modelType,
  quote_id: page.props.record.id,
});

watch(
  () => page.props.record.policy_issuance_status_id,
  (newValue, oldValue) => {
    if (newValue !== oldValue) {
      policyDetailsForm.quote_policy_issuance_status = newValue;
    }
  },
);

const caculateVatAmount = () => {
  if (policyDetailsForm.amount > 0) {
    let vat = policyDetailsForm.amount * page.props.vat.toFixed(2);
    policyDetailsForm.vat = vat.toFixed(2);
    policyDetailsForm.amount_with_vat = (
      Number(vat) + Number(policyDetailsForm.amount)
    ).toFixed(2);
    Number(vat) + Number(policyDetailsForm.amount);
  } else if (policyDetailsForm.price_vat_notapplicable > 0) {
    policyDetailsForm.amount_with_vat = Number(
      policyDetailsForm.price_vat_notapplicable,
    ).toFixed(2);
  } else {
    policyDetailsForm.vat = '';
    policyDetailsForm.amount_with_vat = '';
  }
};

const rules = {
  isRequired: v => !!v || 'This field is required',
  start_date: v => {
    if (v) {
      const date = new Date(v);
      return !isNaN(date.getTime());
    }
    return true;
  },
  expiry_date: v => {
    if (v) {
      const date = new Date(v);
      const startDate = new Date(policyDetailsForm.quote_policy_start_date);
      if (startDate >= date) {
        return 'Expiry date should be greater than Start Date';
      }
      return isNaN(date.getTime());
    }
    return false;
  },
};

const onUpdatePolicyDetails = isValid => {
  if (!isValid) return;
  policyDetailsForm.post(`/quotes/${props.modelType}/update-quote-policy`, {
    preserveScroll: true,
    onSuccess: () => { 
      if (page.props?.bookPolicyDetails?.isLackingOfPayment){
        notification.error({
          title: 'Action Needed: Please revise payment details to reflect plan changes.',
          position: 'top',
          timeout: 10000
        });
      }
      policyDetailsState.isEditing = false;
    },
    onError: errors => {
      Object.keys(errors).forEach(function (key) {
        notification.error({
          title: errors[key],
          position: 'top',
        });
      });
    },
    onFinish: () => {
      policyDetailsState.isEditing = false;
      router.visit(route(route().current(), props?.record.uuid), {
        method: 'get',
        preserveScroll: true,
      });
    },
  });
};
onBeforeMount(() => {
  caculateVatAmount();
});

watch(
  () => policyDetailsForm.quote_policy_start_date,
  quote_policy_start_date => {
    let isCarQuote = props.modelType === quoteTypeCodeEnum.Car.toLowerCase();

    let isHealthOrBusinessQuote =
      props.modelType === quoteTypeCodeEnum.Health.toLowerCase() ||
      props.modelType === quoteTypeCodeEnum.GroupMedical.toLowerCase() ||
      props.modelType === quoteTypeCodeEnum.Business.toLowerCase(); // model type is ""Business"" when visiting the business quote page so added this condition

    if (isCarQuote) {
      //for Car quote, add 13 months to start date to calculate expiry date.
      policyDetailsForm.quote_policy_expiry_date = moment(
        quote_policy_start_date,
      )
        .add(13, 'months')
        .subtract(1, 'days');
    } else if (isHealthOrBusinessQuote) {
      // For Health and Business quote, add 12 months to start date to calculate expiry date
      policyDetailsForm.quote_policy_expiry_date = moment(
        quote_policy_start_date,
      )
        .add(12, 'months')
        .subtract(1, 'days');
    }
  },
);
</script>

<template>
  <div class="p-4 rounded shadow mb-6 bg-white">
    <Collapsible :expanded="expanded">
      <template #header>
        <div class="flex flex-wrap gap-4 justify-between items-center">
          <h3 class="font-semibold text-primary-800 text-lg">Policy Details</h3>
        </div>
      </template>
      <template #body>
        <x-form @submit="onUpdatePolicyDetails" :auto-focus="false">
          <div class="my-4">
            <div class="flex flex-wrap md:flex-nowrap gap-6 w-full pb-5">
              <div class="w-full md:w-1/2">
                <x-tooltip
                  ><label
                    class="font-medium text-gray-800 dark:text-gray-200 mb-1"
                    >Policy Number</label
                  >
                  <template #tooltip>
                    <span>{{
                      productionProcessTooltipEnum.POLICY_NUMBER
                    }}</span>
                  </template>
                </x-tooltip>
                <x-input
                  v-model="policyDetailsForm.quote_policy_number"
                  type="text"
                  placeholder="Policy Number"
                  class="w-full"
                  :disabled="!policyDetailsState.isEditing"
                />
              </div>
              <div class="w-full md:w-1/2">
                <x-tooltip
                  ><label
                    class="font-medium text-gray-800 dark:text-gray-200 mb-1"
                    >ISSUANCE DATE</label
                  >
                  <template #tooltip>
                    <span>{{
                      productionProcessTooltipEnum.ISSUANCE_DATE
                    }}</span>
                  </template>
                </x-tooltip>
                <DatePicker
                  v-model="policyDetailsForm.quote_policy_issuance_date"
                  :disabled="!policyDetailsState.isEditing"
                  type="date"
                  class="w-full"
                />
              </div>
            </div>

            <div class="flex flex-wrap md:flex-nowrap gap-6 w-full pb-5">
              <div class="w-full md:w-1/2">
                <x-tooltip
                  ><label
                    class="font-medium text-gray-800 dark:text-gray-200 mb-1"
                    >Price (VAT NOT APPLICABLE)</label
                  >
                  <template #tooltip>
                    <span>{{
                      productionProcessTooltipEnum.PRICE_VAT_NOT_APPLICABLE
                    }}</span>
                  </template>
                </x-tooltip>
                <x-textarea
                  v-model="policyDetailsForm.price_vat_notapplicable"
                  @change="caculateVatAmount"
                  type="number"
                  placeholder="Price (VAT NOT APPLICABLE)"
                  class="w-full"
                  :disabled="
                    !policyDetailsState.isEditing ||
                    policyDetailsForm.amount > 0
                  "
                />
              </div>
              <div class="w-full md:w-1/2">
                <x-tooltip
                  ><label
                    class="font-medium text-gray-800 dark:text-gray-200 mb-1"
                    >Start Date</label
                  >
                  <template #tooltip>
                    <span>{{ productionProcessTooltipEnum.START_DATE }}</span>
                  </template>
                </x-tooltip>
                <DatePicker
                  v-model="policyDetailsForm.quote_policy_start_date"
                  :rules="[rules.start_date]"
                  type="date"
                  placeholder="Start Date"
                  class="w-full"
                  :disabled="!policyDetailsState.isEditing"
                />
              </div>
            </div>

            <div class="flex flex-wrap md:flex-nowrap gap-6 w-full pb-5">
              <div class="w-full md:w-1/2">
                <x-tooltip
                  ><label
                    class="font-medium text-gray-800 dark:text-gray-200 mb-1"
                    >Price (VAT APPLICABLE)</label
                  >
                  <template #tooltip>
                    <span>{{
                      productionProcessTooltipEnum.PRICE_VAT_APPLICABLE
                    }}</span>
                  </template>
                </x-tooltip>
                <x-textarea
                  v-model="policyDetailsForm.amount"
                  @change="caculateVatAmount"
                  type="number"
                  placeholder="Price (VAT APPLICABLE)"
                  class="w-full"
                  :disabled="
                    !policyDetailsState.isEditing ||
                    policyDetailsForm.price_vat_notapplicable > 0
                  "
                />
              </div>
              <div class="w-full md:w-1/2">
                <x-tooltip
                  ><label
                    class="font-medium text-gray-800 dark:text-gray-200 mb-1"
                    >Expiry Date</label
                  >
                  <template #tooltip>
                    <span>{{ productionProcessTooltipEnum.EXPIRY_DATE }}</span>
                  </template>
                </x-tooltip>
                <DatePicker
                  v-model="policyDetailsForm.quote_policy_expiry_date"
                  :custom-error="
                    rules.expiry_date(
                      policyDetailsForm.quote_policy_expiry_date,
                    )
                  "
                  type="date"
                  placeholder="Expiry Date"
                  class="w-full"
                  :disabled="!policyDetailsState.isEditing"
                />
              </div>
            </div>

            <div class="flex flex-wrap md:flex-nowrap gap-6 w-full pb-5">
              <div class="w-full md:w-1/2">
                <x-tooltip
                  ><label
                    class="font-medium text-gray-800 dark:text-gray-200 mb-1"
                    >Total VAT Amount</label
                  >
                  <template #tooltip>
                    <span>{{
                      productionProcessTooltipEnum.TOTAL_VAT_AMOUNT
                    }}</span>
                  </template>
                </x-tooltip>
                <x-input
                  v-model="policyDetailsForm.vat"
                  type="text"
                  placeholder="Total VAT Amount"
                  class="w-full"
                  :disabled="true"
                  readonly
                />
              </div>
              <div class="w-full md:w-1/2">
                <x-tooltip
                  ><label
                    class="font-medium text-gray-800 dark:text-gray-200 mb-1"
                    >Total Price</label
                  >
                  <template #tooltip>
                    <span>{{ productionProcessTooltipEnum.TOTAL_PRICE }}</span>
                  </template>
                </x-tooltip>
                <x-input
                  v-model="policyDetailsForm.amount_with_vat"
                  type="number"
                  placeholder="Price"
                  class="w-full"
                  readonly
                  :disabled="!policyDetailsState.isEditing"
                />
              </div>
            </div>
            <div class="flex flex-wrap md:flex-nowrap gap-6 w-full pb-5">
              <div class="w-full md:w-1/2">
                <x-tooltip
                  ><label
                    class="font-medium text-gray-800 dark:text-gray-200 mb-1"
                    >Insurer Quote Number</label
                  >
                  <template #tooltip>
                    <span>{{
                      productionProcessTooltipEnum.INSURER_QUOTE_NUMBER
                    }}</span>
                  </template>
                </x-tooltip>
                <x-input
                  v-model="policyDetailsForm.quote_plan_insurer_quote_number"
                  type="text"
                  placeholder="Insurer Quote Number"
                  class="w-full"
                  :disabled="!policyDetailsState.isEditing"
                />
              </div>
              <div class="w-full md:w-1/2">
                <x-tooltip
                  ><label
                    class="font-medium text-gray-800 dark:text-gray-200 mb-1"
                    >Issuance Status</label
                  >
                  <template #tooltip>
                    <span>{{
                      productionProcessTooltipEnum.ISSURANEC_STATUS
                    }}</span>
                  </template>
                </x-tooltip>
                <x-select
                  v-model="policyDetailsForm.quote_policy_issuance_status"
                  class="w-full"
                  placeholder="Select any option"
                  :disabled="!policyDetailsState.isEditing"
                  :options="policyIssuanceStatusOptions"
                />
              </div>
            </div>
            <div class="flex flex-wrap md:flex-nowrap gap-6 w-full pb-5">
              <div class="w-full md:w-1/2">
                <x-input
                  v-if="
                    policyDetailsForm.quote_policy_issuance_status ==
                    quoteIssuanceStatusEnum.Other
                  "
                  v-model="policyDetailsForm.quote_policy_issuance_status_other"
                  type="text"
                  label="Additionl Info"
                  placeholder="Additionl Info"
                  class="w-full"
                  :disabled="!policyDetailsState.isEditing"
                />
              </div>
            </div>
            <div class="flex flex-wrap md:flex-nowrap gap-6 w-full">
              <div class="w-full md:w-1/2"></div>
              <div class="w-full md:w-1/2" />
            </div>

            <div class="flex justify-end">
              <template
                class="flex justify-end"
                v-if="
                  record.quote_status_id == quoteStatusEnum.TransactionApproved ||
                  record.quote_status_id == quoteStatusEnum.PolicyPending ||
                  record.quote_status_id == quoteStatusEnum.PolicyIssued ||
                  record.quote_status_id == quoteStatusEnum.PolicySentToCustomer
                "
              >
                <x-button
                  v-if="policyDetailsState.isEditing"
                  class="mt-4 mr-2"
                  color="emerald"
                  size="sm"
                  :loading="policyDetailsForm.processing"
                  @click.prevent="
                    () => {
                      policyDetailsState.isEditing = false;
                      policyDetailsForm.reset();
                    }
                  "
                >
                  Cancel
                </x-button>
                <x-button
                  v-if="policyDetailsState.isEditing"
                  class="mt-4"
                  color="emerald"
                  size="sm"
                  :loading="policyDetailsForm.processing"
                  type="submit"
                >
                  Update
                </x-button>

                <template
                  v-if="props.modelType === quoteTypeCodeEnum.Car.toLowerCase()"
                >
                  <x-button
                    v-if="
                      !policyDetailsState.isEditing && hasRole(rolesEnum.PA)
                    "
                    class="mt-4"
                    color="emerald"
                    size="sm"
                    @click.prevent="policyDetailsState.isEditing = true"
                  >
                    Edit
                  </x-button></template
                >
                <template v-else>
                  <x-button
                    v-if="
                      !policyDetailsState.isEditing && hasRole(rolesEnum.NRA)
                    "
                    class="mt-4"
                    color="emerald"
                    size="sm"
                    @click.prevent="policyDetailsState.isEditing = true"
                  >
                    Edit
                  </x-button></template
                >
              </template>
              <template v-else>
                <x-tooltip>
                  <x-button
                    v-if="
                      record.quote_status_id == quoteStatusEnum.PolicyBooked
                    "
                    size="sm"
                    color="emerald"
                    :disabled="true"
                    >Edit
                  </x-button>
                  <template #tooltip>
                    <span>{{
                      'The button is not accessable because policy has been booked'
                    }}</span>
                  </template>
                </x-tooltip>
              </template>
            </div>
          </div>
        </x-form>
      </template>
    </Collapsible>
  </div>
</template>

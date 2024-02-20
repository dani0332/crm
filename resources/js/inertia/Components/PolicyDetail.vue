<script setup>
const page = usePage();

const props = defineProps({
  record: {
    type: Object,
    default: {},
  },
  quoteStatusEnum: {
    type: Object,
    default: {},
  },
  policyIssuanceStatus: {
    type: Array,
    default: [],
  },
  modelType: {
    type: String,
    default: '',
  },
});

const hasRole = role => useHasRole(role);
const rolesEnum = page.props.rolesEnum;
const dateToYMD = date => {
  if (date) {
    const [day, month, year] = date.split('-');
    return `${year}-${month}-${day}`;
  }
  return '';
};
const quoteTypeCodeEnum = page.props.quoteTypeCodeEnum;
const quoteIssuanceStatusEnum = page.props.quoteIssuanceStatusEnum;
const policyIssuanceStatusOptions = computed(() => {
  return page.props.policyIssuanceStatus.map(item => {
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
  quote_policy_number: page.props.record.policy_number || null,

  quote_policy_issuance_date:
    dateToYMD(page.props.record.policy_issuance_date) ||
    new Date().toJSON().slice(0, 10),
  price_vat_notapplicable: page.props.record.price_vat_not_applicable || '',
  amount: page.props.record.price_without_vat || '',
  vat: page.props.record.vat || '',
  quote_policy_start_date: dateToYMD(page.props.record.policy_start_date) || '',
  quote_policy_expiry_date:
    dateToYMD(page.props.record.renewal_expiry_date) || '',
  amount_with_vat: page.props.record.price_with_vat || '',
  quote_plan_insurer_quote_number: planQuoteInsurerNumber.value || null,
  quote_policy_issuance_status: page.props.record.policy_issuance_status_id,
  quote_policy_issuance_status_other:
    page.props.record.policy_issuance_status_other || '',
  modelType: props.modelType,
  quote_id: page.props.record.id,
});

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
      if (policyDetailsForm.quote_policy_start_date) {
        const startDate = new Date(policyDetailsForm.quote_policy_start_date);
        if (startDate >= date) {
          return 'Expiry date should be greater than Start Date';
        }
      }
      return !isNaN(date.getTime());
    }
    return true;
  },
};
const onUpdatePolicyDetails = isValid => {
  if (!isValid) return;
  policyDetailsForm.post(`/quotes/${props.modelType}/update-quote-policy`, {
    preserveScroll: true,
    onSuccess: () => {
      policyDetailsState.isEditing = false;
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
</script>

<template>
  <x-form @submit="onUpdatePolicyDetails" :auto-focus="false">
    <div class="p-4 rounded shadow mb-6 bg-white">
      <div>
        <h3 class="font-semibold text-primary-800 text-lg">Policy Details</h3>
        <x-divider class="mb-4 mt-1" />
      </div>
      <div class="flex flex-wrap md:flex-nowrap gap-6 w-full pb-5">
        <div class="w-full md:w-1/2">
          <x-textarea
            v-model="policyDetailsForm.quote_policy_number"
            type="text"
            label="Policy Number"
            placeholder="Policy Number"
            class="w-full"
            :disabled="!policyDetailsState.isEditing"
          />
        </div>
        <div class="w-full md:w-1/2">
          <DatePicker
            v-model="policyDetailsForm.quote_policy_issuance_date"
            :disabled="!policyDetailsState.isEditing"
            type="date"
            label="ISSUANCE DATE"
            class="w-full"
          />
        </div>
      </div>

      <div class="flex flex-wrap md:flex-nowrap gap-6 w-full pb-5">
        <div class="w-full md:w-1/2">
          <x-textarea
            v-model="policyDetailsForm.price_vat_notapplicable"
            @change="caculateVatAmount"
            type="number"
            label="Price (VAT NOT APPLICABLE)"
            placeholder="Price (VAT NOT APPLICABLE)"
            class="w-full"
            :disabled="
              !policyDetailsState.isEditing || policyDetailsForm.amount > 0
            "
          />
        </div>
        <div class="w-full md:w-1/2">
          <DatePicker
            v-model="policyDetailsForm.quote_policy_start_date"
            :rules="[rules.start_date]"
            type="date"
            label="Start Date"
            placeholder="Start Date"
            class="w-full"
            :disabled="!policyDetailsState.isEditing"
          />
        </div>
      </div>

      <div class="flex flex-wrap md:flex-nowrap gap-6 w-full pb-5">
        <div class="w-full md:w-1/2">
          <x-textarea
            v-model="policyDetailsForm.amount"
            @change="caculateVatAmount"
            type="number"
            label="Price (VAT APPLICABLE)"
            placeholder="Price (VAT APPLICABLE)"
            class="w-full"
            :disabled="
              !policyDetailsState.isEditing ||
              policyDetailsForm.price_vat_notapplicable > 0
            "
          />
        </div>
        <div class="w-full md:w-1/2">
          <DatePicker
            v-model="policyDetailsForm.quote_policy_expiry_date"
            :rules="[rules.expiry_date]"
            type="date"
            label="Expiry Date"
            placeholder="Expiry Date"
            class="w-full"
            :disabled="!policyDetailsState.isEditing"
          />
        </div>
      </div>

      <div class="flex flex-wrap md:flex-nowrap gap-6 w-full pb-5">
        <div class="w-full md:w-1/2">
          <x-input
            v-model="policyDetailsForm.vat"
            type="text"
            label="Total VAT Amount"
            placeholder="Total VAT Amount"
            class="w-full"
            :disabled="!policyDetailsState.isEditing"
            readonly
          />
        </div>
        <div class="w-full md:w-1/2">
          <x-input
            v-model="policyDetailsForm.amount_with_vat"
            type="number"
            label="Total Price"
            placeholder="Price"
            class="w-full"
            readonly
            :disabled="!policyDetailsState.isEditing"
          />
        </div>
      </div>
      <div class="flex flex-wrap md:flex-nowrap gap-6 w-full pb-5">
        <div class="w-full md:w-1/2">
          <x-input
            v-model="policyDetailsForm.quote_plan_insurer_quote_number"
            type="text"
            label="Insurer Quote Number"
            placeholder="Insurer Quote Number"
            class="w-full"
            :disabled="!policyDetailsState.isEditing"
          />
        </div>
        <div class="w-full md:w-1/2">
          <x-select
            v-model="policyDetailsForm.quote_policy_issuance_status"
            class="w-full"
            label="Issuance Status"
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
          v-if="record.quote_status_id == quoteStatusEnum.TransactionApproved"
        >
          <x-button
            v-if="policyDetailsState.isEditing"
            class="mt-4 mr-2"
            color="emerald"
            size="sm"
            :loading="policyDetailsForm.processing"
            @click.prevent="policyDetailsState.isEditing = false"
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
              v-if="!policyDetailsState.isEditing && hasRole(rolesEnum.PA)"
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
              v-if="!policyDetailsState.isEditing && hasRole(rolesEnum.NRA)"
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
              v-if="record.quote_status_id == quoteStatusEnum.PolicyBooked"
              size="sm"
              color="emerald"
              :disabled="true"
              >Edit
            </x-button>
            <template #tooltip>
              <span>{{
                'The Button is not accessable because policy has been booked'
              }}</span>
            </template>
          </x-tooltip>
        </template>
      </div>
    </div>
  </x-form>
</template>

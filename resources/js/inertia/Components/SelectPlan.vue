<script setup>
const props = defineProps({
  plan: Object,
  plans: Array,
  payments: Array,
  quoteType: String,
  uuid: String,
  hasChildLead: Boolean,
  disabled: {
    type: Boolean,
    default: false,
  },
  extraDetails: {
    type: Object,
    default: {},
  },
  insuranceProviderId: Number,
  code: String,
  buttonSize: {
    type: String,
    default: 'xs',
  },
  buttonClass: {
    type: String,
    default: '',
  },
});

const page = usePage();
const paymentStatusEnum = page.props.paymentStatusEnum;
const quoteTypeCodeEnum = page.props.quoteTypeCodeEnum;
const leadSourceEnum = page.props.leadSource;
const insuranceProviderCodeEnum = page.props.insuranceProviderCodeEnum;
const quote = page.props.quote;

const notification = useNotifications('toast');
const isLoading = ref(false);
const isPlanSelectionEnable = ref(false);
const hasAnyAuthorizedPayment = ref(false);
const hasAnyPendingPayment = ref(false);
const hasSameGateway = ref(true);
const showSelectPlanConfirm = ref(false);

const confirmationModal = reactive({
  isConfirmed: false,
  show: false,
  message: '',
});

const can = permission => useCan(permission);
const permissionEnum = page.props.permissionsEnum;

const emit = defineEmits(['update:selectedPlanChanged']);

const isPlanSelectionDisable = computed(() => {
  const quoteType = props.quoteType?.toLowerCase();
  const isNormalPlan = props.extraDetails?.planType == 'normalPlans';
  const isSourceIMCRM = quote?.source == leadSourceEnum?.IMCRM;
  const isALNCProvider =
    props.plan?.providerCode == insuranceProviderCodeEnum?.ALNC;

  if (
    quoteType == quoteTypeCodeEnum?.Travel?.toLowerCase() &&
    isSourceIMCRM &&
    isNormalPlan &&
    isALNCProvider
  ) {
    const travelers = page.props.travelers ?? [];
    return (
      travelers.filter(
        traveler =>
          !traveler.first_name || !traveler.last_name || !traveler.passport,
      ).length > 0
    );
  }

  return false;
});

const closeSelectPlanConfirmModal = () => {
  showSelectPlanConfirm.value = false;
};

const validatePayments = selectedPlanObj => {
  return new Promise((resolve, reject) => {
    const payments = props.payments;
    for (let i = 0; i < payments.length; i++) {
      if (payments[i].payment_status_id == paymentStatusEnum.AUTHORISED) {
        hasAnyAuthorizedPayment.value = true;
      }
      if (payments[i].payment_status_id == paymentStatusEnum.PENDING) {
        hasAnyPendingPayment.value = true;
      }

      for (let j = 0; j < payments[i].payment_splits.length; j++) {
        if (
          payments[i].payment_splits[j].payment_status_id ==
          paymentStatusEnum.AUTHORISED
        ) {
          hasAnyAuthorizedPayment.value = true;
        }
        if (
          payments[i].payment_splits[j].payment_status_id ==
          paymentStatusEnum.PENDING
        ) {
          hasAnyPendingPayment.value = true;
        }
      }
    }

    if (!hasAnyPendingPayment.value) {
      return resolve(false);
    }

    const planIds = [];
    for (let i = 0; i < props.plans.length; i++) {
      if (props.extraDetails.selectedPlansIds.includes(props.plans[i].id)) {
        if (props.quoteType.toLocaleLowerCase() == 'health') {
          planIds.push({
            providerId: props.plans[i].providerId,
            planId: props.plans[i].id,
          });
        } else {
          planIds.push({
            providerId: props.plans[i].insuranceProviderId,
            planId: props.plans[i].id,
          });
        }
        break;
      }
    }
    if (
      props.quoteType.toLocaleLowerCase() == 'travel' &&
      props.extraDetails?.planType == 'seniorPlans'
    ) {
      planIds.push({
        providerId:
          selectedPlanObj.selected_insurance_provider_id ||
          selectedPlanObj.insurance_provider_id,
        planId: selectedPlanObj.selected_plan_id || selectedPlanObj.plan_id,
      });
    } else {
      planIds.push({
        providerId: selectedPlanObj.insurance_provider_id,
        planId: selectedPlanObj.plan_id,
      });
    }

    const data = {
      plan_ids: planIds,
    };

    axios
      .post(
        `/personal-quotes/${props.quoteType}/${props.code}/get-plans-payment-gateway`,
        data,
      )
      .then(res => {
        const responsePlans = res.data?.plans;
        const firstGatewayId = responsePlans[0]?.gateway_id;
        const allSameGateway = responsePlans.every(
          item => item.gateway_id === firstGatewayId,
        );
        hasSameGateway.value = allSameGateway;
        resolve(allSameGateway);
      })
      .catch(err => {
        isLoading.value = false;
        notification.error({
          title: err?.response?.data?.error,
          position: 'top',
          timeout: 3000,
        });
        hasSameGateway.value = false;
        reject(err);
      });
  });
};

const checkAndUpdateSelectedPlan = async () => {
  isLoading.value = true;

  let data = {
    plan_id: props.plan.id,
    provider_code: props.plan?.providerCode ?? null,
    insurance_provider_id: props.plan?.insuranceProviderId ?? null,
  };

  if (props.quoteType.toLocaleLowerCase() == 'health') {
    data.insurance_provider_id = props.plan?.providerId ?? null;
    data.copay_id = props.plan.selectedCopayId;
  }

  if (props.quoteType.toLocaleLowerCase() == 'travel') {
    data.insurance_provider_id = props.plan?.insuranceProviderId ?? null;
    data.planType = props.extraDetails?.planType;
    if (props.extraDetails?.selectedPlansIds.length > 0) {
      for (let i = 0; i < props.extraDetails?.selectedPlansIds.length; i++) {
        if (
          props.extraDetails?.planType == 'normalPlans' &&
          props.extraDetails?.seniorPlansIds.includes(
            props.extraDetails?.selectedPlansIds[i],
          )
        ) {
          data.plan_id = props.plan.id;
          data.selected_plan_id = props.extraDetails?.selectedPlansIds[i];
          data.selected_insurance_provider_id =
            props.extraDetails?.selectedPlansIds[i]?.insuranceProviderId ??
            null;
        }

        if (
          props.extraDetails?.planType == 'seniorPlans' &&
          props.extraDetails?.normalPlansIds.includes(
            props.extraDetails?.selectedPlansIds[i],
          )
        ) {
          data.selected_plan_id = props.plan.id;
          data.selected_insurance_provider_id =
            props.plan?.insuranceProviderId ?? null;
          data.plan_id = props.extraDetails?.selectedPlansIds[i];
        }
      }
    } else {
      data.plan_id = props.plan.id;
    }
  }
  // data.insurance_provider_id = props.insuranceProviderId;
  data.code = props.code;
  hasAnyAuthorizedPayment.value = false;
  hasAnyPendingPayment.value = false;
  hasSameGateway.value = true;
  if (props.payments?.length) {
    await validatePayments(data);
    if (hasAnyAuthorizedPayment.value) {
      notification.error({
        title:
          'This lead is linked to an authorized payment. Please void the existing payment before switching to another plan.',
        position: 'top',
        timeout: 3000,
      });
      isLoading.value = false;
      return;
    }
    if (hasAnyPendingPayment.value && !hasSameGateway.value) {
      showSelectPlanConfirm.value = true;
      isLoading.value = false;
      return;
    }
  }
  updateSelectedPlan();
};

const handleConfirmConfirmationModal = () => {
  confirmationModal.show = false;
  confirmationModal.isConfirmed = true;
  updateSelectedPlan();
};

const handleCancelConfirmationModal = () => {
  confirmationModal.show = false;
  confirmationModal.isConfirmed = false; // Reset confirmation flag when user cancels
};

const updateSelectedPlan = () => {
  isLoading.value = true;
  showSelectPlanConfirm.value = false;
  let data = {
    plan_id: props.plan.id,
    provider_code: props.plan?.providerCode ?? null,
  };

  if (props.quoteType.toLocaleLowerCase() == 'health') {
    data.copay_id = props.plan.selectedCopayId;
  }

  if (props.quoteType.toLocaleLowerCase() == 'car') {
    // Check if customer has an active ECB transaction that requires confirmation
    if (
      props.plan.repairType != page.props.carPlanTypeEnum.COMP &&
      page.props.isEpEcbPaymentPaid &&
      !confirmationModal.isConfirmed
    ) {
      confirmationModal.message = `If you proceed with the change, the Excess Cashback amount will be refunded to the customer, as the update does not meet the eligibility criteria for the product.`;
      confirmationModal.show = true;
      isLoading.value = false;
      return;
    }
  }

  if (props.quoteType.toLocaleLowerCase() == 'travel') {
    data.planType = props.extraDetails?.planType;
    data.quoteSource = quote?.source;
    data.quoteId = quote?.id;

    if (props.extraDetails?.selectedPlansIds.length > 0) {
      for (let i = 0; i < props.extraDetails?.selectedPlansIds.length; i++) {
        if (
          props.extraDetails?.planType == 'normalPlans' &&
          props.extraDetails?.seniorPlansIds.includes(
            props.extraDetails?.selectedPlansIds[i],
          )
        ) {
          data.plan_id = props.plan.id;
          data.selected_plan_id = props.extraDetails?.selectedPlansIds[i];
        }

        if (
          props.extraDetails?.planType == 'seniorPlans' &&
          props.extraDetails?.normalPlansIds.includes(
            props.extraDetails?.selectedPlansIds[i],
          )
        ) {
          data.selected_plan_id = props.plan.id;
          data.plan_id = props.extraDetails?.selectedPlansIds[i];
        }
      }
    } else {
      data.plan_id = props.plan.id;
    }
  }
  data.insurance_provider_id = props.insuranceProviderId;
  data.code = props.code;
  axios
    .post(
      `/personal-quotes/${props.quoteType}/${props.uuid}/update-selected-plan`,
      data,
    )
    .then(res => {
      isLoading.value = false;
      let premium = 0;
      switch (props.quoteType.toLowerCase()) {
        case 'travel':
          if (res.data.plan.planProcessValue[0]) {
            premium = res.data.plan.planProcessValue[0].totalPremium;
          }
          break;
        case 'car':
          premium = res.data.plan.planProcessValue.totalPremium;
          break;
        case 'health':
          premium =
            props.plan?.actualPremium +
            (props.plan?.policyFee || 0) +
            (props.plan?.basmah || 0) +
            props.plan?.vat +
            (props.plan?.loadingPrice || 0);
          break;
        case 'savings':
          // For savings quotes, the premium is typically the investment amount
          premium =
            res.data.plan?.planProcessValue?.totalPremium ||
            props.plan?.actualPremium ||
            0;
          break;
        default:
          break;
      }

      if (props.quoteType.toLowerCase() == 'travel') {
        let selectedPlan = {
          id: props.plan.id,
          providerName: props.plan.providerName,
          planName: props.plan.name,
        };

        if (res.data.plan.planProcessValue[0]) {
          selectedPlan.premium = premium.toFixed(2);
        }
        emit('update:selectedPlanChanged', selectedPlan);
      } else {
        emit('update:selectedPlanChanged', {
          id: props.plan.id,
          providerName: props.plan.providerName,
          planName: props.plan.name,
          premium: premium.toFixed(2),
          planType: props.plan.plan_type,
        });
      }
      notification.success({
        title: 'Selected plan updated',
        position: 'top',
      });
    })
    .catch(err => {
      isLoading.value = false;
      Object.keys(err?.response?.data?.errors).forEach(function (key, value) {
        notification.error({
          title: err?.response?.data?.errors[key][0],
          position: 'top',
          timeout: key == 'authorized' ? 10000 : 3000,
        });
      });
    });
};

watch(() => {
  const quoteType = props.quoteType?.toLowerCase();

  if (quoteType == quoteTypeCodeEnum?.Health?.toLowerCase()) {
    let premiumCalculate =
      props.plan?.actualPremium +
      (props.plan?.policyFee || 0) +
      (props.plan?.basmah || 0) +
      props.plan?.vat +
      (props.plan?.loadingPrice || 0);
    isPlanSelectionEnable.value =
      premiumCalculate > 0 && can(permissionEnum.AVAILABLE_PLANS_SELECT_BUTTON);
  } else {
    isPlanSelectionEnable.value =
      props.plan?.actualPremium > 0 &&
      can(permissionEnum.AVAILABLE_PLANS_SELECT_BUTTON);
  }
});
const [SelectPlanButtonTemplate, SelectPlanButtonReuseTemplate] =
  createReusableTemplate();
</script>

<template>
  <SelectPlanButtonTemplate v-slot="{ isDisabled }">
    <x-button
      v-if="isPlanSelectionEnable"
      :size="buttonSize"
      color="success"
      outlined
      :loading="isLoading"
      :disabled="isDisabled || isPlanSelectionDisable"
      @click.prevent="checkAndUpdateSelectedPlan()"
      :class="[
        buttonSize === 'sm' ? 'min-w-[100px]' : '',
        buttonClass
      ]"
    >
      Select
    </x-button>
  </SelectPlanButtonTemplate>

  <x-tooltip
    v-if="page.props.lockLeadSectionsDetails.plan_selection"
    position="left"
    align="center"
    class="yoyo-tip"
  >
    <SelectPlanButtonReuseTemplate :isDisabled="true" />
    <template #tooltip>
      <div class="whitespace-normal text-xs">
        No further actions can be taken on an issued policy. For changes, such
        as a change in insurer, go to 'Send Update', select 'Add Update', and
        choose 'Cancellation from inception and reissuance.
      </div>
    </template>
  </x-tooltip>
  <SelectPlanButtonReuseTemplate v-else :isDisabled="hasChildLead" />
  <div
    class="modal-confirm-overlay fixed inset-0 bg-opacity-30 flex items-center justify-center"
    v-if="showSelectPlanConfirm"
  >
    <div
      class="modal-retry-container bg-white w-full max-w-full overflow-hidden rounded-lg"
    >
      <div class="modal-confirm-header text-base text-white bg-white">
        <div
          class="flex items-center justify-between text-lg font-semibold px-6 py-4 border-b"
        >
          <div class="flex items-center space-x-2">Override Payments</div>
          <div class="flex items-center space-x-2">
            <span
              @click="closeSelectPlanConfirmModal"
              class="flex items-center justify-center w-8 h-8 rounded-full bg-gray-200 cursor-pointer"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                class="w-4 h-4 text-gray-800"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M6 18L18 6M6 6l12 12"
                ></path>
              </svg>
            </span>
          </div>
        </div>
      </div>
      <div class="w-full h-full mt-2 flex flex-col">
        <div
          class="text-lg px-6 py-4 border-b flex justify-between items-start"
        >
          <div class="text-left">
            <span>
              The current plan has a pending payment. Switching to a new plan
              with a different payment gateway will override the existing
              payment. Do you want to continue?</span
            >
          </div>
        </div>
      </div>
      <div class="w-full h-full mt-2 flex flex-col items-center">
        <x-button
          size="lg"
          @click="updateSelectedPlan"
          color="orange"
          class="px-4 py-2 mt-4 mb-4"
        >
          <span>Proceed</span></x-button
        >
      </div>
    </div>
  </div>

  <ConfirmationModal
    v-model="confirmationModal.show"
    title="Are you sure?"
    :message="confirmationModal.message"
    @confirm="handleConfirmConfirmationModal"
    @cancel="handleCancelConfirmationModal"
  />
</template>

<style scoped>
/* Modal overlay */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  z-index: 1040;
}
/* Modal container */
.modal-container {
  position: fixed;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 100%;
  height: 100%;
  background-color: hsl(0, 4%, 9%);
  border-radius: 4px;
  padding: 5px;
  z-index: 1050;
}
/* Modal header */
.modal-header {
  background-color: hsl(0, 4%, 9%);
}
/* Modal body */
.modal-body {
  padding: 10px 0;
}

.modal-confirm-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: #33333333;
  z-index: 1040;
}
.modal-confirm-container {
  position: fixed;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 75% !important;
  height: 37%;
  background-color: hsla(0, 0%, 100%, 0.99);
  border-radius: 8px; /* Adjust the radius for desired roundness */
  padding: 2px;
  z-index: 1050;
  border: 1px solid #ccc; /* Grey color for the border */
}

.modal-retry-container {
  position: fixed;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 45%;
  background-color: hsla(0, 0%, 100%, 0.99);
  border-radius: 8px; /* Adjust the radius for desired roundness */
  padding: 2px;
  z-index: 1050;
  border: 1px solid #ccc; /* Grey color for the border */
}
/* Modal header */
.modal-confirm-header {
  color: #000;
}
.inner-th-class {
  min-width: 160px;
}
</style>

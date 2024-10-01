<script setup>
const props = defineProps({
  plan: Object,
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
});

const page = usePage();
const notification = useNotifications('toast');
const isLoading = ref(false);
const isPlanSelectionEnable = ref(false);

const can = permission => useCan(permission);
const permissionEnum = page.props.permissionsEnum;

const emit = defineEmits(['update:selectedPlanChanged']);

const updateSelectedPlan = () => {
  isLoading.value = true;

  let data = {
    plan_id: props.plan.id,
  };

  if (props.quoteType.toLocaleLowerCase() == 'health') {
    data.copay_id = props.plan.selectedCopayId;
  }

  if (props.quoteType.toLocaleLowerCase() == 'travel') {
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
      console.log(err);
      isLoading.value = false;
      notification.error({
        title: err?.response?.data?.message ?? 'something went wrong',
        position: 'top',
      });
    });
};

watch(() => {
  if (props.quoteType.toLowerCase() == 'health') {
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
      size="xs"
      color="success"
      outlined
      :loading="isLoading"
      :disabled="isDisabled"
      @click.prevent="updateSelectedPlan()"
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
</template>

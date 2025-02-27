<script setup>
const props = defineProps({
  modelValue: Boolean,
  plan: Object,
  genders: Object,
  members: Array,
  memberCategories: Array,
  memebersDetailsChanged: Boolean,
});

const page = usePage();

const emit = defineEmits([
  'copayUpdate',
  'update:modelValue',
  'membersDetailsReviewed',
  'onLoadAvailablePlansData',
  'markPlanAsManual',
]);

const dateFormat = date => useDateFormat(date, 'DD-MM-YYYY').value;

const membersDetailRef = ref(props.members);

const showModal = computed({
  get: () => props.modelValue,
  set: val => emit('update:modelValue', val),
});

const memberCategoryText = memberId => {
  let memberCategoryId = null;
  memberCategoryId = props.members.find(
    member => member.id === memberId,
  )?.member_category_id;
  return props.memberCategories.find(
    category => category.id === memberCategoryId,
  )?.text;
};

const memberDobText = memberId => {
  return dateFormat(props.members.find(member => member.id === memberId)?.dob);
};

const memberGenderText = memberId => {
  let gender = null;
  gender = props.members.find(member => member.id === memberId)?.gender;
  return props.genders[gender];
};

const notification = useToast();

const genderText = v => {
  return props.genders[v];
};

const coPayOptions = computed(() => {
  return (
    Object.keys(props.plan.coPayments).map(index => ({
      value: props.plan.coPayments[index].id,
      label: props.plan.coPayments[index].text,
    })) || []
  );
});

const ipmiBenefits = reactive({
  region: '',
  insurance: '',
  payment: '',
  network: '',
  healthCare: false,
  motherBaby: false,
});

const hidePlan = ref(props.plan?.isHidden),
  isManual = ref(false),
  memberFormLoader = ref(false),
  newPremiums = ref([]);

const toggleLoader = ref(false);

const coPay = ref('');

const canUpdate = computed(() => {
  return (
    props.plan.providerCode == 'CIG' ||
    props.plan.providerCode == 'BUP' ||
    false
  );
});

const tabs = ref([
  { index: 0, label: 'General Info' },
  // { index: 1, label: 'IPMI Benefits' },
  { index: 1, label: 'Members' },
  { index: 2, label: 'In Patient' },
  { index: 3, label: 'Out Patient' },
  { index: 4, label: 'Region coverage & Network list' },
  { index: 5, label: 'Co-pay/Co-insurance' },
  { index: 6, label: 'Maternity cover' },
  { index: 7, label: 'key Hospitals & Clinics' },
  // { index: 8, label: 'Exclusions' },
  // { index: 9, label: 'Policy Detail' },
]);

const onMemberPremiumUpdate = (member, premium) => {
  const index = newPremiums.value.findIndex(m => m.memberId == member.memberId);
  if (index > -1) {
    newPremiums.value[index].premium = premium;
  } else {
    newPremiums.value.push({
      memberId: member.memberId,
      premium: premium,
    });
  }
};

const onMemberUpdate = member => {
  const memberData = {
    quoteUID: usePage().props.quote.uuid,
    planId: props.plan.id,
    planDetails: [
      {
        ...member,
        premium:
          Number(
            newPremiums.value.find(m => m.memberId == member.memberId)?.premium,
          ) || member.premium,
      },
    ],
  };

  memberFormLoader.value = true;

  axios
    .post('/health-plan-manual-update-process', memberData)
    .then(res => {
      if (res.data == 'Plan has been updated') {
        notification.success({
          title: res.data,
          position: 'top',
        });
      } else {
        notification.error({
          title: res.data,
          position: 'top',
        });
      }
    })
    .catch(err => {
      console.log(err);
    })
    .finally(() => {
      memberFormLoader.value = false;
    });
};

const selectedCopay = ref([]);
const defaultCopayId = ref(props.plan?.selectedCopayId);

const onCoPaySelect = copayId => {
  props.plan.ratesPerCopay?.forEach(element => {
    if (element.healthPlanCoPaymentId == copayId) {
      let copayDetails = {
        id: element.healthPlanCoPaymentId,
        premium: element.premium,
        discounted_premium: element.discountPremium,
        vat: element.vat,
        planId: props.plan.id,
        loadingPrice: element.loadingPrice ? element.loadingPrice : 0,
      };

      selectedCopay.value = copayDetails;

      loadingPrices.value = [];
      totalLoadingPrice.value = 0;
    }
  });

  props.plan?.memberPremiumBreakdown?.forEach(members => {
    members?.ratesPerCopay?.forEach(data => {
      if (
        data.healthPlanCoPaymentId == copayId &&
        data.loadingPrice != undefined
      ) {
        loadingPrices.value.push({
          memberId: members.memberId,
          price: data.loadingPrice,
        });
      } else if (
        data.healthPlanCoPaymentId == copayId &&
        (data.loadingPrice == undefined || data.loadingPrice == 0)
      ) {
        loadingPrices.value.push({
          memberId: members.memberId,
          price: 0,
        });
      }
    });
  });

  if (loadingPrices != undefined || loadingPrices.length != 0) {
    loadingPrices.value.forEach(data => {
      totalLoadingPrice.value =
        Number(totalLoadingPrice.value) + Number(data.price);
    });
  }

  // emit('copayUpdate', selectedCopay.value);
  // emit("onLoadAvailablePlansData")
};

const onTogglePlans = () => {
  toggleLoader.value = true;

  axios
    .post('/quotes/health/manual-plan-toggle', {
      modelType: 'Health',
      planIds: [props.plan.id],
      quote_uuid: usePage().props.quote.uuid,
      toggle: hidePlan.value,
    })
    .then(response => {
      notification.success({
        title: 'Plan has been updated',
        position: 'top',
      });
      emit('onLoadAvailablePlansData');
    })
    .catch(error => {
      notification.error({
        title: error,
        position: 'top',
      });
    })
    .finally(() => {
      toggleLoader.value = false;
    });
};

const newActualPremium = ref(0);

const defaultLoadingPrice = ref(0);

const getDefaultVaues = () => {
  let smallestCopayValue = 0;
  if (defaultCopayId.value != undefined || defaultCopayId != null) {
    props.plan?.ratesPerCopay?.forEach(function callback(element, index) {
      if (element.healthPlanCoPaymentId == defaultCopayId.value) {
        smallestCopayValue = Number(element.discountPremium);
      }
    });
    newActualPremium.value = smallestCopayValue;
  } else if (
    (defaultCopayId.value == undefined || defaultCopayId == null) &&
    (selectedCopay.value === undefined || selectedCopay.value.length == 0)
  ) {
    props.plan?.ratesPerCopay?.forEach(function callback(element, index) {
      if (index == 0) {
        smallestCopayValue = Number(element.discountPremium);
        defaultCopayId.value = element.healthPlanCoPaymentId;
      } else if (element.discountPremium < smallestCopayValue) {
        smallestCopayValue = Number(element.discountPremium);
        defaultCopayId.value = element.healthPlanCoPaymentId;
      }
    });
    newActualPremium.value = smallestCopayValue;
  }
};

const loadingPrices = ref([]);
const membersLoadingPrices = ref([]);
const finalPrice = ref(0);
const totalLoadingPrice = ref(0);
const vatAmount = ref(0);
const loadingPriceBeingUpdated = ref(false);

const handleLoadingPrice = (event, memberId) => {
  loadingPriceBeingUpdated.value = true;
  const index = loadingPrices.value.findIndex(m => m.memberId == memberId);
  if (index > -1) {
    loadingPrices.value[index].price = event.target.value;
  } else {
    loadingPrices.value.push({
      memberId: memberId,
      price: event.target.value,
    });
  }
};

const manualPlansMembersPremium = ref([]);
const handleManualBasePrice = (event, memberId) => {
  const index = manualPlansMembersPremium.value.findIndex(
    m => m.memberId == memberId,
  );
  if (index > -1) {
    manualPlansMembersPremium.value[index].premium = event.target.value;
  } else {
    manualPlansMembersPremium.value.push({
      memberId: memberId,
      premium: event.target.value,
    });
  }
};

const checkLoadingPriceUpdate = e => {
  if (loadingPriceBeingUpdated.value) {
    if (
      confirm('You have unsaved loading price changes. Do you want to leave?')
    ) {
      loadingPriceBeingUpdated.value = false;
      return true;
    } else {
      e.preventDefault();
      return false;
    }
  }
};

const memberIndexPerId = id => {
  return props.plan.memberPremiumBreakdown.findIndex(m => m.memberId == id);
};

const updateGeneralInfo = () => {
  //   if (confirm('Do you want to update this values?')) {
  totalLoadingPrice.value = 0;
  if (loadingPrices.value.length > 0) {
    loadingPrices.value.forEach(loadingPrice => {
      totalLoadingPrice.value =
        Number(totalLoadingPrice.value) + Number(loadingPrice.price);
    });
  }
  loadingPriceBeingUpdated.value = false;
  emit('membersDetailsReviewed', true);
  //   }
};

const onLoadingPricesUpdate = (member, updateManual = 1) => {
  updateGeneralInfo();
  const memberData = {
    quoteUID: usePage().props.quote.uuid,
    planId: props.plan.id,
    planDetails: {
      ...member,
    },
    defaultCopayId: defaultCopayId.value,
    selectedCopay: selectedCopay.value,
    loadingPrice: loadingPrices.value.map(m => ({
      memberId: m.memberId,
      price: parseFloat(m.price),
    })),
    manualPremiumPrice: manualPlansMembersPremium.value.map(m => ({
      memberId: m.memberId,
      premium: m.premium,
    })),
    tagAsManual: updateManual,
  };

  memberFormLoader.value = true;

  axios
    .post('/health-plan-manual-update-process-v2', memberData)
    .then(res => {
      if (res.data == 'Plan has been updated') {
        notification.success({
          title: res.data,
          position: 'top',
        });
        router.reload({ only: ['payments', 'ecomDetails'] });
      } else {
        notification.error({
          title: res.data,
          position: 'top',
        });
      }
    })
    .catch(err => {
      console.log(err);
    })
    .finally(() => {
      memberFormLoader.value = false;
      isManual.value = false;
      // if (updateManual)
      // {
      //     emit('markPlanAsManual', props.plan, totalLoadingPrice.value);
      // }
      emit('onLoadAvailablePlansData');
    });
};

const markMemberBasePriceRevise = (event, id) => {
  if (event.target.checked) {
    const apiData = {
      quoteUID: usePage().props.quote.uuid,
      planId: props.plan.id,
      memberId: id,
      defaultCopayId: defaultCopayId.value,
      selectedCopay: selectedCopay.value,
      notifyAgent: false,
    };

    memberFormLoader.value = true;

    axios
      .post('/health-plan-notify-agent', apiData)
      .then(res => {
        if (res.status == 200) {
          notification.success({
            title: res.data,
            position: 'top',
          });
        } else {
          notification.error({
            title: res.data,
            position: 'top',
          });
        }
      })
      .catch(err => {
        console.log(err);
      })
      .finally(() => {
        memberFormLoader.value = false;
        emit('onLoadAvailablePlansData');
      });
  }
};

onUpdated(() => {
  defaultCopayId.value = props.plan?.selectedCopayId;
  hidePlan.value = props.plan?.isHidden;
  loadingPrices.value = [];
  manualPlansMembersPremium.value = [];
  selectedCopay.value = [];
  totalLoadingPrice.value = 0;
  isManual.value = false;
  loadingPriceBeingUpdated.value = false;
  getDefaultVaues();
  coPay.value = defaultCopayId.value; // get the default selected value for coPay

  props.plan?.memberPremiumBreakdown?.forEach(members => {
    members?.ratesPerCopay?.forEach(data => {
      if (
        data.healthPlanCoPaymentId == selectedCopay.value.id &&
        (!data.premium || data.premium == undefined)
      ) {
        manualPlansMembersPremium.value.push({
          memberId: members.memberId,
          premium: 0,
        });
      } else if (
        data.healthPlanCoPaymentId == selectedCopay.value.id &&
        data.premium != undefined
      ) {
        manualPlansMembersPremium.value.push({
          memberId: members.memberId,
          premium: data.premium,
        });
      } else if (
        (selectedCopay.value === undefined ||
          selectedCopay.value.length == 0) &&
        data.healthPlanCoPaymentId == defaultCopayId.value &&
        (!data.premium || data.premium == undefined)
      ) {
        manualPlansMembersPremium.value.push({
          memberId: members.memberId,
          premium: 0,
        });
      } else if (
        (selectedCopay.value === undefined ||
          selectedCopay.value.length == 0) &&
        data.healthPlanCoPaymentId == defaultCopayId.value &&
        data.premium != undefined
      ) {
        manualPlansMembersPremium.value.push({
          memberId: members.memberId,
          premium: 0,
        });
      }

      if (
        data.healthPlanCoPaymentId == selectedCopay.value.id &&
        data.loadingPrice != undefined
      ) {
        loadingPrices.value.push({
          memberId: members.memberId,
          price: data.loadingPrice,
        });
      } else if (
        data.healthPlanCoPaymentId == selectedCopay.value.id &&
        data.loadingPrice == undefined
      ) {
        loadingPrices.value.push({
          memberId: members.memberId,
          price: 0,
        });
      } else if (
        (selectedCopay.value === undefined ||
          selectedCopay.value.length == 0) &&
        data.healthPlanCoPaymentId == defaultCopayId.value &&
        data.loadingPrice != undefined
      ) {
        loadingPrices.value.push({
          memberId: members.memberId,
          price: data.loadingPrice,
        });
      } else if (
        (selectedCopay.value === undefined ||
          selectedCopay.value.length == 0) &&
        data.healthPlanCoPaymentId == defaultCopayId.value &&
        data.loadingPrice == undefined
      ) {
        loadingPrices.value.push({
          memberId: members.memberId,
          price: 0,
        });
      }

      if (data.notifyAgent && data.notifyAgent === true) {
        data.priceIsRevised = false;
      } else {
        data.priceIsRevised = true;
      }
    });
  });

  loadingPrices.value.forEach(data => {
    totalLoadingPrice.value =
      Number(totalLoadingPrice.value) + Number(data.price);
  });
});
const [ToggleManualButtonTemplate, ToggleManualButtonReuseTemplate] =
  createReusableTemplate();
</script>

<template>
  <x-modal
    v-model="showModal"
    size="xl"
    :title="`${plan?.providerName} - ${plan?.name}`"
    show-close
    backdrop
    :has-actions="false"
  >
    <div class="flex justify-end">
      <ToggleManualButtonTemplate v-slot="{ isDisabled }">
        <x-toggle
          v-model="isManual"
          color="success"
          label="Manual"
          :loading="toggleLoader"
          :disabled="isDisabled"
        />
      </ToggleManualButtonTemplate>

      <div class="flex justify-between items-center">
        <div class="flex gap-3 pr-8">
          <x-tooltip
            v-if="page.props.lockLeadSectionsDetails.plan_selection"
            placement="bottom"
          >
            <ToggleManualButtonReuseTemplate :isDisabled="true" />
            <template #tooltip>
              No further action allowed on issued policy, If changes are
              required, such as increase in price, please proceed through the
              'Send Update' feature using the 'Correction of Policy' option.
            </template>
          </x-tooltip>
          <ToggleManualButtonReuseTemplate v-else />
          <x-toggle
            v-model="hidePlan"
            color="error"
            label="Hide Plan"
            @change="onTogglePlans"
            :loading="toggleLoader"
          />
        </div>
      </div>
    </div>
    <div class="w-full">
      <TabGroup>
        <TabList
          class="flex flex-row flex-wrap gap-2 rounded-xl bg-slate-100 p-1.5 w-full"
        >
          <Tab
            v-for="{ index, label } in tabs"
            :key="index"
            v-slot="{ selected }"
          >
            <!-- don't remove this commented part anyone please -->
            <x-tooltip
              v-if="
                label == 'Members' &&
                props.plan.isManualPlan &&
                props.plan.needPriceUpdate
              "
              placement="bottom"
            >
              <x-badge
                size="xs"
                color="error"
                outlined
                offset-x="-8"
                offset-y="-10"
              >
                <button
                  @click="checkLoadingPriceUpdate"
                  :class="[
                    'rounded-lg px-3 py-2 md:min-w-[15%] text-sm font-medium text-gray-800 transition duration-200 ease-in-out uppercase',
                    'ring-white ring-opacity-60 ring-offset-2 ring-offset-primary-50 focus:outline-none focus:ring-2',
                    selected
                      ? 'bg-white shadow text-primary-600'
                      : 'hover:bg-white/50',
                  ]"
                >
                  {{ label }}
                </button>
                <template #content>!</template>
              </x-badge>
              <template #tooltip>
                Price outdated! <br />
                Please update</template
              >
            </x-tooltip>

            <button
              v-else
              @click="checkLoadingPriceUpdate"
              :class="[
                'rounded-lg px-3 py-2 md:min-w-[15%] text-sm font-medium text-gray-800 transition duration-200 ease-in-out uppercase',
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

        <TabPanels class="mt-2 text-sm min-h-[70vh]">
          <TabPanel>
            <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4 p-4">
              <!-- <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Provider Code</dt>
              <dd>{{ props.plan.providerCode }}</dd>
            </div> -->
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">Provider Name</dt>
                <dd>{{ props.plan.providerName }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">Network Provider</dt>
                <dd>{{ props.plan.eligibilityName }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">Base Price</dt>
                <dd
                  v-if="
                    selectedCopay === undefined || selectedCopay.length == 0
                  "
                >
                  {{ newActualPremium?.toLocaleString() }}
                </dd>
                <dd v-else>
                  {{ selectedCopay.premium?.toLocaleString() }}
                </dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">Total Price(exclusive of VAT)</dt>
                <dd
                  v-if="
                    selectedCopay === undefined || selectedCopay.length == 0
                  "
                >
                  {{
                    (finalPrice =
                      newActualPremium +
                      (props.plan.basmah || 0) +
                      (props.plan.policyFee || 0) +
                      (props.plan.icpFee || 0) +
                      totalLoadingPrice)?.toLocaleString()
                  }}
                </dd>
                <dd v-else>
                  {{
                    (finalPrice =
                      Number(selectedCopay.discountPremium) +
                      (props.plan.basmah || 0) +
                      (props.plan.policyFee || 0) +
                      (props.plan.icpFee || 0) +
                      totalLoadingPrice)?.toLocaleString()
                  }}
                </dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">Loading Price</dt>
                <dd>
                  {{ Number(totalLoadingPrice).toFixed(2)?.toLocaleString() }}
                </dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">Total VAT amount</dt>
                <dd>
                  {{
                    vatAmount = Number(props.plan.vat)
                      .toFixed(2)
                      ?.toLocaleString()
                  }}
                </dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">Basmah</dt>
                <dd>
                  {{ Number(props.plan.basmah).toFixed(2)?.toLocaleString() }}
                </dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">Total Price with VAT</dt>
                <dd>
                  {{
                    Number(
                      (Number(finalPrice) + Number(vatAmount)).toFixed(2),
                    )?.toLocaleString()
                  }}
                </dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">Policy Fee</dt>
                <dd>
                  {{
                    Number(props.plan.policyFee).toFixed(2)?.toLocaleString()
                  }}
                </dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">ICP Fee</dt>
                <dd>
                  {{
                    props.plan?.icpFee
                      ? Number(props.plan?.icpFee).toFixed(2)?.toLocaleString()
                      : '0.00'
                  }}
                </dd>
              </div>
              <!-- <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Total (exclusive of VAT)</dt>
              <dd v-if="selectedCopay === undefined || selectedCopay.length == 0">
                    {{
                    props.plan.actualPremium +
                    (props.plan.basmah || 0) +
                    (props.plan.policyFee || 0)
                    }}
                </dd>
                <dd v-else>
                    {{
                    selectedCopay.premium +
                    (props.plan.basmah || 0) +
                    (props.plan.policyFee || 0)
                    }}
                </dd>
            </div> -->
            </dl>
          </TabPanel>

          <!-- <TabPanel>
          <div class="grid md:grid-cols-2 gap-x-6 gap-y-4 p-4">
            <div class="md:col-span-2 text-right select-none border-b pb-2">
              <x-toggle
                v-model="isManual"
                color="success"
                label="Manual"
                :disabled="!canUpdate"
              />
            </div>
            <x-select
              v-model="ipmiBenefits.region"
              label="Region Coverage"
              placeholder="Select Option"
              :disabled="!isManual"
              :options="[
                { value: '0', label: 'Regional Middle East' },
                { value: '1', label: 'Worldwide excluding US' },
                { value: '2', label: 'Worldwide' },
              ]"
              class="w-full"
            />
            <x-select
              v-model="ipmiBenefits.insurance"
              label="OP Co-insurance"
              placeholder="Select Option"
              :disabled="!isManual"
              :options="[
                { value: '0', label: '0%' },
                { value: '1', label: '20%' },
                { value: '2', label: '10% up to AED 50/OP visit' },
                { value: '3', label: '20% up to AED 100/OP visit' },
              ]"
              class="w-full"
            />
            <x-select
              v-model="ipmiBenefits.payment"
              label="Payment Terms"
              placeholder="Select Option"
              :disabled="!isManual"
              :options="[
                { value: '0', label: 'Annual' },
                { value: '1', label: 'Quarterly' },
                { value: '2', label: 'Monthly' },
              ]"
              class="w-full"
            />
            <x-select
              v-model="ipmiBenefits.network"
              label="Network"
              placeholder="Select Option"
              :disabled="!isManual"
              :options="[
                { value: '0', label: 'General' },
                { value: '1', label: 'General Plus' },
                { value: '2', label: 'Comprehensive Excluding AH' },
                { value: '3', label: 'Comprehensive' },
              ]"
              class="w-full"
            />
            <x-checkbox
              v-model="ipmiBenefits.healthCare"
              label="Healthy Connect"
              :disabled="!isManual"
            />
            <x-checkbox
              v-model="ipmiBenefits.motherBaby"
              label="Mother and Baby Care"
              :disabled="!isManual"
            />
            <div v-if="isManual">
              <x-button color="emerald">Update</x-button>
            </div>
            <div class="md:col-span-2 text-right border-t pt-3 font-bold">
              Total Indicative Premium (with VAT):
              {{
                props.plan.actualPremium +
                (props.plan.vat || 0) +
                (props.plan.basmah || 0)
              }}
            </div>
          </div>
        </TabPanel> -->

          <TabPanel>
            <div class="p-4 relative">
              <div
                v-if="memberFormLoader"
                class="flex items-center justify-center absolute w-full h-[97%] rounded-md !bg-gray-800/20 z-20 inset-0"
              >
                <x-button class="!p-4" color="gray" size="lg" loading rounded />
              </div>
              <DataTable
                :headers="[
                  //   { text: 'Revised', value: 'memberaction' },
                  { text: 'Relationship', value: 'membercategory' },
                  { text: 'DOB', value: 'dobText' },
                  { text: 'Gender', value: 'genderText' },
                  { text: 'Base Price', value: 'premium' },
                  { text: 'Loading Price', value: 'loadingPrice' },
                  { text: 'Final Price', value: 'finalPrice' },
                ]"
                :items="props.plan.memberPremiumBreakdown || []"
                hide-rows-per-page
                hide-footer
                table-class-name="plain"
              >
                <template #header-premium="header">
                  <div class="customize-header">
                    <x-tooltip placement="bottom">
                      <span
                        class="font-semibold tracking-widest uppercase text-xs underline decoration-dotted decoration-primary-600 cursor-help"
                      >
                        {{ header.text }}
                      </span>
                      <template #tooltip>
                        <div class="whitespace-normal normal-case text-[10px]">
                          Base Price (exclusive of VAT, Basmah & Policy Fee)
                        </div>
                      </template>
                    </x-tooltip>
                  </div>
                </template>

                <template #header-loadingPrice="header">
                  <div class="customize-header large-tip">
                    <x-tooltip placement="bottom">
                      <span
                        class="font-semibold tracking-widest uppercase text-xs underline decoration-dotted decoration-primary-600 cursor-help"
                      >
                        {{ header.text }}
                      </span>
                      <template #tooltip>
                        <div
                          class="whitespace-normal text-wrap normal-case text-[10px]"
                        >
                          <p>
                            Additional cost or fee that is added to the base
                            price. This extra charge is
                          </p>
                          <p>
                            applied to cover specific risks or factors
                            associated with the policyholder,
                          </p>
                          <p>
                            such as pre-existing medical conditions or other
                            higher-risk situations
                          </p>
                          <p>(exclusive of VAT)</p>
                        </div>
                      </template>
                    </x-tooltip>
                  </div>
                </template>

                <template #header-finalPrice="header">
                  <div class="customize-header">
                    <x-tooltip placement="bottom">
                      <span
                        class="font-semibold tracking-widest uppercase text-xs underline decoration-dotted decoration-primary-600 cursor-help"
                      >
                        {{ header.text }}
                      </span>
                      <template #tooltip>
                        <div class="whitespace-normal normal-case text-[10px]">
                          Total Price (exclusive of VAT)
                        </div>
                      </template>
                    </x-tooltip>
                  </div>
                </template>

                <!-- Here we goo  -->
                <!-- <template #item-memberaction="item">
                  <section v-for="data in item.ratesPerCopay">
                    <x-checkbox
                      v-if="data.healthPlanCoPaymentId == selectedCopay.id"
                      v-model="data.priceIsRevised"
                      :disabled="!isManual"
                      @change="markMemberBasePriceRevise($event, item.memberId)"
                    />
                    <x-checkbox
                      v-else-if="
                        (selectedCopay === undefined ||
                          selectedCopay.length == 0) &&
                        data.healthPlanCoPaymentId == defaultCopayId
                      "
                      v-model="data.priceIsRevised"
                      :disabled="!isManual"
                      @change="markMemberBasePriceRevise($event, item.memberId)"
                    />
                  </section>
                </template> -->

                <template #item-membercategory="{ memberId }">
                  {{ memberCategoryText(memberId) }}
                </template>

                <template #item-dobText="{ memberId }">
                  <section class="w-28">
                    {{ memberDobText(memberId) }}
                  </section>
                </template>

                <template #item-genderText="{ memberId }">
                  {{ memberGenderText(memberId) }}
                </template>

                <template #item-premium="item">
                  <section v-for="data in item.ratesPerCopay">
                    <!-- <x-input
                      v-if="
                        (!data.premium || data.premium == undefined) &&
                        data.healthPlanCoPaymentId == selectedCopay.id
                      "
                      v-model="
                        manualPlansMembersPremium[
                          memberIndexPerId(item.memberId)
                        ].premium
                      "
                      :disabled="true"
                      size="sm"
                    />
                    <x-input
                      v-else-if="
                        (!data.premium || data.premium == undefined) &&
                        (selectedCopay === undefined ||
                          selectedCopay.length == 0) &&
                        data.healthPlanCoPaymentId == defaultCopayId
                      "
                      v-model="
                        manualPlansMembersPremium[
                          memberIndexPerId(item.memberId)
                        ].premium
                      "
                      :disabled="true"
                      size="sm"
                    /> -->
                    <x-input
                      v-if="data.healthPlanCoPaymentId == selectedCopay.id"
                      :modelValue="data.premium?.toLocaleString()"
                      :disabled="true"
                      size="sm"
                    />
                    <x-input
                      v-else-if="
                        (selectedCopay === undefined ||
                          selectedCopay.length == 0) &&
                        data.healthPlanCoPaymentId == defaultCopayId
                      "
                      :modelValue="data.premium?.toLocaleString()"
                      :disabled="true"
                      size="sm"
                    />
                    <!-- <x-button
                      v-if="$page.props.permissions.pa"
                      color="primary"
                      class="ml-2"
                      size="sm"
                      outlined
                      :loading="memberFormLoader"
                      @click.prevent="onMemberUpdate(item)"
                    >
                      Update
                    </x-button> -->
                  </section>
                </template>

                <template #item-loadingPrice="item">
                  <section v-for="data in item.ratesPerCopay">
                    <x-input
                      v-if="
                        data.healthPlanCoPaymentId == selectedCopay.id &&
                        data.loadingPrice != undefined
                      "
                      v-model="
                        loadingPrices[memberIndexPerId(item.memberId)].price
                      "
                      :disabled="!isManual"
                      size="sm"
                      type="number"
                      step="0.01"
                      onkeydown="return event.keyCode !== 69"
                      @paste.prevent
                      @keyup="handleLoadingPrice($event, item.memberId)"
                    />
                    <x-input
                      v-if="
                        data.healthPlanCoPaymentId == selectedCopay.id &&
                        data.loadingPrice == undefined
                      "
                      v-model="
                        loadingPrices[memberIndexPerId(item.memberId)].price
                      "
                      :disabled="!isManual"
                      size="sm"
                      type="number"
                      step="0.01"
                      onkeydown="return event.keyCode !== 69"
                      @paste.prevent
                      @keyup="handleLoadingPrice($event, item.memberId)"
                    />
                    <x-input
                      v-else-if="
                        (selectedCopay === undefined ||
                          selectedCopay.length == 0) &&
                        data.healthPlanCoPaymentId == defaultCopayId &&
                        data.loadingPrice != undefined
                      "
                      v-model="
                        loadingPrices[memberIndexPerId(item.memberId)].price
                      "
                      :disabled="!isManual"
                      size="sm"
                      type="number"
                      step="0.01"
                      onkeydown="return event.keyCode !== 69"
                      @paste.prevent
                      @keyup="handleLoadingPrice($event, item.memberId)"
                    />
                    <x-input
                      v-else-if="
                        (selectedCopay === undefined ||
                          selectedCopay.length == 0) &&
                        data.healthPlanCoPaymentId == defaultCopayId &&
                        data.loadingPrice == undefined
                      "
                      v-model="
                        loadingPrices[memberIndexPerId(item.memberId)].price
                      "
                      :disabled="!isManual"
                      size="sm"
                      type="number"
                      step="0.01"
                      onkeydown="return event.keyCode !== 69"
                      @paste.prevent
                      @keyup="handleLoadingPrice($event, item.memberId)"
                    />
                  </section>
                  <!-- <x-input
                    v-model="
                      membersLoadingPrices[memberIndexPerId(item.memberId)]
                    "
                    :disabled="!isManual"
                    size="sm"
                    @keyup="handleLoadingPrice($event, item.memberId)"
                  /> -->
                </template>

                <template #item-finalPrice="item">
                  <section v-for="data in item.ratesPerCopay">
                    <x-input
                      v-if="
                        data.healthPlanCoPaymentId == selectedCopay.id &&
                        (!data.premium || data.premium == undefined) &&
                        (data.loadingPrice == undefined ||
                          data.loadingPrice == 0)
                      "
                      :disabled="true"
                      size="sm"
                      :modelValue="
                        (
                          Number(
                            loadingPrices[memberIndexPerId(item.memberId)]
                              ?.price || 0,
                          ) +
                          Number(
                            manualPlansMembersPremium[
                              memberIndexPerId(item.memberId)
                            ]?.premium || 0,
                          )
                        )?.toLocaleString()
                      "
                    />
                    <x-input
                      v-else-if="
                        (selectedCopay === undefined ||
                          selectedCopay.length == 0) &&
                        data.healthPlanCoPaymentId == defaultCopayId &&
                        (!data.premium || data.premium == undefined) &&
                        (data.loadingPrice == undefined ||
                          data.loadingPrice == 0)
                      "
                      :disabled="true"
                      size="sm"
                      :modelValue="
                        (
                          Number(
                            loadingPrices[memberIndexPerId(item.memberId)]
                              ?.price || 0,
                          ) +
                          Number(
                            manualPlansMembersPremium[
                              memberIndexPerId(item.memberId)
                            ]?.premium || 0,
                          )
                        )?.toLocaleString()
                      "
                    />

                    <x-input
                      v-else-if="
                        data.healthPlanCoPaymentId == selectedCopay.id &&
                        (!data.premium || data.premium == undefined) &&
                        data.loadingPrice != undefined &&
                        data.loadingPrice != 0
                      "
                      :disabled="true"
                      size="sm"
                      :modelValue="
                        (
                          Number(
                            loadingPrices[memberIndexPerId(item.memberId)]
                              ?.price || 0,
                          ) +
                          Number(
                            manualPlansMembersPremium[
                              memberIndexPerId(item.memberId)
                            ]?.premium || 0,
                          )
                        )?.toLocaleString()
                      "
                    />

                    <x-input
                      v-else-if="
                        (selectedCopay === undefined ||
                          selectedCopay.length == 0) &&
                        data.healthPlanCoPaymentId == defaultCopayId &&
                        data.healthPlanCoPaymentId == selectedCopay.id &&
                        (!data.premium || data.premium == undefined) &&
                        data.loadingPrice != undefined &&
                        data.loadingPrice != 0
                      "
                      :disabled="true"
                      size="sm"
                      :modelValue="
                        (
                          Number(
                            loadingPrices[memberIndexPerId(item.memberId)]
                              ?.price || 0,
                          ) +
                          Number(
                            manualPlansMembersPremium[
                              memberIndexPerId(item.memberId)
                            ]?.premium || 0,
                          )
                        )?.toLocaleString()
                      "
                    />

                    <x-input
                      v-else-if="
                        data.healthPlanCoPaymentId == selectedCopay.id &&
                        data.loadingPrice != undefined &&
                        data.loadingPrice != 0
                      "
                      :disabled="true"
                      size="sm"
                      :modelValue="
                        (
                          Number(
                            loadingPrices[memberIndexPerId(item.memberId)]
                              ?.price || 0,
                          ) + Number(data.premium || 0)
                        )?.toLocaleString()
                      "
                    />
                    <x-input
                      v-else-if="
                        data.healthPlanCoPaymentId == selectedCopay.id &&
                        (data.loadingPrice == undefined ||
                          data.loadingPrice == 0)
                      "
                      :disabled="true"
                      size="sm"
                      :value="
                        (
                          Number(
                            loadingPrices[memberIndexPerId(item.memberId)]
                              ?.price || 0,
                          ) + Number(data.premium || 0)
                        )?.toLocaleString()
                      "
                    />
                    <x-input
                      v-else-if="
                        (selectedCopay === undefined ||
                          selectedCopay.length == 0) &&
                        data.healthPlanCoPaymentId == defaultCopayId &&
                        data.loadingPrice != undefined &&
                        data.loadingPrice != 0
                      "
                      :disabled="true"
                      size="sm"
                      :modelValue="
                        (
                          Number(
                            loadingPrices[memberIndexPerId(item.memberId)]
                              ?.price || 0,
                          ) + Number(data.premium || 0)
                        )?.toLocaleString()
                      "
                    />
                    <x-input
                      v-else-if="
                        (selectedCopay === undefined ||
                          selectedCopay.length == 0) &&
                        data.healthPlanCoPaymentId == defaultCopayId &&
                        (data.loadingPrice == undefined ||
                          data.loadingPrice == 0)
                      "
                      :disabled="true"
                      size="sm"
                      :modelValue="
                        (
                          Number(
                            loadingPrices[memberIndexPerId(item.memberId)]
                              ?.price || 0,
                          ) + Number(data.premium || 0)
                        )?.toLocaleString()
                      "
                    />
                  </section>
                </template>

                <!-- <template #item-premium="{ item }">
                <x-input
                  :value="item.premium"
                  :disabled="item.premium != 0 && !isManual"
                  size="sm"
                  @update:modelValue="onMemberPremiumUpdate(item, $event)"
                />
                <x-button
                  v-if="$page.props.permissions.pa"
                  color="primary"
                  class="ml-2"
                  size="sm"
                  outlined
                  :loading="memberFormLoader"
                  @click.prevent="onMemberUpdate(item)"
                >
                  Update
                </x-button>
              </template> -->
              </DataTable>
              <div class="grid md:grid-cols-1 gap-5 p-4 float-right">
                <x-button
                  :disabled="!isManual"
                  color="primary"
                  size="sm"
                  @click="
                    onLoadingPricesUpdate(props.plan.memberPremiumBreakdown)
                  "
                >
                  Update & Save
                </x-button>
              </div>
            </div>
          </TabPanel>

          <TabPanel>
            <dl class="grid md:grid-cols-2 gap-5 p-4">
              <div
                v-for="data in props.plan.benefits.inpatient || []"
                :key="data.code"
              >
                <dt class="font-medium mb-1">{{ data.text }}</dt>
                <dd>{{ data.value }}</dd>
              </div>
            </dl>
          </TabPanel>

          <TabPanel>
            <dl class="grid md:grid-cols-2 gap-5 p-4">
              <div
                v-for="data in props.plan.benefits.outpatient || []"
                :key="data.code"
              >
                <dt class="font-medium mb-1">{{ data.text }}</dt>
                <dd>{{ data.value }}</dd>
              </div>
            </dl>
          </TabPanel>

          <TabPanel>
            <dl class="grid md:grid-cols-2 gap-5 p-4">
              <div
                v-for="data in props.plan.benefits.regionCover || []"
                :key="data.code"
              >
                <dt class="font-medium mb-1">{{ data.text }}</dt>
                <dd>{{ data.value }}</dd>
              </div>
              <div
                v-for="data in props.plan.benefits.networkList || []"
                :key="data.code"
              >
                <dt class="font-medium mb-1">{{ data.text }}</dt>
                <dd>{{ data.value }}</dd>
              </div>
            </dl>
          </TabPanel>
          <!-- I will work here -->
          <TabPanel>
            <div
              v-if="memberFormLoader"
              class="flex items-center justify-center absolute w-full h-[97%] rounded-md !bg-gray-800/20 z-20 inset-0"
            >
              <x-button class="!p-4" color="gray" size="lg" loading rounded />
            </div>
            <div class="grid md:grid-cols-1 gap-5 p-4 copay-select">
              <ComboBox
                class="w-full"
                v-model="coPay"
                :options="coPayOptions"
                :disabled="!isManual"
                :single="true"
                label="Co-Pay"
                placeholder="Select a Co-Pay option"
                @update:model-value="onCoPaySelect"
              >
              </ComboBox>
            </div>
            <dl class="grid md:grid-cols-2 gap-5 p-4">
              <div
                v-for="data in props.plan.benefits.coInsurance || []"
                :key="data.code"
              >
                <dt class="font-medium mb-1">{{ data.text }}</dt>
                <dd>{{ data.value }}</dd>
              </div>
            </dl>
            <!-- <div class="grid md:grid-cols-1 gap-5 p-4 float-right">
            <x-button
              color="primary"
              size="sm"
            >
              Update & Save
            </x-button>
          </div> -->

            <!-- Set Manual plan modification true for CoPay, Requested from API Team-->
            <div class="grid md:grid-cols-1 gap-5 p-4 float-right">
              <x-button
                :disabled="!isManual"
                color="primary"
                size="sm"
                @click="
                  onLoadingPricesUpdate(
                    props.plan.memberPremiumBreakdown,
                    false,
                  )
                "
              >
                Update & Save
              </x-button>
            </div>
          </TabPanel>
          <!-- between here -->
          <TabPanel>
            <dl class="grid md:grid-cols-2 gap-5 p-4">
              <div
                v-for="data in props.plan.benefits.maternityCover || []"
                :key="data.code"
              >
                <dt class="font-medium mb-1">{{ data.text }}</dt>
                <dd>{{ data.value }}</dd>
              </div>
            </dl>
          </TabPanel>

          <TabPanel>
            <dl class="grid md:grid-cols-1 gap-x-6 gap-y-4 p-4">
              <div
                class="grid sm:grid-cols-4"
                v-if="
                  (
                    props.plan?.healthNetwork?.featuredFacilities?.filter(
                      e => e.type === 'HOSPITAL',
                    ) || []
                  ).length > 0
                "
              >
                <dt class="font-medium">Key Hospitals:</dt>
                <dd>
                  <div
                    v-for="data in props.plan?.healthNetwork?.featuredFacilities?.filter(
                      e => e.type === 'HOSPITAL',
                    ) || []"
                    :key="data.id"
                  >
                    {{ data.text }}
                  </div>
                </dd>
              </div>
              <div
                class="grid sm:grid-cols-4"
                v-if="
                  (
                    props.plan?.healthNetwork?.featuredFacilities?.filter(
                      e => e.type === 'CLINIC',
                    ) || []
                  ).length > 0
                "
              >
                <dt class="font-medium">Key Clinics:</dt>
                <dd>
                  <div
                    v-for="data in props.plan?.healthNetwork?.featuredFacilities?.filter(
                      e => e.type === 'CLINIC',
                    ) || []"
                    :key="data.id"
                  >
                    {{ data.text }}
                  </div>
                </dd>
              </div>
            </dl>
          </TabPanel>

          <!-- <TabPanel>
          <dl class="grid md:grid-cols-2 gap-5 p-4">
            <div
              v-for="data in props.plan.benefits.exclusion || []"
              :key="data.code"
            >
              <dt class="font-medium mb-1">{{ data.text }}</dt>
              <dd>{{ data.value }}</dd>
            </div>
          </dl>
        </TabPanel>
        <TabPanel>
          <dl class="grid md:grid-cols-2 gap-5 p-4">
            <x-link
              v-for="data in props.plan.benefits.networkLink || []"
              :key="data.code"
              :href="data.value"
              target="_blank"
              title="Open File"
              external
            >
              {{ data.text }}
            </x-link>
          </dl>
        </TabPanel> -->
        </TabPanels>
      </TabGroup>
    </div>
  </x-modal>
</template>

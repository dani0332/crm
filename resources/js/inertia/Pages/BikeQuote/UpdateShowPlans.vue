<script setup>
const props = defineProps({
  plan: Object,
  quote: Object,
});
const { isRequired } = useRules();
const page = usePage();
const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;
const emit = defineEmits(['onLoadAvailablePlansData']);

const notification = useToast();
const coreInsurer = ['AXA', 'OIC', 'TM', 'QIC', 'RSA'];
const halfLiveInsurer = [
  'SI',
  'OI',
  'Watania',
  'DNIRC',
  'NIA',
  'UI',
  'IHC',
  'NT',
];

const ancillaryExcessOptions = computed(() => {
  let arr = [];
  for (let i = 0; i <= 20; i++) {
    arr.push({ value: i, label: `${i}%` });
  }
  return arr;
});

const totalPremiumWithVat = computed(() => {
  let addonVat = 0;
  props.plan.addons.forEach(addon => {
    (addon?.bikeAddonOption || []).forEach(option => {
      if (option.isSelected && option.price != 0) {
        addonVat += parseInt(option.price) + option.vat;
      }
    });
  });
  return props.plan.discountPremium + props.plan.vat + addonVat;
});

const toggleLoader = ref(false);
const toggleManualLoader = ref(false);
const showInsurerError = ref(false);

const planForm = useForm({
  bike_quote_uuid: usePage().props.quote.uuid,
  bike_plan_id: props.plan.id,
  actual_premium: props.plan.actualPremium,
  discounted_premium: props.plan.discountPremium,
  premium_vat: props.vat ? props.vat : 0,
  bike_value: props.plan.bikeValue,
  excess: props.plan.excess || 0,
  is_disabled: props.plan.isDisabled,
  is_create: 0,
  addons: props.plan.addons,
  insurer_quote_no:
    props.plan.insurerQuoteNo != null && props.plan.insurerQuoteNo != ''
      ? props.plan.insurerQuoteNo
      : '',
  is_manual_update: props.plan.isManualUpdate,
  ancillary_excess: props.plan.ancillaryExcess,
  current_url: usePage().url,
});

watch(
  () => planForm.actual_premium,
  () => {
    planForm.discounted_premium = planForm.actual_premium;
  },
  { immediate: true },
);

const dateFormat = date => useDateFormat(date, 'DD-MM-YYYY').value;
const tabs = ref([
  { index: 0, label: 'General Info' },
  { index: 1, label: 'Addons' },
  { index: 2, label: 'Inclusions' },
  { index: 3, label: 'Exclusions' },
  { index: 4, label: 'Road Side Assistance' },
  { index: 5, label: 'Policy Detail' },
]);

const onTogglePlans = () => {
  toggleLoader.value = true;

  axios
    .post(route('bikeManualPlanToggle', { quoteType: 'Bike' }), {
      modelType: 'Bike',
      planIds: [props.plan.id],
      bike_quote_uuid: usePage().props.quote.uuid,
      toggle: planForm.is_disabled,
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

const onUpdatePlan = () => {
  if (planForm.discounted_premium > planForm.actual_premium) {
    notification.error({
      title: 'Discounted Price must be lower than Actual Price',
      position: 'top',
    });
    return;
  }

  if (planForm.is_manual_update && planForm.insurer_quote_no == '') {
    showInsurerError.value = true;
    return;
  } else {
    showInsurerError.value = false;
  }

  let addons = [];
  let tempAddons = planForm.addons;
  tempAddons.forEach(addon => {
    addon.bikeAddonOption.forEach(option => {
      addons.push({
        addonId: addon.id,
        addonOptionId: option.id,
        price: parseInt(option.price),
        vat: option.vat,
        isSelected: option.isSelected,
      });
    });
  });
  planForm
    .transform(data => ({
      ...data,
      addons,
    }))
    .post('/bike-plan-manual-update-process', {
      preserveScroll: true,
      preserveState: true,
      onSuccess: response => {
        notification.success({
          title: 'Plan updated successfully',
          position: 'top',
        });
        emit('onLoadAvailablePlansData');
      },
      onError: error => {
        notification.error({
          title: error[0],
          position: 'top',
        });
      },
    });
};

const validateDecimal = event => {
  if (
    event.key === '.' ||
    event.key === 'Backspace' ||
    event.key === 'Delete' ||
    (event.ctrlKey &&
      (event.key === 'c' ||
        event.key === 'C' ||
        event.key === 'v' ||
        event.key === 'V'))
  ) {
    return;
  }
  const regex = /^\d+(\.\d{0,2})?$/;
  if (!regex.test(event.key)) {
    event.preventDefault();
  }
};
const onToggleManual = () => {
  toggleManualLoader.value = true;
  setTimeout(() => {
    toggleManualLoader.value = false;
  }, 300);
};
const readOnlyMode = reactive({
  isDisable: true,
});
onMounted(() => {
  readOnlyMode.isDisable = !can(permissionsEnum.All_QUOTES_VIEWONLY_ACCESS);
});
</script>

<template>
  <div class="w-full">
    <TabGroup>
      <TabList
        class="flex flex-row flex-wrap gap-2 rounded-xl bg-slate-100 p-1.5 w-full"
      >
        <Tab
          v-for="{ index, label } in tabs"
          as="template"
          :key="index"
          v-slot="{ selected }"
        >
          <button
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
            <div class="grid sm:grid-cols-2 mb-3">
              <x-toggle
                v-model="planForm.is_disabled"
                color="success"
                label="Hide Plan?"
                @change="onTogglePlans"
                :loading="toggleLoader"
              />
            </div>
            <div class="grid sm:grid-cols-2 mb-3">
              <x-toggle
                v-model="planForm.is_manual_update"
                color="success"
                label="Manual"
                @change="onToggleManual"
                :loading="toggleManualLoader"
              />
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="">Provider Name</dt>
              <dd>{{ props.plan.providerName }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="">Repair Type:</dt>
              <dd>
                {{
                  props.plan.repairType && props.plan.repairType == 'COMP'
                    ? coreInsurer.includes(props.plan.providerCode)
                      ? 'Premium workshop'
                      : halfLiveInsurer.includes(props.plan.providerCode)
                        ? 'Non-Agency workshop'
                        : 'NON-AGENCY'
                    : props.plan.repairType
                }}
              </dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="mt-2">Insurer Quote No.:</dt>
              <x-input
                v-model="planForm.insurer_quote_no"
                :disabled="!planForm.is_manual_update"
                :error="showInsurerError ? 'This field is required' : ''"
                maxlength="50"
                size="sm"
              />
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="mt-2">Actual Price:</dt>
              <x-input
                v-model="planForm.actual_premium"
                :disabled="!planForm.is_manual_update"
                size="sm"
                type="number"
              />
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="mt-2">Discounted Price:</dt>
              <x-input
                v-model="planForm.discounted_premium"
                size="sm"
                type="number"
              />
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="mt-2">Bike value:</dt>
              <x-input
                v-model="planForm.bike_value"
                :helper="
                  planForm.is_manual_update
                    ? `Min: AED ${props.plan.bikeValueLowerLimit} - Max: AED ${props.plan.bikeValueUpperLimit}`
                    : ''
                "
                size="sm"
                type="number"
              />
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="mt-2">Excess:</dt>
              <x-input
                v-model="planForm.excess"
                :disabled="!planForm.is_manual_update"
                type="number"
                size="sm"
              />
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="mt-2">Ancillary Excess:</dt>
              <x-select
                v-model="planForm.ancillary_excess"
                placeholder="Select Option"
                :options="ancillaryExcessOptions"
                class="w-full"
              />
            </div>
            <div class="grid sm:grid-cols-2"></div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-bold">Features:</dt>
            </div>
            <div class="grid sm:grid-cols-2" />
            <template v-for="feature in plan.benefits.feature" :key="feature">
              <div class="grid sm:grid-cols-2">
                <dt class="font-small">{{ feature?.text }}</dt>
                <dd>{{ feature.value }}</dd>
              </div>
              <div class="grid sm:grid-cols-2" />
            </template>
            <x-divider class="mt-1" />
          </dl>
          <dl class="grid md:grid-cols-2 gap-x-6 border-top pl-4">
            <div class="grid sm:grid-cols-2">
              <dt class="font-bold">Total Price with VAT:</dt>
              <dd>AED: {{ totalPremiumWithVat?.toFixed(2) }}</dd>
            </div>
            <div class="flex justify-end">
              <span class="font-medium text-end">Created Date:</span>
              <span>{{ props.quote.created_at }}</span>
            </div>
            <br />
            <div class="flex justify-end">
              <span class="font-medium text-end">Updated At:</span>
              <span>{{ props.quote.updated_at }}</span>
            </div>
          </dl>
          <br />
          <div class="flex justify-end">
            <x-button
              color="primary"
              size="sm"
              @click="onUpdatePlan"
              :loading="planForm.processing"
              v-if="readOnlyMode.isDisable === true"
            >
              Update
            </x-button>
          </div>
        </TabPanel>

        <TabPanel>
          <div class="p-4">
            <template v-for="addon in planForm.addons" :key="addon">
              <template v-for="option in addon.bikeAddonOption" :key="option">
                <div class="flex my-2">
                  <span class="w-60">{{ addon.text }}</span>
                  <span class="w-60">{{ option.value }}</span>
                  <x-input
                    class="w-20 mr-10"
                    :value="option.price"
                    :disabled="!planForm.is_manual_update || !option.isSelected"
                    size="sm"
                    v-model="option.price"
                    type="number"
                  />
                  <x-toggle
                    v-model="option.isSelected"
                    :disabled="!planForm.is_manual_update"
                    color="success"
                    class="mt-2"
                  />
                </div>
              </template>
            </template>
            <x-divider class="mb-3 mt-3" />
            <div class="grid sm:grid-cols-4">
              <dt class="font-bold">Total Price with VAT:</dt>
              <dd>AED: {{ totalPremiumWithVat?.toFixed(2) }}</dd>
            </div>
            <div class="flex justify-end">
              <x-button
                color="primary"
                class="mt-5"
                size="sm"
                @click.prevent="onUpdatePlan"
                :loading="planForm.processing"
                v-if="readOnlyMode.isDisable === true"
              >
                Update
              </x-button>
            </div>
          </div>
        </TabPanel>

        <TabPanel>
          <dl class="grid md:grid-cols-2 gap-5 p-4">
            <div
              v-for="data in props.plan.benefits.inclusion || []"
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
              v-for="data in props.plan.benefits.exclusion || []"
              :key="data"
            >
              <dt class="font-medium mb-1">{{ data.text }}</dt>
              <dd>{{ data.value }}</dd>
            </div>
          </dl>
        </TabPanel>

        <TabPanel>
          <dl class="grid md:grid-cols-2 gap-5 p-4">
            <div
              v-for="data in props.plan.benefits.roadSideAssistance || []"
              :key="data"
            >
              <dt class="font-medium mb-1">{{ data.text }}</dt>
              <dd>{{ data.value }}</dd>
            </div>
          </dl>
        </TabPanel>

        <TabPanel>
          <dl class="grid md:grid-cols-2 gap-5 p-4">
            <div v-for="data in props.plan.policyWordings || []" :key="data">
              <a :href="data.link" class="font-medium mb-1" target="_blank">{{
                data.text
              }}</a>
            </div>
          </dl>
        </TabPanel>
      </TabPanels>
    </TabGroup>
  </div>
</template>

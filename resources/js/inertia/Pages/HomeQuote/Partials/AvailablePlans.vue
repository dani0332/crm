<script setup>
import { computed, provide } from 'vue';

const can = permission => useCan(permission);

const props = defineProps({
  plan: Object,
});

const permissionsEnum = props.plan.permissionsEnum;

const emit = defineEmits([]);

console.log('props.plan', props.plan);
const notification = useToast();

const toggleLoader = ref(false);
const toggleManualLoader = ref(false);
const showInsurerError = ref(false);

const planForm = useForm({
  quote_uuid: usePage().props.quote.uuid || '',
  home_plan_id: props.plan.id || '',
  provider_name: props.plan.providerName    || '',
  actual_premium: props.plan.actualPremium || 0,
  discounted_premium: props.plan.discountPremium || 0,
  premium_vat: props.vat ? props.vat : 0,
  bike_value: props.plan.bikeValue || 0,
  excess: props.plan.excess || 0,
  is_disabled: props.plan.isDisabled || false,
  insurer_quote_no:
    props.plan.insurerQuoteNo != null && props.plan.insurerQuoteNo != ''
      ? props.plan.insurerQuoteNo
      : '',
  is_manual_update: props.plan.isManualUpdate || false,
  ancillary_excess: props.plan.ancillaryExcess || 0,
  current_url: usePage().url || '',
  listQuotePlanBenefitsInclusions : props.plan.listQuotePlanBenefitsInclusions || [],
  listQuotePlanBenefitsExclusions : props.plan.listQuotePlanBenefitsExclusions || [],
});

console.log('planForm', planForm);

const listQuotePlansMembers = computed(() => {
  return props.plan.listQuotePlansMembers.map((item, index) => {
    return { ...item, index };
  });
});

const dateFormat = date => useDateFormat(date, 'DD-MM-YYYY').value;
const tabs = ref([
  { index: 0, label: 'General Info' },
  { index: 1, label: 'Inclusions' },
  { index: 2, label: 'Additonal Covers' },
  { index: 3, label: 'Exclusions' },
  { index: 4, label: 'Policy Details' },
]);

const onToggleManual = () => {
  toggleManualLoader.value = true;
  setTimeout(() => {
    toggleManualLoader.value = false;
  }, 300);
};

const onTogglePlans = () => {
  toggleLoader.value = true;

  axios
    .post(route('homeManualPlanToggle', { quoteType: 'Home' }), {
      modelType: 'PersonalQuote',
      planIds: [planForm.home_plan_id],
      personal_quote_uuid: usePage().props.quote.uuid,
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
const homeDiscountOptions = computed(() => {
  let arr = [];
  for (let i = 0; i <= 20; i++) {
    arr.push({ value: i, label: `${i}%` });
  }
  return arr;
});

const buildingValue = computed({
  get() {
    console.log(
      'buildingValue',
      planForm.listQuotePlanBenefitsInclusions?.buildings?.[0]?.value,
    );

    // Get the raw value (e.g., "AED 40,000")
    const rawValue =
      planForm.listQuotePlanBenefitsInclusions?.buildings?.[0]?.value || '0';

    // Remove non-numeric characters (like "AED" or commas) and parse it as float
    const numericValue = parseFloat(rawValue.replace(/[^\d.-]/g, ''));

    console.log('numericValue', numericValue);

    // Return the numeric value directly for v-model binding (input expects raw numbers)
    return numericValue;
  },
  set(newValue) {
    // Keep it as raw number, but remove commas or format artifacts when setting
    planForm.listQuotePlanBenefitsInclusions.buildings[0].value =
      newValue.toString();
  },
});

const contentValue = computed({
  get() {
    console.log(
      'contentValue',
      planForm.listQuotePlanBenefitsInclusions?.contents?.[0]?.value,
    );

    const rawValue =
      planForm.listQuotePlanBenefitsInclusions?.contents?.[0]?.value || '0';

    const numericValue = parseFloat(rawValue.replace(/[^\d.-]/g, ''));

    console.log('numericValue', numericValue);

    return numericValue;
  },
  set(newValue) {
    planForm.listQuotePlanBenefitsInclusions.contents[0].value =
      newValue.toString();
  },
});

const personalBelonginsValue = computed({
  get() {
    console.log(
      'personalBelonginsValue',
      planForm.listQuotePlanBenefitsInclusions?.contents?.[0]?.value,
    );

    const rawValue =
      planForm.listQuotePlanBenefitsInclusions?.contents?.[0]?.value || '0';

    const numericValue = parseFloat(rawValue.replace(/[^\d.-]/g, ''));

    console.log('numericValue', numericValue);

    return numericValue;
  },
  set(newValue) {
    planForm.listQuotePlanBenefitsInclusions.contents[0].value =
      newValue.toString();
  },
});

const readOnlyMode = reactive({
  isDisable: true,
});
onMounted(() => {
  readOnlyMode.isDisable = !can(permissionsEnum.All_QUOTES_VIEWONLY_ACCESS);
});

const onUpdatePlan = () => {
  console.log('onUpdatePlan', props.plan);
  // return false
  //   if (plan > planForm.actual_premium
  //     notification.error({
  //       title: 'Discounted Price must be lower than Actual Price',
  //       position: 'top',
  //     });
  //     return;
  //   }

  //   if (planForm.is_manual_update && planForm.insurer_quote_no == '') {
  //     showInsurerError.value = true;
  //     return;
  //   } else {
  //     showInsurerError.value = false;
  //   }

//   axios.post(
//     '/home-plan-manual-update-process',
//     {
//       plan: planForm,
//     },
//     {
//       preserveScroll: true,
//       preserveState: true,
//       onSuccess: response => {
//         console.log('response', response);
//         notification.success({
//           title: 'Plan updated successfully',
//           position: 'top',
//         });
//         emit('onLoadAvailablePlansData');
//       },
//       onError: error => {
//         console.log('error', error);
//         notification.error({
//           title: error[0],
//           position: 'top',
//         });
//       },
//     },
//   );

axios.post('/home-plan-manual-update-process', {
    plan: planForm, // Send your planForm data here
  }, {
    preserveScroll: true,
    preserveState: true
  })
  .then(response => {
    // Log the success response
    console.log('response', response);

    // Show success notification
    notification.success({
      title: 'Plan updated successfully',
      position: 'top',
    });

    // Emit event to load available plans
    emit('onLoadAvailablePlansData');
  })
  .catch(error => {
    // Log the error for debugging
    console.error('error', error);

    // Show error notification (handle server-side or network errors)
    notification.error({
      title: error.response?.data?.message || 'An error occurred',
      position: 'top',
    });
  });
};
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
              <dt class="font-medium">Provider Name</dt>
              <dd>{{ planForm.provider_name }}</dd>
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
              <dt class="mt-2">Building Value:</dt>
              <x-input v-model="buildingValue" size="sm" type="number" />
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="mt-2">Contents Value:</dt>
              <x-input v-model="contentValue" size="sm" type="number" />
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="mt-2">Personal Belongings Value:</dt>
              <x-input
                v-model="personalBelonginsValue"
                size="sm"
                type="number"
              />
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="mt-2">Excess:</dt>
              <x-input
                v-model="planForm.excess"
                :disabled="!planForm.is_manual_update"
                size="sm"
                type="number"
              />
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="mt-2">Discounted Price:</dt>
              <x-input
                v-model="planForm.discounted_premium"
                :disabled="!planForm.is_manual_update"
                size="sm"
                type="number"
              />
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="mt-2">Discounted Price:</dt>
              <x-select
                v-model="planForm.discounted_premium"
                placeholder="Select Option"
                :options="homeDiscountOptions"
                class="w-full"
              />
            </div>
            <!-- <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Provider Code</dt>
              <dd>{{ props.plan.providerCode }}</dd>
            </div> -->
          </dl>
          <br />
          <div class="flex justify-end">
            <x-button
              color="primary"
              size="sm"
              @click="onUpdatePlan"
              :loading="props.plan.processing"
              v-if="readOnlyMode.isDisable === true"
            >
              Update
            </x-button>
          </div>
        </TabPanel>

        <TabPanel>
          <div class="p-4">
            <x-table
              :headers="[
                { text: 'Member ', value: 'member' },
                { text: 'DOB', value: 'dob' },
                { text: 'Price', value: 'premium' },
              ]"
              :items="listQuotePlansMembers || []"
            >
              <template #item-member="{ item }">
                Traveler {{ item.index + 1 }}
              </template>
              <template #item-dob="{ item }">
                {{ dateFormat(item.dob) }}
              </template>
              <template #item-gender="{ item }">
                {{ item.premium }}
              </template>
            </x-table>
          </div>
        </TabPanel>

        <TabPanel>
          <div class="p-4">
            <table cellpadding="3" cellspacing="3" class="table-auto">
              <thead class="">
                <tr>
                  <th class="px-6 py-3" scope="col">Features & Benefits</th>
                  <th class="px-4 py-2"></th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="feature in props.plan.listQuotePlanBenefitsFeatures"
                  :key="feature.id"
                >
                  <td class="px-4 py-2">{{ feature.text }}</td>
                  <td class="px-4 py-2">{{ feature.value }}</td>
                </tr>
              </tbody>
            </table>
            <table cellpadding="3" cellspacing="3" class="table-auto">
              <thead>
                <tr>
                  <th class="px-4 py-2">Travel Inconvenience Cover</th>
                  <th class="px-4 py-2"></th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="feature in props.plan
                    .listQuotePlanBenefitstravelInconvenienceCover"
                  :key="feature.id"
                >
                  <td class="px-4 py-2">{{ feature.text }}</td>
                  <td class="px-4 py-2">{{ feature.value }}</td>
                </tr>
              </tbody>
            </table>

            <table cellpadding="3" cellspacing="3" class="table-auto">
              <thead>
                <tr>
                  <th class="px-4 py-2">Emergency Medical Cover</th>
                  <th class="px-4 py-2"></th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="feature in props.plan
                    .listQuotePlanBenefitsemergencyMedicalCover"
                  :key="feature.id"
                >
                  <td class="px-4 py-2">{{ feature.text }}</td>
                  <td class="px-4 py-2">{{ feature.value }}</td>
                </tr>
              </tbody>
            </table>

            <table cellpadding="3" cellspacing="3" class="table-auto">
              <thead>
                <tr>
                  <th class="px-4 py-2">Included in the plan</th>
                  <th class="px-4 py-2"></th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="feature in planForm.listQuotePlanBenefitsInclusions"
                  :key="feature.id"
                >
                  <td class="px-4 py-2">{{ feature.text }}</td>
                  <td class="px-4 py-2">{{ feature.value }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </TabPanel>

        <TabPanel>
          <div class="p-4">
            <table cellpadding="3" cellspacing="3" class="table-auto">
              <thead>
                <tr>
                  <!-- <th class="px-4 py-2">Exclusions</th> -->
                  <th class="px-4 py-2"></th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="feature in planForm.listQuotePlanBenefitsExclusions"
                  :key="feature.id"
                >
                  <td class="px-4 py-2">{{ feature.text }}</td>
                  <td class="px-4 py-2">{{ feature.value }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </TabPanel>
        <TabPanel>
          <div>
            <table cellpadding="3" cellspacing="3" class="table-auto">
              <thead>
                <tr>
                  <!-- <th class="px-4 py-2">COVID-19 Cover</th> -->
                  <th class="px-4 py-2"></th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="feature in props.plan.listQuotePlanBenefitsCovid19"
                  :key="feature.id"
                >
                  <td class="px-4 py-2">{{ feature.text }}</td>
                  <td class="px-4 py-2">{{ feature.value }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </TabPanel>
        <TabPanel>
          <div class="p-4">
            <table cellpadding="3" cellspacing="3" class="table-auto">
              <tbody>
                <tr
                  v-for="feature in props.plan
                    .listQuotePlanBenefitsPolicyDetails"
                  :key="feature.id"
                >
                  <td class="px-4 py-2">
                    <a
                      :href="feature.link"
                      target="_blank"
                      title="click to open"
                      >📃 {{ feature.text }}</a
                    >
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </TabPanel>
      </TabPanels>
    </TabGroup>
  </div>
</template>

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
  provider_name: props.plan.providerName || '',
  actual_premium: props.plan.actualPremium || 0,
  discounted_premium: props.plan.discountPremium || 0,
  premium_vat: props.vat ? props.vat : 0,
  excess: props.plan.excess || 0,
  is_disabled: props.plan.is_disabled || false,
  insurer_quote_no:
    props.plan.insurer_quote_no != null && props.plan.insurer_quote_no != ''
      ? props.plan.insurer_quote_no
      : '',
  is_manual_update: props.plan.is_manual_update || false,
  current_url: usePage().url || '',
  listQuotePlanBenefitsInclusions:
    props.plan.listQuotePlanBenefitsInclusions || [],
  listQuotePlanBenefitsExclusions:
    props.plan.listQuotePlanBenefitsExclusions || [],
  listQuotePlanBenefitsPolicyDetails:
    props.plan.listQuotePlanBenefitsPolicyDetails || [],
});

console.log('planForm', planForm);

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
      if (error.response && error.response.status === 422) {
        const errorData = error.response.data.errors;

        Object.keys(errorData).forEach(field => {
          errorData[field].forEach(errorMessage => {
            notification.error({
              title: 'Validation Error',
              message: errorMessage,
              position: 'top',
            });
          });
        });
      } else {
        notification.error({
          title: 'Error',
          message: 'An unexpected error occurred',
          position: 'top',
        });
      }
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
      planForm.listQuotePlanBenefitsInclusions?.personalBelongings?.[0]?.value,
    );

    const rawValue =
      planForm.listQuotePlanBenefitsInclusions?.personalBelongings?.[0]?.value || '0';

    const numericValue = parseFloat(rawValue.replace(/[^\d.-]/g, ''));

    console.log('numericValue', numericValue);

    return numericValue;
  },
  set(newValue) {
    planForm.listQuotePlanBenefitsInclusions.personalBelongings[0].value =
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

  axios
    .post(
      '/home-plan-manual-update-process',
      {
        plan: planForm, // Send your planForm data here
      },
      {
        preserveScroll: true,
        preserveState: true,
      },
    )
    .then(response => {
      console.log('response', response);

      notification.success({
        title: 'Plan updated successfully',
        position: 'top',
      });

      emit('onLoadAvailablePlansData');
    })
    .catch(error => {
      console.error('error', error);

      const errors = error.response?.data?.errors;

      if (errors) {
        Object.values(errors).forEach(messages => {
          messages.forEach(message => {
            notification.error({
              title: message,
              position: 'top',
            });
          });
        });
      } else {
        notification.error({
          title: error.response?.data?.message || 'An error occurred',
          position: 'top',
        });
      }
    });
};

const formattedCategories = computed(() => {
  const categories = props.plan?.listQuotePlanBenefitsInclusions || {};

  // Create a new object with formatted keys
  return Object.keys(categories).reduce((acc, key) => {
    acc[key] = camelCaseToSpacedText(key);
    return acc;
  }, {});
});

function camelCaseToSpacedText(camelCaseStr) {
  return camelCaseStr.replace(/([A-Z])/g, ' $1').trim();
}
</script>

<template>
  <div class="w-full">
    <TabGroup>
      <TabList
        class="grid grid-cols-[repeat(auto-fit,minmax(150px,1fr))] gap-2 rounded-xl bg-slate-100 p-1.5 w-full"
      >
        <Tab
          v-for="{ index, label } in tabs"
          as="template"
          :key="index"
          v-slot="{ selected }"
        >
          <button
            :class="[
              'rounded-lg px-3 py-2 text-sm font-medium text-gray-800 transition duration-200 ease-in-out uppercase',
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
        <!-- General Info Tab -->
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

        <!-- Inclusions Tab -->
        <TabPanel>
          <div class="p-4">
            <!-- Loop through the main keys (buildings, contents, personalBelongings) -->
            <div
              v-for="(items, category) in props.plan
                ?.listQuotePlanBenefitsInclusions"
              :key="category"
              class="mb-6"
            >
              <!-- Heading for each category (e.g., "Buildings", "Contents") -->
              <h6 class="font-bold capitalize mb-1">
                {{ formattedCategories[category] }}:
              </h6>

              <!-- Loop through each item in the current category (e.g., buildings[0], buildings[1], etc.) -->
              <div class="grid sm:grid-cols-2 gap-4">
                <div
                  v-for="(item, index) in items"
                  :key="index"
                  class="space-y-1"
                >
                  <!-- Display the text field -->
                  <div class="font-medium">{{ item.text }}</div>

                  <!-- Display the description field with grey text -->
                  <div class="text-gray-500">{{ item.description }}</div>
                </div>
              </div>
            </div>
          </div>
        </TabPanel>

        <!-- Additonal Covers Tab -->
        <TabPanel>
          <div class="p-4">
            <div class="grid sm:grid-cols-2 gap-4">
              <!-- Loop through the listQuotePlanBenefitsAditionalCovers array -->
              <div
                v-for="(cover, index) in props.plan
                  ?.listQuotePlanBenefitsAditionalCovers"
                :key="index"
                class="space-y-2"
              >
                <!-- Display the text field -->
                <div class="font-medium">{{ cover.text }}</div>

                <!-- Display the description field with grey text -->
                <div class="text-gray-500">{{ cover.description }}</div>
              </div>
            </div>
          </div>
        </TabPanel>

        <!-- Exclusions Tab -->
        <TabPanel>
          <div class="p-4">
            <div class="grid sm:grid-cols-2 gap-4">
              <!-- Loop through the listQuotePlanBenefitsExclusions array -->
              <div
                v-for="(exclusion, index) in props.plan
                  ?.listQuotePlanBenefitsExclusions"
                :key="index"
                class="space-y-2"
              >
                <!-- Display the text field -->
                <div class="font-medium">{{ exclusion.text }} ABC</div>

                <!-- Display the description field with grey text -->
                <div class="text-gray-500">{{ exclusion.description }}</div>
              </div>
            </div>
          </div>
        </TabPanel>

        <!-- Policy Details Tab -->
        <TabPanel>
          <dl class="grid md:grid-cols-2 gap-5 p-4">
            <div
              v-for="data in props.plan?.listQuotePlanBenefitsPolicyDetails ||
              []"
              :key="data"
            >
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

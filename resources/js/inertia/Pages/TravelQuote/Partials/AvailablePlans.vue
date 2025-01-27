<script setup>
const notification = useNotifications('toast');

const props = defineProps({
  plan: Object,
  quote: Object,
  access: Object,
  quoteType: String,
  extraDetails: {
    type: Object,
    default: {},
  },
});

const emit = defineEmits(['onLoadAvailablePlansData']);

const listQuotePlansMembers = computed(() => {
  return props.plan.listQuotePlansMembers.map((item, index) => {
    return { ...item, index };
  });
});

const dateFormat = date => useDateFormat(date, 'DD-MM-YYYY').value;
const tabs = ref([
  { index: 0, label: 'General Info' },
  { index: 1, label: 'Addons' },
  { index: 2, label: 'Members' },
  { index: 3, label: 'Inclusions' },
  { index: 4, label: 'Exclusions' },
  { index: 5, label: 'COVID-19 Cover' },
  { index: 6, label: 'Policy Details' },
]);

const planForm = useForm({
  travel_quote_uuid: props.quote.uuid,
  travel_plan_id: props.plan.id,
  actual_premium: props.plan.actualPremium,
  discounted_premium: props.plan.discountPremium,
  premium_vat: props.vat ? props.vat : 0,
  addons: props.plan.addons,
  is_create: 0,
  current_url: usePage().url,
});

const totalPremiumWithVat = computed(() => {
  let addonVat = 0;
  props.plan.addons.forEach(addon => {
    addon.addonOptions.forEach(option => {
      if (option.isSelected && option.price != 0) {
        addonVat += parseInt(option.price) + option.vat;
      }
    });
  });
  return props.plan.discountPremium + addonVat + props.plan.vat;
});

const validateAddons = addons => {
  const excludedAddons = ['myAlfred', 'fastTrackClaim'];
  for (let addon of addons) {
    for (let option of addon.addonOptions) {
      if (
        option.isSelected === true &&
        parseInt(option.price ?? 0) === 0 &&
        !excludedAddons.includes(addon.code)
      ) {
        notification.error({
          title: 'Addon price must be greater than 0',
          position: 'top',
        });
        return false;
      }
    }
  }
  return true;
};

const onUpdatePlan = () => {
  let addons = [];
  let tempAddons = planForm.addons;

  //Validate addons
  if (!validateAddons(tempAddons)) {
    return;
  }

  tempAddons.forEach(addon => {
    addon.addonOptions.forEach(option => {
      addons.push({
        addonId: addon.id,
        addonOptionId: option.id,
        price: parseInt(option.price ?? 0),
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
    .post('/travel-plan-manual-update-process', {
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => {
        notification.success({
          title: 'Plan has been updated',
          position: 'top',
        });
        emit('onLoadAvailablePlansData', {
          plan: props.plan,
          quoteType: props.quoteType,
          extraDetails: props.extraDetails,
        });
      },
      onError: errors => {
        Object.keys(errors).forEach(function (key) {
          notification.error({
            title: errors[key],
            position: 'top',
          });
        });
      },
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
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Provider Code</dt>
              <dd>{{ props.plan.providerCode }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Provider Name</dt>
              <dd>{{ props.plan.providerName }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Travel Type</dt>
              <dd>{{ props.plan.travelType }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Actual Price</dt>
              <dd>{{ props.plan.actualPremium }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Discount Price</dt>
              <dd>{{ props.plan.discountPremium }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Insurer Quote Number</dt>
              <dd>{{ props.plan.insurerQuoteNo }}</dd>
            </div>
          </dl>
        </TabPanel>

        <TabPanel>
          <div class="p-4">
            <template v-for="addon in planForm.addons" :key="addon">
              <template v-for="option in addon.addonOptions" :key="option">
                <div class="flex my-2">
                  <span class="w-60">{{ addon.text }}</span>
                  <span class="w-60">{{ option.value }}</span>
                  <x-input
                    class="w-20 mr-10"
                    :value="option.price"
                    :disabled="!option.isSelected"
                    size="sm"
                    v-model="option.price"
                    type="number"
                  />
                  <x-toggle
                    v-model="option.isSelected"
                    color="success"
                    class="mt-2"
                  />
                </div>
              </template>
            </template>
            <x-divider class="mb-3 mt-3" />
            <div class="grid sm:grid-cols-4">
              <dt class="font-bold">Total Premium with VAT:</dt>
              <dd>AED: {{ totalPremiumWithVat.toFixed(2) }}</dd>
            </div>
            <div class="flex justify-end">
              <x-button
                v-if="
                  access.travelManagerCanEdit || access.travelAdvisorCanEdit
                "
                color="primary"
                class="mt-5"
                size="sm"
                :disabled="!planForm.isDirty"
                @click.prevent="onUpdatePlan"
                :loading="planForm.processing"
              >
                Update
              </x-button>
            </div>
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
                  v-for="feature in props.plan.listQuotePlanBenefitsInclusions"
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
                  v-for="feature in props.plan.listQuotePlanBenefitsExclusions"
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

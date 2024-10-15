<script setup>
const props = defineProps({
  plan: Object,
});

console.log('props.plan', props.plan);

const listQuotePlansMembers = computed(() => {
  return props.plan.listQuotePlansMembers.map((item, index) => {
    return { ...item, index };
  });
});

const dateFormat = date => useDateFormat(date, 'DD-MM-YYYY').value;
const tabs = ref([
  { index: 0, label: 'General Info' },
  { index: 1, label: 'Members' },
  { index: 2, label: 'Inclusions' },
  { index: 3, label: 'Exclusions' },
  { index: 4, label: 'COVID-19 Cover' },
  { index: 5, label: 'Policy Details' },
]);
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
          </dl>
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

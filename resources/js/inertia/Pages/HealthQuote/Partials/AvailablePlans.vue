<script setup>
import { ref } from 'vue';
import { TabGroup, TabList, Tab, TabPanels, TabPanel } from '@headlessui/vue';
import { useDateFormat } from '@vueuse/shared';

const props = defineProps({
  plan: Object,
  genders: Object,
});

const genderText = v => {
  return props.genders[v];
};

const dateFormat = date => useDateFormat(date, 'DD-MM-YYYY').value;
const tabs = ref([
  { index: 0, label: 'General Info' },
  { index: 1, label: 'Members' },
  { index: 2, label: 'In Patient' },
  { index: 3, label: 'Out Patient' },
  { index: 4, label: 'Co-pay/Co-insurance' },
  { index: 5, label: 'Region coverage & Network list' },
  { index: 6, label: 'Maternity cover' },
  { index: 7, label: 'Exclusions' },
  { index: 8, label: 'Policy Detail' },
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
        <TabPanel>
          <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4 p-4">
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Provider Code</dt>
              <dd>{{ props.plan.code }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Provider Name</dt>
              <dd>{{ props.plan.providerName }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Actual Premium</dt>
              <dd>{{ props.plan.actualPremium }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Discount Premium</dt>
              <dd>{{ props.plan.discountPremium }}</dd>
            </div>
          </dl>
        </TabPanel>

        <TabPanel>
          <div class="p-4">
            <x-table
              :headers="[
                { text: 'Relationship', value: 'memberCategoryText' },
                { text: 'DOB', value: 'dob' },
                { text: 'Gender', value: 'gender' },
                { text: 'Premium', value: 'premium' },
              ]"
              :items="props.plan.memberPremiumBreakdown || []"
            >
              <template #item-dob="{ item }">
                {{ dateFormat(item.dob) }}
              </template>
              <template #item-gender="{ item }">
                {{ genderText(item.gender) }}
              </template>
              <template #item-premium="{ item }">
                <x-input :value="item.premium" :disabled="true" size="sm" />
              </template>
            </x-table>
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
              v-for="data in props.plan.benefits.coInsurance || []"
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
        </TabPanel>
      </TabPanels>
    </TabGroup>
  </div>
</template>

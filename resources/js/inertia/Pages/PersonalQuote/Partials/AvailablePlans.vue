<script setup>
const props = defineProps({
  plan: Object,
  genders: Object,
});

const notification = useToast();

const genderText = v => {
  return props.genders[v];
};

const ipmiBenefits = reactive({
  region: '',
  insurance: '',
  payment: '',
  network: '',
  healthCare: false,
  motherBaby: false,
});

const hidePlan = ref(props.plan.isHidden),
  isManual = ref(false),
  memberFormLoader = ref(false),
  newPremiums = ref([]);

const toggleLoader = ref(false);

const canUpdate = computed(() => {
  return props.plan.providerCode == 'CIG' || props.plan.providerCode == 'BUP';
});

const dateFormat = date => useDateFormat(date, 'DD-MM-YYYY').value;
const tabs = ref([
  { index: 0, label: 'General Info' },
  { index: 1, label: 'Addons' },
  { index: 2, label: 'Inclusions' },
  { index: 3, label: 'Exclusions' },
  { index: 4, label: 'Road Side Assistance' },
  { index: 5, label: 'Policy Detail' },
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

const onTogglePlans = () => {
  toggleLoader.value = true;

  axios
    .post('/quotes/car/manual-plan-toggle', {
      modelType: 'Car',
      planIds: [props.plan.id],
      quote_uuid: usePage().props.record.uuid,
      toggle: hidePlan.value,
    })
    .then(response => {
      notification.success({
        title: 'Plan has been updated',
        position: 'top',
      });
      router.reload({
        preserveScroll: true,
        preserveState: true,
      });
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
</script>

<template>
  <div class="w-full">
    <TabGroup>
      <TabList class="flex flex-row flex-wrap gap-2 rounded-xl bg-slate-100 p-1.5 w-full">
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
            <div class="md:col-span-2 text-right select-none border-b pb-2 d-flex justify-between">
                <x-toggle
                v-model="hidePlan"
                color="error"
                label="Hide Plan"
                @change="onTogglePlans"
                :loading="toggleLoader"
              />
              <x-toggle
                v-model="hidePlan"
                color="error"
                label="Manual"
                @change="onTogglePlans"
                :loading="toggleLoader"
              />
            </div>
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
              <dd>{{ props.plan.actualPremium }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Basmah</dt>
              <dd>{{ props.plan.basmah }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Policy Fee</dt>
              <dd>{{ props.plan.policyFee }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Total (exclusive of VAT)</dt>
              <dd>
                {{
                  props.plan.actualPremium +
                  (props.plan.basmah || 0) +
                  (props.plan.policyFee || 0)
                }}
              </dd>
            </div>
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
              v-for="data in props.plan.benefits.maternityCover || []"
              :key="data.code"
            >
              <dt class="font-medium mb-1">{{ data.text }}</dt>
              <dd>{{ data.value }}</dd>
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
</template>

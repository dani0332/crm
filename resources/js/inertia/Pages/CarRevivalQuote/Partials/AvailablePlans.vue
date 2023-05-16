<script setup>
const props = defineProps({
    plan: Object,
    genders: Object,
    quote: Object
});

const notification = useToast();

const genderText = v => {
  return props.genders[v];
};

const hidePlan = ref(props.plan.isHidden),
  isManual = ref(false),
  memberFormLoader = ref(false),
  newPremiums = ref([]),
  toggleLoader = ref(false),
  ancillaryExcess = ref(false),
  insurerAvailableTrims = ref(false);

const ipmiBenefits = reactive({
  region: '',
  insurance: '',
  payment: '',
  network: '',
  healthCare: false,
  motherBaby: false,
});

const insuranceAvailableTrim = computed(() => {
  return props.plan.insurerAvailableTrims.map(insuranceAvailableTrim => ({
    value: insuranceAvailableTrim.admeId,
    label: insuranceAvailableTrim.description,
  }));
});

const canUpdate = computed(() => {
  return props.plan.providerCode == 'CIG' || props.plan.providerCode == 'BUP';
});

const repairType = computed(() => {
  return props.plan.repairType === 'COMP'
    ? 'NON-AGENCY'
    : props.plan.repairType;
});

const AncillaryExcessOptions = computed(() => {
  return Array.from({ length: 20 }, (_, i) => {
    return {
      value: i,
      label: i + '%',
    };
  });
});

const planFeatures = computed(() => {
  return props.plan.benefits.feature;
});

const planAddons = computed(() => {
  return props.plan.addons;
});

const planInclusion = computed(() => {
  return props.plan.benefits.inclusion;
});

const planExclusion = computed(() => {
  return props.plan.benefits.exclusion;
});

const roadSideAssistance = computed(() => {
  return props.plan.benefits.roadSideAssistance;
});

const policyWordings = computed(() => {
  return props.plan.policyWordings;
});

console.log(props.plan);

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
    .post('/car-plan-manual-update-process', memberData)
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
            quote_uuid: props.quote.uuid,
            toggle: hidePlan.value,
        })
        .then(response => {
            notification.success({
                title: 'Plan has been updated',
                position: 'top',
            });
            router.reload({
                preserveScroll: true,
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
            <div
              class="md:col-span-2 gap-2 text-right select-none border-b pb-2"
            >
              <x-toggle
                v-model="hidePlan"
                color="error"
                label="Hide Plan"
                @change="onTogglePlans"
                :loading="toggleLoader"
              />
              <x-toggle
                v-model="isManual"
                color="success"
                label="Manual"
                :loading="toggleLoader"
              />
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Provider Name</dt>
              <dd>{{ props.plan.providerName }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Repair Type</dt>
              <dd>{{ repairType }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Insurer Quote No</dt>
              <dd>{{ props.plan.insurerQuoteNo }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Actual Premium</dt>
              <dd>{{ props.plan.actualPremium }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Discounted Premium</dt>
              <dd>
                <x-input v-model="props.plan.discountPremium" class="w-full" />
              </dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Car value</dt>
              <dd>
                <x-input
                  v-model="props.plan.carValue"
                  class="w-full"
                  disabled:true
                />
              </dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Excess</dt>
              <dd>{{ props.plan.excess }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Ancillary Excess</dt>
              <dd>
                <x-select
                  v-model="props.plan.ancillaryExcess"
                  placeholder="Select Option"
                  :options="AncillaryExcessOptions"
                  class="w-full"
                />
              </dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Car Trim</dt>
              <dd>
                <x-select
                  v-model="props.plan.insurerAvailableTrims"
                  placeholder="Select Option"
                  :options="insuranceAvailableTrim"
                  class="w-full"
                />
              </dd>
            </div>
          </dl>
          <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4 p-4">
            <div class="mt-6">
              <h3 class="font-semibold text-primary-800">Features</h3>
              <x-divider class="mb-4 mt-1" />
            </div>
            <div class="grid sm:grid-cols-2" v-for="feature in planFeatures">
              <dt class="font-medium">{{ feature.text }}</dt>
              <dd>{{ feature.value }}</dd>
            </div>
          </dl>
          <dl>
            <x-divider class="mb-4 mt-1" />
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Total Premium with VAT</dt>
              <dd>{{ props.plan.basmah }}</dd>
            </div>
          </dl>

          <!--                    @if(! auth()->user()->hasRole(RolesEnum::PA))-->
          <x-button
            color="primary"
            class="ml-2"
            size="sm"
            outlined
            :loading="memberFormLoader"
          >
            Update
          </x-button>
        </TabPanel>

        <TabPanel>
          <div class="grid gap-x-6 gap-y-4 p-4">
            <div v-for="addons in planAddons">
              <div
                class="flex gap-5 items-center justify-between font-medium"
                v-for="addonOptions in addons.carAddonOption"
              >
                <div class="w-1/4">{{ addons.text }}</div>
                <div class="w-1/4">{{ addonOptions.value }}</div>
                <div class="w-1/4">
                  <x-input
                    v-model="addonOptions.price"
                    class="w-full"
                    readonly
                  />
                </div>
                <div class="w-1/5 text-center">
                  <x-toggle
                    v-model="addonOptions.isSelected"
                    color="success"
                    :loading="toggleLoader"
                  />
                </div>
              </div>
            </div>

            <!--  @if(! auth()->user()->hasRole(RolesEnum::PA))-->
            <div class="flex justify-end mr-4">
              <x-button
                color="primary"
                size="sm"
                outlined
                :loading="memberFormLoader"
              >
                Update
              </x-button>
            </div>
          </div>
        </TabPanel>

        <TabPanel>
          <div class="grid md:grid-cols-2 gap-x-6 gap-y-4 p-4">
            <div class="row" v-for="inclusion in planInclusion">
              <div class="col-6">{{ inclusion.text }}</div>
              <div class="col-6">{{ inclusion.value }}</div>
            </div>
          </div>
        </TabPanel>

        <TabPanel>
          <div class="grid md:grid-cols-2 gap-x-6 gap-y-4 p-4">
            <div class="row" v-for="exclusion in planExclusion">
              <div class="col-6">{{ exclusion.text }}</div>
              <div class="col-6">{{ exclusion.value }}</div>
            </div>
          </div>
        </TabPanel>

        <TabPanel>
          <div class="grid md:grid-cols-2 gap-x-6 gap-y-4 p-4">
            <div class="row" v-for="roadSideAssist in roadSideAssistance">
              <div class="col-6">{{ roadSideAssist.text }}</div>
              <div class="col-6">{{ roadSideAssist.value }}</div>
            </div>
          </div>
        </TabPanel>

        <TabPanel>
          <div class="grid md:grid-cols-2 gap-x-6 gap-y-4 p-4">
            <x-link
              v-for="policyWording in policyWordings || []"
              :href="policyWording.link"
              target="_blank"
              title="Open File"
              external
            >
              {{ policyWording.text }}
            </x-link>
          </div>
        </TabPanel>
      </TabPanels>
    </TabGroup>
  </div>
</template>

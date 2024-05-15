<script setup>
const props = defineProps({
  plan: Object,
  quote: Object,
});

const notification = useToast();
const page = usePage();

const hasRole = role => useHasRole(role);

const fixedValue = number => {
  if (number == Math.floor(number)) {
    return number;
  } else {
    return number.toFixed(2);
  }
};

const hidePlan = ref(props.plan.isDisabled),
  isManual = ref(false),
  planUpdateLoader = ref(false),
  newPremiums = ref([]),
  toggleLoader = ref(false),
  ancillaryExcess = ref(false),
  insurerAvailableTrims = ref(false);

const addonFormData = ref([]);

const updatePlanForm = reactive({
  car_plan_id: props.plan.id,
  car_quote_uuid: page.props.quote.uuid,
  is_create: ref(false),
  current_url: ref(null),
  is_manual_update: props.plan.isManualUpdate,
  insurer_quote_no: props.plan.insurerQuoteNo || null,
  actual_premium: props.plan.actualPremium || null,
  discounted_premium: props.plan.discountPremium || null,
  premium_vat: props.plan.vat || null,
  car_value: props.plan.carValue || null,
  excess: props.plan.excess || null,
  ancillary_excess: props.plan.ancillaryExcess || null,
  insurerTrim: props.plan.insurerTrimId || null,
});

const setAddons = (parentAddon, childAddonsOption) => {
  addonFormData.value.push({
    addonId: parentAddon.id,
    addonOptionId: childAddonsOption.id,
    price: childAddonsOption.price,
    vat: childAddonsOption.vat,
    isSelected: childAddonsOption.isSelected,
  });
};

const onAddonsUpdate = (event, addonId, addonOptionId) => {
  const { isSelected, price } = event;
  const addonIndex = addonFormData.value.findIndex(
    addon => addon.addonId == addonId && addon.addonOptionId == addonOptionId,
  );

  if (addonIndex > -1) {
    if (isSelected !== undefined)
      addonFormData.value[addonIndex].isSelected = isSelected;
    if (price !== undefined) addonFormData.value[addonIndex].price = price;
  }
};

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

const dateFormat = date => useDateFormat(date, 'DD-MM-YYYY').value;

const tabs = ref([
  { index: 0, label: 'General Info' },
  { index: 1, label: 'Addons' },
  { index: 2, label: 'Inclusions' },
  { index: 3, label: 'Exclusions' },
  { index: 4, label: 'Road Side Assistance' },
  { index: 5, label: 'Policy Detail' },
]);

const onPlanUpdate = planDetails => {
  planUpdateLoader.value = true;

  const currentPlanURL =
    page.props.baseUrl +
    '/quotes/car/' +
    planDetails.car_quote_uuid +
    '/plan_details/' +
    planDetails.car_plan_id;

  const uniqueAddons = addonFormData.value.filter(
    (addon, index, self) =>
      index ===
      self.findIndex(
        t =>
          t.addonId === addon.addonId &&
          t.addonOptionId === addon.addonOptionId,
      ),
  );

  const updatePlanData = {
    ...planDetails,
    current_url: currentPlanURL,
    addons: uniqueAddons,
  };

  axios
    .post('/car-plan-manual-update-process', updatePlanData)
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
      planUpdateLoader.value = false;
    });
};

const onTogglePlans = () => {
  toggleLoader.value = true;
  axios
    .post('/quotes/car/manual-plan-toggle', {
      modelType: 'Car',
      planIds: [props.plan.id],
      car_quote_uuid: page.props.quote.uuid,
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
          <div class="grid md:grid-cols-2 gap-x-6 gap-y-4 p-4">
            <div
              class="md:col-span-2 flex gap-2 justify-end select-none border-b pb-2"
            >
              <x-toggle
                v-model="hidePlan"
                color="error"
                label="Hide Plan"
                @change="onTogglePlans"
                :loading="toggleLoader"
              />
              <x-toggle
                v-model="updatePlanForm.is_manual_update"
                color="success"
                label="Manual"
              />
            </div>

            <div class="grid sm:grid-cols-2">
              <div class="font-medium">Provider Name</div>
              <div>{{ props.plan.providerName }}</div>
            </div>
            <div class="grid sm:grid-cols-2">
              <div class="font-medium">Repair Type</div>
              <div>{{ repairType }}</div>
            </div>
            <div class="grid sm:grid-cols-2">
              <div class="font-medium">Insurer Quote No</div>
              <div>
                <x-input
                  v-model="updatePlanForm.insurer_quote_no"
                  class="w-full"
                />
              </div>
            </div>
            <div class="grid sm:grid-cols-2">
              <div class="font-medium">Actual Premium</div>
              <div>
                <x-input
                  v-model="updatePlanForm.actual_premium"
                  class="w-full"
                />
              </div>
            </div>
            <div class="grid sm:grid-cols-2">
              <div class="font-medium">Discounted Premium</div>
              <div>
                <x-input
                  v-model="updatePlanForm.discounted_premium"
                  class="w-full"
                />
              </div>
            </div>
            <div class="grid sm:grid-cols-2">
              <div class="font-medium">Car value</div>
              <div>
                <x-input
                  v-model="updatePlanForm.car_value"
                  class="w-full"
                  disabled:true
                />
              </div>
            </div>
            <div class="grid sm:grid-cols-2">
              <div class="font-medium">Excess</div>
              <div>
                <x-input v-model="updatePlanForm.excess" class="w-full" />
              </div>
            </div>
            <div class="grid sm:grid-cols-2">
              <div class="font-medium">Ancillary Excess</div>
              <div>
                <x-select
                  v-model="updatePlanForm.ancillary_excess"
                  placeholder="Select Option"
                  :options="AncillaryExcessOptions"
                  class="w-full"
                />
              </div>
            </div>
            <div class="grid sm:grid-cols-2">
              <div class="font-medium">Car Trim</div>
              <div>
                <x-select
                  v-model="updatePlanForm.insurerTrimId"
                  placeholder="Select Option"
                  :options="insuranceAvailableTrim"
                  class="w-full"
                />
              </div>
            </div>

            <div class="md:col-span-2">
              <h3 class="font-semibold text-primary-800">Features</h3>
              <x-divider class="mt-1" />
            </div>

            <div class="grid sm:grid-cols-2" v-for="feature in planFeatures">
              <div class="font-medium">{{ feature.text }}</div>
              <div>{{ feature.value }}</div>
            </div>

            <x-divider class="md:col-span-2" />

            <div class="md:col-span-2 grid sm:grid-cols-2 font-bold">
              <div>Total Premium with VAT</div>
              <div class="lining-nums">
                {{
                  fixedValue(
                    props.plan.actualPremium +
                      (props.plan.vat || 0) +
                      (props.plan.basmah || 0),
                  )
                }}
              </div>
            </div>

            <div class="md:col-span-2 flex justify-end">
              <x-button
                color="primary"
                size="sm"
                outlined
                :loading="planUpdateLoader"
                @click.prevent="onPlanUpdate(updatePlanForm)"
              >
                Update
              </x-button>
            </div>
          </div>
        </TabPanel>

        <TabPanel>
          <div class="grid gap-x-6 gap-y-4 p-4">
            <div v-for="(addons, index) in planAddons" :key="index">
              <div
                v-for="addonOptions in addons.carAddonOption"
                :key="addonOptions.id"
                class="flex gap-5 items-center justify-between font-medium"
              >
                <input type="hidden" :value="setAddons(addons, addonOptions)" />
                <div class="w-1/4">{{ addons.text }}</div>
                <div class="w-1/4">{{ addonOptions.value }}</div>
                <div class="w-1/4">
                  <x-input
                    :value="addonOptions.price"
                    :disabled="false"
                    @update:modelValue="
                      onAddonsUpdate(
                        { price: $event },
                        addons.id,
                        addonOptions.id,
                      )
                    "
                  />
                </div>
                <div class="w-1/5 text-center">
                  <x-toggle
                    :modelValue="addonFormData[index].isSelected"
                    color="success"
                    @update:modelValue="
                      onAddonsUpdate(
                        { isSelected: $event },
                        addons.id,
                        addonOptions.id,
                      )
                    "
                  />
                </div>
              </div>
            </div>

            <div class="flex justify-end">
              <x-button
                v-if="hasRole(page.props.rolesEnum.PA)"
                color="primary"
                size="sm"
                outlined
                @click.prevent="onPlanUpdate(updatePlanForm)"
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

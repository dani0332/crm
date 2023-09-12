<script setup>
import { computed } from "vue";

const props = defineProps({
  plan: Object,
  genders: Object,
  record: Object,
  access: Object,
  hidden: Boolean,
  notAdvisorAndManagerAndPA: Boolean,
  isPlanUpdateActive: Boolean,
  totalSelectedAddonsPriceWithVat: Number
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

const ancillaryExcessOptions = computed(() => {
  let arr = [];
  for (let i = 0; i <= 20; i++) {
    arr.push({ value: i, label: `${i}%` });
  }
  return arr;
});

const totalPremiumWithVat = computed(() => {
  return props.plan.discountPremium + props.plan.vat + props.totalSelectedAddonsPriceWithVat;
})

const insurerAvailableTrimsOptions = computed(() => {
  if (!Array.isArray(props.plan.insurerAvailableTrims)) return [];

  return props.plan.insurerAvailableTrims.map(ins => {
    return { value: ins.admeId, label: ins.description }
  })
})

const hidePlan = ref(props.plan.isHidden),
  isManual = ref(false),
  memberFormLoader = ref(false),
  newPremiums = ref([]);

const planAddons = ref(props.plan.addons);

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

const insurerQuoteNoValue = ref(props.plan.insurerQuoteNo);
const actualPremiumValue = ref(props.plan.actualPremium);
const discountPremium = ref(props.plan.discountPremium);
const carValue = ref(props.record.car_value);
const excessValue = ref(props.plan.excess || 0);
const ancillaryExcessValue = ref(props.plan.ancillaryExcess);
const insurerTrimIdValue = ref(props.plan.insurerTrimId);

const onUpdatePlan = () => {	
    axios	
    .post('/car-plan-manual-update-process', {	
      car_quote_uuid: usePage().props.record.uuid,	
      car_plan_id: props.plan.id,	
      actual_premium: actualPremiumValue.value,	
      discounted_premium: discountPremium.value,	
      premium_vat: props.vat ? props.vat : 0 ,	
      car_value:carValue.value,	
      excess: excessValue.value,	
      is_disabled:hidePlan.value,	
      is_create:0,	
      addons:'',	
      insurerTrim: insurerTrimIdValue.value,	
      insurer_quote_no: insurerQuoteNoValue.value,	
      is_manual_update: isManual.value,	
      ancillary_excess: ancillaryExcessValue.value,	
    })	
    .then(response => {	
      notification.success({	
        title: response.data,	
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
    });	
}
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
            <div class="grid sm:grid-cols-2 mb-3">
              <x-toggle
                v-model="hidePlan"
                color="error"
                label="Hide Plan?"
                @change="onTogglePlans"
                :loading="toggleLoader"
              />
            </div>
            <div class="grid sm:grid-cols-2 mb-3">
              <x-toggle
                v-model="isManual"
                color="error"
                label="Manual"
                :disabled="plan.isManualUpdate"
                :loading="toggleLoader"
              />
          </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Provider Name</dt>
              <dd>{{ props.plan.providerName }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Repair Type:</dt>
              <dd>{{ props.plan.repairType }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium mt-2">Insurer Quote No.:</dt>
              <x-input
                :value="insurerQuoteNoValue"
                v-model = "insurerQuoteNoValue"
                :disabled="!isManual"
                size="sm"
              />
              <!-- <dd>{{ props.plan.insurerQuoteNo }}</dd> -->
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium mt-2">Actual Premium:</dt>
              <x-input
                :value="actualPremiumValue"
                v-model = "actualPremiumValue"
                :disabled="!isManual"
                size="sm"
              />
              <!-- <dd>{{ props.plan.actualPremium }}</dd> -->
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium mt-2">Discounted Premium:	</dt>
              <x-input
                :value="discountPremium"
                v-model = "discountPremium"
                size="sm"
              />
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium mt-2">Car value:</dt>
              <x-input
                :value="carValue"
                v-model = "carValue"
                :helper="isManual ? `Min: AED ${props.plan.carValueLowerLimit} - Max: AED ${props.plan.carValueUpperLimit}` : ''"              
                size="sm"
              />
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium mt-2">Excess:</dt>
              <x-input
                :value="excessValue"
                v-model = "excessValue"
                :disabled="!isManual"
                type="number"
                size="sm"
              />
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium mt-2">Ancillary Excess:</dt>
              <x-select
                v-model="ancillaryExcessValue"
                placeholder="Select Option"
                :options="ancillaryExcessOptions"
                class="w-full"
              />
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Car Trim</dt>
              <x-select
                v-model="insurerTrimIdValue"
                placeholder="Select Option"
                :options="insurerAvailableTrimsOptions"                
                class="w-full"
              />
            </div>
            <div class="grid sm:grid-cols-2">
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Features:</dt>
            </div>
            <div class="grid sm:grid-cols-2" />
            <template v-for="feature in plan.benefits.feature" :key="feature">
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">{{ feature?.text }}</dt>
                <dd>{{ feature.value }}</dd>
              </div>
              <div class="grid sm:grid-cols-2" />
            </template>            
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Total Premium with VAT:</dt>
              <dd>AED: {{ totalPremiumWithVat.toFixed(2) }}</dd>
            </div>
          </dl>

          <div class="flex justify-end">
              <span class="font-medium text-end">Created Date:</span>
              <span>{{ props.record.created_at }}</span>
          </div>
          <div class="flex justify-end">
              <span class="font-medium text-end">Updated At:</span>
              <span>{{ props.record.updated_at }}</span>
          </div><br />

          <div class="flex justify-end">
            <x-button 
              v-if="access.carManagerCanEdit || access.carAdvisorCanEdit || notAdvisorAndManagerAndPA"
              color="primary"
              size="sm"
              :disabled="isPlanUpdateActive"
              @click = "onUpdatePlan"
            >
              Update
            </x-button>           
          </div>
        </TabPanel>

        <TabPanel>
          <div class="p-4">
            <template v-for="addon in planAddons" :key="addon">
              <template v-for="option in addon.carAddonOption" :key="option">
                <div class="flex my-2">
                  <span class="w-60">{{ addon.text }}</span>
                  <span class="w-60">{{ option.value }}</span>
                  <x-input class="w-20 mr-10" type="text" :value="option.price" :disabled="!isManual" size="sm" />
                  <x-toggle
                    v-model="option.isSelected"
                    :disabled="!isManual"
                    color="error"
                    class="mt-2"
                  />
                </div>                  
              </template>
            </template>
            <div class="flex justify-end">
              <x-button 
                v-if="access.carManagerCanEdit || access.carAdvisorCanEdit || notAdvisorAndManagerAndPA"
                color="primary"
                class="mt-5"
                size="sm"
                :disabled="isPlanUpdateActive"
                @click.prevent="onUpdatePlan"
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
            <div
              v-for="data in props.plan.policyWordings || []"
              :key="data"
            >
              <a :href="data.link" class="font-medium mb-1">{{ data.text }}</a>
            </div>
          </dl>
        </TabPanel>
      </TabPanels>
    </TabGroup>
  </div>
</template>

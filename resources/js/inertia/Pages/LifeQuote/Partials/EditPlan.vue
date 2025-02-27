<script setup>
const props = defineProps({
  uuid: String,
  insuranceProviders: Array,
  plans: Array,
  currencies: Array,
  lifeRiders: Array,
  selectedPlan: Object,
});

const { isRequired } = useRules();

const shown = computed({
  get: () => props.modelValue,
  set: value => emit('update:modelValue', value),
});

let riders = props.lifeRiders.map(rider => ({
    riderId: rider.id,
    active: 0,
    price: 0,
    coverValue: 0,
    text: rider.text,
}));

const showInsurerError = ref(false);
const showGetQuoteBtn = ref(false);

const ridersData = ref(riders);

const page = usePage();

const emit = defineEmits(['success', 'error']);

const dateFormat = date => useDateFormat(date, 'DD-MM-YYYY').value;

const active = ref(false);

const lifeCoverToggled = true;

const paymentTerms = [
  { value: 1, label: 'Monthly' },
  { value: 3, label: 'Quarterly' },
  { value: 6, label: 'Semi-Annually' },
  { value: 12, label: 'Annually' },
];

const options = reactive({
  providerPlans: [],
  loading: false,
});

const availableInsuranceProviders = computed(() => {
  if(editForm.isUW) {return props.insuranceProviders;}
  return props.insuranceProviders.filter(item => {
    return !props.plans.some(plan => plan.providerId === item.id);
  });
});

const editForm = reactive({
  providerId: props?.selectedPlan?.providerId ?? null,
  planId: props?.selectedPlan?.planId ?? null,
  loading: false,
  isUW: props.selectedPlan?.isUW ?? 0,
  currency: props.selectedPlan.currency,
  paymentTerm: props.selectedPlan?.paymentTerm ?? null,
  sumAssured: props.selectedPlan.sumInsured ?? 0, 
  policyTerm: props.selectedPlan.policyTerm,
  actualPremium: props.selectedPlan.actualPremium ?? 0,
  insurerQuoteNo: props.selectedPlan.insurerQuoteNo ?? null,
  isVariant: false,
  update: false,
  isDisabled:props.selectedPlan.isDisabled,
  loading: false,
  isManualPlan: props.selectedPlan.isManualPlan,
});

// Update the Plan (if it is manual)
const onSubmit = isValid => {

  if (!isValid) {
    return;
  }
  editForm.loading = true;
  editForm.riders = ridersData.value;

  console.log(editForm);
  editForm.loading = false;
  return;
  // remove loading from editForm
  // const data  = editForm.filter((item) => item !== 'loading');
  axios
    .post('/personal-quotes/life-plan-manual-create', {
      quoteUID: props.uuid,
      formData: editForm,
    })
    .then(res => {
      if (res.data == 200) {
        emit('success');
      } else {
        emit('error', res.data);
      }
    })
    .catch(err => {
      emit('error');
    })
    .finally(() => {
      editForm.loading = false;
    });
};

// On Modal Open, Load the Addons (Riders)
onMounted(() => {
  if (editForm.providerId) {
    ridersData.value = props.selectedPlan.riders.map(rider => ({
            riderId: rider.id,
            active: rider.active ?? 0,
            price: rider.price ?? 0,
            coverValue: rider.coverValue ?? 0,
            text: rider.text,
    }));

    console.log(ridersData.value);
  }
});



// tabs 
const tabs = ref([
  { index: 0, label: 'General Info' },
  { index: 1, label: 'Addons' },
  { index: 2, label: 'Inclusions' },
  { index: 3, label: 'Exclusions' },
  { index: 4, label: 'Policy Detail' },
]);


const setActiveTab = (index, selected) => {
  if(index == 1){
    showGetQuoteBtn.value = true;
    return;  
  }
  showGetQuoteBtn.value = false;

};


</script>

<template>
  <x-modal
    v-model="shown"
    size="lg"
    :title="selectedPlan?.providerName ?? 'Edit Plan'"
    show-close
    backdrop
    is-form
    @submit="onSubmit"
  >
    <div class="w-full">
        <TabGroup>
          <TabList class="flex flex-row flex-wrap gap-2 rounded-xl bg-slate-100 p-1.5 w-full">
            <Tab
              v-for="{ index, label } in tabs"
              as="template"
              :key="index"
              v-slot="{ selected }"
              @click="setActiveTab(index, selected)"
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
                <!-- General Info -->
                <TabPanel>
          <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4 p-4">
            
            
            <div class="grid sm:grid-cols-2 mb-3">
              <x-toggle
                v-model="editForm.isDisabled"
                color="success"
                label="Hide"
              />
            </div>
            <div v-if="editForm.isManualPlan" class="grid sm:grid-cols-2 mb-3">
              <x-toggle
                v-model="editForm.isManualPlan"
                color="success"
                label="Manual"
              />
            </div>

            <div class="grid sm:grid-cols-2">
              <dt class="">Provider Name</dt>
              <dd>{{ props.selectedPlan.providerName }} </dd>
            </div>

            <div class="grid sm:grid-cols-2">
              <dt class="">Plan Name</dt>
              <dd>{{ props.selectedPlan.planName }} <x-tag
                    v-if="props.selectedPlan.isManualPlan"
                    size="xs"
                    color="error"
                    class="mt-0.5 text-[10px] bg-gray-200 text-gray-700 font-semibold px-2 py-1 rounded-md"
                  >
                    Manual
                  </x-tag>
                  <x-tag
                    v-else-if="!props.selectedPlan.isManualPlan"
                    size="xs"
                    color="error"
                    class="mt-0.5 text-[10px] bg-orange-200 text-orange-700 font-semibold px-2 py-1 rounded-md"
                  >
                    API
                  </x-tag></dd>
            </div>

            <div class="grid sm:grid-cols-2">
              <dt class="mt-2">Insurer Quote No.:</dt>
              <x-input
                v-model="editForm.insurerQuoteNo"
                :disabled="!editForm.isManualPlan"
                :error="showInsurerError ? 'This field is required' : ''"
                maxlength="50"
                size="sm"
              />
            </div>

            <div class="grid sm:grid-cols-2">
              <dt class="mt-2">Price:</dt>
              <x-input
                v-model="editForm.actualPremium"
                :disabled="!editForm.isManualPlan"
                size="sm"
                type="number"
              />
            </div>

            <div class="grid sm:grid-cols-2">
              <dt class="mt-2">Currency:</dt>
              <x-select
                v-model="editForm.currency"
                placeholder="AED"
                class="w-full"
                :disabled="!editForm.isManualPlan"
                :options="
                    props.currencies?.map(currency => ({
                    value: currency.text,
                    label: currency.text,
                    }))
                "
                :rules="[isRequired]"
                />
            </div>
            
            <div class="grid sm:grid-cols-2">
              <dt class="mt-2">Sum Assured:</dt>
              <x-input
                v-model="editForm.sumAssured"
                :disabled="!editForm.isManualPlan"
                size="sm"
                type="number"
              />
            </div>

            <div class="grid sm:grid-cols-2">
              <dt class="mt-2">Policy Term:</dt>
              <x-input
                v-model="editForm.policyTerm"
                :disabled="!editForm.isManualPlan"
                size="sm"
              />
            </div>


            <div class="grid sm:grid-cols-2">
              <dt class="mt-2">Payment Term:</dt>
              <x-select
                v-model="editForm.paymentTerm"
                placeholder="Select Payment Terms"
                class="w-full"
                :options="paymentTerms"
                :disabled="!editForm.isManualPlan"
                :rules="[isRequired]"
                />
            </div>

            <div class="grid sm:grid-cols-4 col-span-2">
                <dt class="">Total Price: </dt>
                <dd>AED: {{ props.selectedPlan.actualPremium }}</dd>
            </div>

        </dl>
        </TabPanel>
                <!-- General Info end -->
            
                <TabPanel>
                    <div class="mt-6">
                        <h3 class="font-semibold  rounded-md text-gray-700">Add On</h3>
                        <p></p>
                    </div>
                    <div class="mt-6">
                        <div class="grid grid-cols-6  items-center gap-2 p-2 border-b" 
                            >
                            <div>
                                <span class="text-gray-700 font-bold">Riders</span>
                            </div>
                            <div></div>
                            <div>
                                <span class="text-gray-700 font-bold">Cover</span>
                            </div>
                            <div></div>
                            <div></div>
                            <div>
                                <span class="text-gray-700 font-bold">Price</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-6 items-center gap-4 p-2 border-b">
                        <span class="text-gray-700 col-span-2">Life Cover</span>
                        <span class="text-gray-700">Included</span>
                        <input type="number" class="w-full h-10 p-2 border border-gray-300 rounded-md" v-model="editForm.sumAssured" disabled />

                        <x-toggle v-model="lifeCoverToggled" color="emerald" size="lg" disabled/>
                        <input type="number" class="w-full h-10 p-2 border border-gray-300 rounded-md" v-model="editForm.actualPremium" disabled />
                        </div>
                        <div class="grid grid-cols-6 items-center gap-4 p-2 border-b" 
                            v-for="(rider, index) in ridersData" :key="rider.id">
                            <span class="text-gray-700 col-span-2">{{ rider.text }}</span>
                            <span class="text-gray-700">{{rider.active ? 'Included' : 'Optional'}}</span>
                            <input type="number" class="w-full h-10 p-2 border border-gray-300 rounded-md" v-model="rider.coverValue" />
                            <x-toggle v-model="rider.active" color="success" size="lg" />
                            <input type="number" class="w-full h-10 p-2 border border-gray-300 rounded-md" v-model="rider.price"/>
                        </div>
                    </div>

                </TabPanel>
            
                <TabPanel>
                        <div class="mb-2"
                            v-if="props?.selectedPlan?.benefits?.inclusion"
                            v-for="data in props?.selectedPlan?.benefits?.inclusion || []"
                            :key="data.code">
                            <dt class="font-medium">{{ data.text }}</dt>
                            <dd>{{ data.value ?? 'Included' }}</dd>
                        </div>
                </TabPanel>


                <TabPanel>
                    
                    <div
                        v-if="props?.selectedPlan?.benefits?.exclusion"
                        v-for="data in props?.selectedPlan?.benefits?.exclusion || []"
                        :key="data.code"
                        class="mt-2"
                        >
                        <dt class="font-medium mb-2">{{ data.text }}</dt>
                        <dd>{{ data.value }}</dd>
                    </div>
                </TabPanel>

                <TabPanel>
                    <dl class="grid md:grid-cols-2 gap-5 p-4">
                        <div 
                        
                        v-for="data in props?.selectedPlan?.policyWordings || []" :key="data">
                            <a :href="data.link" class="font-medium mb-1" target="_blank">{{
                                data.text
                            }}</a>
                        </div>
                    </dl>
                </TabPanel>
            </TabPanels>
        </TabGroup>

    </div>


    <template #actions>
      <div class="flex justify-end">
        <template v-if="editForm.isManualPlan">
            <x-button type="submit" color="blue" x-if="editForm.isManualPlan" :loading="editForm.loading">
            Save
            </x-button>
        </template>
    
        <template v-if="showGetQuoteBtn && !editForm.isManualPlan">
            <x-button type="submit" color="blue"  :loading="editForm.loading">
              Get Quotes
            </x-button>
        </template>
      </div>
    </template>
  </x-modal>
</template>

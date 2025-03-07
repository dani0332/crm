<script setup>
import { ref } from 'vue';
import { errorMessages } from 'vue/compiler-sfc';
import moment from 'moment';
import { reactify } from '@vueuse/core';
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
const showSaveButton = ref(false);

let selectedTabIndex = ref(0);
let overallLoading = ref(0);
let totalPrice = props.selectedPlan.actualPremium;  
let errorMessage = ref(null)

const formatDate = (timestamp) => {
  return moment(timestamp).format('DD-MM-YYYY HH:mm:ss');
}

const ridersData = ref(riders);

const page = usePage();

const emit = defineEmits(['success', 'error']);

const dateFormat = date => useDateFormat(date, 'DD-MM-YYYY').value;

const active = ref(false);

const lifeCoverToggled = true;

const notification = useNotifications('toast');


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

const updateQuotesLoader = ref(false);


const availableInsuranceProviders = computed(() => {
  if(editForm.isUW) {return props.insuranceProviders;}
  return props.insuranceProviders.filter(item => {
    return !props.plans.some(plan => plan.providerId === item.id);
  });
});

const extraAttr = reactive({
  getQuoteLoading: false,
  loading:false,
})

const editForm = reactive({
  providerId: props?.selectedPlan?.providerId ?? null,
  planId: props?.selectedPlan?.planId ?? null,
  isUW: props.selectedPlan?.isUW ?? false,
  currency: props.selectedPlan.currency,
  paymentTerm: props.selectedPlan?.paymentTerm ?? null,
  sumAssured: props.selectedPlan.sumInsured ?? 0, 
  policyTerm: props.selectedPlan.policyTerm,
  actualPremium: props.selectedPlan.actualPremium ?? 0,
  insurerQuoteNo: props.selectedPlan.insurerQuoteNo ?? null,
  isVariant: false,
  update: true,
  isDisabled:props.selectedPlan.isDisabled ?? false,
  isManualPlan: props.selectedPlan.isManualPlan,
  version: props.selectedPlan.version, 
  isApi: props.selectedPlan.isApi, 
  isManualUpdate: props.selectedPlan.isManualPlan ? true : false
});

let actualPremium = ref(parseFloat(props.selectedPlan.actualPremium)); 


// Update the Plan (if it is manual)
const onSubmit = isValid => {

  if (!isValid) {
    return;
  }
  extraAttr.loading = true;
  editForm.riders = ridersData.value;

  // remove loading from editForm
  // const data  = editForm.filter((item) => item !== 'loading');
  axios
    .post('/personal-quotes/life-plan-manual-create', {
      quoteUID: props.uuid,
      formData: editForm,
    })
    .then(res => {
      extraAttr.loading = false;
      if (res.status == 200) {
        
        notification.success({
          title: res.data.message,
          position: 'top',
        });

        shown.value = false;

        setTimeout(() => {
          location.reload();
        }, 2000);

      } else {

        emit('error', res.data);
        notification.error({
          title: 'Something went wrong',
          position: 'top',
        });
      }
    })
    .catch(err => {
      emit('error');
      extraAttr.loading = false;
      // notification.error({
      //     title: err.response.data.message,
      //     position: 'top',
      // });
      errorMessage.value = err.response.data.message
    })
    .finally(() => {
      extraAttr.loading = false;
    });
};

// Get updated provider plan (API Mode only)

const getQuote = () => {
    extraAttr.getQuoteLoading = true;
    axios
    .post(`/personal-quotes/get-life-provider-plan`, {
        data: {
        quoteUID: props.uuid,
        planId: props.selectedPlan.planId,
        providerCode: props.selectedPlan.providerCode,
        isIndividualLoading: true,
        planData: {
            currency: editForm.currency,
            sumAssured: editForm.sumAssured,
            policyTerm: editForm.policyTerm,
            paymentTerm: editForm.paymentTerm,
            riders: ridersData.value,
        },
        lang: "en"
        }
    })
    .then(res => {
        console.log('success response', res)
        
        if (res.data.providerPlan.message) {
            errorMessage.value = res.data.providerPlan.message;
            return; 
        }

        if (res.data) {
            editForm.actualPremium = res.data.providerPlan.plan.actualPremium;
            actualPremium.value = res.data.providerPlan.plan.actualPremium;
            errorMessage.value = null;
        }
        console.log('Error message', errorMessage); 

        extraAttr.getQuoteLoading = false;
        showGetQuoteBtn.value = false;
        showSaveButton.value = true;

    })
    .catch(err => {
      console.log(err)
      errorMessage.value = err.response.data.message;

    })
    .finally(() => {
      extraAttr.getQuoteLoading = false;
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
            loading:rider?.loading ?? 0
    }));

    console.log(ridersData.value);
  }
});

const getRiderPrice = () => {
  // Calculate the total price for active riders
  return ridersData.value.filter(rider => rider.active == 1)  // Filter active riders
    .reduce((sum, rider) => parseFloat(sum) + (parseFloat(rider.price) || 0), 0); // Sum the prices
}

// Calculate total price where active != 0
watch(ridersData, (newRidersData) => {
  console.log('Rider Data', ridersData)
  // Calculate the total price for active riders
  const totalActivePrice = newRidersData
    .filter(rider => rider.active == parseInt(1))  // Filter active riders
    .reduce((sum, rider) => parseFloat(sum) + (parseFloat(rider.price) || 0), 0); // Sum the prices

    // Add the total active rider price to the actualPremium
    actualPremium.value = parseFloat(editForm.actualPremium) + parseFloat(totalActivePrice);
}, { deep: true });

const handleActualPremium = () => {
  const totalRider = getRiderPrice();
  actualPremium.value = parseFloat(editForm.actualPremium) + parseFloat(totalRider);
}



// tabs 
const tabs = ref([
  { index: 0, label: 'General Info' },
  { index: 1, label: 'Addons' },
  { index: 2, label: 'Inclusions' },
  { index: 3, label: 'Exclusions' },
  { index: 4, label: 'Policy Detail' },
]);


const setActiveTab = (index, selected) => {
  selectedTabIndex.value = index; 
  if(index == 1){
    showGetQuoteBtn.value = true;
    return;  
  }
  showGetQuoteBtn.value = false;

};

const getInputRules = (rider) => {
  return parseInt(rider.active) == 1 ? [isRequired] : [];
};

</script>

<template>
  <x-modal
    :hasActions="false"
    v-model="shown"
    size="lg"
    :title="selectedPlan?.providerName ?? 'Edit Plan'"
    show-close
    backdrop
    is-form
    @submit="onSubmit"
  >
    <div class="w-full">
        <TabGroup
          >
          <TabList class="flex flex-row flex-wrap rounded-xl bg-slate-100 p-1.5 w-full">
            <Tab
              v-for="{ index, label } in tabs"
              as="template"
              :key="index"
              v-slot="{ selected }"
              @click="setActiveTab(index, selected)"
            >
              <button
                :class="[
                  'rounded-lg px-3 py-2 flex-auto md:min-w-[20%] text-sm font-medium text-gray-800 transition duration-200 ease-in-out uppercase',
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
            <TabPanels class="mt-2 text-sm min-h-[40vh]">
                <!-- General Info -->
                <TabPanel>
          <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4 p-4">
            
            
            <div :class="{'col-span-2': editForm.isApi}">
              <x-toggle
                v-model="editForm.isDisabled"
                color="success"
                label="Hide"
              />
            </div>

            <div v-if="!editForm.isApi" class="grid sm:grid-cols-2 mb-3">
              <x-toggle
                v-model="editForm.isManualPlan"
                color="success"
                label="Manual"
                disabled
              />
            </div>

            <div class="grid sm:grid-cols-2">
              <dt class="">Provider Name</dt>
              <dd>{{ props.selectedPlan.providerName }} </dd>
            </div>

            <div class="grid sm:grid-cols-2">
              <dt class="">Plan Name</dt>
              <dd>{{ props.selectedPlan.planName }}
                 <x-tag
                  v-if="props.selectedPlan.isUnderwritten"
                    size="xs"
                    color="error"
                    class="mt-0.5 text-[10px] bg-green-300 text-green-800 font-semibold px-2 py-1 rounded-md"
                  >
                    UW
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
                :disabled="editForm.isApi"
                :error="showInsurerError ? 'This field is required' : ''"
                maxlength="50"
                size="sm"
              />
            </div>

            <div class="grid sm:grid-cols-2">
              <dt class="mt-2">Price:</dt>
              <x-input
                v-model="editForm.actualPremium"
                :disabled="editForm.isApi"
                @input="handleActualPremium"
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
                :disabled="editForm.isApi"
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
                :disabled="editForm.isApi"
                :rules="[isRequired]"
                size="sm"
                type="number"
              />
            </div>

            <div class="grid sm:grid-cols-2">
              <dt class="mt-2">Policy Term:</dt>
              <x-input
                v-model="editForm.policyTerm"
                :disabled="editForm.isApi"
                :rules="[isRequired]"
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
                :disabled="editForm.isApi"
                :rules="[isRequired]"
                />
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
                        <div class="grid grid-cols-10 items-center gap-2 p-2 border-b" 
                            >
                            <div class="col-span-2">
                                <span class="text-gray-700 font-bold">Riders</span>
                            </div>
                            <div class="col-span-3">
                                <span class="text-gray-700 font-bold">Cover</span>
                            </div>
                            <div class="col-span-2">
                                <span class="text-gray-700 font-bold">Price</span>
                            </div>
                            <div class="col-span-2" v-if="props.selectedPlan.isUnderwritten">
                                <span class="text-gray-700 font-bold">Rider Loading</span>
                            </div>
                            <div class="col-span-1" v-if="props.selectedPlan.isUnderwritten">
                                <span class="text-gray-700 font-bold">Final Price</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-10 items-center gap-4 p-2 border-b">
                          <div>
                            <span class="text-gray-700">Life Cover</span>
                          </div>
                          
                          <div>
                            <span class="text-gray-700">Included</span>
                          </div>
                          
                          <div class="col-span-2">
                            <x-input type="number" class="w-full h-10 p-2 rounded-md" v-model="editForm.sumAssured" disabled />
                          </div>
                          
                          <div>
                            <x-toggle v-model="lifeCoverToggled" color="emerald" size="lg" disabled/>
                          </div>
                          <div class="col-span-2">
                            <x-input type="number" 
                            class="w-full h-10 p-2 rounded-md" v-model="editForm.actualPremium" disabled />
                          </div>
                          <div class="col-span-2" v-if="props.selectedPlan.isUnderwritten">
                            <x-input type="number" class="w-full h-10 p-2 rounded-md" v-model="editForm.actualPremium" disabled />
                          </div>
                          <div class="col-span-1">
                            <x-input type="number" v-if="props.selectedPlan.isUnderwritten" class="w-full h-10 p-2 rounded-md" v-model="editForm.actualPremium" disabled />
                          </div>
                        </div>
                        <div class="grid grid-cols-10 items-center gap-4 p-2" 
                            v-for="(rider, index) in ridersData" :key="rider.id">
                            <div>
                              <span class="text-gray-700 col-span-2">{{ rider.text }}</span>
                            </div>
                            <div>
                              <span class="text-gray-700">{{ rider.active ? 'Included' : 'Optional'}}</span>
                            </div>

                            <div class="col-span-2">
                              <x-input :disabled="!rider.active" 
                              :rules="parseInt(rider.active) == 1 ? isRequired : []" 
                              type="number" class="w-full h-10 p-2 rounded-md" v-model="rider.coverValue" />
                            </div>

                            <div>
                              <x-toggle v-model="rider.active" color="success" size="lg" />
                            </div>

                            <div class="col-span-2">
                              <x-input type="number" :disabled="!props.selectedPlan.isManualPlan || !rider.active" class="w-full h-10 p-2 rounded-md" v-model="rider.price"/>
                            </div>

                            <div class="col-span-2" v-if="props.selectedPlan.isUnderwritten">
                              <x-input type="number" :disabled="!props.selectedPlan.isManualPlan || !rider.active" class="w-full h-10 p-2 rounded-md" v-model="rider.loading"/>
                            </div>

                            <div class="col-span-1" v-if="props.selectedPlan.isUnderwritten">
                              <x-input type="number" :disabled="true" class="w-full h-10 p-2 rounded-md" v-model="rider.price"/>
                            </div>
                        </div>
                    </div>

                </TabPanel>
            
                <TabPanel>
  <div
    class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 mb-2 mt-6"
    v-if="props?.selectedPlan?.benefits?.inclusion"
  >
    <div
      v-for="data in props?.selectedPlan?.benefits?.inclusion || []"
      :key="data.code"
      class="mb-3 text-center"
    >
      <dt class="font-semibold">{{ data.text }}</dt>
      <dd>{{ data.value ?? 'Included' }}</dd>
    </div>
  </div>
</TabPanel>


                <TabPanel>
                <div
                  class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 mb-2 mt-6"
                  v-if="props?.selectedPlan?.benefits?.exclusion"
                >
                  <div
                    v-for="data in props?.selectedPlan?.benefits?.exclusion || []"
                    :key="data.code"
                    class="mb-3 text-center"
                  >
                    <dt class="font-semibold">{{ data.text }}</dt>
                    <dd>{{ data.value ?? 'Excluded' }}</dd>
                  </div>
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


    <div>
  
  <template v-if="selectedTabIndex == 0">
    <x-divider></x-divider>
      <div class="flex justify-between gap-4 mt-4">
          <div class="flex-col">
            <p v-if="errorMessage" class="text-red-600">{{ errorMessage }}</p>

          </div>
      </div>
      <div class="flex justify-between gap-4 mt-4">
        <!-- Price section aligned to the left -->
        <div class="flex flex-row">
          <dt class="font-bold text-lg ml-4">Total Price:</dt>
          <dd class="text-lg">&nbsp; AED {{ actualPremium }}</dd>
        </div>

        <!-- Timestamps aligned to the right -->
        <div class="flex flex-col items-end">
          <dd><strong>Created Date:</strong> {{ formatDate(props.selectedPlan.created_at) }}</dd>
          <dd><strong>Updated at:</strong> {{ formatDate(props.selectedPlan.updated_at) }}</dd>
        </div>
      </div>

      <!-- Buttons section aligned to the right -->
      <div class="flex justify-end gap-4 mt-4">
        <div v-if="!editForm.isApi">
          <x-button type="submit" color="blue" :loading="extraAttr.loading">
            Save
          </x-button>
        </div>
      </div>  
  </template>

  <template v-else-if="selectedTabIndex == 1 && editForm.isApi">
    <x-divider></x-divider>
      <div class="flex justify-between gap-4 mt-4">
        <!-- Price section aligned to the left -->
        <div class="flex flex-col">
        <p v-if="errorMessage" class="text-red-600">{{ errorMessage }}</p>
        <div class="flex flex-row">
          <dt class="font-bold text-sm ml-4">Total Price:</dt>
          <dd class="text-sm">&nbsp; AED {{ editForm.actualPremium }}</dd>
        </div>
        
      
      </div>
        <!-- Timestamps aligned to the right -->
        
      </div>

      <!-- Buttons section aligned to the right -->
      <div class="flex justify-end gap-4 mt-4">
        <div v-if="showSaveButton">
          <x-button type="submit" color="blue" :loading="extraAttr.loading">
            Save
          </x-button>
        </div>

        <div v-else-if="showGetQuoteBtn">
          <x-button type="button" @click="getQuote()" color="blue" :loading="extraAttr.getQuoteLoading">
            Update Quotes
          </x-button>
        </div>
      </div>  
  </template>

  <template v-else-if="selectedTabIndex == 1 && editForm.isManualPlan && !editForm.isApi">
    <x-divider></x-divider>
    <div class="flex justify-between gap-4 mt-4 items-center">
      <!-- Main container pushed to the right -->
      <div class="ml-auto flex items-center gap-4">
        <!-- Overall Loading input field -->
        <div class="flex items-center" v-if="props.selectedPlan.isUnderwritten">
          <span class="mr-2">Overall Loading:</span>
          <input type="number" class="w-32 p-4 border h-6 rounded border-gray-500" /> <!-- Adjust width as needed -->
        </div>

        <!-- Total Price section -->
        <div class="flex items-center">
          <span class="font-bold mr-2">Total Price:</span>
          <span class="">AED {{ actualPremium }}</span>
        </div>
      </div>
    </div>

    <!-- Buttons section aligned to the right -->
    <div class="flex justify-end gap-4 mt-4">
      <div v-if="!editForm.isApi">
        <x-button type="submit" color="blue" :loading="extraAttr.loading">
          Save
        </x-button>
      </div>
    </div>
  </template>




  </div>
  </x-modal>
</template>

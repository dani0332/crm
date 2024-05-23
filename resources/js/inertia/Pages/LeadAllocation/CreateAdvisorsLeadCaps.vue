<script setup>
const props = defineProps({
  lead: { type: Object, default: null },

  advisors: {
    type: Object,
    default: () => {},
  },

});
const editMode = computed(() => (props.lead ? true : false));
const quoteTypes = ref([]);
const loader = reactive({
  submit: false,
  table: false,
});

const { isRequired } = useRules();
const isEmptyField = ref(false);
const leadForm = useForm({
    quoteTypeId: props.lead?.quote_type_id ,
    userId: props.lead?.user_id ,
    allocation_count: props.lead?.allocation_count ,
    maxCapacity: props.lead?.max_capacity ,
    is_available: props.lead?.is_available,

});

watch(() => leadForm?.userId, async (userId) => { 
   // Watch for changes in user_id
   if (userId) {
     
     quoteTypes.value = [];
     await getAdvisorByQuoteType(userId); // Call the function to fetch advisors
   }
});



const getAdvisorByQuoteType = async (id) => {

  try {
    
    const response = await axios.get(`/advisor-by-quotetype/${id}`);
   
    quoteTypes.value = response.data.quoteTypes;

  } catch (error) {

    console.error('Error fetching advisors:', error);

  }
};

 const onSubmit = (isValid) => {
  if (leadForm.quoteTypeId == null) {
    isEmptyField.value = true;
  } else {
    isEmptyField.value = false;
  }

  if (isValid) {
    let method = editMode.value ? 'put' : 'post';
    let url = editMode.value
      ? route('lead.allocations.update', props.lead.id)
      : route('allocation.store');

    leadForm.clearErrors();
    leadForm.submit(method, url, {
      onError: errors => {
        console.log(leadForm.setError(errors));
      },
    });
    
  }
}
</script>

<template>
    <div class="flex justify-between items-center">
   

      <h2 class="text-xl font-semibold">
        Advisor Capacity Management
      </h2>
      <div>
        <Link :href="route('allocations.index')">
          <x-button size="sm" color="#ff5e00">   Advisor Capacity Management List </x-button>
        </Link>
      </div>
    </div>
    <x-divider class="my-4" />
  
    <x-form @submit="onSubmit" :auto-focus="false">
      <x-alert color="error" class="mb-5" v-if="leadForm.errors.error">
        {{ leadForm?.errors?.error }}
      </x-alert>

      <div class="grid sm:grid-cols-2 gap-4">
        <x-field label="Advisors" required>
        <x-select
            v-model="leadForm.userId"
          
            :rules="[isRequired]"
          
            :options="
              props.advisors?.map(item => ({
                value: item.id,
                label: item.name,
              }))
            "
            class="w-full"
            :error="leadForm.errors.userId"
          />
          </x-field>
        <x-field v-if="quoteTypes.length > 0"  label="Quote Type" required>
       
          <x-select
            v-model="leadForm.quoteTypeId"
            
            :rules="[isRequired]"
            :options="
             quoteTypes?.map(item => ({
                value: item.id,
                label: item.code,
              }))
            "
            class="w-full"
            :error="leadForm.errors.quoteTypeId"
          />
        </x-field>
      

          <x-field label="Max Capacity" required>
          <x-input
            v-model="leadForm.maxCapacity"
            type="number"
            :rules="[isRequired]"
            min="-1"
            class="w-full"
            :error="leadForm.errors.maxCapacity"
          />
        </x-field>
      </div>

      <x-divider class="my-4" />
      <div class="flex justify-end gap-3 mb-4">
        <x-button
          size="md"
          color="emerald"
          type="submit"
          :loading="leadForm.processing"
        >
          {{ editMode ? 'Update' : 'Save' }}
        </x-button>
      </div>
    </x-form>
</template>

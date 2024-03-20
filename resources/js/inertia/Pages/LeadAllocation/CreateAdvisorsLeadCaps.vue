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
    quote_type_id: props.lead?.quote_type_id ,
    user_id: props.lead?.user_id ,
    allocation_count: props.lead?.allocation_count ,
    max_capacity: props.lead?.max_capacity ,
    is_available: props.lead?.is_available,

});

watch(() => leadForm?.user_id, async (user_id) => { 
   // Watch for changes in user_id
   if (user_id) {
    
     await getAdvisorByQuoteType(user_id); // Call the function to fetch advisors
   }
});



const getAdvisorByQuoteType = async (id) => {
  quoteTypes.value = [];
  try {
    const response = await axios.get(`/advisor-by-quotetype/${id}`);
   
    quoteTypes.value = response.data.quoteTypes;
    
  } catch (error) {
    console.error('Error fetching advisors:', error);

   
  }
};

 const onSubmit = (isValid) => {
  
  if (leadForm.quote_type_id == null) {
    isEmptyField.value = true;
  } else {
    isEmptyField.value = false;
  }

  if (isValid) {
    let method = editMode.value ? 'put' : 'post';
    let url = editMode.value
      ? route('lead.allocations.update', props.lead.id)
      : route('lead.allocations.store');

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
        <Link :href="route('lead.allocations.index')">
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
            v-model="leadForm.user_id"
          
            :rules="[isRequired]"
          
            :options="
              props.advisors?.map(item => ({
                value: item.id,
                label: item.name,
              }))
            "
            class="w-full"
            :error="leadForm.errors.user_id"
          />
          </x-field>
        <x-field v-if="quoteTypes.length > 0"  label="Quote Type" required>
       
          <x-select
            v-model="leadForm.quote_type_id"
            
            :rules="[isRequired]"
            :options="
             quoteTypes?.map(item => ({
                value: item.id,
                label: item.code,
              }))
            "
            class="w-full"
            :error="leadForm.errors.quote_type_id"
          />
        </x-field>
      

          <x-field label="Max Capacity" required>
          <x-input
            v-model="leadForm.max_capacity"
            type="number"
            :rules="[isRequired]"
            min="-1"
            class="w-full"
            :error="leadForm.errors.max_capacity"
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

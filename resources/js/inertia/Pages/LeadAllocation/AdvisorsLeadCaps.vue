<script setup>

const props = defineProps({
  allocationsLeads: {
    type: Array,
    default: () => [],
  },
  quoteTypes: {
    type: Object,
    default: () => {},
  },
  advisors: {
    type: Object,
    default: () => {},
  },

});

const page = usePage();
const hasRole = role => useHasRole(role);
const hasAnyRole = role => useHasAnyRole(role);
const rolesEnum = page.props.rolesEnum;
const notification = useNotifications('toast');


const loader = reactive({
  submit: false,
  table: false,
});

const tableHeader = ref([
  { text: 'Name', value: 'userName', width: '240' },
  { text: 'Quote Type', value: 'quote_type_code', sortable: true },


  { text: 'Max Cap Limit', value: 'max_capacity', sortable: true },
  { text: 'Last Modified', value: 'updated_at', sortable: true },

]);



const leadData = ref([
  {
    id: 0,
    userId: 0,
    status: '1',
    loading: false,
    reset: false,
  },
]);


let availableFilters = {
  userIds: [],
  quoteTypeIds: [],
  page: 1,
};

const filters = reactive(availableFilters);
const editCap = id => {

  if (
    hasAnyRole([rolesEnum.Admin, rolesEnum.LeadPool, rolesEnum.Engineering])
  ) {
    const row = leadData?.value.find(item => item.id === id);
    console.log(row);
    row.capEdit = true;
  }
};

const currentRow = id => {
  const row = leadData?.value.find(item => item.id === id);
  return row?.capEdit;
};

const updateCap = (value, id) => {
  const row = leadData?.value.find(item => item.id === id);
  row.cap = value;
};

const resetCap = (id, maxCapacity) => {
  const row = leadData?.value.find(item => item.id === id);
  row.cap = maxCapacity;
  row.capEdit = false;
};

const isCapChanged = computed(() => {
  return leadData?.value.some(item => item.capEdit);
});


const onSubmitChanges = async () => {
  loader.submit = true;
  const max_cap = leadData?.value
    .filter(item => item.capEdit && item.cap !== item.maxCapacity)
    .map(item => {
      return {
        userId: item.userId,
        maxCap: item.cap,
        id: item.id,

      };
    });

  await axios
    .post(`/update-cap/lead-allocation`, { 'items': max_cap })
    .then(() => {
      router.get('/allocations', {
        replace: true,
        preserveScroll: true,
        preserveState: true,
      });
    })
    .finally(() => {
      loader.submit = false;
    });
};

const onToggleResetCap = async (active, userId, lead_id) => {
  loader.submit = true;
  await axios
    .post('/lead-allocation/toggle-reset-cap', { lead_id, userId, resetCap: active })
    .finally(() => {
      loader.submit = false;
      notification.success({
        title: 'Lead reset cap update successfully.',
        position: 'top',
      });
    });
};

function setQueryStringFilters() {
  let queryString = window.location.search;
  let urlParams = new URLSearchParams(queryString);

  for (const [key] of Object.entries(availableFilters)) {
    if (urlParams.has(key)) {
      
      filters[key] = urlParams.get(key);
     
    }
  }
}

function onSubmit(isValid) {
  if (isValid) {
    filters.page = 1;

    Object.keys(filters).forEach(
      key =>
        (filters[key] === '' || filters[key].length === 0) &&
        delete filters[key],
    );

    router.visit(route('allocations.index'), {
      method: 'get',
      data: filters,
      preserveState: true,
      preserveScroll: true,
      onBefore: () => (loader.table = true),
      onSuccess: () => (loader.table = false),
    });
  } else {
    console.log('Invalid');
  }
}

function onReset() {
  router.visit(route('allocations.index'), {
    method: 'get',
    data: { page: 1 },
    preserveScroll: true,
    onBefore: () => (loader.table = true),
    onSuccess: () => (loader.table = false),
  });
}

onMounted(() => {
  setQueryStringFilters();
  leadData.value = props.allocationsLeads.data.map(item => {
    return {
      id: item.id,
      userId: item.userId,
      cap: item.max_capacity,

      capEdit: false,
      status: item.is_available,
    };
  });
});
</script>


<template>
  <div>

    <Head title="Advisors Capacity Management" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">Advisors Capacity Management</h2>
      <x-button size="sm" color="#ff5e00" :href="route('allocation.create')">
        Create Allocation
      </x-button>
    </div>
    <x-divider class="my-4" />
    <x-divider class="my-4" />
    <div class="mt-5 mb-5">

      <div class="grid grid-cols-2 md:grid-cols-4 w-full gap-5">

        <TransitionGroup name="fade">
          <div v-if="isCapChanged" class="col-span-2">
            <x-alert type="info" light>For Unlimited Capactiy Add ( -1 )</x-alert>
          </div>
          <div v-if="isCapChanged" class="col-span-2">
            <x-button color="emerald" :loading="loader.submit" block @click="onSubmitChanges">
              Save Cap Changes
            </x-button>
          </div>
        </TransitionGroup>
      </div>
    </div>

      <!--   filters     -->
      <x-form @submit="onSubmit" :auto-focus="false">
      <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-4">
       
      
        <x-field label="Advisors">
          <ComboBox
            v-model="filters.userIds"
            name="user_id"
            placeholder="Search by Quote Type"
            :options="
              props.advisors?.map(item => ({
                value: item.id,
                label: item.name,
              }))
            "
          />
        </x-field>
     
        <x-field label="Quote Type">
          <ComboBox
            v-model="filters.quoteTypeIds"
            name="quote_type_id"
            placeholder="Search by Quote Type"
            :options="
              props.quoteTypes.map(item => ({
                value: item.id,
                label: item.code,
              }))
            "
          />
        </x-field>
     
     
      </div>
      <div class="flex justify-between gap-3 mb-4 mt-1">
      
        <div class="flex justify-self-end gap-3">
          <x-button size="sm" color="#ff5e00" type="submit">Search</x-button>
          <x-button size="sm" color="primary" @click.prevent="onReset">
            Reset
          </x-button>
        </div>
      </div>
    </x-form>

    <DataTable id="car-lead-allocation" table-class-name="compact" :loading="loader.table" :headers="tableHeader"
      :items="props.allocationsLeads.data || []" :sort-by="'userName'" :sort-type="'asc'" :rows-per-page="999"
      border-cell hide-rows-per-page hide-footer>
      <template #item-max_capacity="{ max_capacity, id }">
        <div v-if="!currentRow(id)" @click="editCap(id)">
          {{ max_capacity }}
        </div>
        <div v-else class="flex gap-1">
          <x-input type="number" :value="max_capacity" class="w-16" @update:model-value="updateCap($event, id)" />
          <x-button icon="reset" size="sm" ghost @click="resetCap(id, max_capacity)" />
        </div>
      </template>
  
   
      

    </DataTable>

    

    <Pagination :links="{
        next: props.allocationsLeads.next_page_url,
        prev: props.allocationsLeads.prev_page_url,
        current: props.allocationsLeads.current_page,
        from: props.allocationsLeads.from,
        to: props.allocationsLeads.to,
      }" />
  </div>

</template>

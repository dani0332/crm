<script setup>

const props = defineProps({
  allocations_leads: {
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
  {
    text: 'Total Assigned Leads',
    value: 'allocation_count',
    sortable: true,
  },
  { text: 'Last Allocations', value: 'last_allocated', sortable: true },
  { text: 'Max Cap Limit', value: 'max_capacity', sortable: true },
  { text: 'Status', value: 'is_available', sortable: true, width: '100' },
  { text: 'Reset Cap', value: 'reset_cap', sortable: true, width: '100' },

]);

const statusText = statusId =>
({
  1: 'Online',
  2: 'Offline',
  3: 'Unavailable',
  4: 'Sick',
  5: 'On leave',
}[parseInt(statusId)] || 'Unavailable');


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
  user_ids: [],
  quote_type_ids: [],
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
      router.get('/advisor-allocations', {
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

const onToggleStatus = (status, id, userId, quote_type_code) => {
  statusModal.data.id = id;
  statusModal.data.userId = userId;
  statusModal.data.quote_type_code = quote_type_code;

  if (status) {
    statusModal.data.reason = 1;
    onStatusSubmit();
  } else {
    statusModal.data.reason = 3;
    statusModal.show = true;
  }
};


const statusModal = reactive({
  show: false,
  loader: false,
  data: {
    id: 0,
    userId: 0,
    reason: 1,
    quote_type_code: "",
    loader: false,
  },
});

const onStatusSubmit = async () => {
  statusModal.loader = true;

  const item = leadData.value.find(item => item.id === statusModal.data.id);
 
  item.loading = true;
  await axios
    .post(`/lead-allocation/${statusModal.data.quote_type_code}/update-availability`, [
      {
        userId: statusModal.data.userId,
        id: statusModal.data.id,
        reason: statusModal.data.reason,

      },
    ])
    .then(res => {
      router.reload({
        only: ['data'],
        preserveScroll: true,
        preserveState: true,
      });
    })
    .finally(() => {
      statusModal.loader = false;
      item.loading = false;
      statusModal.show = false;
    });
};

const onStatusModalClose = event => {
  const item = leadData.value.find(item => item.id === statusModal.data.id);
  if (!event) {
    item.reset = true;
    setTimeout(() => {
      item.reset = false;
    }, 300);
    statusModal.show = false;
  }
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

    router.visit(route('lead.allocations.index'), {
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
  router.visit(route('lead.allocations.index'), {
    method: 'get',
    data: { page: 1 },
    preserveScroll: true,
    onBefore: () => (loader.table = true),
    onSuccess: () => (loader.table = false),
  });
}

onMounted(() => {
  setQueryStringFilters();
  leadData.value = props.allocations_leads.data.map(item => {
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
      <x-button size="sm" color="#ff5e00" :href="route('lead.allocations.create')">
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
            v-model="filters.user_ids"
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
            v-model="filters.quote_type_ids"
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
      :items="props.allocations_leads.data || []" :sort-by="'userName'" :sort-type="'asc'" :rows-per-page="999"
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
      <template #item-is_available="{ is_available, id, userId, quote_type_code }">
        <div class="flex flex-col gap-1.5 items-center">
          <x-tag size="xs" :color="['emerald', 'red', 'gray', 'yellow', 'yellow', 'gray'][
        +is_available - 1
        ]
        ">
            {{ statusText(is_available) }}
          </x-tag>

          <ItemToggler v-if="hasAnyRole([
        rolesEnum.Admin,
        rolesEnum.LeadPool,
        rolesEnum.Engineering,
      ])
        " :is-active="parseInt(leadData.find(item => item.id === id)?.status)" :id="id"
            @toggle="onToggleStatus($event.active, id, userId, quote_type_code)"
            :loading="leadData.find(item => item.id === id)?.loading"
            :refresh="leadData.find(item => item.id === id)?.reset" />
        </div>
      </template>
      <template #item-last_allocated="{ last_allocated }">
        <div class="text-center">
          {{ new Date(last_allocated * 1000).toLocaleString() }}
        </div>
      </template>
      <template #item-reset_cap="{ reset_cap, userId, id }">
        <div class="text-center">
          <ItemToggler :is-active="reset_cap" :id="id" @toggle="onToggleResetCap($event.active, userId, id)" />
        </div>
      </template>

    </DataTable>

    <x-modal v-model="statusModal.show" show-close backdrop @update:model-value="onStatusModalClose($event)">
      <template #header> Select Reason of Unavailability </template>
      <x-select placeholder="Select Reason" :options="[
        { value: 3, label: 'Temp. Unavailable' },
        { value: 4, label: 'Sick' },
        { value: 5, label: 'On Leave' },
      ]" class="w-full mb-28" v-model="statusModal.data.reason" />

      <template #actions>
        <div class="text-right space-x-4">
          <x-button size="sm" ghost @click.prevent="onStatusModalClose(false)">
            Cancel
          </x-button>
          <x-button size="sm" color="primary" :loading="statusModal.loader" @click="onStatusSubmit">
            Submit
          </x-button>
        </div>
      </template>
    </x-modal>

    <Pagination :links="{
        next: props.allocations_leads.next_page_url,
        prev: props.allocations_leads.prev_page_url,
        current: props.allocations_leads.current_page,
        from: props.allocations_leads.from,
        to: props.allocations_leads.to,
      }" />
  </div>

</template>

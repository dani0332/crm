<script setup>
import {
  toggleNormalAllocation,
  toggleResetCap,
  toggleBlStatus,
  toggleBLResetCap,
  statusSubmit
} from '../../Services/LeadAllocation/Travel';

const page = usePage();

const refreshGrid = useStorage('refresh-user-counts');
const { isRequired } = useRules();

const props = defineProps({
  quoteType: String,
  userBLStatuses: {
    type: Array,
    default: () => []
  },
  availableUsers: {
    type: Number,
    default: 0,
  },
  unAvailableUsers: {
    type: Number,
    default: 0,
  },
  todayTotalUnAssignedLeadCount: {
    type: Number,
    default: 0
  },
  totalAssignedLeadCount: {
    type: Number,
    default: 0
  },
  data: {
    type: Array,
    default: () => [],
  },
});

const statusText = statusId => resolveUserStatusText(statusId);
const { resume, pause } = useTimeoutPoll(fetchData, 90000);

const hasRole = role => useHasRole(role);
const hasAnyRole = role => useHasAnyRole(role);
const rolesEnum = page.props.rolesEnum;
const notification = useToast();
const loading = ref(false);
const autoRefresh = ref(false);

const canManage = computed(
  () =>
    !loading.value &&
    hasAnyRole([rolesEnum.Admin, rolesEnum.LeadPool, rolesEnum.Engineering]),
);

const tableHeader = ref([
  { text: 'Name', value: 'userName', sortable: true },
  { text: 'Team', value: 'team', sortable: true},
  { text: 'Tot. Assigned', value: 'allocationCount', sortable: true },
  { text: 'M. Assigned', value: 'manualAllocationCount', sortable: true },
  { text: 'A. Assigned', value: 'autoAllocationCount', sortable: true },
  { text: 'Cap Limit', value: 'maxCapacity', sortable: true },
  { text: 'Status', value: 'isAvailable', sortable: true, width: '100' },
  {
    text: 'Norm Allo.',
    value: 'normalAllocationEnabled',
    sortable: true,
    width: '100',
  },
  { text: 'Reset Cap', value: 'reset_cap', sortable: true, width: '100' },
  {
    text: 'BL Cap Limit',
    value: 'BLMaxCapacity',
    sortable: true,
    width: '100',
  },
  { text: 'BL Status', value: 'BLStatus', sortable: true, width: '100' },
  {
    text: 'BL Assigned',
    value: 'BLAllocationCount',
    sortable: true,
    width: '100',
    tooltip:
      'The BL ASSIGNED count shows only the leads requested through Buy Leads. It excludes system-assigned leads. Check the TOT. ASSIGNED column for the total number of assigned leads.',
  },
  { text: 'BL Reset CAP', value: 'blResetCap', sortable: true, width: '100' },
  { text: 'Last Login', value: 'lastLogin', sortable: true, width: '100' },
]);


const statusModal = getStatusModal();

const filters = reactive({
  userBLStatus: null
});

const loaders = reactive({
  submit: false,
  table: false,
  search: false,
  reset: false,
});

async function fetchData() {
  await router.reload({
    replace: true,
    preserveScroll: true,
    preserveState: true,
  });
}


const onReset = () => {
  router.visit('travel-lead-allocation', {
    method: 'get',
    data: {},
    preserveScroll: true,
    onBefore: () => (loaders.reset = true),
    onSuccess: () => (loaders.reset = false)
  });
}

const onSubmit = isValid => {
  if (isValid) {
    router.visit('travel-lead-allocation', {
      method: 'get',
      data: { ...filters },
      preserveState: true,
      preserveScroll: true,
      onBefore: () => (loaders.search = true),
      onFinish: () => (loaders.search = false),
    });
  }
};


const leadData = ref([
  {
    id: 0,
    userId: 0,
    cap: 0,
    BlMaxcap: 0,
    BlCapEdit: false,
    BlAllocationStatus: false,
    capEdit: false,
    status: '1',
    loading: false,
    reset: false,
  },
]);

const onToggleStatus = (status, id, userId) => {
  statusModal.data = reactive({
    ...statusModal.data,
    id,
    userId
  });

  if (status) {
    statusModal.data.reason = 1;
    onStatusSubmit();
  } else {
    statusModal.data.reason = 3;
    statusModal.show = true;
  }
}

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

const onToggleNormalAllocation = async (active, userId, laId) => {
  loaders.table = true;

  try {
    await toggleNormalAllocation(active, userId, laId);
  } catch (error) {
    notification.error({
      title: 'Error',
      description: 'Something went wrong!',
      position: 'top',
    });
  } finally {
    loaders.table = false;
  }
};

const onToggleResetCap = async (active, userId, leadId) => {
  loaders.table = true;

  try {
    await toggleResetCap(active, userId, leadId);
  } catch (error) {
    notification.error({
      title: 'Error',
      description: 'Something went wrong!',
      position: 'top',
    });
  } finally {
    loaders.table = false;
  }
};

const onToggleBlStatus = async (active, userId, leadId) => {
  loaders.table = true;
  
  try {
    await toggleBlStatus(active, userId, leadId);
  } catch (error) {
    notification.error({
      title: 'Error',
      description: 'Something went wrong!',
      position: 'top',
    });
  } finally {
    loaders.table = false;
  }
};

const onToggleBLResetCap = async (active, userId, laId) => {
  loaders.table = true;

  try {
    await toggleBLResetCap(active, userId, laId);
  } catch(error) {
    notification.error({
      title: 'Error',
      description: 'Something went wrong!',
      position: 'top',
    });
  } finally {
    loaders.table = false;
  }
};

const onStatusSubmit = async () => {
  statusModal.loader = true;
  item.loading = true;
  const item = leadData.value.find(item => item.id === statusModal.data.id);

  try {
    await statusSubmit(page.props.quoteType, [
      {
        userId: statusModal.data.userId,
        id: statusModal.data.id,
        reason: statusModal.data.reason,
      },
    ]);
  } catch (error) {
    notification.error({
      title: 'Error',
      description: 'Something went wrong!',
      position: 'top',
    });
  } finally {
    router.reload({
      only: ['data'],
      preserveScroll: true,
      preserveState: true,
    });
    statusModal.loader = false;
    item.loading = false;
    statusModal.show = false;
  }
}

watch(
  () => autoRefresh.value,
  () => {
    if (autoRefresh.value) {
      resume();
    } else {
      pause();
    }
  },
  {
    immediate: true,
  },
);

onMounted(() => {
  tableHeader.value = tableHeader.value.filter(column => column);

  if (props.data && Array.isArray(props.data)) {
    leadData.value = props.data.map(item => ({
        id: item.id,
        userId: item.userId,
      cap: item.maxCapacity,
      capEdit: false,
      status: item.isAvailable,
    }));
  } else {
    leadData.value = [];

    notification.error({
      title: 'Error',
      description: 'Something went wrong! Data not found!',
      position: 'top',
    });
  }
});
</script>

<template>
  <div>
    <Head title="Travel Lead Allocation" />
    <div class="flex justify-between items-center">
      <div
        class="flex gap-1"
        v-if="hasAnyRole([rolesEnum.Admin, rolesEnum.Engineering])"
      >
        <h2 class="text-lg font-semibold">Travel Lead Allocation Management</h2>
      </div>

      <!-- Auto Refresh -->
      <div
        class="flex gap-1"
        v-if="
          hasAnyRole([
            rolesEnum.Admin,
            rolesEnum.LeadPool,
            rolesEnum.Engineering,
          ])
        "
      >
        <h2 class="text-lg font-semibold">Auto Refresh :</h2>
        <x-toggle v-model="autoRefresh" color="emerald" size="lg" />
      </div>
    </div>
    <x-divider class="my-4" />

    <!-- Statistics Boxes -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-5 my-6">
      <div class="labox border-green-500">
        <h3>Team</h3>
        <p>Travel</p>
      </div>
      <div class="labox border-primary-500">
        <h3>Assigned Lead Count</h3>
        <p>{{ props.totalAssignedLeadCount }}</p>
      </div>
      <div class="labox border-yellow-500">
        <h3>Available / UnAvailable</h3>
        <p>{{ props.availableUsers }} / {{ props.unAvailableUsers }}</p>
      </div>
      <div class="labox border-yellow-500">
        <h3>Total UnAssigned Leads</h3>
        <p>{{ props.todayTotalUnAssignedLeadCount }}</p>
      </div>

      <TransitionGroup name="fade">
        <div v-if="isCapChanged" class="col-span-2">
          <x-alert type="info" light>For Unlimited Capactiy Add ( -1 )</x-alert>
        </div>
        <div v-if="isCapChanged" class="col-span-2">
          <x-button
            color="emerald"
            :loading="loaders.submit"
            block
      
          >
            Save Cap Changes
          </x-button>
        </div>
      </TransitionGroup>

      <TransitionGroup name="fade">
        <div v-if="isBlCapChanged" class="col-span-2">
          <x-alert type="info" light>For Unlimited Capactiy Add ( -1 )</x-alert>
        </div>
        <div v-if="isBlCapChanged" class="col-span-2">
          <x-button
            color="emerald"
            :loading="loaders.submit"
            block
          
          >
            Save Buy Lead Cap Changes
          </x-button>
        </div>
      </TransitionGroup>
    </div>

    <!-- Filters -->
    <x-divider class="my-4" />
    <x-form @submit="onSubmit" :auto-focus="false">
      <div class="grid sm:grid-cols-2 gap-4">
        <x-select
        label="Buy Lead Status of Users"
          required
          placeholder="Select Status"
          :options="userBLStatuses || []"
          filterable
          v-model="filters.userBlStatus"
          :rules="[isRequired]"
        ></x-select>
      </div>
      <div class="flex justify-end gap-3 mb-4">
        <x-button
          size="md"
          color="orange"
          type="submit"
          :loading="loaders.search"
        >
          Search
        </x-button>
        <x-button
          size="md"
          color="primary"
          type="submit"
          :loading="loaders.reset"
          @click.prevent="onReset()"
        >
          Reset
        </x-button>
      </div>
    </x-form>

    <!-- Table -->
    <DataTable
      id="travel-lead-allocation"
      table-class-name="compact"
      :headers="tableHeader"
      :items="props.data || []"
      :sort-by="'userName'"
      :sort-type="'asc'"
      :rows-per-page="999"
      border-cell
      hide-rows-per-page
      hide-footer
    >
      <template #item-isAvailable="{ isAvailable, id, userId }">
        <div class="flex flex-col gap-1.5 items-center">
          <x-tag
            size="xs"
            :color="
              ['emerald', 'red', 'gray', 'yellow', 'yellow', 'gray'][
                isAvailable - 1
              ]
            "
          >
            {{ statusText(isAvailable) }}
          </x-tag>


          <ItemToggler
            v-if="
              hasAnyRole([
                rolesEnum.Admin,
                rolesEnum.LeadPool,
                rolesEnum.Engineering,
              ])
            "
            :is-active="parseInt(leadData.find(item => item.id === id)?.status)"
            :disabled="!canManage"
            :id="id"
            :loading="leadData.find(item => item.id === id)?.loading"
            :refresh="leadData.find(item => item.id === id)?.reset"
            @toggle="onToggleStatus($event.active, id, userId)"
          />
        </div>
      </template>

      <template
        #item-normalAllocationEnabled="{ normalAllocationEnabled, userId, id }"
      >
        <div class="text-center">
          <ItemToggler
            :is-active="normalAllocationEnabled"
            :id="id"
            @toggle="onToggleNormalAllocation($event.active, userId, id)"
          />
        </div>
      </template>

      <template #item-reset_cap="{ reset_cap, userId, id }">
        <div class="text-center">
          <ItemToggler
            :is-active="reset_cap"
            :id="id"
            @toggle="onToggleResetCap($event.active, userId, id)"
          />
        </div>
      </template>

      <template #item-blResetCap="{ blResetCap, userId, id }">
        <div class="text-center">
          <ItemToggler
            :is-active="blResetCap"
            :id="id"
            @toggle="onToggleBLResetCap($event.active, userId, id)"
          />
        </div>
      </template>

      <template #item-BLStatus="{ BLStatus, userId, id }">
        <div class="text-center">
          <ItemToggler
            :is-active="BLStatus"
            :id="id"
            @toggle="onToggleBlStatus($event.active, userId, id)"
          />
        </div>
      </template>

    </DataTable>

    <!-- Status Modal -->
    <x-modal
      v-model="statusModal.show"
      title="Select Reason of Unavailability"
      show-close
      backdrop
      @update:model-value="onStatusModalClose($event)"
    >
      <x-select
        v-model="statusModal.data.reason"
        placeholder="Select Reason"
        :options="[
          { value: 3, label: 'Temp. Unavailable' },
          { value: 4, label: 'Sick' },
          { value: 5, label: 'On Leave' },
        ]"
        @update:model-value="statusModal.data.reason = $event"
        class="w-full mb-28"
      />

      <template #actions>
        <div class="text-right space-x-4">
          <x-button size="sm" ghost @click.prevent="onStatusModalClose(false)">
            Cancel
          </x-button>
          <x-button
            size="sm"
            color="primary"
            :loading="statusModal.loader"
            @click="onStatusSubmit"
          >
            Submit
          </x-button>
        </div>
      </template>
    
    </x-modal>
  </div>
</template>

<style>
.labox {
  @apply rounded-lg bg-white shadow-md p-4 border-b-4 border-primary-500 text-center;
}

.labox h3 {
  @apply text-lg font-semibold text-gray-500;
}

.labox p {
  @apply text-2xl font-semibold my-1;
}
</style>

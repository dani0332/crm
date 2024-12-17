<script setup>
const page = usePage();

const refreshGrid = useStorage('refresh-user-counts');

const props = defineProps({
  data: {
    type: Array,
    default: () => [],
  },
  quoteType: String,
  totalAssignedLeadCount: {
    type: Number,
    default: 0,
  },
  availableUsers: {
    type: Number,
    default: 0,
  },
  unAvailableUsers: {
    type: Number,
    default: 0,
  },
  todayTotalLeadCount: {
    type: Number,
    default: 0,
  },
  todayTotalUnAssignedLeadCount: {
    type: Number,
    default: 0,
  },
});

const autoRefresh = ref(true);
const hasRole = role => useHasRole(role);
const hasAnyRole = role => useHasAnyRole(role);
const rolesEnum = page.props.rolesEnum;
const notification = useToast();

const statusModal = getStatusModal();

const loaders = reactive({
  submit: false,
  table: false,
});

const statusText = statusId => resolveUserStatusText(statusId);

const tableHeader = ref([
  { text: 'Name', value: 'userName', width: '240' },
  { text: 'Teams', value: 'teamNames', sortable: true },
  {
    text: 'Total Assigned Leads',
    value: 'allocationCount',
    sortable: true,
  },
  { text: 'Last Allocations', value: 'lastAllocation', sortable: true },
  { text: 'Max Cap Limit', value: 'maxCapacity', sortable: true },
  { text: 'Status', value: 'isAvailable', sortable: true, width: '100' },
  { text: 'Reset Cap', value: 'reset_cap', width: '100' },
]);

const leadData = ref([
  {
    id: 0,
    userId: 0,
    cap: 0,
    capEdit: false,
    status: '1',
    loading: false,
    reset: false,
  },
]);

const currentRow = id => {
  const row = leadData?.value.find(item => item.id === id);
  return row?.capEdit;
};

const editCap = id => {
  if (
    hasAnyRole([rolesEnum.Admin, rolesEnum.LeadPool, rolesEnum.Engineering])
  ) {
    const row = leadData?.value.find(item => item.id === id);
    row.capEdit = true;
  }
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

const onStatusModalClose = event => {
  const item = leadData?.value.find(item => item.id === statusModal?.data.id);
  if (!event) {
    item.reset = true;
    setTimeout(() => {
      item.reset = false;
    }, 300);
    statusModal.show = false;
  }
};

const onStatusSubmit = async () => {
  statusModal.loader = true;
  const item = leadData.value.find(item => item.id === statusModal.data.id);

  item.loading = true;
  await axios
    .post(`/lead-allocation/${page.props.quoteType}/update-availability`, [
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

const onToggleStatus = (status, id, userId) => {
  statusModal.data.id = id;
  statusModal.data.userId = userId;
  if (status) {
    statusModal.data.reason = 1;
    onStatusSubmit();
  } else {
    statusModal.data.reason = 3;
    statusModal.show = true;
  }
};

const onToggleResetCap = async (active, userId, leadAllocationId) => {
  loaders.table = true;
  await axios
    .post('/lead-allocation/toggle-reset-cap', {
      leadId: leadAllocationId,
      userId,
      resetCap: active,
    })
    .finally(() => {
      loaders.table = false;
    });
};

async function fetchData() {
  await router.reload({
    replace: true,
    preserveScroll: true,
    preserveState: true,
  });
}

const onSubmitChanges = async () => {
  loaders.submit = true;
  const max_cap = leadData?.value
    .filter(item => item.capEdit && item.cap !== item.maxCapacity)
    .map(item => {
      return {
        userId: item.userId,
        maxCap: item.cap,
      };
    });
  await axios
    .post(`/lead-allocation/${page.props.quoteType}/update-cap`, { max_cap })
    .then(response => {
      notification.success({
        title: response.data.message,
        position: 'top',
      });

      router.get(route('lead-allocation-dashboard', page.props.quoteType), {
        replace: true,
        preserveScroll: true,
        preserveState: true,
      });
    })
    .finally(() => {
      loaders.submit = false;
    });
};

const { pause, resume } = useTimeoutPoll(fetchData, 90000);

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

watch(
  () => refreshGrid.value,
  () => {
    setTimeout(() => {
      fetchData();
    }, 1500);
  },
);

onMounted(() => {
  tableHeader.value = tableHeader.value.filter(column => {
    if (
      !hasAnyRole([rolesEnum.Admin, rolesEnum.LeadPool, rolesEnum.Engineering])
    ) {
      return column.value !== 'reset_cap';
    }
    return column;
  });
  leadData.value = props.data.map(item => {
    return {
      id: item.id,
      userId: item.userId,
      cap: item.maxCapacity,
      capEdit: false,
      status: item.isAvailable,
    };
  });
});
</script>

<template>
  <div>
    <UserStatus />

    <Head :title="quoteType + ' Lead Allocation'" />
    <div class="flex justify-between items-center">
      <div></div>
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

    <div class="grid grid-cols-2 md:grid-cols-4 gap-5 my-6">
      <div class="labox border-green-500">
        <h3>Team</h3>
        <p>{{ quoteType }}</p>
      </div>
      <div class="labox border-primary-500">
        <h3>Assigned Lead Count</h3>
        <p>{{ props.totalAssignedLeadCount }}</p>
      </div>
      <div class="labox border-purple-500">
        <h3>Available / UnAvailable</h3>
        <p>{{ props.availableUsers }} / {{ props.unAvailableUsers }}</p>
      </div>
      <div class="labox border-yellow-500">
        <h3>Total Advisors</h3>
        <p>{{ data.length }}</p>
      </div>
      <div class="labox border-red-500">
        <h3>Unassigned Leads Count</h3>
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
            @click="onSubmitChanges"
          >
            Save Cap Changes
          </x-button>
        </div>
      </TransitionGroup>
    </div>

    <DataTable
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
      <template #item-maxCapacity="{ maxCapacity, id }">
        <div v-if="!currentRow(id)" @click="editCap(id)">
          {{ maxCapacity }}
        </div>
        <div v-else class="flex gap-1">
          <x-input
            type="number"
            :value="maxCapacity"
            class="w-16"
            @update:model-value="updateCap($event, id)"
          />
          <x-button
            icon="reset"
            size="sm"
            ghost
            @click="resetCap(id, maxCapacity)"
          />
        </div>
      </template>

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
            :id="id"
            :loading="leadData.find(item => item.id === id)?.loading"
            :refresh="leadData.find(item => item.id === id)?.reset"
            @toggle="onToggleStatus($event.active, id, userId)"
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
    </DataTable>

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

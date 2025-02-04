<script setup>
const page = usePage();

const refreshGrid = useStorage('refresh-user-counts');
const { isRequired } = useRules();
const params = useUrlSearchParams('history');

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
  isAutoAllocationWorking: {
    type: Number,
    default: 0,
  },
  isFIFO: {
    type: Number,
    default: 0,
  },
  userBLStatuses: {
    type: Array,
    default: () => [],
  },
});

const canManage = ref(props.isAutoAllocationWorking === 1 ? true : false);
const pickupSequence = ref(props.isFIFO === 1 ? true : false);
const autoRefresh = ref(true);
const hasRole = role => useHasRole(role);
const hasAnyRole = role => useHasAnyRole(role);
const rolesEnum = page.props.rolesEnum;

const confirmModal = reactive({
  show: false,
  title: '',
  type: 1,
  status: 1,
  loader: false,
});

const statusModal = getStatusModal();

const loaders = reactive({
  submit: false,
  table: false,
  search: false,
});

const statusText = statusId => resolveUserStatusText(statusId);

const tableHeader = ref([
  { text: 'Name', value: 'userName', sortable: true },
  { text: 'Tiers', value: 'tiers', width: '240' },
  { text: 'Quads', value: 'quads', sortable: true },
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

const currentRow = (id, type = 'normal') => {
  const row = leadData?.value.find(item => item.id === id);

  if (type === 'buy-lead') {
    return row?.BlCapEdit;
  } else {
    return row?.capEdit;
  }
};

const editCap = (id, type = 'normal') => {
  if (
    hasAnyRole([rolesEnum.Admin, rolesEnum.LeadPool, rolesEnum.Engineering])
  ) {
    const row = leadData?.value.find(item => item.id === id);

    if (type === 'buy-lead') {
      row.BlCapEdit = true;
    } else {
      row.capEdit = true;
    }
  }
};

const updateCap = (value, id, type = 'normal') => {
  const row = leadData?.value.find(item => item.id === id);

  if (type === 'buy-lead') {
    row.BlMaxcap = value;
  } else {
    row.cap = value;
  }
};

const resetCap = (id, maxCapacity, type = 'normal') => {
  const row = leadData?.value.find(item => item.id === id);

  if (type === 'buy-lead') {
    row.BlMaxcap = maxCapacity;
    row.BlCapEdit = false;
  } else {
    row.cap = maxCapacity;
    row.capEdit = false;
  }
};

const isCapChanged = computed(() => {
  return leadData?.value.some(item => item.capEdit);
});

const isBlCapChanged = computed(() => {
  return leadData?.value.some(item => item.BlCapEdit);
});

const onConfirmClose = event => {
  if (!event) {
    if (confirmModal.type === 1) {
      canManage.value = !canManage.value;
    } else if (confirmModal.type === 2) {
      pickupSequence.value = !pickupSequence.value;
    }

    confirmModal.show = false;
  }
};

const toggleOption = (value, type) => {
  confirmModal.type = type;
  confirmModal.status = value ? 1 : 0;
  confirmModal.title =
    type === 1 ? 'Car Lead Allocation' : 'CAR LEAD PICKUP FIFO';
  confirmModal.show = true;
};

const onUpdateConfirm = async () => {
  confirmModal.loader = true;
  const url =
    confirmModal.type === 1
      ? '/lead-allocation/toggle-car-lead-allocation-job-status'
      : '/lead-allocation/toggle-car-lead-fetch-sequence';
  await axios
    .post(url)
    .then(res => {
      router.reload({
        preserveScroll: true,
        preserveState: true,
      });
    })
    .finally(() => {
      confirmModal.loader = false;
      confirmModal.show = false;
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

const onToggleResetCap = async (active, userId, leadId) => {
  loaders.table = true;
  await axios
    .post('/lead-allocation/toggle-reset-cap', {
      leadId,
      userId,
      resetCap: active,
    })
    .finally(() => {
      loaders.table = false;
    });
};

const onToggleBlStatus = async (active, userId, leadId) => {
  loaders.table = true;
  await axios
    .post('/lead-allocation/toggle-bl-status', {
      leadId,
      userId,
      buyLeadStatus: active,
    })
    .finally(() => {
      loaders.table = false;
    });
};

const onToggleNormalAllocation = async (active, userId, laId) => {
  loaders.table = true;
  await axios
    .post('/lead-allocation/toggle-normal-allocation', {
      laId,
      userId,
      nlStatus: active,
    })
    .finally(() => {
      loaders.table = false;
    });
};

const onToggleBLResetCap = async (active, userId, laId) => {
  loaders.table = true;
  await axios
    .post('/lead-allocation/toggle-bl-reset-cap', {
      laId,
      userId,
      blResetCap: active,
    })
    .finally(() => {
      loaders.table = false;
    });
};

const onSubmitChanges = async type => {
  loaders.submit = true;
  let max_cap = leadData?.value;

  if (type === 'buy-lead') {
    max_cap = max_cap.filter(
      item => item.BlCapEdit && item.BlMaxcap !== item.BlMaxCapacity,
    );
  } else {
    max_cap = max_cap.filter(
      item => item.capEdit && item.cap !== item.maxCapacity,
    );
  }

  max_cap = max_cap.map(item => {
    return {
      userId: item.userId,
      maxCap: type === 'buy-lead' ? item.BlMaxcap : item.cap,
    };
  });
  await axios
    .post(`/lead-allocation/${page.props.quoteType}/update-cap`, {
      type,
      max_cap,
    })
    .then(() => {
      router.get('/lead-allocation/car', {
        replace: true,
        preserveScroll: true,
        preserveState: true,
      });
    })
    .finally(() => {
      loaders.submit = false;
    });
};

async function fetchData() {
  await router.reload({
    replace: true,
    preserveScroll: true,
    preserveState: true,
  });
}

const filters = reactive({
  userBlStatus: null,
});

function onReset() {
  router.visit(route('car-lead-allocation.index'), {
    method: 'get',
    data: {},
    preserveScroll: true,
    onBefore: () => (loaders.search = true),
    onSuccess: () => (loaders.search = false),
  });
}

const onSubmit = isValid => {
  if (isValid) {
    loaders.search = true;
    router.visit(route('car-lead-allocation.index'), {
      method: 'get',
      data: { ...filters },
      preserveState: true,
      preserveScroll: true,
      onBefore: () => (loaders.search = true),
      onFinish: () => (loaders.search = false),
    });
  }
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
      // router.reload({
      //   replace: false,
      //   preserveScroll: true,
      //   preserveState: true,
      // });
      router.get('/lead-allocation/car', {
        only: ['data'],
        preserveScroll: true,
        preserveState: true,
      });
    }, 1500);
  },
);

onMounted(() => {
  setQueryStringFilters(params, filters);
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
    <Head title="Car Lead Allocation" />
    <div class="flex justify-between items-center">
      <div
        class="flex gap-1"
        v-if="hasAnyRole([rolesEnum.Admin, rolesEnum.Engineering])"
      >
        <h2 class="text-lg font-semibold">Car Lead Allocation Management</h2>
        <x-toggle
          v-model="canManage"
          color="emerald"
          size="lg"
          @update:model-value="toggleOption($event, 1)"
        />
      </div>
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
        <h2 class="text-lg font-semibold">Pickup Sequence : FIFO</h2>
        <x-toggle
          v-model="pickupSequence"
          color="emerald"
          size="lg"
          @update:model-value="toggleOption($event, 2)"
        />
      </div>
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
        <p>Car</p>
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
            @click="() => onSubmitChanges()"
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
            @click="() => onSubmitChanges('buy-lead')"
          >
            Save Buy Lead Cap Changes
          </x-button>
        </div>
      </TransitionGroup>
    </div>

    <x-divider class="my-4" />
    <x-form @submit="onSubmit" :auto-focus="false">
      <div class="grid sm:grid-cols-2 gap-4">
        <x-field label="Buy Lead Status of Users" required>
          <x-select
            placeholder="Select Status"
            :options="userBLStatuses || []"
            filterable
            v-model="filters.userBlStatus"
            :rules="[isRequired]"
          ></x-select>
        </x-field>
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
          :loading="loaders.search"
          @click.prevent="onReset()"
        >
          Reset
        </x-button>
      </div>
    </x-form>

    <DataTable
      id="car-lead-allocation"
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
      <template #header-BLAllocationCount="header">
        <x-tooltip placement="top">
          <p class="underline decoration-dotted decoration-primary-600">
            {{ header.text }}
          </p>
          <template #tooltip>{{ header.tooltip }}</template>
        </x-tooltip>
      </template>

      <template #item-tiers="{ tiers }">
        <div class="relative">
          <x-tooltip placement="top">
            <p
              class="truncate w-60 underline decoration-dotted decoration-primary-600"
            >
              {{ tiers }}
            </p>
            <template #tooltip> {{ tiers }} </template>
          </x-tooltip>
        </div>
      </template>
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

      <template #item-BLMaxCapacity="{ BLMaxCapacity, id }">
        <div
          v-if="!currentRow(id, 'buy-lead')"
          @click="editCap(id, 'buy-lead')"
        >
          {{ BLMaxCapacity }}
        </div>
        <div v-else class="flex gap-1">
          <x-input
            type="number"
            :value="BLMaxCapacity"
            class="w-16"
            @update:model-value="updateCap($event, id, 'buy-lead')"
          />
          <x-button
            icon="reset"
            size="sm"
            ghost
            @click="resetCap(id, maxCapacity, 'buy-lead')"
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
            :disabled="!canManage"
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

      <template #item-BLStatus="{ BLStatus, userId, id }">
        <div class="text-center">
          <ItemToggler
            :is-active="BLStatus"
            :id="id"
            @toggle="onToggleBlStatus($event.active, userId, id)"
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

      <template #item-blResetCap="{ blResetCap, userId, id }">
        <div class="text-center">
          <ItemToggler
            :is-active="blResetCap"
            :id="id"
            @toggle="onToggleBLResetCap($event.active, userId, id)"
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

    <x-modal
      v-model="confirmModal.show"
      title="Status Change"
      show-close
      backdrop
      @update:model-value="onConfirmClose($event)"
    >
      <p>
        Are you sure you want to change
        <strong>{{ confirmModal.title }}</strong> status?
      </p>
      <template #actions>
        <div class="text-right space-x-4">
          <x-button size="sm" ghost @click.prevent="onConfirmClose(false)">
            Cancel
          </x-button>
          <x-button size="sm" color="primary" @click.prevent="onUpdateConfirm">
            Yes, confirmed!
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

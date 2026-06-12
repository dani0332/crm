<script setup>
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
  isAutoAllocationWorking: {
    type: Number,
    default: 0,
  },
  unAssignedGood: {
    type: Number,
    default: 0,
  },
  unAssignedBest: {
    type: Number,
    default: 0,
  },
  unAssignedEntryLevel: {
    type: Number,
    default: 0,
  },
  userBLStatuses: {
    type: Array,
    default: () => [],
  },
  totalUnassignedLeadsCount: {
    type: Number,
    default: 0,
  },
  /** False for view-only (see LeadAllocationPermissionService::userCanMutate). */
  canMutateLeadAllocation: {
    type: Boolean,
    default: false,
  },
});

const page = usePage();
const hasRole = role => useHasRole(role);
const hasAnyRole = role => useHasAnyRole(role);
const { isRequired } = useRules();
const params = useUrlSearchParams('history');
const rolesEnum = page.props.rolesEnum;

const canManage = ref(props.isAutoAllocationWorking === 1 ? true : false);
const autoRefresh = ref(true);
const leadData = ref([
  {
    id: 0,
    userId: 0,
    status: '1',
    loading: false,
    reset: false,
    BlMaxcap: 0,
    BlCapEdit: false,
  },
]);

const statusModal = getStatusModal();

const confirmModal = reactive({
  show: false,
  title: '',
  type: 1,
  status: 1,
  loader: false,
});

const loader = reactive({
  submit: false,
  table: false,
  search: false,
});

const isBlCapChanged = computed(() => {
  return leadData?.value.some(item => item.BlCapEdit);
});

const statusText = statusId => resolveUserStatusText(statusId);

const currentRow = (id, type = 'mormal') => {
  const row = leadData?.value.find(item => item.id === id);
  if (type === 'buy-lead') {
    return row?.BlCapEdit;
  } else {
    return row?.capEdit;
  }
};

const editCap = (id, type = 'normal') => {
  if (
    hasAnyRole([rolesEnum.Admin, rolesEnum.LeadPool, rolesEnum.Engineering]) ||
    props.canMutateLeadAllocation
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

const toggleOption = (value, type) => {
  if (!props.canMutateLeadAllocation) {
    return;
  }
  confirmModal.type = type;
  confirmModal.status = value ? 1 : 0;
  confirmModal.title = 'Health Lead Allocation';
  confirmModal.show = true;
};

const onConfirmClose = event => {
  if (!event) {
    if (confirmModal.type === 1) {
      canManage.value = !canManage.value;
    }
    confirmModal.show = false;
  }
};

const onUpdateConfirm = async () => {
  confirmModal.loader = true;
  const url = '/lead-allocation/toggle-lead-allocation-job-status';
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

const tableHeader = ref([
  { text: 'Name', value: 'userName', width: '240', tooltip: "Advisor's name" },
  {
    text: 'Team Type',
    value: 'teamName',
    sortable: true,
    tooltip: "Advisor's team type",
  },
  {
    text: 'IM Total Assigned Leads',
    value: 'im_total_assigned_leads',
    sortable: true,
    tooltip: "Advisor's IM lead count",
  },
  {
    text: 'Total Assigned Leads',
    value: 'allocation_count',
    sortable: true,
    tooltip: 'All assigned leads per advisor',
  },
  {
    text: 'Last Allocations',
    value: 'last_allocated',
    sortable: true,
    tooltip: 'Last lead assigned date & time',
  },
  {
    text: 'Max Cap Limit',
    value: 'max_capacity',
    sortable: true,
    tooltip: 'Daily lead cap limit',
  },
  {
    text: 'Status',
    value: 'is_available',
    sortable: true,
    width: '100',
    tooltip: 'Advisor system status',
  },
  {
    text: 'Norm Allo.',
    value: 'normalAllocationEnabled',
    sortable: true,
    width: '100',
    tooltip: 'Standard (non-buy leads) allocation',
  },
  {
    text: 'Reset Cap',
    value: 'reset_cap',
    sortable: true,
    width: '100',
    tooltip: 'Daily lead cap reset',
  },
  {
    text: 'BL Cap Limit',
    value: 'BLMaxCapacity',
    sortable: true,
    width: '100',
    tooltip: "Advisor's buy lead cap",
  },
  {
    text: 'BL Status',
    value: 'BLStatus',
    sortable: true,
    width: '100',
    tooltip: "Advisor's buy lead status",
  },
  {
    text: 'BL Assigned',
    value: 'BLAllocationCount',
    sortable: true,
    width: '100',
    tooltip: 'Assigned buy leads count',
  },
  {
    text: 'BL Reset CAP',
    value: 'blResetCap',
    sortable: true,
    width: '100',
    tooltip: 'Daily buy cap reset',
  },
]);

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

const onSubmitChanges = async (type = 'normal') => {
  if (!props.canMutateLeadAllocation) {
    return;
  }
  loader.submit = true;
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
      max_cap: type === 'buy-lead' ? item.BlMaxcap : item.cap,
      id: item.id,
      team_type: 'health',
      type: type,
    };
  });

  await axios
    .post(
      `/lead-allocation/${page.props.quoteType}/update-availability`,
      max_cap,
    )
    .then(() => {
      router.get('/lead-allocation', {
        replace: true,
        preserveScroll: true,
        preserveState: true,
      });
    })
    .finally(() => {
      loader.submit = false;
    });
};

const onToggleResetCap = async (active, userId, leadId) => {
  loader.submit = true;
  await axios
    .post('/lead-allocation/toggle-reset-cap', {
      leadId,
      userId,
      resetCap: active,
    })
    .finally(() => {
      loader.submit = false;
    });
};

onMounted(() => {
  setQueryStringFilters(params, filters);
  leadData.value = props.data.map(item => {
    return {
      id: item.id,
      userId: item.userId,
      cap: item.max_capacity,
      capEdit: false,
      status: item.is_available,
      BlMaxcap: item.BLMaxCapacity,
      BlCapEdit: false,
    };
  });
});

const onToggleBlStatus = async (active, userId, leadId) => {
  loader.table = true;
  await axios
    .post('/lead-allocation/toggle-bl-status', {
      leadId,
      userId,
      buyLeadStatus: active,
    })
    .finally(() => {
      loader.table = false;
    });
};

const onToggleNormalAllocation = async (active, userId, laId) => {
  loader.table = true;
  await axios
    .post('/lead-allocation/toggle-normal-allocation', {
      laId,
      userId,
      nlStatus: active,
    })
    .finally(() => {
      loader.table = false;
    });
};

const onToggleBLResetCap = async (active, userId, laId) => {
  loader.table = true;
  await axios
    .post('/lead-allocation/toggle-bl-reset-cap', {
      laId,
      userId,
      blResetCap: active,
    })
    .finally(() => {
      loader.table = false;
    });
};

const filters = reactive({
  userBlStatus: null,
});

function onReset() {
  router.visit(route('lead-allocation.index'), {
    method: 'get',
    data: {},
    preserveScroll: true,
    onBefore: () => (loader.search = true),
    onSuccess: () => (loader.search = false),
  });
}

const onSubmit = isValid => {
  if (isValid) {
    loader.search = true;
    router.visit(route('lead-allocation.index'), {
      method: 'get',
      data: { ...filters },
      preserveState: true,
      preserveScroll: true,
      onBefore: () => (loader.search = true),
      onFinish: () => (loader.search = false),
    });
  }
};

// Real-time polling for unassigned leads count
async function fetchData() {
  await router.reload({
    replace: true,
    preserveScroll: true,
    preserveState: true,
  });
}

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

// Watch for changes in props.data to update leadData
watch(
  () => props.data,
  newData => {
    if (newData && newData.length > 0) {
      leadData.value = newData.map(item => {
        return {
          id: item.id,
          userId: item.userId,
          cap: item.max_capacity,
          capEdit: false,
          status: item.is_available,
          BlMaxcap: item.BLMaxCapacity,
          BlCapEdit: false,
        };
      });
    }
  },
  { deep: true },
);
</script>
<template>
  <Head title="Health Lead Allocation" />
  <div class="flex justify-between items-center">
    <div class="flex gap-1" v-if="hasAnyRole([rolesEnum.Admin])">
      <h2 class="text-lg font-semibold">Health Lead Allocation Management</h2>
      <x-toggle
        v-model="canManage"
        color="emerald"
        size="lg"
        :disabled="!canMutateLeadAllocation"
        @update:model-value="toggleOption($event, 1)"
      />
    </div>
    <div
      class="flex gap-1"
      v-if="
        hasAnyRole([rolesEnum.Admin, rolesEnum.LeadPool, rolesEnum.Engineering])
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
      <p>Health</p>
    </div>
    <div class="labox border-primary-500">
      <h3>Assigned Lead Count</h3>
      <p>{{ totalAssignedLeadCount ?? 0 }}</p>
    </div>
    <div class="labox border-[#3015ca]">
      <h3>Available / UnAvailable</h3>
      <p>{{ availableUsers }} / {{ unAvailableUsers }}</p>
    </div>
    <div class="labox border-yellow-500">
      <h3>Total Advisors</h3>
      <p>{{ unAvailableUsers + availableUsers ?? 0 }}</p>
    </div>
    <div class="labox border-red-500">
      <h3>Total Unassigned Leads</h3>
      <p>{{ totalUnassignedLeadsCount ?? 0 }}</p>
    </div>
  </div>
  <div class="mt-5 mb-5">
    <h2 class="text-lg font-semibold">Cap Changes</h2>
    <div class="grid grid-cols-2 md:grid-cols-4 w-full gap-5">
      <TransitionGroup name="fade">
        <div v-if="isCapChanged" class="col-span-2">
          <x-alert type="info" light>For Unlimited Capactiy Add ( -1 )</x-alert>
        </div>
        <div v-if="isCapChanged" class="col-span-2">
          <x-button
            color="emerald"
            :loading="loader.submit"
            block
            :disabled="!canMutateLeadAllocation"
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
            :loading="loader.submit"
            block
            :disabled="!canMutateLeadAllocation"
            @click="() => onSubmitChanges('buy-lead')"
          >
            Save Buy Lead Cap Changes
          </x-button>
        </div>
      </TransitionGroup>
    </div>
  </div>
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
      <x-button size="md" color="orange" type="submit" :loading="loader.search">
        Search
      </x-button>
      <x-button
        size="md"
        color="primary"
        type="submit"
        :loading="loader.search"
        @click.prevent="onReset()"
      >
        Reset
      </x-button>
    </div>
  </x-form>
  <DataTable
    id="car-lead-allocation"
    table-class-name="compact"
    :loading="loader.table"
    :headers="tableHeader"
    :items="props.data || []"
    :sort-by="'userName'"
    :sort-type="'asc'"
    :rows-per-page="999"
    border-cell
    hide-rows-per-page
    hide-footer
  >
    <template #header-userName="header">
      <x-tooltip placement="top">
        <p class="underline decoration-dotted decoration-primary-600">
          {{ header.text }}
        </p>
        <template #tooltip>{{ header.tooltip }}</template>
      </x-tooltip>
    </template>

    <template #header-teamName="header">
      <x-tooltip placement="top">
        <p class="underline decoration-dotted decoration-primary-600">
          {{ header.text }}
        </p>
        <template #tooltip>{{ header.tooltip }}</template>
      </x-tooltip>
    </template>

    <template #header-im_total_assigned_leads="header">
      <x-tooltip placement="top">
        <p class="underline decoration-dotted decoration-primary-600">
          {{ header.text }}
        </p>
        <template #tooltip>{{ header.tooltip }}</template>
      </x-tooltip>
    </template>

    <template #header-allocation_count="header">
      <x-tooltip placement="top">
        <p class="underline decoration-dotted decoration-primary-600">
          {{ header.text }}
        </p>
        <template #tooltip>{{ header.tooltip }}</template>
      </x-tooltip>
    </template>

    <template #header-last_allocated="header">
      <x-tooltip placement="top">
        <p class="underline decoration-dotted decoration-primary-600">
          {{ header.text }}
        </p>
        <template #tooltip>{{ header.tooltip }}</template>
      </x-tooltip>
    </template>

    <template #header-max_capacity="header">
      <x-tooltip placement="top">
        <p class="underline decoration-dotted decoration-primary-600">
          {{ header.text }}
        </p>
        <template #tooltip>{{ header.tooltip }}</template>
      </x-tooltip>
    </template>

    <template #header-is_available="header">
      <x-tooltip placement="top">
        <p class="underline decoration-dotted decoration-primary-600">
          {{ header.text }}
        </p>
        <template #tooltip>{{ header.tooltip }}</template>
      </x-tooltip>
    </template>

    <template #header-normalAllocationEnabled="header">
      <x-tooltip placement="top">
        <p class="underline decoration-dotted decoration-primary-600">
          {{ header.text }}
        </p>
        <template #tooltip>{{ header.tooltip }}</template>
      </x-tooltip>
    </template>

    <template #header-reset_cap="header">
      <x-tooltip placement="top">
        <p class="underline decoration-dotted decoration-primary-600">
          {{ header.text }}
        </p>
        <template #tooltip>{{ header.tooltip }}</template>
      </x-tooltip>
    </template>

    <template #header-BLMaxCapacity="header">
      <x-tooltip placement="top">
        <p class="underline decoration-dotted decoration-primary-600">
          {{ header.text }}
        </p>
        <template #tooltip>{{ header.tooltip }}</template>
      </x-tooltip>
    </template>

    <template #header-BLStatus="header">
      <x-tooltip placement="top">
        <p class="underline decoration-dotted decoration-primary-600">
          {{ header.text }}
        </p>
        <template #tooltip>{{ header.tooltip }}</template>
      </x-tooltip>
    </template>

    <template #header-BLAllocationCount="header">
      <x-tooltip placement="top">
        <p class="underline decoration-dotted decoration-primary-600">
          {{ header.text }}
        </p>
        <template #tooltip>{{ header.tooltip }}</template>
      </x-tooltip>
    </template>

    <template #header-blResetCap="header">
      <x-tooltip placement="top">
        <p class="underline decoration-dotted decoration-primary-600">
          {{ header.text }}
        </p>
        <template #tooltip>{{ header.tooltip }}</template>
      </x-tooltip>
    </template>

    <template #item-max_capacity="{ max_capacity, id }">
      <div v-if="!currentRow(id)" @click="editCap(id)">
        {{ max_capacity }}
      </div>
      <div v-else class="flex gap-1">
        <x-input
          type="number"
          :value="max_capacity"
          class="w-16"
          @update:model-value="updateCap($event, id)"
        />
        <x-button
          icon="reset"
          size="sm"
          ghost
          @click="resetCap(id, max_capacity)"
        />
      </div>
    </template>
    <template #item-BLMaxCapacity="{ BLMaxCapacity, id }">
      <div v-if="!currentRow(id, 'buy-lead')" @click="editCap(id, 'buy-lead')">
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
    <template #item-is_available="{ is_available, id, userId }">
      <div class="flex flex-col gap-1.5 items-center">
        <x-tag
          size="xs"
          :color="
            ['emerald', 'red', 'gray', 'yellow', 'yellow', 'gray'][
              +is_available - 1
            ]
          "
        >
          {{ statusText(is_available) }}
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
          :disabled="!canManage || !canMutateLeadAllocation"
          :id="id"
          @toggle="onToggleStatus($event.active, id, userId)"
          :loading="leadData.find(item => item.id === id)?.loading"
          :refresh="leadData.find(item => item.id === id)?.reset"
        />
      </div>
    </template>
    <template #item-last_allocated="{ last_allocated }">
      <div class="text-center">
        {{ new Date(last_allocated * 1000).toLocaleString() }}
      </div>
    </template>
    <template #item-reset_cap="{ reset_cap, userId, id }">
      <div class="text-center">
        <ItemToggler
          :is-active="reset_cap"
          :id="id"
          :disabled="!canMutateLeadAllocation"
          @toggle="onToggleResetCap($event.active, userId, id)"
        />
      </div>
    </template>
    <template #item-BLStatus="{ BLStatus, userId, id }">
      <div class="text-center">
        <ItemToggler
          :is-active="BLStatus"
          :id="id"
          :disabled="!canMutateLeadAllocation"
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
          :disabled="!canMutateLeadAllocation"
          @toggle="onToggleNormalAllocation($event.active, userId, id)"
        />
      </div>
    </template>

    <template #item-blResetCap="{ blResetCap, userId, id }">
      <div class="text-center">
        <ItemToggler
          :is-active="blResetCap"
          :id="id"
          :disabled="!canMutateLeadAllocation"
          @toggle="onToggleBLResetCap($event.active, userId, id)"
        />
      </div>
    </template>
  </DataTable>

  <x-modal
    v-model="statusModal.show"
    title="Select Reason of Unavailability "
    show-close
    backdrop
    @update:model-value="onStatusModalClose($event)"
  >
    <x-select
      placeholder="Select Reason"
      :options="[
        { value: 3, label: 'Temp. Unavailable' },
        { value: 4, label: 'Sick' },
        { value: 5, label: 'On Leave' },
      ]"
      class="w-full mb-28"
      v-model="statusModal.data.reason"
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
</template>

<script setup>
const page = usePage();

const refreshGrid = useStorage('refresh-user-counts');

const props = defineProps({
  data: {
    type: Array,
    default: () => [],
  },
  assignedCountsByLob: {
    type: Object,
    default: () => ({}),
  },
  availableUsers: {
    type: Number,
    default: 0,
  },
  unAvailableUsers: {
    type: Number,
    default: 0,
  },
  unassignedCountsByLob: {
    type: Object,
    default: () => ({}),
  },
  canMutatePqaAllocation: {
    type: Boolean,
    default: false,
  },
});

const autoRefresh = ref(true);
const hasRole = role => useHasRole(role);
const hasAnyRole = role => useHasAnyRole(role);
const rolesEnum = page.props.rolesEnum;
const notification = useToast();

const statusModal = getStatusModal();

const selectedLob = ref('');

const displayLobName = code => (code === 'CorpLine' ? 'CorpLine' : code);

const lobFilterOptions = computed(() => {
  const codes = [...new Set(props.data.map(item => item.quoteTypeCode))].sort();
  return [
    { value: '', label: 'All' },
    ...codes.map(code => ({ value: code, label: displayLobName(code) })),
  ];
});

const filteredData = computed(() => {
  if (!selectedLob.value) return props.data;
  return props.data.filter(item => item.quoteTypeCode === selectedLob.value);
});

const filteredAssignedCount = computed(() => {
  if (!selectedLob.value) return props.assignedCountsByLob?.total ?? 0;
  return props.assignedCountsByLob?.[selectedLob.value] ?? 0;
});

const filteredAvailableUsers = computed(
  () => displayData.value.filter(item => item.isAvailable == 1).length,
);

const filteredUnavailableUsers = computed(
  () => displayData.value.filter(item => item.isAvailable != 1).length,
);

const filteredUnassignedCount = computed(() => {
  if (!selectedLob.value) return props.unassignedCountsByLob?.total ?? 0;
  return props.unassignedCountsByLob?.[selectedLob.value] ?? 0;
});

const displayData = computed(() => {
  if (!selectedLob.value) {
    const groupMap = new Map();
    filteredData.value.forEach(item => {
      if (!groupMap.has(item.userId)) {
        groupMap.set(item.userId, {
          id: item.id,
          userId: item.userId,
          userName: item.userName,
          lobs: [item.quoteTypeCode],
          allocationCount: item.allocationCount ?? 0,
          lastAllocation: item.lastAllocation,
          isAvailable: item.isAvailable,
          maxCapacity: item.maxCapacity,
          reset_cap: item.reset_cap,
        });
      } else {
        const group = groupMap.get(item.userId);
        group.lobs.push(item.quoteTypeCode);
        group.allocationCount += item.allocationCount ?? 0;
        if (item.lastAllocation > group.lastAllocation) {
          group.lastAllocation = item.lastAllocation;
        }
      }
    });
    return Array.from(groupMap.values());
  }
  return filteredData.value;
});

const loaders = reactive({
  submit: false,
  table: false,
});

const statusText = statusId => resolveUserStatusText(statusId);

const tableHeader = ref([
  { text: 'Name', value: 'userName', width: '240' },
  { text: 'Line of Business', value: 'quoteTypeCode', width: '160' },
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

const visibleHeaders = computed(() => {
  const lobSpecificColumns = ['maxCapacity', 'reset_cap'];
  return tableHeader.value.filter(col => {
    if (!selectedLob.value && lobSpecificColumns.includes(col.value)) {
      return false;
    }
    return true;
  });
});

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
    hasAnyRole([rolesEnum.Admin, rolesEnum.LeadPool, rolesEnum.Engineering]) ||
    props.canMutatePqaAllocation
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
    .post(`/pqa-allocation/update-availability`, {
      items: [
        {
          userId: statusModal.data.userId,
          id: statusModal.data.id,
          reason: statusModal.data.reason,
        },
      ],
    })
    .then(res => {
      router.reload({
        only: ['data'],
        preserveScroll: true,
        preserveState: true,
      });
      notification.success({
        title: res.data.message,
        position: 'top',
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
    .post(`/pqa-allocation/toggle-reset-cap`, {
      items: [
        {
          userId: userId,
          id: leadAllocationId,
          resetCap: active,
        },
      ],
    })
    .then(res => {
      notification.success({
        title: res.data.message,
        position: 'top',
      });
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
        id: item.id,
        maxCap: item.cap,
      };
    });
  await axios
    .post(`/pqa-allocation/update-cap`, { items: max_cap })
    .then(response => {
      notification.success({
        title: response.data.message,
        position: 'top',
      });

      router.get(route('pqa-lead-allocation-dashboard'), {
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
      !hasAnyRole([
        rolesEnum.Admin,
        rolesEnum.LeadPool,
        rolesEnum.Engineering,
      ]) &&
      !props.canMutatePqaAllocation
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
      maxCapacity: item.maxCapacity,
    };
  });
});
</script>

<template>
  <div>
    <UserStatus />

    <Head :title="'Pre Qualification Advisor (ILA)'" />
    <div class="flex justify-between items-center">
      <div class="flex items-center gap-2">
        <span class="text-sm font-medium text-gray-600">Line of Business:</span>
        <x-select
          v-model="selectedLob"
          :options="lobFilterOptions"
          placeholder="All"
          class="w-40"
        />
      </div>
      <div
        class="flex gap-1"
        v-if="
          hasAnyRole([
            rolesEnum.Admin,
            rolesEnum.LeadPool,
            rolesEnum.Engineering,
          ]) || canMutatePqaAllocation
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
        <p>Pre Qualification</p>
      </div>
      <div class="labox border-primary-500">
        <h3>Assigned Lead Count (today)</h3>
        <p>{{ filteredAssignedCount }}</p>
      </div>
      <div class="labox border-purple-500">
        <h3>Available / UnAvailable</h3>
        <p>{{ filteredAvailableUsers }} / {{ filteredUnavailableUsers }}</p>
      </div>
      <!-- <div class="labox border-yellow-500">
        <h3>Total Advisors</h3>
        <p>{{ filteredData.length }}</p>
      </div> -->
      <div class="labox border-red-500">
        <h3>Unassigned Leads Count (today)</h3>
        <p>{{ filteredUnassignedCount }}</p>
      </div>
      <!-- <div class="labox border-slate-300 col-span-2">
        <h3>Leads today (total)</h3>
        <p>{{ props.todayTotalLeadCount }}</p>
      </div> -->

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
      :headers="visibleHeaders"
      :items="displayData"
      :sort-by="'userName'"
      :sort-type="'asc'"
      :rows-per-page="999"
      border-cell
      hide-rows-per-page
      hide-footer
    >
      <template #item-quoteTypeCode="{ quoteTypeCode, lobs }">
        <div class="flex flex-wrap gap-1">
          <span
            v-for="lob in (lobs ?? [quoteTypeCode])"
            :key="lob"
            class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-700"
          >
            {{ displayLobName(lob) }}
          </span>
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
              ]) || canMutatePqaAllocation
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
            v-if="
              hasAnyRole([
                rolesEnum.Admin,
                rolesEnum.LeadPool,
                rolesEnum.Engineering,
              ]) || canMutatePqaAllocation
            "
            :is-active="reset_cap"
            :id="id"
            @toggle="onToggleResetCap($event.active, userId, id)"
          />
          <span v-else class="text-sm text-gray-400">—</span>
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
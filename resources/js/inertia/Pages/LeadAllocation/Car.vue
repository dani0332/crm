<script setup>
const notification = useToast();

const props = defineProps({
  data: {
    type: Array,
    default: () => [],
  },
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

const canManage = ref(true);
const pickupSequence = ref(true);
const autoRefresh = ref(false);

const confirmModal = reactive({
  show: false,
  title: '',
  type: 1,
  status: 1,
  loader: false,
});

const statusModal = reactive({
  show: false,
  loader: false,
  data: {
    id: 0,
    userId: 0,
    reason: 1,
    loader: false,
  },
});

const loaders = reactive({
  submit: false,
  table: false,
});

const statusText = statusId => {
  switch (parseInt(statusId)) {
    case 1:
      return 'Online';
    case 2:
      return 'Offline';
    case 3:
      return 'Unavailable';
    case 4:
      return 'Sick';
    case 5:
      return 'On leave';
    default:
      return 'Unavailable';
  }
};

const tableHeader = ref([
  { text: 'Name', value: 'userName', sortable: true },
  { text: 'Tiers', value: 'tiers' },
  { text: 'Quads', value: 'quads', sortable: true },
  { text: 'Total Assigned', value: 'allocationCount' },
  { text: 'Manual Assigned', value: 'manualAllocationCount' },
  { text: 'Auto Assigned', value: 'autoAllocationCount' },
  { text: 'Max Cap Limit', value: 'maxCapacity' },
  { text: 'Status', value: 'isAvailable' },
  { text: 'Last Login', value: 'lastLogin' },
  { text: 'Reset Cap', value: 'reset_cap' },
]);

const leadData = ref([{ id: 0, userId: 0, cap: 0, capEdit: false, status: 0 }]);

const currentRow = id => {
  const row = leadData?.value.find(item => item.id === id);
  return row?.capEdit;
};

const editCap = id => {
  const row = leadData?.value.find(item => item.id === id);
  row.capEdit = true;
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

const onToggleStatus = (status, id, userId) => {
  console.log(status, id, userId);
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

const onStatusSubmit = async () => {
  statusModal.loader = true;
  loaders.table = true;
  await axios
    .post('/lead-allocation/update-availability', {
      userId: statusModal.data.userId,
      id: statusModal.data.id,
      reason: statusModal.data.reason,
    })
    .then(res => {
      router.reload({
        only: ['data'],
        preserveScroll: true,
        preserveState: true,
      });
    })
    .finally(() => {
      statusModal.loader = false;
      loaders.table = false;
      statusModal.show = false;
    });
};

const onToggleResetCap = ({ id, active }) => {
  console.log(id, active);
};

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
    .post('/lead-allocation/update-cap', { max_cap })
    .then(res => {
      router.get('/lead-allocation/car', {
        replace: false,
        preserveScroll: true,
      });
    })
    .finally(() => {
      loaders.submit = false;
    });
};

onMounted(() => {
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
      <div class="flex gap-1">
        <h2 class="text-lg font-semibold">Car Lead Allocation Management</h2>
        <x-toggle
          v-model="canManage"
          color="emerald"
          size="lg"
          @update:model-value="toggleOption($event, 1)"
        />
      </div>
      <div class="flex gap-1">
        <h2 class="text-lg font-semibold">Pickup Sequence : FIFO</h2>
        <x-toggle
          v-model="pickupSequence"
          color="emerald"
          size="lg"
          @update:model-value="toggleOption($event, 2)"
        />
      </div>
      <div class="flex gap-1">
        <h2 class="text-lg font-semibold">Auto Refresh :</h2>
        <x-toggle
          v-model="autoRefresh"
          color="emerald"
          size="lg"
          @update:model-value="toggleOption($event, 3)"
        />
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
            @click="onSubmitChanges"
          >
            Save Cap Changes
          </x-button>
        </div>
      </TransitionGroup>
    </div>

    <DataTable
      table-class-name="compact"
      :loading="false"
      :headers="tableHeader"
      :items="props.data || []"
      :sort-by="'userName'"
      :sort-type="'asc'"
      :loader="loaders.table"
      border-cell
      hide-rows-per-page
      hide-footer
      alternating
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
        <div class="text-center space-y-2">
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
            :is-active="isAvailable === '1' ? 1 : 0"
            :disabled="!canManage"
            :id="id"
            @toggle="onToggleStatus($event.active, id, userId)"
          />
        </div>
      </template>

      <template #item-reset_cap="{ reset_cap, id }">
        <div class="text-center">
          <ItemToggler
            :is-active="reset_cap"
            :id="id"
            @toggle="onToggleResetCap"
          />
        </div>
      </template>
    </DataTable>

    <x-modal v-model="statusModal.show" show-close backdrop>
      <template #header> Select Reason of Unavailability </template>
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
          <x-button size="sm" ghost @click.prevent="statusModal.show = false">
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
      show-close
      backdrop
      @update:model-value="onConfirmClose($event)"
    >
      <template #header> Status Change </template>
      <p>Are you sure you want to change {{ confirmModal.title }} status?</p>
      <template #actions>
        <div class="text-right space-x-4">
          <x-button size="sm" ghost @click.prevent="onConfirmClose(false)">
            Cancel
          </x-button>
          <x-button size="sm" color="primary"> Yes </x-button>
        </div>
      </template>
    </x-modal>
  </div>
</template>

<style>
.labox {
  @apply rounded-lg bg-white shadow-md p-4 border-l-4 border-primary-500 text-center;
  > h3 {
    @apply text-lg font-semibold text-gray-500;
  }
  > p {
    @apply text-2xl font-semibold my-1;
  }
}
</style>

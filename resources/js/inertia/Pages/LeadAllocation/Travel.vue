<script setup>
const page = usePage();

const refreshGrid = useStorage('refresh-user-counts');

const props = defineProps({
  userBLStatuses: {
    type: Array,
    default: () => []
  },
  data: {
    type: Array,
    default: () => [],
  },
});

const hasRole = role => useHasRole(role);
const hasAnyRole = role => useHasAnyRole(role);
const rolesEnum = page.props.rolesEnum;
const notification = useToast();
const loading = ref(false);

const canManage = computed(
  () =>
    !loading.value &&
    hasAnyRole([rolesEnum.Admin, rolesEnum.LeadPool, rolesEnum.Engineering]),
);

const statusText = isHardStop => {
  return isHardStop ? 'Active' : 'Inactive';
};

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

const onToggleStatus = async (status, userId) => {
  loading.value = true;
  try {
    const response = await axios.post(
      `/travel-lead-allocation/update-hard-stop`,
      {
        userId: userId,
        status: status,
      },
    );

    notification.success({
      title: response.data.message,
      position: 'top',
    });

    router.reload({
      only: ['data'],
      preserveScroll: true,
      preserveState: true,
    });
  } catch (error) {
    console.error('Error updating hard stop:', error);

    notification.error({
      title: 'Error',
      description: 'Failed to update hard stop. Please try again later.',
      position: 'top',
    });
  } finally {
    loading.value = false;
  }
};

const filters = reactive({
  userBLStatuse: null
});

const loaders = reactive({
  submit: false,
  table: false,
  search: false,
});

function onReset() {
  router.visit('travel-lead-allocation', {
    method: 'get',
    data: {},
    preserveScroll: true,
    onBefore: () => (loaders.search = true),
    onSuccess: () => (loaders.search = false)
  });
}

const userData = ref([
  {
    userId: 0,
    isHardStop: false,
  },
]);

onMounted(() => {
  tableHeader.value = tableHeader.value.filter(column => column);

  if (props.data && Array.isArray(props.data)) {
    userData.value = props.data.map(item => ({
        userId: item.userId,
        isHardStop: item.isHardStop,
    }));
  } else {
    userData.value = [];

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
        <p>3</p>
      </div>
      <div class="labox border-yellow-500">
        <h3>Available / UnAvailable</h3>
        <p>3</p>
      </div>
      <div class="labox border-yellow-500">
        <h3>Total UnAssigned Leads</h3>
        <p>1</p>
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
          v-model="filters.userBLStatuse"
          filterable
          :rules="[isRequired]"
        ></x-select>
      </div>
      <div class="flex justify-end gap-3 mb-4">
        <x-button
          size="md"
          color="orange"
          type="submit"
        >
          Search
        </x-button>
        <x-button
          size="md"
          color="primary"
          type="submit"
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
      <template #item-isHardStop="{ isHardStop, userId }">
        <div class="flex flex-col gap-1.5 items-center">
          <x-tag size="xs" :color="isHardStop ? 'emerald' : 'gray'">
            {{ statusText(isHardStop) }}
          </x-tag>

          <ItemToggler
            v-if="
              hasAnyRole([
                rolesEnum.Admin,
                rolesEnum.LeadPool,
                rolesEnum.Engineering,
              ])
            "
            :is-active="isHardStop"
            :disabled="!canManage"
            :id="userId"
            @toggle="onToggleStatus($event.active, userId)"
          />
        </div>
      </template>
    </DataTable>
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

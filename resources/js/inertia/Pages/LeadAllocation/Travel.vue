<script setup>
const page = usePage();

const refreshGrid = useStorage('refresh-user-counts');

const props = defineProps({
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
  {
    text: 'Hard Stop Lead Allocation',
    value: 'isHardStop',
    sortable: true,
    width: '100',
  },
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

const userData = ref([
  {
    userId: 0,
    isHardStop: false,
  },
]);

onMounted(() => {
  tableHeader.value = tableHeader.value.filter(column => {
    return column;
  });
  if (props.data && Array.isArray(props.data)) {
    userData.value = props.data.map(item => {
      return {
        userId: item.userId,
        isHardStop: item.isHardStop,
      };
    });
  } else {
    userData.value = [];
    console.error('Error: Something went wrong while fetching data in Travel.');
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

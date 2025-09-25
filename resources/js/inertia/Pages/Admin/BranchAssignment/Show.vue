<script setup>
const dateFormat = date => useDateFormat(date, 'DD-MM-YYYY').value;

const props = defineProps({
  user: Object,
});

const activeTableHeader = ref([
  { text: 'Record ID', value: 'id' },
  { text: 'Branch', value: 'branch' },
  { text: 'Is Primary', value: 'is_primary' },
  { text: 'Effective From', value: 'effective_from' },
  { text: 'Effective To', value: 'effective_to' },
  { text: 'Actions', value: 'actions' },
]);

const historicalTableHeader = ref([
  { text: 'Record ID', value: 'id' },
  { text: 'Branch', value: 'branch' },
  { text: 'Is Primary', value: 'is_primary' },
  { text: 'Effective From', value: 'effective_from' },
  { text: 'Effective To', value: 'effective_to' },
  { text: 'Status', value: 'status' },
]);

const activeUserBranches = computed(() => {
  return props.user.user_branches
    ? props.user.user_branches.filter(branch => branch.status === 1)
    : [];
});

const historicalUserBranches = computed(() => {
  return props.user.user_branches
    ? props.user.user_branches.filter(branch => branch.status === 0)
    : [];
});
</script>
<template>
  <Head title="Advisor Branch Assignment Detail" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">Advisor Branch Assignment Detail</h2>
    <div class="space-x-3">
      <Link :href="route('branch-assignments.index')">
        <x-button size="sm" color="#1d83bc" tag="div">
          Advisor Branch Assignment List
        </x-button>
      </Link>
      <Link :href="route('branch-assignments.create', user.id)">
        <x-button size="sm" color="#ff5e00" tag="div"> Add Branch </x-button>
      </Link>
    </div>
  </div>
  <x-divider class="my-4" />
  <div class="p-4 rounded shadow mb-6 bg-white">
    <div class="text-sm">
      <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">ID</dt>
          <dd>{{ user.id ?? 'N/A' }}</dd>
        </div>

        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Name</dt>
          <dd>{{ user.name ?? 'N/A' }}</dd>
        </div>
      </dl>
    </div>
  </div>

  <div class="p-4 rounded shadow mb-6 bg-white">
    <Collapsible :expanded="true">
      <template #header>
        <div>
          <h3 class="font-semibold text-primary-800 text-lg">
            Active Branch Assignment
          </h3>
        </div>
      </template>
      <template #body>
        <x-divider class="my-4" />
        <DataTable
          table-class-name="compact tablefixed mb-6"
          :headers="activeTableHeader"
          :items="activeUserBranches || []"
          border-cell
          hide-rows-per-page
          :rows-per-page="15"
          :hide-footer="activeUserBranches?.length < 15"
        >
          <template #item-branch="item">
            {{ item.branch.name }}
          </template>
          <template #item-is_primary="{ is_primary }">
            {{ is_primary ? 'Yes' : 'No' }}
          </template>
          <template #item-effective_from="{ effective_from }">
            {{ dateFormat(effective_from) }}
          </template>
          <template #item-effective_to="{ effective_to }">
            {{ dateFormat(effective_to) }}
          </template>
          <template #item-actions="item">
            <div class="flex gap-2">
              <Link
                v-if="!item.is_primary"
                method="patch"
                :href="
                  route('branch-assignments.make-primary', {
                    user_id: user.id,
                    branch_id: item.branch.id,
                  })
                "
              >
                <x-button size="sm" color="#1d83bc" tag="div">
                  Make Primary
                </x-button>
              </Link>
              <Link
                method="patch"
                :href="
                  route('branch-assignments.delete', {
                    user: user.id,
                    branch_id: item.branch.id,
                  })
                "
              >
                <x-button size="sm" color="#ff5e00" tag="div">
                  Remove Branch
                </x-button>
              </Link>
            </div>
          </template>
        </DataTable>
      </template>
    </Collapsible>
  </div>

  <div class="p-4 rounded shadow mb-6 bg-white">
    <Collapsible :expanded="false">
      <template #header>
        <div>
          <h3 class="font-semibold text-primary-800 text-lg">
            Historical Branch Assignment
          </h3>
        </div>
      </template>
      <template #body>
        <x-divider class="my-4" />
        <DataTable
          table-class-name="compact tablefixed mb-6"
          :headers="historicalTableHeader"
          :items="historicalUserBranches || []"
          border-cell
          hide-rows-per-page
          :rows-per-page="15"
          :hide-footer="historicalUserBranches?.length < 15"
        >
          <template #item-branch="item">
            {{ item.branch.name }}
          </template>
          <template #item-is_primary="{ is_primary }">
            {{ is_primary ? 'Yes' : 'No' }}
          </template>
          <template #item-effective_from="{ effective_from }">
            {{ dateFormat(effective_from) }}
          </template>
          <template #item-effective_to="{ effective_to }">
            {{ dateFormat(effective_to) }}
          </template>
          <template #item-status="{ status }">
            {{ status ? 'Active' : 'Inactive' }}
          </template>
        </DataTable>
      </template>
    </Collapsible>
  </div>

  <AuditLogs
    :url="'\\auditable'"
    :type="'App\\Models\\UserBranch'"
    :id="$page.props.user.id"
    :quoteType="'UserBranch'"
  />
</template>

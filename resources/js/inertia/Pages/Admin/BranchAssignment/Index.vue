<script setup>
const { isRequired } = useRules();
const props = defineProps({
  branchAssignments: Object,
  branches: Object,
  advisorList: Object,
  assignedUsers: Array,
});
const params = useUrlSearchParams('history');

const tableHeader = ref([
  { text: 'Assignment ID', value: 'assignment_id' },
  { text: 'Advisor ID', value: 'user_id' },
  { text: 'Advisor Name', value: 'user.name' },
  { text: 'Branch ID', value: 'branch_id' },
  { text: 'Branch Name', value: 'branch.name' },
  { text: 'Primary Branch', value: 'is_primary' },
  { text: 'Role / Level', value: 'roles' },
  { text: 'Status', value: 'status' },
  { text: 'Actions', value: 'actions' },
]);

const formObject = {
  user_id: null,
  branch_id: null,
  effective_from: null,
  effective_to: '9999/12/31',
  is_primary: false,
  force_primary: false,
};

const assignmentForm = useForm(formObject);

const effectiveToDisplay = '31/12/9999';

const modals = reactive({
  branchAssignment: false,
});

const loader = reactive({
  table: false,
});

const filters = reactive({
  advisors: '',
  primary_branch: '',
  page: 1,
});

const onReset = () => {
  router.visit(route('branch-assignments.index'), {
    method: 'get',
    data: { page: 1 },
    preserveScroll: true,
    onBefore: () => (loader.table = true),
    onSuccess: () => (loader.table = false),
  });
};

const onSubmit = isValid => {
  if (isValid) {
    filters.page = 1;

    Object.keys(filters).forEach(
      key =>
        (filters[key] === '' || filters[key].length === 0) &&
        delete filters[key],
    );
    router.visit(route('branch-assignments.index'), {
      method: 'get',
      data: filters,
      preserveState: true,
      preserveScroll: true,
      onBefore: () => (loader.table = true),
      onSuccess: () => (loader.table = false),
    });
  }
};

function setQueryStringFilters() {
  for (const [key] of Object.entries(params)) {
    if (key.includes('[]')) {
      filters[key.substring(0, key.length - 2)] = params[key];
    } else {
      filters[key] = params[key];
    }
  }
}

onMounted(() => {
  setQueryStringFilters();
});

const advisorOptions = computed(() => {
  return props.advisorList.map(item => ({
    value: item.id,
    label: item.name,
  }));
});

const branchOptions = computed(() => {
  return props.branches.map(item => ({
    value: item.id,
    label: item.name,
  }));
});

const onBranchAssignmentSubmit = isValid => {
  if (!isValid) return;

  let method = 'post';
  let url = route('branch-assignments.store', { user: assignmentForm.user_id });
  assignmentForm.submit(method, url, {
    onSuccess: (page) => {
      if (page.props.flash?.success) {
        modals.branchAssignment = false;
      }
    },
    onError: errors => {
      Object.keys(errors).forEach(function (key) {
        assignmentForm.setError(key, errors[key]);
      });
      return false;
    },
  });
};

const openModal = () => {
  Object.assign(assignmentForm, { ...formObject });

  modals.branchAssignment = true;
};

watch(
  () => assignmentForm.user_id,
  (newUserId) => {
    // Only check when a user_id is selected/set (not null/empty)
    if (newUserId) {
      const alreadyAssigned = props.assignedUsers?.includes(newUserId);
      if (!alreadyAssigned) {
        assignmentForm.is_primary = true;
        assignmentForm.force_primary = true;
      } else {
        assignmentForm.is_primary = false;
        assignmentForm.force_primary = false;
      }
    }
  }
);

const handleMakePrimary = (userId, branchId) => {
  router.visit(route('branch-assignments.make-primary', { user_id: userId, branch_id: branchId }), {
    method: 'patch',
    preserveState: true,
    preserveScroll: true,
    onBefore: () => (loader.table = true),
    onSuccess: () => (loader.table = false),
  });
};

const handleRemoveBranch = (userId, branchId) => {
  router.visit(route('branch-assignments.delete', { user: userId, branch_id: branchId }), {
    method: 'patch',
    preserveState: true,
    preserveScroll: true,
    onBefore: () => (loader.table = true),
    onSuccess: () => (loader.table = false),
  });
};

</script>
<template>
  <Head title="Advisor Branch Assignments" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">Advisor Branch Assignments</h2>
  </div>
  <x-divider class="my-4" />
  <x-form @submit="onSubmit" :auto-focus="false">
    <div class="grid sm:grid-cols-2 gap-4">
      <div class="gap-4">
        <x-select
          label="Advisor"
          class="w-full"
          multiple
          v-model="filters.advisors"
          filterable
          truncate
          :options="advisorOptions"
          placeholder="Select Advisor"
        />
      </div>
      <div class="gap-4">
        <x-select
          label="Primary Branch"
          class="w-full"
          v-model="filters.primary_branch"
          :options="[{ value: '', label: 'All Branches' }, ...branchOptions]"
          placeholder="Select Branch"
        />
      </div>
    </div>
    <div class="flex justify-end gap-3">
      <x-button size="sm" color="emerald" @click.prevent="openModal">Add Assignment</x-button>
      <x-button size="sm" color="#ff5e00" type="submit">Search</x-button>
      <x-button size="sm" color="primary" @click.prevent="onReset">
        Reset
      </x-button>
    </div>
  </x-form>
  <DataTable
    table-class-name="mt-4"
    :loading="loader.table"
    :headers="tableHeader"
    :items="props.branchAssignments.data || []"
    border-cell
    hide-rows-per-page
    hide-footer
  >
    <template #item-user_id="{ user_id }">
      <Link
        class="text-primary-500 hover:underline"
        :href="route('branch-assignments.show', user_id)"
      >
        {{ user_id }}
      </Link>
    </template>
    <template #item-is_primary="{ is_primary }">
      {{ is_primary ? 'Yes' : 'No' }}
    </template>
    <template #item-status="{ status }">
      {{ status ? 'Active' : 'Inactive' }}
    </template>
    <template #item-actions="{ user_id, branch_id, status, is_primary }">
      <div class="flex gap-2">
        <Link
          method="get"
          :href="
            route('branch-assignments.show', user_id)
          "
        >
          <x-button size="sm" color="emerald" tag="div">
            View
          </x-button>
        </Link>
        <x-button
          v-if="status == 1 && !is_primary"
          size="sm"
          color="#1d83bc"
          @click="handleMakePrimary(user_id, branch_id)"
        >
          Make Primary
        </x-button>
        <x-button
          v-if="status == 1"
          size="sm"
          color="#ff5e00"
          @click="handleRemoveBranch(user_id, branch_id)"
        >
          Inactivate
        </x-button>
      </div>
    </template>
  </DataTable>
  <Pagination
    :links="{
      next: props.branchAssignments.next_page_url,
      prev: props.branchAssignments.prev_page_url,
      current: props.branchAssignments.current_page,
      from: props.branchAssignments.from,
      to: props.branchAssignments.to,
    }"
  />

  <x-modal
    v-model="modals.branchAssignment"
    size="lg"
    :title="`Add Branch Assignment`"
    show-close
    backdrop
    is-form
    persistent
    @submit="onBranchAssignmentSubmit"
  >
    <div class="grid sm:grid-cols-2 gap-4">

      <x-select
        v-model="assignmentForm.user_id"
        :options="advisorOptions"
        :rules="[isRequired]"
        label="Advisor"
        truncate
        placeholder="Select Advisor"
        filterPlaceholder="Filter advisor...."
        required
        :error="$page.props.errors.user_id"
      />

      <x-select
        v-model="assignmentForm.branch_id"
        :options="branchOptions"
        :rules="[isRequired]"
        label="Branch"
        truncate
        placeholder="Select Branch"
        filterPlaceholder="Filter branch...."
        required
        :error="$page.props.errors.branch_id"
      />

      <DatePicker
        label="EFFECTIVE FROM"
        required
        v-model="assignmentForm.effective_from"
        name="created_at_start"
        :rules="[isRequired]"
        :hasError="
          assignmentForm.errors.effective_from ||
          $page.props.errors.effective_from
        "
      />

      <x-input
        v-model="effectiveToDisplay"
        :rules="[isRequired]"
        class="w-full"
        label="Effective To"
        disabled
      />


      <div>
        <x-label>Is Primary</x-label>
        <x-checkbox
          label="Is Primary"
          color="primary"
          class="mt-3"
          v-model="assignmentForm.is_primary"
          :disabled="assignmentForm.force_primary"
        />
      </div>
    </div>

    <template #secondary-action>
      <x-button
        size="sm"
        ghost
        tabindex="-1"
        @click.prevent="modals.branchAssignment = false"
      >
        Cancel
      </x-button>
    </template>
    <template #primary-action>
      <x-button
        size="sm"
        color="emerald"
        :loading="assignmentForm.processing"
        type="submit"
      >
        Save
      </x-button>
    </template>
  </x-modal>

</template>

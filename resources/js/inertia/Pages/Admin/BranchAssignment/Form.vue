<script setup>
import { ref } from 'vue';

const dateFormat = date => useDateFormat(date, 'DD/MM/YYYY').value;
const props = defineProps({
  userId: Number,
  branches: Object,
  activeBranchCount: Number,
});

const { isRequired } = useRules();
const notification = useToast();

const isError = ref(false);

const assignmentForm = useForm({
  branch_id: null,
  effective_from: null,
  effective_to: '9999/12/31',
  is_primary: props.activeBranchCount == 0 ? true : false,
});

const branchOptions = computed(() => {
  return props.branches.map(item => ({
    value: item.id,
    label: item.name,
  }));
});

function onSubmit(isValid) {
  if (isValid && !isError.value) {
    let method = 'post';
    let url = route('branch-assignments.store', { user_id: props.userId });
    assignmentForm.submit(method, url, {
      onError: errors => {
        Object.keys(errors).forEach(function (key) {
          assignmentForm.setError(key, errors[key]);
        });
        return false;
      },
    });
  }
}

const effectiveTo = computed(() => {
  return dateFormat(assignmentForm.effective_to);
});
</script>
<template>
  <Head title="Add Branch Assignment" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">Add Branch Assignment</h2>
    <div>
      <Link :href="route('branch-assignments.index')" class="mr-2">
        <x-button size="sm" color="#1d83bc" tag="div">
          Branch Assignment List
        </x-button>
      </Link>
      <Link :href="route('branch-assignments.show', userId)">
        <x-button size="sm" color="#1d83bc" tag="div">
          Branch Assignment Detail
        </x-button>
      </Link>
    </div>
  </div>
  <x-divider class="my-4" />
  <x-form @submit="onSubmit" :auto-focus="false">
    <div class="grid sm:grid-cols-2 gap-4">
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

      <div>
        <x-label>Is Primary</x-label>
        <x-checkbox
          label="Is Primary"
          color="primary"
          class="mt-3"
          v-model="assignmentForm.is_primary"
          :disabled="activeBranchCount == 0"
        />
      </div>

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
        v-model="effectiveTo"
        :rules="[isRequired]"
        class="w-full"
        label="Effective To"
        disabled
      />
    </div>
    <x-divider class="my-4" />
    <div class="flex justify-end gap-3 mb-4">
      <x-button
        size="md"
        color="emerald"
        type="submit"
        :loading="assignmentForm.processing"
      >
        Add
      </x-button>
    </div>
  </x-form>
</template>

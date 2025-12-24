<script setup>
import { ref } from 'vue';

const props = defineProps({
  branch: Object,
});

const { isRequired } = useRules();
const notification = useToast();

const isError = ref(false);
const isEdit = computed(() => {
  return route().current().includes('edit');
});

const branchForm = useForm({
  id: props.branch?.id ?? null,
  code: props.branch?.code ?? null,
  name: props.branch?.name ?? null,
  type: props.branch?.type ?? null,
  status: props.branch?.status ?? 1,
});

const branchStatus = [
  { value: 1, label: 'Active' },
  { value: 0, label: 'InActive' },
];

const branchTypes = [
  { value: 'Headquarters', label: 'Headquarters' },
  { value: 'Regional Branch', label: 'Regional Branch' },
  { value: 'International Branch', label: 'International Branch' },
];

function onSubmit(isValid) {
  if (isValid && !isError.value) {
    let method = isEdit.value ? 'put' : 'post';
    let url = isEdit.value
      ? route('branches.update', branchForm.id)
      : route('branches.store');
    branchForm.submit(method, url, {
      onError: errors => {
        Object.keys(errors).forEach(function (key) {
          branchForm.setError(key, errors[key]);
        });
        return false;
      },
    });
  }
}
</script>
<template>
  <Head :title="isEdit ? 'Edit Branch' : 'Create Branch'" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">
      {{ isEdit ? 'Edit' : 'Create' }} Branch
    </h2>
    <div>
      <Link :href="route('branches.index')">
        <x-button size="sm" color="#1d83bc" tag="div"> Branch List </x-button>
      </Link>
    </div>
  </div>
  <x-divider class="my-4" />
  <x-form @submit="onSubmit" :auto-focus="false">
    <div class="grid sm:grid-cols-2 gap-4">
      <x-input
        v-model="branchForm.name"
        :rules="[isRequired]"
        class="w-full"
        :error="$page.props.errors.name"
        required
        label="Name"
      />

      <x-input
        v-model="branchForm.code"
        :rules="[isRequired]"
        class="w-full"
        :error="$page.props.errors.code"
        required
        label="Code"
      />

      <x-select
        v-model="branchForm.type"
        :options="branchTypes"
        :rules="[isRequired]"
        label="Type"
        truncate
        placeholder="Select Type"
        filterPlaceholder="Filter type...."
        required
      >
      </x-select>

      <x-select
        v-model="branchForm.status"
        :options="branchStatus"
        class="w-full"
        :label="'Active'"
        required
      />
    </div>
    <x-divider class="my-4" />
    <div class="flex justify-end gap-3 mb-4">
      <x-button size="md" color="emerald" type="submit">
        {{ isEdit ? 'Update' : 'Create' }}
      </x-button>
    </div>
  </x-form>
</template>

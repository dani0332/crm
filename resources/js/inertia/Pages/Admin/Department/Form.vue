<script setup>
import { ref } from 'vue';

const props = defineProps({
  teams: Array,
  department: Object,
});

const { isRequired } = useRules();
const notification = useToast();

const isError = ref(false);
const isEdit = computed(() => {
  return route().current().includes('edit');
});

const departmentForm = useForm({
  id: props.department?.id ?? null,
  name: props.department?.name ?? null,
  teams: [],
  is_active: props.department?.is_active ?? 1,
});

const validTeams = computed(() => {
  return (departmentForm.teams.length == 0 && isError.value) ?? false;
});

watch(
  () => props.department?.teams,
  () => {
    if (props.department?.teams) {
      props.department?.teams.forEach(element => {
        departmentForm.teams.push(+element.team_id);
      });
    }
  },
  { immediate: true },
);

const departmentStatus = [
  { value: 1, label: 'Active' },
  { value: 0, label: 'InActive' },
];
function onSubmit(isValid) {
  if (departmentForm.teams.length == 0) {
    isError.value = true;
  } else {
    isError.value = false;
  }
  if (isValid && !isError.value) {
    let method = isEdit.value ? 'put' : 'post';
    let url = isEdit.value
      ? route('departments.update', departmentForm.id)
      : route('departments.store');
    departmentForm.submit(method, url, {
      onError: errors => {
        Object.keys(errors).forEach(function (key) {
          departmentForm.setError(key, errors[key]);
        });
        return false;
      },
    });
  }
}
</script>
<template>
  <Head :title="isEdit ? 'Edit Department' : 'Create Department'" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">
      {{ isEdit ? 'Edit' : 'Create' }} Department
    </h2>
    <div>
      <Link :href="route('departments.index')">
        <x-button size="sm" color="#1d83bc" tag="div">
          Departments List
        </x-button>
      </Link>
    </div>
  </div>
  <x-divider class="my-4" />
  <x-form @submit="onSubmit" :auto-focus="false">
    <div class="grid sm:grid-cols-2 gap-4">
      <x-field label="NAME" required>
        <x-input
          v-model="departmentForm.name"
          :rules="[isRequired]"
          class="w-full"
          :error="$page.props.errors.name"
        />
      </x-field>
      <x-field label="teams" required>
        <ComboBox
          v-model="departmentForm.teams"
          :options="
            props.teams.map(x => ({
              value: x.id,
              label: x.name,
            }))
          "
          :rules="[isRequired]"
          :hasError="validTeams"
          autocomplete
        />
      </x-field>
      <x-field :label="'Active'" required>
        <x-select
          v-model="departmentForm.is_active"
          :options="departmentStatus"
          class="w-full"
        />
      </x-field>
    </div>
    <x-divider class="my-4" />
    <div class="flex justify-end gap-3 mb-4">
      <x-button size="md" color="emerald" type="submit">
        {{ isEdit ? 'Update' : 'Create' }}
      </x-button>
    </div>
  </x-form>
</template>

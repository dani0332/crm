<script setup>
const props = defineProps({
  permissions: Array,
  rolePermissions: Object,
  role: Object,
});

const { isRequired } = useRules();
const notification = useToast();

const isError = ref(false);
const isEdit = computed(() => {
  return route().current().includes('edit');
});

const roleForm = useForm({
  id: props.role?.id ?? null,
  name: props.role?.name ?? null,
  permission: [],
});

const validPermission = computed(() => {
  return (roleForm.permission.length == 0 && isError.value) ?? false;
});

watch(
  () => props.rolePermissions,
  () => {
    if (props.rolePermissions) {
      for (let value in props.rolePermissions) {
        roleForm.permission.push(+value);
      }
    }
  },
  { immediate: true },
);

function onSubmit(isValid) {
  if (roleForm.permission.length == 0) {
    isError.value = true;
  } else {
    isError.value = false;
  }
  if (isValid && !isError.value) {
    let method = isEdit.value ? 'put' : 'post';
    let url = isEdit.value
      ? route('roles.update', roleForm.id)
      : route('roles.store');

    roleForm.submit(method, url, {
      onError: errors => {
        Object.keys(errors).forEach(function (key) {
          roleForm.setError(key, errors[key]);
        });
        return false;
      },
    });
  }
}
</script>
<template>
  <Head :title="isEdit ? 'Edit Role' : 'Create Role'" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">{{ isEdit ? 'Edit' : 'Create' }} Role</h2>
    <div>
      <Link :href="route('roles.index')">
        <x-button size="sm" color="#1d83bc" tag="div"> Roles List </x-button>
      </Link>
    </div>
  </div>
  <x-divider class="my-4" />
  <x-form @submit="onSubmit" :auto-focus="false">
    <div class="grid sm:grid-cols-2 gap-4">
      <x-field label="NAME" required>
        <x-input
          v-model="roleForm.name"
          :rules="[isRequired]"
          class="w-full"
          :error="$page.props.errors.name"
        />
      </x-field>
      <x-field label="PERMISSIONS" required>
        <ComboBox
          v-model="roleForm.permission"
          :options="
            props.permissions.map(x => ({
              value: x.id,
              label: x.name,
            }))
          "
          :rules="[isRequired]"
          :hasError="validPermission"
          autocomplete
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

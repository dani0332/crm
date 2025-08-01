<script setup>
const props = defineProps({
  permission: Object,
});

const { isRequired } = useRules();
const notification = useToast();

const isEdit = computed(() => {
  return route().current().includes('edit');
});

const permissionForm = useForm({
  id: props.permission?.id ?? null,
  name: props.permission?.name ?? null,
  guard_name: props.permission?.guard_name ?? 'web',
});

function onSubmit(isValid) {
  if (isValid) {
    let method = isEdit.value ? 'put' : 'post';
    let url = isEdit.value
      ? route('permissions.update', permissionForm.id)
      : route('permissions.store');

    permissionForm.submit(method, url, {
      onError: errors => {
        Object.keys(errors).forEach(function (key) {
          permissionForm.setError(key, errors[key]);
        });
        return false;
      },
    });
  }
}
</script>
<template>
  <Head :title="isEdit ? 'Edit Permission' : 'Create Permission'" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">
      {{ isEdit ? 'Edit' : 'Create' }} Permission
    </h2>
    <div>
      <Link :href="route('permissions.index')">
        <x-button size="sm" color="#1d83bc" tag="div">
          Permissions List
        </x-button>
      </Link>
    </div>
  </div>
  <x-divider class="my-4" />
  <x-form @submit="onSubmit" :auto-focus="false">
    <div class="grid sm:grid-cols-2 gap-4">
      <x-field label="NAME" required>
        <x-input
          v-model="permissionForm.name"
          :rules="[isRequired]"
          class="w-full"
          :error="$page.props.errors.name"
        />
      </x-field>
      <x-field label="GUARD NAME">
        <x-input
          v-model="permissionForm.guard_name"
          class="w-full"
          :error="$page.props.errors.guard_name"
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
